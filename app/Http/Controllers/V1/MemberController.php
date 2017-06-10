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
	    
	    $member->mem_account_id = $member->mem_acc_id;
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
    
    public function network()
    {
	$member = \Swirf::getMember();
	
	$network = $this->__getNetwork($member->mem_id);
	
	$this->code = CC::RESPONSE_SUCCESS;
	$this->results = $network;
	
	return $this->json();
    }
    
    private function __getNetwork($member_id)
    {
	$statement = 'select '
		. ' mem_id as network_id,'
		. ' mem_name as network_name'
		. ' from tbl_network'
		. ' left join tbl_member on mem_id = net_network_id'
		. ' where net_member_id = :member_id'
		. ' and net_status = 1';
	
	$network = \DB::select($statement,['member_id' => $member_id]);
	
	return $network;
		
    }
    
}
