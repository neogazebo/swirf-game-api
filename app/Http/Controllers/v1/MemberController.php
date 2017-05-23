<?php
namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class MemberController extends Controller
{
    const ACCOUNT_PENDING   = 0;
    const ACCOUNT_ACTIVE    = 0;
    const ACCOUNT_SUSPENDED = 0;

    const COUNTRY           = 'ID';

    const LOGIN_APP         = 1;
    const LOGIN_GOOGLE      = 2;

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'     => [
                            'required',
                            'email',
                            'unique:tbl_member,mem_email',
                        ],
            'password'  => 'required|string',
            'phone'     => [
                            'required',
                            'string',
                            'unique:tbl_member,mem_mobile',
                        ],
            'name'      => 'required|string',
            'device_id' => 'required|string'
        ]);

        if (!$validator->fails())
        {
            try {
                // STORE MEMBER
                $memId = $this->storeMember(self::LOGIN_APP, $request->name, $request->phone, $request->password, $request->email, null, self::ACCOUNT_PENDING, self::COUNTRY);

                // UPDATE DEVICE
                \DB::table('tbl_device')->where('dev_device_id', $request->device_id)->update(['dev_mem_id' => $memId]);

                // SIGN PAY
                $pay     = $this->signPay($request->email, $request->chanel, $request->phone, $request->password, self::COUNTRY);

                if($pay['success'] == 0) {
                    throw new \Exception();
                }

                $accId   = $pay['data']['id'];
                $token   = $pay['data']['token'];
                $dokuId  = $pay['data']['doku_id'];

                \DB::table('tbl_member')->where('mem_id', $memId)->update(['mem_acc_id' => $accId, 'mem_token' => $token]);

                // TODO STORE REDIS

                $status  = 200;
                $code    = 1;
                $result  = ['token' => $token];
                $message = 'Success register';
            } catch (\Exception $e) {
                $status  = 500;
                $code    = 0;
                $result  = [];
                $message = 'Error server';
            }
        }else{
            $status  = 400;
            $code    = 0;
            $result  = $validator->errors();
            $message = 'Error Parameters';
        }

        $content = ['code' => $code, 'message' => $message, 'result' => $result];
        return response($content, $status);
    }

    public function login_app(request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'     => 'required|email',
            'password'  => 'required|string',
            'device_id' => 'required|string'
        ]);

        if (!$validator->fails())
        {
            $member = \DB::table('tbl_member')->where('mem_email', $request->email)->first();

            try {
                if($member == null) {
                    $status     = 200;
                    $code       = 0;
                    $result     = [];
                    $message    = 'Wrong Credential Account';
                } elseif($member->mem_status_flag == 2) {
                    $status     = 403;
                    $code       = 0;
                    $result     = [];
                    $message    = 'Your Account Suspended';
                } else {
                    if (Hash::check($request->password, $member->mem_password))
                    {
                        // UPDATE DEVICE
                        \DB::table('tbl_device')->where('dev_device_id', $request->device_id)->update(['dev_mem_id' => $member->mem_id]);

                        // SIGN PAY
                        $pay     = $this->signPay($request->email, $member->mem_signup_channel, $member->mem_mobile, $request->password, $member->mem_country);

                        if($pay['success'] == 0) {
                            throw new \Exception('Error server');
                        }
                        $token   = $pay['data']['token'];
                        $dokuId  = $pay['data']['doku_id'];

                        \DB::table('tbl_member')->where('mem_id', $member->mem_id)->update(['mem_token' => $token]);

                        // TODO STORE REDIS

                        $status  = 200;
                        $code    = 1;
                        $result  = ['token' => $token];
                        $message = 'Success login';
                    }else{
                        $status  = 200;
                        $code    = 0;
                        $result  = [];
                        $message = 'Wrong Credential Account';
                    }
                }
            } catch(\Exception $e) {
                $status  = 500;
                $code    = 0;
                $result  = [];
                $message = 'Error server';
            }
        }else{
            $status  = 400;
            $code    = 0;
            $result  = $validator->errors();
            $message = 'Error Parameters';
        }

        $content = ['code' => $code, 'message' => $message, 'result' => $result];
        return response($content, $status);
    }

    public function login_google(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'     => 'required|email',
            'name'      => 'required',
            'device_id' => 'required|string'
        ]);

        if (!$validator->fails())
        {
            $member = \DB::table('tbl_member')->where('mem_g_email', $request->email)->first();

            try {
                if($member == null) {
                    $phone      = null;
                    $password   = null;

                    // STORE MEMBER
                    $memId = $this->storeMember(self::LOGIN_GOOGLE, $request->name, $phone, $password, null, $request->email, self::ACCOUNT_PENDING, self::COUNTRY);
                }else{
                    $phone    = $member->mem_mobile;
                    $password = $member->mem_password;
                    $memId    = $member->mem_id;
                }

                // UPDATE DEVICE
                \DB::table('tbl_device')->where('dev_device_id', $request->device_id)->update(['dev_mem_id' => $memId]);

                // SIGN PAY
                $pay = $this->signPay($request->email, self::LOGIN_GOOGLE, $phone, $password, self::COUNTRY);

                if($pay['success'] == 0) {
                    throw new \Exception();
                }

                $token   = $pay['data']['token'];
                $accId   = $pay['data']['id'];
                $dokuId  = $pay['data']['doku_id'];

                \DB::table('tbl_member')->where('mem_id', $memId)->update(['mem_acc_id' => $accId, 'mem_token' => $token]);

                // TODO STORE REDIS

                $status  = 200;
                $code    = 1;
                $result  = ['token' => $token];
                $message = 'Success login';
            } catch(\Exception $e) {
                $status  = 200;
                $code    = 0;
                $result  = [];
                $message = 'Error login';
            }
        }else{
            $status  = 400;
            $code    = 0;
            $result  = $validator->errors();
            $message = 'Error Parameters';
        }

        $content = ['code' => $code, 'message' => $message, 'result' => $result];
        return response($content, $status);
    }

    public function logout(Request $request) {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string'
        ]);

        if (!$validator->fails())
        {
            // TODO hwo to get  mem_id by token ?

            // UPDATE DEVICE
            \DB::table('tbl_device')->where('dev_device_id', $request->device_id)->update(['dev_mem_id' => null, 'mem_token' => null]);

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
        echo 'hi';
    }

    private function storeMember($chanel, $name, $phone, $password, $email, $gmail, $status, $country)
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
                'mem_status_flag'           => $status,
                'mem_country'               => $country,
            ]);

        return $id;
    }

    private function signPay($email, $chanel, $phone, $pass, $country)
    {
        $param = "email={$email}&signup_chanel={$chanel}&phone_number={$phone}&password={$pass}&country={$country}";
        $key   = env('PAY_KEY');
        $secret= env('PAY_SECRET');
        $url   = env('PAY_URL_SIGN');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param) ;
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/x-www-form-urlencoded","app-key: ".$key,"app-secret: ".$secret));

        $response = curl_exec($ch);

        return json_decode($response, true);

        curl_close($ch);
    }
}