<?php
namespace App\Helpers;

use App\Helpers\CommonConstants as CC;
use Illuminate\Support\Facades\Redis;

class RedisHelper
{
    /**
     * @param $id member id
     * @param $data member data Json
     */
    public static function setProfileCache($id, $data)
    {
        Redis::set(CC::PREFIX_PROFILE . $id, $data);
    }
    
    public static function getProfileCache($id){
	return Redis::get(CC::PREFIX_PROFILE . $id);
    }
    
    public static function deleteProfileCache($id)
    {
	Redis::command('DEL', [CC::PREFIX_PROFILE . $id]);
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
}