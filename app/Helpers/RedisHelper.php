<?php

namespace App\Helpers;

use App\Helpers\CommonConstants as CC;
use Illuminate\Support\Facades\Redis;

class RedisHelper {

    /**
     * @param $account_id mem_acc_id
     * @param $data member data Json
     */
    public static function setProfileCache($account_id, $data)
    {
	Redis::set(CC::PREFIX_PROFILE . $account_id, $data);
    }

    public static function getProfileCache($account_id)
    {
	return Redis::get(CC::PREFIX_PROFILE . $account_id);
    }

    public static function deleteProfileCache($account_id)
    {
	Redis::command('DEL', [CC::PREFIX_PROFILE . $account_id]);
    }

    public static function setCollectedItems($id, $data)
    {
	Redis::set(CC::PREFIX_COLECTED_ITEMS . $id, $data);
    }

    public static function getCollectedItems($id)
    {
	return Redis::get(CC::PREFIX_COLECTED_ITEMS . $id);
    }

    public static function deleteCollectedItems($id)
    {
	Redis::command('DEL', [CC::PREFIX_COLECTED_ITEMS . $id]);
    }

    public static function setRewardMember($id, $data)
    {
	Redis::pipeline(function ($pipe) use ($id, $data){
	    foreach ($data as $row){
		$pipe->zadd(CC::PREFIX_REWARD_MEMBER . $id, $row->member_reward_id, json_encode($row));
	    }
	});
    }

    public static function getRewardMember($id, $start, $end)
    {
	$is_exist = Redis::command('EXISTS', [CC::PREFIX_REWARD_MEMBER . $id]);
	if(!$is_exist)
	{
	    return null;
	}
	
	$data = Redis::command('ZRANGE', [CC::PREFIX_REWARD_MEMBER . $id, $start, $end]);
	
	foreach($data as $key => $val)
	{
	    $data[$key] = json_decode($val);
	}
	
	return $data;
    }

    public static function deleteRewardMember($id)
    {
	Redis::command('DEL', [CC::PREFIX_REWARD_MEMBER . $id]);
    }

    public static function setNetworkMember($id, $data)
    {
	Redis::set(CC::PREFIX_NETWORK_MEMBER . $id, $data);
    }

    public static function getNetworkMember($id)
    {
	return Redis::get(CC::PREFIX_NETWORK_MEMBER . $id);
    }

    public static function deleteNetworkMember($id)
    {
	Redis::command('DEL', [CC::PREFIX_NETWORK_MEMBER . $id]);
    }

}
