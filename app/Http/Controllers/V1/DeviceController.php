<?php
namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeviceController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'os'            => 'required|string',
            'os_version'    => 'required|string',
            'brand'         => 'required|string',
            'model'         => 'required|string',
            'imsi'          => 'required|string',
            'imei'          => 'required|string',
            'device_id'     => 'required|string',
            'adv_id'        => 'required|string',
            'push_id'       => 'required|string'
        ]);

        if (!$validator->fails())
        {
            $results = \DB::table('tbl_device')->where('dev_device_id', $request->device_id)->first();
            $time    = time();

            try {
                if ($results == null) {
                    $insert = \DB::table('tbl_device')->insert(
                        [
                            'dev_os'            => $request->os,
                            'dev_os_version'    => $request->os_version,
                            'dev_brand'         => $request->brand,
                            'dev_model'         => $request->model,
                            'dev_imsi'          => $request->imsi,
                            'dev_imei'          => $request->imei,
                            'dev_device_id'     => $request->device_id,
                            'dev_adv_id'        => $request->adv_id,
                            'dev_push_id'       => $request->push_id,
                            'dev_created_at'    => $time,
                            'dev_updated_at'    => $time
                        ]);

                    if ($insert == 0) {
                        throw new Exception('error insert');
                    }
                    $message = 'Store new device';
                } else {
                    /*
                    $update = \DB::table('tbl_device')->where('dev_device_id', $request->device_id)->update(['dev_updated_at' => $time]);
                    if($update == 0) {
                        throw new Exception('error update');
                    }
                    */
                    $message = 'Device is registered';
                }

                $status = 200;
                $code   = 1;
                $result = [];
            } catch (Exception $e) {
                $message = $e->getMessage();
                $status  = 500;
                $code    = 0;
                $result  = [];
            }
        }else{
            $message = 'Error Parameters';
            $status  = 400;
            $code    = 0;
            $result  = $validator->errors();
        }

        $content = ['code' => $code, 'message' => $message, 'result' => $result];
        return response($content, $status);
    }
}