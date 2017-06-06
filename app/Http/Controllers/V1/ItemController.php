<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Helpers\CommonConstants as CC;
use App\Helpers\ResponseHelper as RS;
use App\Helpers\RedisHelper as Redis;

class ItemController extends Controller {

    use AppTrait;

    public function listItem()
    {
	//limit to 2KM nearby
	$statement = '
			select 
			geo_id,
			geo_latitude,
			geo_longitude,
			itm_id as item_id,
			itm_name as item_name, 
			itm_point_value as item_point_value,
			itm_image as image,
			clc_id as collection_id,
			clc_name as collection_name,
			clc_description as collection_description,
			par_id as partner_id,
			par_name as partner_name 
			from tbl_geo_position 
			left join tbl_item on geo_item_id=itm_id
			left join tbl_collection on itm_collection_id=clc_id
			left join tbl_partner on clc_partner_id=par_id
			where
			clc_status = 1 and geo_broadcast = 1 and (unix_timestamp(now()) between clc_start_date and clc_end_date) and
			111.045* DEGREES(ACOS(COS(RADIANS(:lat1))
							 * COS(RADIANS(geo_latitude))
							 * COS(RADIANS(:lon) - RADIANS(geo_longitude))
							 + SIN(RADIANS(:lat2))
							 * SIN(RADIANS(geo_latitude)))) <2
			and itm_id not in (select col_item_id from tbl_collectible where col_member_id = :mem_id)
		';

	$items = \DB::select($statement, [
		    'mem_id' => \Swirf::getMember()->mem_id,
		    'lat1' => \Swirf::getLatitude(),
		    'lat2' => \Swirf::getLatitude(),
		    'lon' => \Swirf::getLongitude()
	]);
	if (count($items) <> 0)
	{
	    $this->code = CC::RESPONSE_SUCCESS;
	    $this->results = ['count' => count($items), 'items' => $items];
	    $this->message = "Successful pulling the item list";
	}
	else
	{
	    $this->code = CC::RESPONSE_SUCCESS;
	    $this->results = ['count' => count($items), 'items' => $items];
	    $this->message = "No items to show, please try again later.";
	}
	return $this->json();
    }

    public function collectedItem()
    {
	$collections = $this->__getCollectedItems(\Swirf::getMember()->mem_id);

	if (count($collections) <> 0)
	{
	    $this->code = CC::RESPONSE_SUCCESS;
	    $this->results = $collections;
	    $this->message = "Successful pulling the collected items.";
	}
	else
	{
	    $this->code = CC::RESPONSE_SUCCESS;
	    $this->results = $collections;
	    $this->message = "No collection, please try to grab an item first.";
	}

	return $this->json();
    }

    public function grabItem()
    {
	$validator = Validator::make(\Swirf::input(null, true), [
		    'geo_id' => 'required',
		    'item_id' => 'required',
		    'collection_id' => 'required',
		    'partner_id' => 'required',
		    'element_id' => 'required',
	]);

	if (!$validator->fails())
	{
	    $member = \Swirf::getMember()->mem_id;
	    $time = time();
	    $payload = [];
	    try {
		\DB::beginTransaction();
		//0.get item point value and user's current point
		$query = \DB::table('tbl_point_history')
			->where([['poi_member_id', '=', $member]])
			->orderBy('poi_id', 'desc')
			->first();
		if (empty($query))
		{
		    $current_point = 0;
		}
		else
		{
		    $current_point = $query->poi_current;
		}
		$query2 = \DB::table('tbl_item')
			->where('itm_id', \Swirf::input()->item_id)
			->first();
		if (empty($query2))
		{
		    $point_value = 0;
		}
		else
		{
		    $point_value = $query2->itm_point_value;
		}

		//1.update geo_position, flag the record 
		\DB::table('tbl_geo_position')->where('geo_id', \Swirf::input()->geo_id)->update(['geo_broadcast' => '0', 'geo_counter' => '1']);

		//2.update tbl_item add increment on itm_counter for particular item
		\DB::table('tbl_item')->where('itm_id', \Swirf::input()->item_id)->increment('itm_counter', 1);

		//3.check collection is exist in tbl_collectible_collection for particular member,
		$collected = \DB::table('tbl_collectible_collection')
			->where([['coc_member_id', '=', $member], ['coc_collection_id', '=', \Swirf::input()->collection_id]])
			->first();

		//4.if not exist insert new collection
		if (count($collected) == 0)
		{
		    \DB::table('tbl_collectible_collection')->insert(
			    [
				'coc_member_id' => $member,
				'coc_collection_id' => \Swirf::input()->collection_id,
				'coc_datetime' => $time,
			    ]
		    );
		}

		//5.insert into tbl_collectible for particular item
		\DB::table('tbl_collectible')->insert(
			[
			    'col_member_id' => $member,
			    'col_collection_id' => \Swirf::input()->collection_id,
			    'col_item_id' => \Swirf::input()->item_id,
			    'col_geoposition_id' => \Swirf::input()->geo_id,
			    'col_element_id' => \Swirf::input()->element_id,
			    'col_partner_id' => \Swirf::input()->partner_id,
			    'col_datetime' => $time,
			    'col_latitude' => \Swirf::getLatitude(),
			    'col_longitude' => \Swirf::getLongitude(),
			]
		);

		//6.count items under particalr collection, if count == 6 then flag the collection as completed
		// and insert the reward to the tbl_member_redeemable
		$redeemable_id = '';
		$collected_items = \DB::table('tbl_collectible')
			->where([['col_member_id', '=', $member], ['col_collection_id', '=', \Swirf::input()->collection_id]])
			->count();
		if ($collected_items == 6)
		{
		    \DB::table('tbl_collectible_collection')
			    ->where([['coc_member_id', '=', $member], ['coc_collection_id', '=', \Swirf::input()->collection_id]])
			    ->update(['coc_flag' => '1', 'coc_update_datetime' => $time, 'coc_completed_datetime' => $time]);
		    //insert to member redeemable
		    $redeemable_id = \DB::table('tbl_collection')
				    ->where('clc_id', \Swirf::input()->collection_id)
				    ->first()
			    ->clc_redeemable_id;
		    \DB::table('tbl_rel_member_redeemable')->insert(
			    [
				'rmr_member_id' => $member,
				'rmr_redeemable_id' => $redeemable_id,
				'rmr_datetime' => $time,
			    ]
		    );
		}

		//7.add point earned into tbl_point_history
		\DB::table('tbl_point_history')->insert(
			[
			    'poi_member_id' => $member,
			    'poi_datetime' => $time,
			    'poi_point_type_id' => '103', //collected item
			    'poi_method' => 'C',
			    'poi_value' => $point_value,
			    'poi_current' => $current_point + $point_value,
			]
		);
		//8.clear redis for that user profile
		Redis::deleteProfileCache($member);

		//9.insert into tbl_collectible_geoposition and delete from tbl_geo_position
		$geo = \DB::table('tbl_geo_position')
			->where('geo_id', \Swirf::input()->geo_id)
			->first();
		\DB::table('tbl_collectible_geoposition')->insert(
			[
			    'cog_member_id' => $member,
			    'cog_datetime' => $time,
			    'cog_geo_id' => \Swirf::input()->geo_id,
			    'cog_geo_latitude' => $geo->geo_latitude,
			    'cog_geo_longitude' => $geo->geo_longitude,
			    'cog_geo_name' => $geo->geo_name,
			    'cog_geo_item_id' => $geo->geo_item_id,
			    'cog_geo_datetime' => $geo->geo_datetime,
			    'cog_geo_counter' => $geo->geo_counter,
			    'cog_geo_broadcast' => $geo->geo_broadcast,
			]
		);

		\DB::table('tbl_geo_position')
			->where('geo_id', \Swirf::input()->geo_id)
			->delete();

		//10.return completed_flag, remaining items to complete
		$payload = [
		    'point_value' => $point_value,
		    'total_point' => $current_point + $point_value,
		    'remaining_items' => 6 - $collected_items, //total remaining item to collect for particular collection
		    'collection_id' => \Swirf::input()->collection_id,
		    'item_id' => \Swirf::input()->item_id,
		    'redeemable_id' => $redeemable_id,
		];

		//todo : create tbl_history_geoposition : contain all geaoposition before and after grabbed

		\DB::commit();
		$this->code = CC::RESPONSE_SUCCESS;
		$this->results = ['payload' => $payload];
		$this->message = "Successful grab the item.";
	    } catch (\Exception $e) {
		\DB::rollBack();

		$this->status = RS::HTTP_INTERNAL_SERVER_ERROR;
		$this->message = 'Error server ' . $e;
	    }
	}
	else
	{
	    $this->status = RS::HTTP_BAD_REQUEST;
	    $this->results = $validator->errors();
	    $this->message = 'Error Parameters';
	}
	return $this->json();
    }

    private function __getCollectedItems($member_id)
    {
	//TODO get from Redis
	$statement = '
		select 
		coc_id as collected_id,
		coc_collection_id as collection_id,
		clc_name as collection_name,
		clc_description as collection_description,
		clc_start_date as collection_start_date,
		clc_end_date as collection_end_date,
		coc_datetime as collected_creation_date,
		coc_flag as collected_flag,
		coc_completed_datetime as collected_completed_date,
		coc_redeemed_datetime as collected_redeemed_date,
		clc_partner_id as partner_id,
		clc_status as collection_status,
		par_name as partner_name
		from tbl_collectible_collection
		inner join tbl_collection on coc_collection_id=clc_id
		left join tbl_partner on clc_partner_id=par_id
		where coc_member_id=:mem_id
	';

	$collections = \DB::select($statement, [
		    'mem_id' => $member_id,
	]);

	if (count($collections) <> 0)
	{
	    foreach ($collections as $collection) {
		$statement = '
			select 
			col_id as collected_item_id,
			col_datetime as collected_item_date,
			itm_name as item_name, 
			itm_point_value as item_point_value,
			itm_rarity as rarity,
			itm_image as image
			from tbl_collectible
			left join tbl_item on col_item_id=itm_id
			where col_member_id=:mem_id and col_collection_id=:clc_id
		';

		$items = \DB::select($statement, [
			    'mem_id' => \Swirf::getMember()->mem_id,
			    'clc_id' => $collection->collection_id,
		]);
		$collection->items = $items;
	    }
	}

	return $collections;
    }

}
