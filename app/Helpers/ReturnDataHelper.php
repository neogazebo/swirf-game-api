<?php

/**
 * Description of ReturnDataHelper
 *
 * @author neogazebo
 */

namespace App\Helpers;

class ReturnDataHelper {

    private $code = 0;
    private $message = null;
    private $result = null;
    private $status = 200;

    public function __construct($code = 0, $message = null, $result = null)
    {
	$this->code = $code;
	$this->message = $message;
	$this->result = $result;
    }

    public function setStatus($status)
    {
	$this->status = $status;
    }

    public function status()
    {
	return $this->status;
    }

    public function setCode($code)
    {
	$this->code = $code;
    }

    public function code()
    {
	return $this->code;
    }

    public function setMessage($message)
    {
	$this->message = $message;
    }

    public function message()
    {
	return $this->message;
    }

    public function setResult($result)
    {
	$this->result = $result;
    }

    public function result()
    {
	return $this->result;
    }

}
