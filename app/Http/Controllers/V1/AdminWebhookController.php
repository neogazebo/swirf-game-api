<?php


namespace App\Http\Controllers\V1;

/**
 * Description of AdminWebhookController
 *
 * @author neogazebo
 */

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Helpers\RedisHelper as Redis;
use App\Helpers\CommonConstants as CC;

class AdminWebhookController  extends Controller{
    
    use AppTrait;
    
    public function profile(Request $request)
    {
	$validator = Validator::make(\Swirf::input(null, true), [
            'member_id' => 'required|integer'
        ]);

	$id = \Swirf::input()->member_id;
	
	Redis::deleteProfileCache($id);
	
	$this->code = CC::RESPONSE_SUCCESS;
	return $this->json();
    }
}
