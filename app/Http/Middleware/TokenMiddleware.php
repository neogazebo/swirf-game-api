<?php


namespace App\Http\Middleware;

use App\Helpers\ResponseHelper as RH;
use Illuminate\Support\Facades\Validator;
use App\Helpers\CommonConstants as CC;

class TokenMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
	$checkHeader = $this->checkTokenHeader($request->header());
	
	if (!($checkHeader->success)) {
	    return RH::errorResponse(RH::HTTP_BAD_REQUEST, $checkHeader->message);
	}
	
        $valid = \Swirf::validToken();
	
        if ($valid->code() == CC::RESPONSE_FAILED ) {
	    return RH::errorResponse($valid->status(), $valid->message());
        }
	

        return $next($request);
    }
    
    private function checkTokenHeader($headers)
    {
	$result = [
	    'success' => true,
	    'message' => null,
	];
	    
	$validator = Validator::make($headers, [
	    'token' => 'required',
	]);
	
	if($validator->fails())
	{
	    $result['success'] = false;
	    $result['message'] = $validator->errors();
	}

	return array2object($result);
    }
}
