<?php
namespace App\Helpers;

use App\Helpers\CommonConstants as CC;
use Illuminate\Support\Facades\Redis;

class RedisHelper
{
    /**
     * @param $account_id mem_acc_id
     * @param $data member data Json
     */
    public static function setProfileCache($account_id, $data)
    {
        Redis::set(CC::PREFIX_PROFILE . $account_id, $data);
    }
    
    public static function getProfileCache($account_id){
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
	Redis::set(CC::PREFIX_REWARD_MEMBER . $id, $data);
    }
    
    public static function getRewardMember($id)
    {
	return Redis::get(CC::PREFIX_REWARD_MEMBER . $id);
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