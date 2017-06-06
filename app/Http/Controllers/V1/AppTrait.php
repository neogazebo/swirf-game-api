<?php

/*
 */

namespace App\Http\Controllers\V1;

use App\Helpers\TimeHelper as TH;
use App\Helpers\CommonConstants as CC;


trait AppTrait
{

    public $code = CC::RESPONSE_FAILED;
    public $message = null;
    public $results = null;
    public $status = 200;

    /**
     * @return Response
     */
    public function json()
    {
        $result = array();
        $result['code'] = $this->code;
        $result['message'] = $this->message;
        if (!($this->results == null)) {
            if (is_array($this->results)) {
                $this->results = valueArrayToValidType($this->results);
            }
            $result['data'] = $this->results;
        }
        $result['elapsed'] = TH::serverElapsedTime();
        if(\Swirf::isEncrypted())
        {
            $encrypted = base64_encode(json_encode($result));
            return response($encrypted, $this->status);
        }
        return response()->json($result, $this->status);
    }
}