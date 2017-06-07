<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Helpers\CommonConstants as CC;
use App\Helpers\ResponseHelper as RS;
use App\Helpers\RedisHelper as Redis;

class RewardController extends Controller {

    use AppTrait;
    
    public function listAll()
    {
	$member_id = \Swirf::getMember()->mem_id;
	$reward = $this->__getReward($member_id);
	
	if(count($reward) == 0)
	{
	    $this->message = 'reward empty';
	    $this->code = CC::RESPONSE_SUCCESS;
	}
	else
	{
	    $this->code = CC::RESPONSE_SUCCESS;
	    $this->results = $reward;
	}
	
	return $this->json();
    }
    
    private function __getReward($member_id)
    {
	$reward = Redis::getRewardMember($member_id);
	
	if(!empty($reward))
	{
	    return json_decode($reward);
	}
	
	$statement = 'select '
		. ' red_id as reward_id,'
		. ' rmr_id as reward_redeemable_id,'
		. ' red_name as reward_name,'
		. ' rmr_redeemed as reward_redeemed,'
		. ' red_start_datetime as reward_start_datetime,'
		. ' `red_end_datetime` as reward_end_datetime'
		. ' from `tbl_rel_member_redeemable` left join `tbl_redeemable` on rmr_redeemable_id = red_id '
		. ' left join tbl_member on mem_id = rmr_member_id '
		. ' where mem_id = :member_id';
	
	$reward = \DB::select($statement, ['member_id' => $member_id]);
	
	Redis::setRewardMember($member_id, json_encode($reward));
	
	return $reward;
    }
}