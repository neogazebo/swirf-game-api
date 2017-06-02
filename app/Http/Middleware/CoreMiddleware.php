<?php

namespace App\Http\Middleware;
use App\Helpers\ResponseHelper as RH;

use Closure;

class CoreMiddleware {

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
	if (!$request->is('v1/webhook/*'))
	{
	    $checkHeader = $this->checkHeader($request->header());
	    if (!($checkHeader->success))
	    {
		return RH::errorResponse(RH::HTTP_BAD_REQUEST, $checkHeader->message);
	    }
	}
	\Swirf::processHeader($request->header());
	\Swirf::processInput($request->getContent());

	return $next($request);
    }

    private function checkHeader($headers)
    {
	$result = [
	    'success' => true,
	    'message' => null,
	];
	$required = [
	    'latitude' => 'latitude is required',
	    'longitude' => 'longitude is required',
	    'os' => 'os is required',
	    'appVer' => 'application Version is required',
	    'apiVer' => 'api Version is required'
	];
	foreach ($required as $key => $message) {
	    if (!(array_key_exists($key, $headers)))
	    {
		$result['success'] = false;
		$result['message'] = $message;
	    } else
	    {
		if (!(isset($headers[$key])))
		{
		    $result['success'] = false;
		    $result['message'] = $message;
		}
	    }
	}

	return array2object($result);
    }

}
