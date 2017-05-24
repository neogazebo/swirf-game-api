<?php
namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Helpers\CommonConstants as CC;
use App\Helpers\ResponseHelper as RS;

class DeviceController extends Controller
{
    use AppTrait;

    public function register()
    {
        $validator = Validator::make(\Swirf::input(null,true), [
            'os'            => 'required',
            'os_version'    => 'required',
            'brand'         => 'required',
            'model'         => 'required',
            'imsi'          => 'required',
            'imei'          => 'required',
            'device_id'     => 'required',
            'adv_id'        => 'required',
            'push_id'       => 'required'
        ]);

        if (!$validator->fails())
        {
            $results = \DB::table('tbl_device')->where('dev_device_id', \Swirf::input()->device_id)->first();
            $time    = time();

            try {
                if ($results == null) {
                    $insert = \DB::table('tbl_device')->insert(
                        [
                            'dev_os'            => \Swirf::input()->os,
                            'dev_os_version'    => \Swirf::input()->os_version,
                            'dev_brand'         => \Swirf::input()->brand,
                            'dev_model'         => \Swirf::input()->model,
                            'dev_imsi'          => \Swirf::input()->imsi,
                            'dev_imei'          => \Swirf::input()->imei,
                            'dev_device_id'     => \Swirf::input()->device_id,
                            'dev_adv_id'        => \Swirf::input()->adv_id,
                            'dev_push_id'       => \Swirf::input()->push_id,
                            'dev_created_at'    => $time,
                            'dev_updated_at'    => $time
                        ]);

                    if ($insert == 0) {
                        throw new Exception('error insert');
                    }
                    $message = 'Store new device';
                } else {
                    /*
                    $update = \DB::table('tbl_device')->where('dev_device_id', \Swirf::input()->device_id)->update(['dev_updated_at' => $time]);
                    if($update == 0) {
                        throw new Exception('error update');
                    }
                    */
                    $message = 'Device is registered';
                }


                $this->code    = CC::RESPONSE_SUCCESS;
                $this->message = $message;

            } catch (Exception $e) {
                $this->status  = RS::HTTP_INTERNAL_SERVER_ERROR;
                $this->message = 'Error server';
            }
        }else{
            $this->status = RS::HTTP_BAD_REQUEST;
            $this->results = $validator->errors();
            $this->message = 'Error Parameters';
        }
        return $this->json();
    }
}