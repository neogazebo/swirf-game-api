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

}