<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Helpers\CommonConstants as CC;
use App\Helpers\ResponseHelper as RS;

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
		'lon' =>  \Swirf::getLongitude()
	    ]);
		if (count($items)<>0) {
			$this->code = CC::RESPONSE_SUCCESS;
			$this->results = ['count'=> count($items), 'items' => $items];
			$this->message = "Successful pulling the item list";	
		} else {
			$this->code = CC::RESPONSE_SUCCESS;
			$this->results = ['count'=> count($items), 'items' => $items];
			$this->message = "No items to show, please try again later.";
		}
		return $this->json();
    }
	
	public function collectedItem()
    {
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
		'mem_id' => \Swirf::getMember()->mem_id,
	    ]);
		$collected=[];
		foreach ($collections as $collection) {
			$statement = '
				select 
				col_id as collected_item_id,
				col_datetime as collected_item_date,
				itm_name as item_name, 
				itm_point_value as item_point_value,
				itm_rarity as rarity
				from tbl_collectible
				left join tbl_item on col_item_id=itm_id
				where col_member_id=:mem_id and col_collection_id=:clc_id
			';
			
			$items = \DB::select($statement, [
			'mem_id' => \Swirf::getMember()->mem_id,
			'clc_id' => $collection->collection_id,
			]);
			$collected=['collection'=>$collection, 'items'=>$items];
		}
		
		if (count($items)<>0) {
			$this->code = CC::RESPONSE_SUCCESS;
			$this->results = ['collected' => $collected];
			$this->message = "Successful pulling the collected items.";	
		} else {
			$this->code = CC::RESPONSE_SUCCESS;
			$this->results = ['count'=> count($items), 'items' => $items];
			$this->message = "No collected items, please try to grab first.";
		}
		return $this->json();
    }

}