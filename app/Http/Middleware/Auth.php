<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Response;

class Auth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */

    public function handle($request, Closure $next)
    {
        $return  = $this->validate($request->header());

        if ($return['code'] == 1)
        {
            $request->merge(
                [
                    'mem_id' => $return['mem_id']
                ]);

            return $next($request);
        }else{
            return (new Response([
                'code'      => $return['code'],
                'message'   => $return['message'],
                'result'    => $return['result'],
            ], 400))->header('Content-Type', 'application/json');
        }
    }

    private function validate($headers)
    {
        $result = [
            'code'    => 1,
            'message' => '',
            'result'  => [],
            'mem_id'  => 0,
        ];

        $required = [
                'token'   => 'Token is required',
                'lang'    => 'Language is required',
                'os'      => 'OS is required',
                'app-ver' => 'App. Version is required',
                'api-ver' => 'Api Version is required'
        ];

        foreach ($required as $key => $message)
        {
            if (!array_key_exists($key, $headers)) {
                $result['code']          = 0;
                $result['message']       = 'Error parameters';
                $result['result'][$key]  = [$message];
            } else {
                if (!isset($headers[$key]) || $headers[$key] == '') {
                    $result['code']         = 0;
                    $result['message']      = 'Error parameters';
                    $result['result'][$key] = [$message];
                }
            }
        }

        if($result['code'] == 1)
        {
            try {
                // CHECK TOKEN
                $app1 = \DB::table('tbl_member')->where('mem_token', $headers['token'][0])->first();

                if($app1 == null) {
                    $result['message'] = 'Token is no valid';
                    throw new \Exception();
                }

                // CHECK APP
                $app2 = \DB::table('tbl_support')->where([
                    ['sup_app_os',      '=', $headers['os'][0]],
                    ['sup_app_version', '=', $headers['app-ver'][0]],
                    ['sup_api_version', '=', $headers['api-ver'][0]]
                ])->first();

                if($app2->sup_valid != 1) {
                    $result['message'] = 'This App is Unsupported. Please Update with newer app';
                    throw new \Exception();
                }

                $result['mem_id'] = $app1->mem_id;

            } catch(\Exception $e) {
                $result['status'] = 0;
            }

        }

        return $result;
    }
}
