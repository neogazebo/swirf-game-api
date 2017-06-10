<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Helpers\CommonConstants as CC;
use App\Helpers\ResponseHelper as RS;
use App\Helpers\SwirfPayHelper as SP;
use App\Helpers\RedisHelper as Redis;

class AuthController extends Controller {

    use AppTrait;

    const COUNTRY = 'ID';

    public function register()
    {
	$validator = Validator::make(\Swirf::input(null, true), [
	    'email' => [
		'required',
		'email',
		Rule::unique('tbl_member', 'mem_email')->where(function ($query) {
		    $query->where('mem_signup_channel', CC::MEMBER_LOGIN_VIA_EMAIL);
		})
//		'unique:tbl_member,mem_email'
	    ],
	    'password' => 'required|string',
	    'phone' => [
		'required',
		Rule::unique('tbl_member', 'mem_mobile')->where(function ($query) {
		    $query->where('mem_signup_channel', CC::MEMBER_LOGIN_VIA_EMAIL);
		})
//		'unique:tbl_member,mem_mobile'
	    ],
	    'name' => 'required|string',
	    'device_id' => 'required|string'
	]);

	if (!$validator->fails())
	{
	    $swirf_account = SP::signin(\Swirf::input()->email, \Swirf::input()->phone, \Swirf::input()->password, self::COUNTRY);
	    if ($swirf_account->code() == CC::RESPONSE_FAILED)
	    {
		$this->status = $swirf_account->status();
		$this->message = 'Cannot connect to pay api';
	    }
	    else
	    {
		$swirf_account = $swirf_account->result();
		if ($swirf_account['success'] == true)
		{
		    $member = $this->__getMemberbyAccountId($swirf_account['data']['id']);
		    if ($member == null)
		    {
			try {
			    \DB::beginTransaction();

			    $member_id = $this->__storeMember($swirf_account['data']['id'], CC::MEMBER_LOGIN_VIA_EMAIL, \Swirf::input()->name, \Swirf::input()->phone, \Swirf::input()->email, '', '', CC::MEMBER_STATUS_ACTIVE, self::COUNTRY);
			    \DB::table('tbl_device')->where('dev_device_id', \Swirf::input()->device_id)->update(['dev_mem_id' => $member_id]);
			    //TODO : put member data to redis

			    \DB::commit();

			    $this->code = CC::RESPONSE_SUCCESS;
			    $this->results = ['token' => $swirf_account['data']['token']];
			    $this->message = 'Success login';
			} catch (\Exception $e) {
			    \DB::rollBack();
			    $this->status = $e->getMessage();
			    $this->message = 'Error server';
			}
		    }
		    else
		    {
			$this->message = 'Member Already exist';
		    }
		}
		else
		{
		    $this->message = 'error from pay api : '.$swirf_account['message'];
		    $this->status = RS::HTTP_UNAUTHORIZED;
		}
	    }
	}
	else
	{
	    $this->status = RS::HTTP_BAD_REQUEST;
	    $this->results = $validator->errors();
	    $this->message = 'Error Parameters';
	}

	return $this->json();
    }

    public function login()
    {
	$validator = Validator::make(\Swirf::input(null, true), [
		    'email' => 'required|email',
		    'password' => 'required|string',
		    'device_id' => 'required|string'
	]);

	if (!$validator->fails())
	{
	    $member = \DB::table('tbl_member')->where([
			['mem_email', '=', \Swirf::input()->email],
			['mem_signup_channel', '=', CC::MEMBER_LOGIN_VIA_EMAIL]
		    ])->first();

	    if ($member == null)
	    {
		$this->message = 'Wrong Credential Account';
	    }
	    elseif ($member->mem_status_flag != CC::MEMBER_STATUS_ACTIVE)
	    {
		$this->status = RS::HTTP_FORBIDDEN;
		$this->message = 'Your account has suspended';
	    }
	    else
	    {
		$swirf_account = SP::signin(\Swirf::input()->email, $member->mem_mobile, \Swirf::input()->password, $member->mem_country);
		if ($swirf_account->code() == CC::RESPONSE_FAILED)
		{
		    $this->status = $swirf_account->status();
		    $this->message = 'Cannot connect to pay api';
		}
		else
		{
		    $swirf_account = $swirf_account->result();
		    if ($swirf_account['success'] == true)
		    {
			try {
			    \DB::beginTransaction();

			    $member = $this->__getMemberbyAccountId($swirf_account['data']['id']);
			    \DB::table('tbl_device')->where('dev_device_id', \Swirf::input()->device_id)->update(['dev_mem_id' => $member->mem_id]);
			    //TODO put member data to redis

			    \DB::commit();
			} catch (\Exception $e) {
			    \DB::rollBack();
			    $this->status = $e->getMessage();
			    $this->message = 'Error server';
			}
			$this->code = CC::RESPONSE_SUCCESS;
			$this->results = ['token' => $swirf_account['data']['token']];
			$this->message = 'Success login';
		    }
		    else
		    {
			$this->message = $swirf_account['message'];
			$this->status = RS::HTTP_UNAUTHORIZED;
		    }
		}
	    }
	}
	else
	{
	    $this->status = RS::HTTP_BAD_REQUEST;
	    $this->message = 'Error Parameters';
	    $this->results = $validator->errors();
	}

	return $this->json();
    }

    public function loginGoogle()
    {
	$validator = Validator::make(\Swirf::input(null, true), [
		    'email' => 'required|email',
		    'google_id' => 'required',
		    'name' => 'required',
		    'device_id' => 'required|string',
		    'phone' => 'string'
	]);

	if (!$validator->fails())
	{
	    $swirf_account = SP::signinGoogle(\Swirf::input()->email, \Swirf::input()->phone, self::COUNTRY, \Swirf::input()->google_id, \Swirf::input()->name);

	    if ($swirf_account->code() == CC::RESPONSE_FAILED)
	    {
		$this->status = $swirf_account->status();
		$this->message = 'Cannot connect to pay api';
	    }
	    else
	    {
		$swirf_account = $swirf_account->result();
		if ($swirf_account['success'] == true)
		{
		    try {
			\DB::beginTransaction();

			$member = $this->__getMemberbyAccountId($swirf_account['data']['id']);
			if ($member == null)
			{
			    $member_id = $this->__storeMember($swirf_account['data']['id'], CC::MEMBER_LOGIN_VIA_GOOGLE, \Swirf::input()->name, \Swirf::input()->phone, \Swirf::input()->email, \Swirf::input()->email, \Swirf::input()->google_id, CC::MEMBER_STATUS_ACTIVE, self::COUNTRY);
			}
			else
			{
			    $member_id = $member->mem_id;
			}
			\DB::table('tbl_device')->where('dev_device_id', \Swirf::input()->device_id)->update(['dev_mem_id' => $member_id]);
			//TODO : put member data to redis

			\DB::commit();

			$this->code = CC::RESPONSE_SUCCESS;
			$this->results = ['token' => $swirf_account['data']['token']];
			$this->message = 'Success login';
		    } catch (\Exception $e) {
			\DB::rollBack();
			$this->status = $e->getMessage();
			$this->message = 'Error server';
		    }
		}
		else
		{
		    $this->message = $swirf_account['message'];
		    $this->status = RS::HTTP_UNAUTHORIZED;
		}
	    }
	}
	else
	{
	    $this->status = RS::HTTP_BAD_REQUEST;
	    $this->message = 'Error Parameters';
	    $this->results = $validator->errors();
	}

	return $this->json();
    }

    public function logout(Request $request)
    {
	$validator = Validator::make($request->all(), [
		    'device_id' => 'required|string'
	]);

	if (!$validator->fails())
	{
	    // UPDATE DEVICE
	    \DB::table('tbl_device')->where([
		['dev_device_id', '=', $request->device_id],
		['dev_mem_id', '=', $request->mem_id],
	    ])->update(['dev_mem_id' => null, 'mem_token' => null]);

	    $status = 200;
	    $code = 1;
	    $result = [];
	    $message = 'Success Logout';
	}
	else
	{
	    $status = 400;
	    $code = 0;
	    $result = $validator->errors();
	    $message = 'Error Parameters';
	}

	$content = ['code' => $code, 'message' => $message, 'result' => $result];
	return response($content, $status);
    }

    private function __storeMember($account_id, $chanel, $name, $phone, $email, $gmail, $google_id, $status, $country)
    {
	$id = \DB::table('tbl_member')->insertGetId([
	    'mem_acc_id' => $account_id,
	    'mem_signup_channel' => $chanel,
	    'mem_name' => $name,
	    'mem_mobile' => $phone,
	    'mem_email' => $email,
	    'mem_g_email' => $gmail,
	    'mem_google_id' => $google_id,
	    'mem_status_flag' => $status,
	    'mem_country' => $country,
	]);

	return $id;
    }

    private function __getMemberbyAccountId($account_id)
    {
	$result = null;
	
	$member = Redis::getProfileCache($account_id);
	if(!empty($member))
	{
	    return json_decode($member);
	}

	$statement = 'Select * from tbl_member where mem_acc_id = :account_id limit 0,1';
	$member = \DB::select($statement, ['account_id' => $account_id]);
	if (count($member) > 0)
	{
	    $result = $member[0];
	    Redis::setProfileCache($account_id, json_encode($result));
	}

	return $result;
    }

}
