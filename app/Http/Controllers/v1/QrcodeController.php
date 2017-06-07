<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Helpers\CommonConstants as CC;
use App\Helpers\ResponseHelper as RS;

class QrcodeController extends Controller {

    use AppTrait;

	public function generate() {
		$validator = Validator::make(\Swirf::input(null, true), [
			'qrcode' => 'required',
		]);

		if (!$validator->fails())
		{
			$qrcode = $this->__encryptdecrypt(\Swirf::input()->qrcode);
			//$qrcode = $hash .' || '.$this->__encryptdecrypt($hash, true);
			//uuid/qr_type/qr_mode/key

			$this->results = $qrcode;
			$this->message = 'QRCode successfuly generated!';
			$this->code = CC::RESPONSE_SUCCESS;
		} else {
			$this->status = RS::HTTP_BAD_REQUEST;
			$this->results = $validator->errors();
			$this->message = 'Error Parameters';
		}
		return $this->json();
	}
    
    public function scan()
    {
		$validator = Validator::make(\Swirf::input(null, true), [
			'qrcode' => 'required',
		]);
	
		if (!$validator->fails())
		{
			$member_id = \Swirf::getMember()->mem_id;
			$qrcode = explode('/',$this->__encryptdecrypt(\Swirf::input()->qrcode, true));
			/* QR Mode
			1	Play AR	triggers an augmented reality animation
			2	Win Carousel	triggers the prize caroussel
			3	Gain Points	add points
			4	Get Item	to redeem 
			5	Trigger Mission	starts a mission
			6	Get Redeemable	NULL
			7	Show Informatoin	NULL
			*/
			switch ($qrcode[1]) {
				case '1' : //QR Poster
					$result = $qrcode[3];
					break;
				case '2' : //QR Product
					$result = $qrcode[3];
					break;
				case '3' : //QR Outlet
					$result = $qrcode[3];
					break;
				case '4' : //QR Redeem type 1
					$result = $qrcode[3];
					break;
				case '5' : //QR Profile
					$result = $qrcode[3];
					break;
				case '6' : //QR Buy
					$result = $qrcode[3];
					break;
				case '7' : //QR Event
					$result = $qrcode[3];
					break;
				default : 
					$result = "Can't find QRCode in the database";
					break;
			}

			$this->results = $result;
			$this->message = 'QRCode successfuly scanned!';
			$this->code = CC::RESPONSE_SUCCESS;
		} else {
			$this->status = RS::HTTP_BAD_REQUEST;
			$this->results = $validator->errors();
			$this->message = 'Error Parameters';
		}
		
		return $this->json();
    }

	private function __encryptdecrypt($string,$action=false,$secret_key ='') {
		$output = false;

		/* DO NOT CHANGE THIS PART */
		$encrypt_method = "AES-256-CBC";
		if($secret_key =='')
			$secret_key = 'SwirfistheBest88$';
		$secret_iv = 'transporter7$'; 
		/* DO NOT CHANGE THIS PART */

		// hash
		$key = hash('sha256', $secret_key);
		
		// iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
		$iv = substr(hash('sha256', $secret_iv), 0, 16);

		if( $action == true ) {
			$output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
		}
		else{
			$output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
			$output = base64_encode($output);
		}

		return $output;
	}
}