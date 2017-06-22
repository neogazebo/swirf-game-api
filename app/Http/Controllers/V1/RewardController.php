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
    
    public function listAll($page = CC::DEFAULT_PAGE, $size = CC::DEFAULT_SIZE)
    {
	$start = ($page - 1) * $size;
	$end = $start + $size - 1;
	$this->results['more'] = false;
	
	$member_id = \Swirf::getMember()->mem_id;
	$reward = $this->__getReward($member_id, $start, $end);
	
	if(count($reward) == $size)
	{
	    $this->results['more'] = true;
	}
	
	$this->code = CC::RESPONSE_SUCCESS;
	$this->results['result'] = $reward;
	
	return $this->json();
    }
    
    public function detail($member_reward_id)
    {
	$reward_detail = $this->__getDetailReward($member_reward_id);
	if(!empty($reward_detail))
	{
	    $this->results['more'] = true;
	    $this->results['result'] = $reward_detail;
	    $this->code = CC::RESPONSE_SUCCESS;
	}
	else
	{
	    $this->message = 'reward detail not available';
	    $this->status = RS::HTTP_BAD_REQUEST;
	}
	
	return $this->json();
    }
    
    private function __getReward($member_id, $start, $end)
    {
	$cdn = env('CDN_REWARD');
	
	$is_exist = Redis::checkRewardMember($member_id);
	if($is_exist)
	{
	    return Redis::getRewardMember($member_id, $start, $end);
	}
	
	$statement = 'select '
		. ' rmr_id as member_reward_id,'
		. ' red_id as reward_id,'
		. ' red_name as reward_name,'
		. ' IF(red_image <> "", CONCAT("' . $cdn . '",red_image), "") as reward_image,'
		. ' rmr_redeemed as reward_redeemed,'
		. ' red_start_datetime as reward_start_datetime,'
		. ' `red_end_datetime` as reward_end_datetime'
		. ' from `tbl_rel_member_redeemable` left join `tbl_redeemable` on rmr_redeemable_id = red_id '
		. ' left join tbl_member on mem_id = rmr_member_id '
		. ' where mem_id = :member_id';
	
	$reward = \DB::select($statement, ['member_id' => $member_id]);
	
	Redis::setRewardMember($member_id, $reward);
	
	return Redis::getRewardMember($member_id, $start, $end);
    }
    
    private function __getDetailReward($member_reward_id)
    {
	$reward_detail = Redis::getRewardDetail($member_reward_id);
	if(!empty($reward_detail))
	{
	    return json_decode($reward_detail);
	}
	
	$cdn = env('CDN_REWARD');
	$statement = 'select'
		. ' red_id as reward_id,'
		. ' par_id as partner_id,'
		. ' red_name as reward_name,'
		. ' par_name as partner_name,'
		. ' red_start_datetime as reward_start_date,'
		. ' red_end_datetime as reward_end_date,'
		. ' rmr_redeemed as reward_redeemed,'
		. ' IF(red_image <> "", CONCAT("' . $cdn . '",red_image), "") as reward_image'
		. ' from tbl_rel_member_redeemable'
		. ' left join tbl_redeemable on red_id = rmr_redeemable_id'
		. ' left join tbl_partner on par_id = red_partner_id'
		. ' left join tbl_category on cat_id = red_category_id'
		. ' where rmr_id = :member_reward_id limit 0,1';
	
	$reward_detail = \DB::select($statement, ['member_reward_id' => $member_reward_id]);
	
	if (count($reward_detail) > 0)
	{
	    Redis::setRewardDetail($member_reward_id, json_encode($reward_detail[0]));
	    return $reward_detail[0];
	}

	return null;
    }
}