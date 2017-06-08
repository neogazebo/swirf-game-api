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
			$qr_id=0;
			if ($qrcode[1]<>5) {
				$qr_id = \DB::table('tbl_qr_master')
					->where([['qrt_code','=', \Swirf::input()->qrcode]])
					->orderBy('qrt_id', 'desc')
					->first();
			}

			//if qr_type <> qr profile then check if user already scan the qr code or not, max 1 scan in a day
			$statement = 'select * from tbl_qr_scanned where qrn_member_id=:mem_id and qrn_qrcode=:qrcode and '
					.'(date(from_unixtime(qrn_datetime))=curdate()) '
					.'order by qrn_id desc limit 1';
	    
			$scan = \DB::select($statement, [
			'mem_id' => $member_id,
			'qrcode' => \Swirf::input()->qrcode,
			]);
			
			//limit the scan process only once in a day except scaning the QR Profile (qr_type=5)
			if (count($scan)<>0) {
				//$this->results = $scan;
				$this->message = 'You are reaching the limit to scan the same QRcode!';
				$this->code = CC::RESPONSE_SUCCESS;
				return $this->json();
				exit();
			}
			/* QR Mode
			1	Play AR	triggers an augmented reality animation
			2	Win Carousel	triggers the prize caroussel
			3	Gain Points	add points
			4	Get Item	to redeem 
			5	Trigger Mission	starts a mission
			6	Get Redeemable	NULL
			7	Show Informatoin	NULL
			*/
			try {
				\DB::beginTransaction();
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
						//check account id and device id
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
				
				if ($qr_id=="") {
					$qrt_id = "";
				} else {
					$qrt_id = $qr_id->qrt_id;
				}
				//insert into tbl_qr_scanned once the proses done
				\DB::table('tbl_qr_scanned')->insert(
					[
					'qrn_member_id' => $member_id,
					'qrn_qrmaster_id' => $qrt_id,
					'qrn_qrcode' => \Swirf::input()->qrcode,
					'qrn_datetime' => time(),
					]
				);

				$this->results = $result;
				$this->message = 'QRCode successfuly scanned!';
				$this->code = CC::RESPONSE_SUCCESS;

				\DB::commit();
			} catch (\Exception $e) {
				\DB::rollBack();

				$this->status = RS::HTTP_INTERNAL_SERVER_ERROR;
				$this->message = 'Error server ' . $e;
			}
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