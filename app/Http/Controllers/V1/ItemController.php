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
			itm_id,
			itm_name, 
			itm_point_value,
			clc_id,
			clc_name,
			clc_description,
			par_id,
			par_name 
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
			col_id,
			col_datetime,
			clc_name,
			clc_description, 
			clc_start_date,
			clc_end_date,
			clc_status,
			itm_name, 
			itm_point_value,
			itm_rarity,
			par_id,
			par_name
			from tbl_collectible
			left join tbl_collection on col_collection_id=clc_id
			left join tbl_item on col_item_id=itm_id
			left join tbl_partner on col_partner_id=par_id
			where col_member_id=:mem_id order by clc_id;
		';
	    
	    $items = \DB::select($statement, [
		'mem_id' => \Swirf::getMember()->mem_id,
	    ]);
		if (count($items)<>0) {
			$this->code = CC::RESPONSE_SUCCESS;
			$this->results = ['count'=> count($items), 'items' => $items];
			$this->message = "Successful pulling the collected items.";	
		} else {
			$this->code = CC::RESPONSE_SUCCESS;
			$this->results = ['count'=> count($items), 'items' => $items];
			$this->message = "No collected items, please try to grab first.";
		}
		return $this->json();
    }

}