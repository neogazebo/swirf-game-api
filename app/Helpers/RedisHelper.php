<?php

namespace App\Helpers;

use App\Helpers\CommonConstants as CC;
use Illuminate\Support\Facades\Redis;

class RedisHelper {

    //Profile Cache
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
    
    //Collected Items Cache
    public static function setCollectedItems($id, $data)
    {
	Redis::pipeline(function ($pipe) use ($id, $data) {
	    foreach ($data as $row) {
		$pipe->zadd(CC::PREFIX_COLECTED_ITEMS . $id, $row->collected_id, json_encode($row));
	    }
	});
    }
    
    public static function checkCollectedItems($id)
    {
	return Redis::command('EXISTS', [CC::PREFIX_COLECTED_ITEMS . $id]);
    }

    public static function getCollectedItems($id,$start, $end)
    {
	$data = Redis::command('ZRANGE', [CC::PREFIX_COLECTED_ITEMS . $id, $start, $end]);

	if (!empty($data)){
	    foreach ($data as $key => $val) 
	    {
		$data[$key] = json_decode($val);
	    }
	}

	return $data;
    }

    public static function deleteCollectedItems($id)
    {
	Redis::command('DEL', [CC::PREFIX_COLECTED_ITEMS . $id]);
    }
    
    //Reward Member Cache
    public static function setRewardMember($id, $data)
    {
	Redis::pipeline(function ($pipe) use ($id, $data) {
	    foreach ($data as $row) {
		$pipe->zadd(CC::PREFIX_REWARD_MEMBER . $id, $row->member_reward_id, json_encode($row));
	    }
	});
    }
    
    public static function checkRewardMember($id)
    {
	return Redis::command('EXISTS', [CC::PREFIX_REWARD_MEMBER . $id]);
    }

    public static function getRewardMember($id, $start, $end)
    {
	$data = Redis::command('ZRANGE', [CC::PREFIX_REWARD_MEMBER . $id, $start, $end]);

	if (!empty($data)){
	    foreach ($data as $key => $val) 
	    {
		$data[$key] = json_decode($val);
	    }
	}

	return $data;
    }

    public static function deleteRewardMember($id)
    {
	Redis::command('DEL', [CC::PREFIX_REWARD_MEMBER . $id]);
    }
    
    
    //Network Cache
    public static function setNetworkMember($id, $data)
    {
	Redis::pipeline(function ($pipe) use ($id, $data) {
	    foreach ($data as $row) {
		$pipe->zadd(CC::PREFIX_NETWORK_MEMBER . $id, $row->network_id, json_encode($row));
	    }
	});
    }
    
    public static function checkNetworkMember($id)
    {
	return Redis::command('EXISTS', [CC::PREFIX_NETWORK_MEMBER . $id]);
    }

    public static function getNetworkMember($id, $start, $end)
    {
	$data = Redis::command('ZRANGE', [CC::PREFIX_NETWORK_MEMBER . $id, $start, $end]);

	if (!empty($data)){
	    foreach ($data as $key => $val) 
	    {
		$data[$key] = json_decode($val);
	    }
	}

	return $data;
    }

    public static function deleteNetworkMember($id)
    {
	Redis::command('DEL', [CC::PREFIX_NETWORK_MEMBER . $id]);
    }

}
