<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Helpers\CommonConstants as CC;
use App\Helpers\ResponseHelper as RS;

class ItemController extends Controller {

    use AppTrait;
    
    public function listItem()
    {
	return 'listItem';
    }

}