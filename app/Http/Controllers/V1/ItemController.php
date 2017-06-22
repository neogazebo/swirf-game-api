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

    public function listItem($page = CC::DEFAULT_PAGE, $size = CC::DEFAULT_SIZE)
    {
	//limit to 2KM nearby
	$start = ($page - 1) * $size;
	$limit = 'LIMIT ' . $start . ',' . $size;
	$this->results['more'] = false;

	$cdn = env('CDN_ITEM');
	$statement = '
			select 
			geo_id,
			geo_latitude,
			geo_longitude,
			itm_id as item_id,
			itm_name as item_name, 
			itm_point_value as item_point_value,
			IF(itm_image <> "", CONCAT("' . $cdn . '",itm_image), "") as image,
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
			and itm_id not in (select col_item_id from tbl_collected_item where col_member_id = :mem_id) ' . $limit;

	$items = \DB::select($statement, [
		    'mem_id' => \Swirf::getMember()->mem_id,
		    'lat1' => \Swirf::getLatitude(),
		    'lat2' => \Swirf::getLatitude(),
		    'lon' => \Swirf::getLongitude()
	]);


	if (count($items) == $size)
	{
	    $this->results['more'] = true;
	}

	$this->code = CC::RESPONSE_SUCCESS;
	$this->results['result'] = $items;

	return $this->json();
    }

    public function collectedItem($page = CC::DEFAULT_PAGE, $size = CC::DEFAULT_SIZE)
    {
	$start = ($page - 1) * $size;
	$end = $start + $size - 1;
	$this->results['more'] = false;

	$collections = $this->__getCollectedItems(\Swirf::getMember()->mem_id, $start, $end);

	if (count($collections) == $size)
	{
	    $this->results['more'] = true;
	}

	$this->code = CC::RESPONSE_SUCCESS;
	$this->results['results'] = $collections;

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
	    $payload = $this->__grabItem(\Swirf::input()->geo_id, \Swirf::input()->item_id, \Swirf::input()->collection_id, \Swirf::input()->partner_id, \Swirf::input()->element_id, \Swirf::getMember()->mem_id, \Swirf::getLatitude(), \Swirf::getLongitude());
	    if (count($payload) <> 0)
	    {
		$this->code = CC::RESPONSE_SUCCESS;
		$this->results = ['payload' => $payload];
		$this->message = "Successful grab the item.";
	    }
	    else
	    {
		$this->status = RS::HTTP_INTERNAL_SERVER_ERROR;
		$this->message = 'Error server ';
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

    private function __getCollectedItems($member_id, $start, $end)
    {

	$is_exist = Redis::checkCollectedItems($member_id);
	if ($is_exist)
	{
	    return Redis::getCollectedItems($member_id, $start, $end);
	}

	$cdn = env('CDN_ITEM');
	$cdn_reward = env('CDN_REWARD');
	$statement = '
		select 
		coc_id as collected_id,
		coc_collection_id as collection_id,
		clc_name as collection_name,
		clc_description as collection_description,
		clc_start_date as collection_start_date,
		clc_end_date as collection_end_date,
		coc_datetime as collected_creation_date,
		IF(red_image <> "", CONCAT("' . $cdn_reward . '",red_image), "") as reward_image,
		coc_flag as collected_flag,
		coc_completed_datetime as collected_completed_date,
		coc_redeemed_datetime as collected_redeemed_date,
		clc_partner_id as partner_id,
		clc_status as collection_status,
		par_name as partner_name
		from tbl_collected_collection
		inner join tbl_collection on coc_collection_id=clc_id
		left join tbl_partner on clc_partner_id=par_id
		left join tbl_redeemable on red_id = clc_redeemable_id
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
			IF(itm_image <> "", CONCAT("' . $cdn . '",itm_image), "") as image
			from tbl_collected_item
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

	Redis::setCollectedItems($member_id, $collections);

	return Redis::getCollectedItems($member_id, $start, $end);
    }

    private function __grabItem($geo_id, $item_id, $collection_id, $partner_id, $element_id, $member, $latitude, $longitude)
    {
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
		    ->where('itm_id', $item_id)
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
	    \DB::table('tbl_geo_position')->where('geo_id', $geo_id)->update(['geo_broadcast' => '0', 'geo_counter' => '1']);

	    //2.update tbl_item add increment on itm_counter for particular item
	    \DB::table('tbl_item')->where('itm_id', $item_id)->increment('itm_counter', 1);

	    //3.check collection is exist in tbl_collected_collection for particular member,
	    $collected = \DB::table('tbl_collected_collection')
		    ->where([['coc_member_id', '=', $member], ['coc_collection_id', '=', $collection_id]])
		    ->first();

	    //4.if not exist insert new collection
	    if (count($collected) == 0)
	    {
		\DB::table('tbl_collected_collection')->insert(
			[
			    'coc_member_id' => $member,
			    'coc_collection_id' => $collection_id,
			    'coc_datetime' => $time,
			]
		);
	    }

	    //5.insert into tbl_collected_item for particular item
	    \DB::table('tbl_collected_item')->insert(
		    [
			'col_member_id' => $member,
			'col_collection_id' => $collection_id,
			'col_item_id' => $item_id,
			'col_geoposition_id' => $geo_id,
			'col_element_id' => $element_id,
			'col_partner_id' => $partner_id,
			'col_datetime' => $time,
			'col_latitude' => $latitude,
			'col_longitude' => $longitude,
		    ]
	    );

	    //6.count items under particalr collection, if count == 6 then flag the collection as completed
	    // and insert the reward to the tbl_member_redeemable
	    $redeemable_id = '';
	    $collected_items = \DB::table('tbl_collected_item')
		    ->where([['col_member_id', '=', $member], ['col_collection_id', '=', $collection_id]])
		    ->count();
	    if ($collected_items == 6)
	    {
		\DB::table('tbl_collected_collection')
			->where([['coc_member_id', '=', $member], ['coc_collection_id', '=', $collection_id]])
			->update(['coc_flag' => '1', 'coc_update_datetime' => $time, 'coc_completed_datetime' => $time]);
		//insert to member redeemable
		$redeemable_id = \DB::table('tbl_collection')
				->where('clc_id', $collection_id)
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


	    //8.insert into tbl_collected_geoposition and delete from tbl_geo_position
	    $geo = \DB::table('tbl_geo_position')
		    ->where('geo_id', $geo_id)
		    ->first();
	    \DB::table('tbl_collected_geoposition')->insert(
		    [
			'cog_member_id' => $member,
			'cog_datetime' => $time,
			'cog_geo_id' => $geo_id,
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
		    ->where('geo_id', $geo_id)
		    ->delete();

	    //9.return completed_flag, remaining items to complete
	    $payload = [
		'point_value' => $point_value,
		'total_point' => $current_point + $point_value,
		'remaining_items' => 6 - $collected_items, //total remaining item to collect for particular collection
		'collection_id' => $collection_id,
		'item_id' => $item_id,
		'redeemable_id' => $redeemable_id,
	    ];

	    //todo : create tbl_history_geoposition : contain all geaoposition before and after grabbed

	    \DB::commit();

	    Redis::deleteCollectedItems($member);
	    Redis::deleteRewardMember($member);
	    Redis::deleteProfileCache($member);
	} catch (\Exception $e) {
	    \DB::rollBack();
	    $payload = [];
	}
	return $payload;
    }

}
