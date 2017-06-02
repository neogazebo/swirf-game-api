<?php

namespace App\Http\Middleware;
use App\Helpers\ResponseHelper as RH;
use Illuminate\Support\Facades\Validator;

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
	    
	$validator = Validator::make($headers, [
	    'latitude' => 'required',
	    'longitude' => 'required',
	    'os' => 'required',
	    'application-version' => 'required',
	    'api-version' => 'required'
	]);
	
	if($validator->fails())
	{
	    $result['success'] = false;
	    $result['message'] = $validator->errors();
	}

	return array2object($result);
    }

}
