<?php

namespace App\Swirf;

use App\Helpers\CommonConstants as CC;

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
	    } else
	    {
		if (isset($this->input[$key]))
		{
		    return is_array($this->input[$key]) ? (($array) ? $this->input[$key] : array2object($this->input[$key])) : $this->input[$key];
		}
	    }
	} else
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
	} else
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

    public function processHeader($header)
    {
	if (isset($header['latitude']))
	{
	    $this->latitude = $header['latitude'];
	}

	if (isset($header['longitude']))
	{
	    $this->longitude = $header['longitude'];
	}

	if (isset($header['Lang']))
	{
	    $this->lang = $header['Lang'];
	}

	if (isset($header['os']))
	{
	    $this->os = $header['os'];
	}

	if (isset($header['appVer']))
	{
	    $this->app_version = $header['appVer'];
	}

	if (isset($header['apiVer']))
	{
	    $this->api_version = $header['apiVer'];
	}

	if (isset($header['token']))
	{
	    $this->token = $header['token'];
	}
    }

}
