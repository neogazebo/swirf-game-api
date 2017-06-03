<?php

/**
 * Description of SwirfPayHelper
 *
 * @author neogazebo
 */

namespace App\Helpers;

use App\Helpers\CommonConstants as CC;
use App\Helpers\ReturnDataHelper as RDH;
use App\Helpers\ResponseHelper as RS;

class SwirfPayHelper {
    
    /**
     * 
     * @param type $email
     * @param type $phone
     * @param type $pass
     * @param type $country
     */
    public static function signin($email, $phone, $pass, $country)
    {
	$result = new RDH();
	
	$param = [
	    'email' => $email,
	    'phone_number' => $phone,
	    'password' => $pass,
	    'country' => $country
	];

	$param = json_encode($param);

	$key = env('PAY_KEY');
	$secret = env('PAY_SECRET');
	$url = env('PAY_URL_SIGN') . CC::APIPAY_SIGNIN;

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_HEADER, FALSE);
	curl_setopt($ch, CURLOPT_POST, TRUE);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
	curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "app-key: " . $key, "app-secret: " . $secret, "lang : en"]);

	$response = curl_exec($ch);
	$err = curl_errno($ch);
	$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	
	curl_close($ch);
	
	if($err)
	{
	    $result->setStatus($http_status);
	}
	else if($http_status >= RS::HTTP_INTERNAL_SERVER_ERROR)
	{
	    $result->setStatus($http_status);
	}
	else
	{
	    $data = json_decode($response, true);
	    if(empty($data))
	    {
		$result->setStatus(RS::HTTP_INTERNAL_SERVER_ERROR);
	    }
	    else
	    {
		$result->setCode(CC::RESPONSE_SUCCESS);
		unset($data['elapsed']);
		$result->setResult($data);
	    }
	}
	
	return $result;
    }
    
    /**
     * 
     * @param type $email
     * @param type $phone
     * @param type $country
     * @param type $google_id
     * @param type $name
     * @return type
     */
    public static function signinGoogle($email, $phone, $country, $google_id, $name)
    {
	$param = [
	    'email' => $email,
	    'phone_number' => 'not set',
	    'country' => $country,
	    'google' => [
		'id' => $google_id,
		'name' => $name,
	    ]
	];

	$param = json_encode($param);

	$key = env('PAY_KEY');
	$secret = env('PAY_SECRET');
	$url = env('PAY_URL_SIGN') . CC::APIPAY_SIGNIN_GOOGLE;

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_HEADER, FALSE);
	curl_setopt($ch, CURLOPT_POST, TRUE);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
	curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "app-key: " . $key, "app-secret: " . $secret, "lang : en"]);

	$response = curl_exec($ch);
	curl_close($ch);

	return json_decode($response, true);
    }
	    
}
