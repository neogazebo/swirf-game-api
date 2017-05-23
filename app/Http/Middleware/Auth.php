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

        if ($return['status'] == 1)
        {
            return $next($request);
        }else{
            return (new Response([
                'code'      => 0,
                'message'   => 'Error parameters',
                'result'    => $return['result'],
            ], 400))->header('Content-Type', 'application/json');
        }
    }

    private function validate($headers)
    {
        $result = [
            'status'  => 1,
            'result'  => [],
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
                $result['status']        = 0;
                $result['result'][$key]  = [$message];
            } else {
                if (!isset($headers[$key]) || $headers[$key] == '') {
                    $result['status']       = 0;
                    $result['result'][$key] = [$message];
                }
            }
        }

        if($result['status'] == 1)
        {
            try {
                // CHECK TOKEN
                $app1 = \DB::table('tbl_member')->where('mem_token', $headers['token'][0])->first();

                if($app1 == null) {
                    $result['result']['token']  = ['Token not valid'];
                    throw new \Exception();
                }

                // CHECK APP
                $app2 = \DB::table('tbl_support')->where()->first();

            } catch(\Exception $e) {
                $result['status'] = 0;
            }

        }

        return $result;
    }
}
