<?php
/**
 * Created by PhpStorm.
 * User: neogazebo
 * Date: 5/24/17
 * Time: 2:00 AM
 */

namespace App\Helpers;


class CommonConstants
{
    //Member
    const MEMBER_LOGIN_VIA_EMAIL = 1;
    const MEMBER_LOGIN_VIA_GOOGLE = 2;
    const MEMBER_STATUS_PENDING = 0;
    const MEMBER_STATUS_ACTIVE = 1;
    const MEMBER_STATUS_SUSPENDED = 2;
    
    const RESPONSE_SUCCESS = 1;
    const RESPONSE_FAILED = 0;
    
    const LANGUAGE_EN = 'en_US';
    const LANGUAGE_EN_SHORT = 'en';
    const LANGUAGE_ID = 'id_ID';
    const LANGUAGE_ID_ANDROID = 'in_ID';
    const LANGUAGE_ID_SHORT = 'id';
    
    const PLATFORM_ANDROID = 1;
    const PLATFORM_IOS = 2;

    //REDIS CACHE
    const PREFIX_PROFILE = 'profile:';
    const PREFIX_COLECTED_ITEMS = 'item:collected:' ;
    const PREFIX_REWARD_MEMBER = 'reward:' ;
    const PREFIX_NETWORK_MEMBER = 'network:' ;
    
    //API PAY endpoints
    const APIPAY_SIGNIN = '/v1/auth/signin';
    const APIPAY_SIGNIN_GOOGLE = '/v1/auth/signin/google';
}