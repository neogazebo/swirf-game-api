<?php
namespace App\Http\Controllers\v1;

use App\Device;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Mockery\CountValidator\Exception;

class DeviceController extends Controller
{
    public function register()
    {
        // PARAM
        $os         = $_POST['os'];
        $osVersion  = $_POST['os_version'];
        $brand      = $_POST['brand'];
        $model      = $_POST['model'];
        $imsi       = $_POST['imsi'];
        $imei       = $_POST['imei'];
        $deviceId   = $_POST['device_id'];
        $advId      = $_POST['adv_id'];
        $pushId     = $_POST['push_id'];

        $results = \DB::table('tbl_device')->where('dev_device_id', $deviceId)->first();

        try {
            if($results == null)
            {
                $insert = \DB::table('tbl_device')->insert(
                    [
                        'dev_os'            => $os,
                        'dev_os_version'    => $osVersion,
                        'dev_brand'         => $brand,
                        'dev_model'         => $model,
                        'dev_imsi'          => $imsi,
                        'dev_imei'          => $imei,
                        'dev_device_id'     => $deviceId,
                        'dev_adv_id'        => $advId,
                        'dev_push_id'       => $pushId,
                        'dev_created_at'    => time()
                    ]);

                if($insert == 0) {
                    throw new Exception('error insert');
                }
                $message = 'Store new device';
            }else{
                $message = 'Device is registered';
            }

            $status = 200;
            $code   = 1;
        } catch (Exception $e) {
            $message = $e->getMessage();
            $code    = 0;
            $status  = 500;
        }

        $content = ['code' => $code, 'message' => $message];
        return response($content, $status);
    }
}