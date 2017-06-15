<?php

namespace App\Helpers;

use App\Helpers\CommonConstants as CC;
use Illuminate\Support\Facades\Redis;

class RedisHelper {

    //Profile Cache
    public static function setProfileCache($account_id, $data, $expired = null)
    {
	Redis::set(CC::PREFIX_PROFILE . $account_id, $data);
        if ($expired == null) {
            $expired = CC::PREFIX_PROFILE_EXPIRED;
        }
        Redis::command('EXPIRE', [CC::PREFIX_PROFILE.$account_id, $expired]);
    }

    public static function getProfileCache($account_id)
    {
	return Redis::get(CC::PREFIX_PROFILE . $account_id);
    }

    public static function deleteProfileCache($account_id)
    {
	Redis::command('DEL', [CC::PREFIX_PROFILE . $account_id]);
    }
    
    //Reward Member Detail Cache
    public static function setRewardDetail($reward_member_id, $data, $expired = null)
    {
	Redis::set(CC::PREFIX_REWARD_DETAIL . $reward_member_id, $data);
	if ($expired == null) {
            $expired = CC::PREFIX_REWARD_DETAIL_EXPIRED;
        }
        Redis::command('EXPIRE', [CC::PREFIX_REWARD_DETAIL.$reward_member_id, $expired]);
    }

    public static function getRewardDetail($reward_member_id)
    {
	return Redis::get(CC::PREFIX_REWARD_DETAIL . $reward_member_id);
    }

    public static function deleteRewardDetail($reward_member_id)
    {
	Redis::command('DEL', [CC::PREFIX_REWARD_DETAIL . $reward_member_id]);
    }
    
    //Collected Items Cache
    public static function setCollectedItems($id, $data, $expired = null)
    {
	Redis::pipeline(function ($pipe) use ($id, $data) {
	    foreach ($data as $row) {
		$pipe->zadd(CC::PREFIX_COLECTED_ITEMS_LIST . $id, $row->collected_id, json_encode($row));
	    }
	});
	
	if($expired == null){
	    $expired = CC::PREFIX_COLECTED_ITEMS_EXPIRED;
	}
	
	Redis::command('EXPIRE', [CC::PREFIX_COLECTED_ITEMS_LIST.$id, $expired]);
    }
    
    public static function checkCollectedItems($id)
    {
	return Redis::command('EXISTS', [CC::PREFIX_COLECTED_ITEMS_LIST . $id]);
    }

    public static function getCollectedItems($id,$start, $end)
    {
	$data = Redis::command('ZRANGE', [CC::PREFIX_COLECTED_ITEMS_LIST . $id, $start, $end]);

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
	Redis::command('DEL', [CC::PREFIX_COLECTED_ITEMS_LIST . $id]);
    }
    
    //Reward Member Cache
    public static function setRewardMember($id, $data, $expired = null)
    {
	Redis::pipeline(function ($pipe) use ($id, $data) {
	    foreach ($data as $row) {
		$pipe->zadd(CC::PREFIX_REWARD_MEMBER_LIST . $id, $row->member_reward_id, json_encode($row));
	    }
	});
	
	if($expired == null){
	    $expired = CC::PREFIX_REWARD_MEMBER_EXPIRED;
	}
	
	Redis::command('EXPIRE', [CC::PREFIX_REWARD_MEMBER_LIST.$id, $expired]);
    }
    
    public static function checkRewardMember($id)
    {
	return Redis::command('EXISTS', [CC::PREFIX_REWARD_MEMBER_LIST . $id]);
    }

    public static function getRewardMember($id, $start, $end)
    {
	$data = Redis::command('ZRANGE', [CC::PREFIX_REWARD_MEMBER_LIST . $id, $start, $end]);

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
	Redis::command('DEL', [CC::PREFIX_REWARD_MEMBER_LIST . $id]);
    }
    
    
    //Network Cache
    public static function setNetworkMember($id, $data, $expired = null)
    {
	Redis::pipeline(function ($pipe) use ($id, $data) {
	    foreach ($data as $row) {
		$pipe->zadd(CC::PREFIX_NETWORK_MEMBER_LIST . $id, $row->network_id, json_encode($row));
	    }
	});
	
	if($expired == null){
	    $expired = CC::PREFIX_NETWORK_MEMBER_EXPIRED;
	}
	
	Redis::command('EXPIRE', [CC::PREFIX_NETWORK_MEMBER_LIST.$id, $expired]);
    }
    
    public static function checkNetworkMember($id)
    {
	return Redis::command('EXISTS', [CC::PREFIX_NETWORK_MEMBER_LIST . $id]);
    }

    public static function getNetworkMember($id, $start, $end)
    {
	$data = Redis::command('ZRANGE', [CC::PREFIX_NETWORK_MEMBER_LIST . $id, $start, $end]);

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
	Redis::command('DEL', [CC::PREFIX_NETWORK_MEMBER_LIST . $id]);
    }

}
