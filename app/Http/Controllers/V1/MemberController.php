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

	if (empty($member)) {
	    $this->status = RS::HTTP_BAD_REQUEST;
	    $this->message = 'data not available';
	}
	else {
	    $this->code = CC::RESPONSE_SUCCESS;

	    $data = [];

	    $member->mem_account_id = $member->mem_acc_id;
	    unset($member->mem_acc_id);
	    foreach ($member as $key => $val) {
		$k = 'member_' . substr($key, 4);
		$data[$k] = $val;
	    }

	    $this->results['more'] = false;
	    $this->results['result'] = $data;
	}

	return $this->json();
    }

    public function network($page = CC::DEFAULT_PAGE, $size = CC::DEFAULT_SIZE)
    {
	$start = ($page - 1) * $size;
	$end = $start + $size - 1;
	$this->results['more'] = false;

	$member = \Swirf::getMember();

	$network = $this->__getNetwork($member->mem_id, $start, $end);
	
	if(count($network) == $size)
	{
	    $this->results['more'] = true;
	}

	$this->code = CC::RESPONSE_SUCCESS;
	$this->results['result'] = $network;

	return $this->json();
    }

    private function __getNetwork($member_id, $start, $end)
    {
	$is_exist = Redis::checkNetworkMember($member_id);
	if ($is_exist) {
	    return Redis::getNetworkMember($member_id, $start, $end);
	}
	
	$statement = 'select '
		. ' net_id as network_id,'
		. ' mem_id as member_id,'
		. ' mem_name as member_name,'
		. ' mem_email as member_email,'
		. ' mem_level as member_level'
		. ' from tbl_network'
		. ' left join tbl_member on mem_id = net_network_id'
		. ' where net_member_id = :member_id'
		. ' and net_status = 1';

	$network = \DB::select($statement, ['member_id' => $member_id]);
	Redis::setNetworkMember($member_id, $network);
	
	return Redis::getNetworkMember($member_id, $start, $end);
    }

}
