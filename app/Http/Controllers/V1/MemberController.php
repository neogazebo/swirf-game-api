<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Helpers\CommonConstants as CC;
use App\Helpers\ResponseHelper as RS;
use App\Helpers\RedisHelper as Redis;

class MemberController extends Controller {

    use AppTrait;

    public function profile()
    {
	$member = \Swirf::getMember();

	if (empty($member))
	{
	    $this->status = RS::HTTP_BAD_REQUEST;
	    $this->message = 'data not available';
	}
	else
	{
	    $this->code = CC::RESPONSE_SUCCESS;

	    $data = [];

	    unset($member->mem_acc_id);
	    foreach ($member as $key => $val) 
	    {
		$k = 'member_' . substr($key, 4);
		$data[$k] = $val;
	    }

	    $this->results = $data;
	}

	return $this->json();
    }

}
