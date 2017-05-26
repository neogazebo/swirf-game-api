<?php
namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Helpers\CommonConstants as CC;
use App\Helpers\ResponseHelper as RS;

class MemberController extends Controller
{
    use AppTrait;

    const ACCOUNT_PENDING   = 0;
    const ACCOUNT_ACTIVE    = 0;
    const ACCOUNT_SUSPENDED = 0;

    const COUNTRY           = 'ID';

    const LOGIN_APP         = 1;
    const LOGIN_GOOGLE      = 2;

    public function register()
    {
        $validator = Validator::make(\Swirf::input(null,true), [
            'email'     => [
                            'required',
                            'email',
                            'unique:tbl_member,mem_email',
                        ],
            'password'  => 'required|string',
            'phone'     => [
                            'required',
                            'unique:tbl_member,mem_mobile',
                        ],
            'name'      => 'required|string',
            'device_id' => 'required|string'
        ]);

        if (!$validator->fails())
        {
            try {
                \DB::beginTransaction();

                    // STORE MEMBER
                    $memId = $this->storeMember(self::LOGIN_APP, \Swirf::input()->name, \Swirf::input()->phone, \Swirf::input()->password, \Swirf::input()->email, null, null, self::ACCOUNT_PENDING, self::COUNTRY);

                    // UPDATE DEVICE
                    \DB::table('tbl_device')->where('dev_device_id', \Swirf::input()->device_id)->update(['dev_mem_id' => $memId]);

                    // SIGN PAY
                    $pay   = $this->signPayApp(\Swirf::input()->email, \Swirf::input()->phone, \Swirf::input()->password, self::COUNTRY);
                    
                    if($pay['success'] == 0) {
                        throw new \Exception();
                    }

                    $accId   = $pay['data']['id'];
                    $token   = $pay['data']['token'];
                    $dokuId  = $pay['data']['doku_id'];

                    \DB::table('tbl_member')->where('mem_id', $memId)->update(['mem_acc_id' => $accId, 'mem_token' => $token]);

                \DB::commit();

                // TODO STORE REDIS
                //$this->cacheMember(self::LOGIN_APP, $memId, \Swirf::input()->email, \Swirf::input()->name, \Swirf::input()->phone);

                $this->code = CC::RESPONSE_SUCCESS;
                $this->results = ['token' => $token];
                $this->message = 'Success register';

            } catch (\Exception $e) {
                \DB::rollBack();

                $this->status = RS::HTTP_INTERNAL_SERVER_ERROR;
                $this->message = 'Error server';
            }
        }else{
            $this->status = RS::HTTP_BAD_REQUEST;
            $this->results = $validator->errors();
            $this->message = 'Error Parameters';
        }

        return $this->json();
    }

    public function login_app()
    {
        $validator = Validator::make(\Swirf::input(null,true), [
            'email'     => 'required|email',
            'password'  => 'required|string',
            'device_id' => 'required|string'
        ]);

        if (!$validator->fails())
        {
            $member = \DB::table('tbl_member')->where('mem_email', \Swirf::input()->email)->first();

            try {
                if($member == null) {
                    $this->message = 'Wrong Credential Account';
                } elseif($member->mem_status_flag == 2) {
                    $this->status  = RS::HTTP_FORBIDDEN;
                    $this->message = 'Your account has suspended';
                } else {
                    if (Hash::check(\Swirf::input()->password, $member->mem_password))
                    {
                        \DB::beginTransaction();

                            // UPDATE DEVICE
                            \DB::table('tbl_device')->where('dev_device_id', \Swirf::input()->device_id)->update(['dev_mem_id' => $member->mem_id]);

                            // SIGN PAY
                            $pay     = $this->signPayApp(\Swirf::input()->email, $member->mem_mobile, \Swirf::input()->password, $member->mem_country);

                            if($pay['success'] == 0) {
                                throw new \Exception('Error server');
                            }
                            $token   = $pay['data']['token'];
                            $dokuId  = $pay['data']['doku_id'];

                            \DB::table('tbl_member')->where('mem_id', $member->mem_id)->update(['mem_token' => $token]);

                        \DB::commit();

                        $this->code = CC::RESPONSE_SUCCESS;
                        $this->results = ['token' => $token];
                        $this->message = 'Success login';
                    }else{
                        $this->message = 'Wrong Credential Account';
                    }
                }
            } catch(\Exception $e) {
                \DB::rollBack();

                $this->status = RS::HTTP_INTERNAL_SERVER_ERROR;
                $this->message = 'Error server';
            }
        }else{
            $this->status = RS::HTTP_BAD_REQUEST;
            $this->code   = CC::RESPONSE_FAILED;
            $this->message = 'Error Parameters';
            $this->results = $validator->errors();
        }

        return $this->json();
    }

    public function login_google()
    {
        $validator = Validator::make(\Swirf::input(null,true), [
            'email'     => 'required|email',
            'google_id' => 'required',
            'name'      => 'required',
            'device_id' => 'required|string'
        ]);

        if (!$validator->fails())
        {
            $member = \DB::table('tbl_member')->where('mem_g_email', \Swirf::input()->email)->first();

            try {
                \DB::beginTransaction();

                    if($member == null) {
                        // STORE MEMBER
                        $memId = $this->storeMember(self::LOGIN_GOOGLE, \Swirf::input()->name, null, null, null, \Swirf::input()->email, \Swirf::input()->google_id, self::ACCOUNT_PENDING, self::COUNTRY);
                    }else{
                        $memId = $member->mem_id;
                    }

                    // UPDATE DEVICE
                    \DB::table('tbl_device')->where('dev_device_id', \Swirf::input()->device_id)->update(['dev_mem_id' => $memId]);

                    // SIGN PAY
                    $pay = $this->signPayGoogle(\Swirf::input()->email, null, self::COUNTRY, \Swirf::input()->google_id, \Swirf::input()->name);

                    if($pay['success'] == 0) {
                        throw new \Exception();
                    }

                    $token   = $pay['data']['token'];
                    $accId   = $pay['data']['id'];
                    $dokuId  = $pay['data']['doku_id'];

                    \DB::table('tbl_member')->where('mem_id', $memId)->update(['mem_acc_id' => $accId, 'mem_token' => $token]);

                \DB::commit();

                $this->code = CC::RESPONSE_SUCCESS;
                $this->results = ['token' => $token];
                $this->message = 'Success login';
            } catch(\Exception $e) {
                \DB::rollBack();

                $this->status = RS::HTTP_INTERNAL_SERVER_ERROR;
                $this->message = 'Error server';
            }
        }else{
            $this->status = RS::HTTP_BAD_REQUEST;
            $this->code   = CC::RESPONSE_FAILED;
            $this->message = 'Error Parameters';
            $this->results = $validator->errors();
        }

        return $this->json();
    }

    public function logout(Request $request) {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string'
        ]);

        if (!$validator->fails())
        {
            // UPDATE DEVICE
            \DB::table('tbl_device')->where([
                ['dev_device_id', '=', $request->device_id],
                ['dev_mem_id',    '=', $request->mem_id],
            ])->update(['dev_mem_id' => null, 'mem_token' => null]);

            $status  = 200;
            $code    = 1;
            $result  = [];
            $message = 'Success Logout';
        }else{
            $status  = 400;
            $code    = 0;
            $result  = $validator->errors();
            $message = 'Error Parameters';
        }

        $content = ['code' => $code, 'message' => $message, 'result' => $result];
        return response($content, $status);
    }

    public function get_info(Request $request)
    {
        $value = Cache::get('member_' . $request->mem_id);

        $content = ['code' => 1, 'message' => '', 'result' => $value];
        return response($content, 200);
    }

    private function storeMember($chanel, $name, $phone, $password, $email, $gmail, $google_id, $status, $country)
    {
        $id = \DB::table('tbl_member')->insertGetId(
            [
                'mem_signup_channel'        => $chanel,
                'mem_name'                  => $name,
                'mem_mobile'                => $phone,
                'mem_email'                 => $email,
                'mem_g_email'               => $gmail,
                'mem_password'              => Hash::make($password),
                'mem_signed_nda'            => 0,
                'mem_given_password'        => 0,
                'mem_change_password_mail'  => 0,
                'mem_level'                 => 0,
                'mem_points'                => 0,
                'mem_points_network'        => 0,
                'mem_balance'               => 0,
                'mem_invite_slot'           => 0,
                'mem_invite_count'          => 0,
                'mem_nr_invite'             => 0,
                'mem_active_flag'           => 0,
                'mem_active_indicator'      => 0,
                'mem_invoice_allowed'       => 0,
                'mem_cookie_value'          => 0,
                'mem_google_id'             => $google_id,
                'mem_status_flag'           => $status,
                'mem_country'               => $country,
            ]);

        return $id;
    }

    private function cacheMember($memId, $channel, $email, $name, $phone) {

        $member = [
                'channel'   => $channel,
                'email'     => $email,
                'name'      => $name,
                'phone'     => $phone,
            ];

        \Cache::forever('member_' . $memId, json_encode($member));
    }

    private function signPayApp($email, $phone, $pass, $country)
    {
        $param = [
            'email'         => $email,
            'phone_number'  => $phone,
            'password'      => $pass,
            'country'       => $country
        ];

        $param = json_encode($param);

        $key   = env('PAY_KEY');
        $secret= env('PAY_SECRET');
        $url   = env('PAY_URL_SIGN') . '/v1/auth/signin';

        $ch    = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param) ;
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json","app-key: ".$key,"app-secret: ".$secret,"lang : en"));

        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }

    private function signPayGoogle($email, $phone, $country, $google_id, $name)
    {
        $param = [
            'email'         => $email,
            'phone_number'  => 'not set',
            'country'       => $country,
            'google'        => [
                'id'   => $google_id,
                'name' => $name,
            ]
        ];

        $param = json_encode($param);

        $key   = env('PAY_KEY');
        $secret= env('PAY_SECRET');
        $url   = env('PAY_URL_SIGN') . '/v1/auth/signin/google';

        $ch    = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param) ;
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json","app-key: ".$key,"app-secret: ".$secret,"lang : en"));

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }
}