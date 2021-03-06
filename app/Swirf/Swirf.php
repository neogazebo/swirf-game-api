<?php

namespace App\Swirf;

use App\Helpers\CommonConstants as CC;
use App\Helpers\TokenHelper as TH;
use App\Helpers\ReturnDataHelper as RDH;
use App\Helpers\ResponseHelper as RH;
use App\Helpers\RedisHelper as Redis;

class Swirf {

    private $request_raw = [];
    private $input = null;
    private $isEncrypted = false;
    private $lang = CC::LANGUAGE_EN_SHORT;
    private $os = null;
    private $app_version = null;
    private $api_version = null;
    private $token = null;
    private $longitude = null;
    private $latitude = null;
    private $headers = [];
    private $memberAccountID = null;
    private $dataToken = null;

    public function isEncrypted()
    {
	return $this->isEncrypted;
    }

    public function getRequestRaw()
    {
	return $this->request_raw;
    }

    public function setInput($input)
    {
	$this->input = $input;
    }

    public function getInput($array = false)
    {
	return ($this->input == null) ? null : (is_array($this->input) ? (($array) ? $this->input : array2object($this->input)) : $this->input);
    }

    public function input($key = null, $array = false)
    {
	if (!($this->input == null))
	{
	    if ($key == null)
	    {
		return is_array($this->input) ? (($array) ? $this->input : array2object($this->input)) : $this->input;
	    }
	    else
	    {
		if (isset($this->input[$key]))
		{
		    return is_array($this->input[$key]) ? (($array) ? $this->input[$key] : array2object($this->input[$key])) : $this->input[$key];
		}
	    }
	}
	else
	{
	    if ($key == null)
	    {
		return ($array) ? array() : (object) array();
	    }
	}

	return;
    }

    public function processInput($input)
    {
	$this->request_raw = $input;
	$data = $input;
	if (beginWith(trim($data), '{'))
	{
	    $clean = prepare_json_decode($input);
	    $json = json_decode($clean, true, 512, JSON_BIGINT_AS_STRING);
	    if (!(is_null($json)))
	    {
		$this->input = valueArrayToValidType($json);
	    }
	}
	else
	{
	    $decrypted = base64_decode($data);
	    $clean = prepare_json_decode($decrypted);
	    $json = json_decode($clean, true);
	    if (!(is_null($json)))
	    {
		$this->input = valueArrayToValidType($json);
		$this->isEncrypted = true;
	    }
	}

	if (!is_null($this->input))
	{
	    $results = array();
	    foreach ($this->input as $key => $value) {
		$results[$key] = $value;
	    }
	    $this->input = $results;
	}
    }

    public function getOS()
    {
	return $this->os;
    }

    public function getAppVersion()
    {
	return $this->app_version;
    }

    public function getApiVersion()
    {
	return $this->api_version;
    }

    public function getToken()
    {
	return $this->token;
    }

    public function getLanguage()
    {
	return $this->lang;
    }

    public function getLatitude()
    {
	return $this->latitude;
    }

    public function getLongitude()
    {
	return $this->longitude;
    }

    public function processHeader($headers)
    {
	if (isset($headers['latitude']))
	{
	    $this->latitude = $headers['latitude'][0];
	}

	if (isset($headers['longitude']))
	{
	    $this->longitude = $headers['longitude'][0];
	}

	if (isset($headers['Lang']))
	{
	    $this->lang = $headers['Lang'][0];
	}

	if (isset($headers['os']))
	{
	    $this->os = $headers['os'][0];
	}

	if (isset($headers['application-version']))
	{
	    $this->app_version = $headers['application-version'][0];
	}

	if (isset($headers['api-version']))
	{
	    $this->api_version = $headers['api-version'][0];
	}

	if (isset($headers['token']))
	{
	    $this->token = $headers['token'][0];
	}

	$this->headers = $headers;
    }

    public function validToken()
    {
	$result = new RDH();

	if (!empty($this->token))
	{
	    $this->dataToken = TH::parse($this->token);
	    if ($this->dataToken->valid)
	    {
		$data = json_decode($this->dataToken->data);
		if ($data->is_active == CC::MEMBER_STATUS_ACTIVE)
		{
		    $this->memberAccountID = $data->account_id;
		    $result->setCode(CC::RESPONSE_SUCCESS);
		}
		else
		{
		    $result->status(RH::HTTP_INVALID_TOKEN);
		    $result->setMessage('Account Master Not Active');
		}
	    }
	    else
	    {
		$result->status(RH::HTTP_INVALID_TOKEN);
		$result->setMessage('Invalid Token');
	    }
	}
	else
	{
	    $result->status(RH::HTTP_INVALID_TOKEN);
	    $result->setMessage('Missing Token');
	}

	return $result;
    }

    public function getMember()
    {
	$member = Redis::getProfileCache($this->memberAccountID);
	if(!empty($member))
	{
	    return json_decode($member);
	}
	
	$statement = 'select * from tbl_member where mem_acc_id = :acc_id limit 0,1';

	$member = \DB::select($statement, ['acc_id' => $this->memberAccountID]);
	if (count($member) > 0)
	{
	    Redis::setProfileCache($this->memberAccountID, json_encode($member[0]));
	    return $member[0];
	}

	return null;
    }

    public function getMemberAccountID()
    {
	return $this->memberAccountID;
    }

}
