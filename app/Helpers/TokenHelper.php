<?php

/**
 * Description of TokenHelper
 *
 * @author neogazebo
 */

namespace App\Helpers;

use Lcobucci\JWT\Parser;
use Lcobucci\JWT\ValidationData;
use Lcobucci\JWT\Signer\Hmac\Sha256;

class TokenHelper {

    public static function parse($token)
    {
	$result = [
	    'data' => null,
	    'valid' => false
	];
	
	$token = (new Parser())->parse((string) $token);
	if ($token != null)
	{
	    $data = new ValidationData();
	    
	    $data->setIssuer(env('JWT_TOKEN_ISSUER'));
	    $data->setAudience(env('JWT_TOKEN_AUDIENCE'));
	    $data->setId(env('JWT_TOKEN_ID'));
	    if($token->validate($data))
//	    if ($token->verify(new Sha256(), env('JWT_TOKEN_SECRET'))) 
	    {
		$data = $token->getClaim('data');
		$result['data'] = $data;
		$result['valid'] = true;
	    }
	}
	
	return array2object($result);
    }
    
    

}
