<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Helpers\CommonConstants as CC;
use App\Helpers\ResponseHelper as RS;
use App\Helpers\RedisHelper as Redis;

class QrcodeController extends Controller {

    use AppTrait;

    public function generate()
    {
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
	}
	else
	{
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
	    $qrcode = explode('/', $this->__encryptdecrypt(\Swirf::input()->qrcode, true));
	    $qr_id = 0;
	    if ($qrcode[1] <> 5)
	    {
		$qr_id = \DB::table('tbl_qr_master')
			->where([['qrt_code', '=', \Swirf::input()->qrcode]])
			->orderBy('qrt_id', 'desc')
			->first();
	    }

	    //if qr_type <> qr profile then check if user already scan the qr code or not, max 1 scan in a day
	    $statement = 'select * from tbl_qr_scanned where qrn_member_id=:mem_id and qrn_qrcode=:qrcode and '
		    . '(date(from_unixtime(qrn_datetime))=curdate()) '
		    . 'order by qrn_id desc limit 1';

	    $scan = \DB::select($statement, [
			'mem_id' => $member_id,
			'qrcode' => \Swirf::input()->qrcode,
	    ]);

	    //limit the scan process only once in a day except scaning the QR Profile (qr_type=5)
	    if (count($scan) <> 0 && $qrcode[1] <> 5 && $qrcode[1] <> 3)
	    {
		//$this->results = $scan;
		$this->message = 'You are reaching the limit to scan the same QRcode!';
		$this->status = RS::HTTP_BAD_REQUEST;
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
			//grab item, check the item that player dont have
			$statement = 'SELECT geo_id, itm_id, itm_collection_id from tbl_geo_position '
				. 'INNER JOIN tbl_item on geo_item_id=itm_id '
				. 'WHERE (unix_timestamp() between itm_start_datetime and itm_end_datetime) and geo_broadcast = 1 and itm_partner_id=:partner_id and '
				. 'itm_id not in (select col_item_id from tbl_collected_item where col_member_id=:mem_id) ORDER BY RAND() LIMIT 1';
			$collected_items = \DB::select($statement, [
				    'mem_id' => $member_id,
				    'partner_id' => $qrcode[3],
			]);
			$result = $this->__grabItem($collected_items[0]->geo_id, $collected_items[0]->itm_id, $collected_items[0]->itm_collection_id, $qrcode[3], '3', $member_id, \Swirf::getLatitude(), \Swirf::getLongitude());
			if (empty($result))
			{
			    $this->status = RS::HTTP_INTERNAL_SERVER_ERROR;
			    $this->message = 'cannot grab item';
			}
			else
			{
			    $this->code = CC::RESPONSE_SUCCESS;
			    $this->results = $result;
			}
			break;

		    case '2' : //QR Product
			$this->code = CC::RESPONSE_SUCCESS;
			$this->results = $qrcode[3];
			break;

		    case '3' : //QR Outlet
			$outlet_id = $qrcode[3];
			$outlet = \DB::table('tbl_outlet')
				->join('tbl_partner', 'tbl_outlet.out_partner_id', '=', 'tbl_partner.par_id')
				->join('tbl_country', 'tbl_outlet.out_country_id', '=', 'tbl_country.cny_id')
				->join('tbl_province', 'tbl_outlet.out_province_id', '=', 'tbl_province.prv_id')
				->join('tbl_city', 'tbl_outlet.out_city_id', '=', 'tbl_city.cty_id')
				->where([['tbl_outlet.out_id', '=', $outlet_id]])
				->select('out_id as outlet_id', 'out_name as outlet_name', 'par_id as partner_id', 'par_name as partner_name', 'out_latitude as latitude', 'out_longitude as longitude', 'out_address as address', 'cty_name as city', 'prv_name as province', 'cny_name as country', 'out_postalcode as postal_code', 'out_phone as phone')
				->first();
			if (count($outlet) <> 0)
			{
			    $this->code = CC::RESPONSE_SUCCESS;
			    $this->results = $outlet;
			}
			else
			{
			    $this->message = 'Outlet not found';
			    $this->status = RS::HTTP_NOT_FOUND;
			}
			break;

		    case '4' :
			$validator_redeem = Validator::make(\Swirf::input(null, true), [
			    'pin' => 'required',
			    'reward_id' => 'required',
			]);

			if (!$validator_redeem->fails())
			{
			    $pin = \Swirf::input()->pin;
			    $reward_id = \Swirf::input()->reward_id;
			    
			    $outlet = $this->__checkPINOutlet($qrcode[3], $pin);
			    $redeemable_member = $this->__checkRedeemableItem($reward_id, $member_id);
			    
			    if(!empty($outlet) && !empty($redeemable_member))
			    {
				$reward = $this->__validRedeemTime($reward_id);
				if($reward->red_partner_id == $outlet->out_partner_id)
				{
				    if(!empty($reward))
				    {
					$redeem = $this->__redeem($reward->red_id, $redeemable_member->rmr_id, $reward->red_counter, $member_id, $outlet->out_id);
					if(!empty($redeem))
					{
					    $this->code = CC::RESPONSE_SUCCESS;
					    $this->results = $redeem;
					}
				    }
				    else
				    {
					$this->message = 'reward expired';
					$this->status = RS::HTTP_BAD_REQUEST;
				    }
				}
				else
				{
				    $this->message = 'invalid reward item';
				    $this->status = RS::HTTP_BAD_REQUEST;
				}
			    }
			    else
			    {
				$this->message = 'invalid PIN / item not valid';
				$this->status = RS::HTTP_BAD_REQUEST;
			    }
			}
			else
			{
			    $this->status = RS::HTTP_BAD_REQUEST;
			    $this->results = $validator_redeem->errors();
			    $this->message = 'Error Parameters';
			}
			break;

		    case '5' : //QR Profile
			//check account id and device id
			$device_id = [];
			$network_id = \DB::table('tbl_member')
				->where([['mem_acc_id', '=', $qrcode[3]]])
				->first();
			if (!empty($network_id) && $network_id->mem_id != $member_id)
			{
			    $device_id = \DB::table('tbl_device')
				    ->where([['dev_mem_id', '=', $network_id->mem_id], ['dev_device_id', '=', $qrcode[0]]])
				    ->first();
			    $channel = 1; //channel for scan qrcode
			    //check if already in the network
			    $check_network = \DB::table('tbl_network')
				    ->where([['net_member_id', '=', $member_id], ['net_network_id', '=', $network_id->mem_id]])
				    ->first();
			    if (count($check_network) == 0)
			    {
				if (count($network_id) <> 0 && count($device_id) <> 0)
				{
				    $result = $this->__addNetwork($member_id, $network_id->mem_id, $channel, \Swirf::getLatitude(), \Swirf::getLongitude());
				    if (!empty($result))
				    {
					$this->code = CC::RESPONSE_SUCCESS;
					$this->results = $result;
				    }
				    else
				    {
					$this->message = 'cannot add network';
					$this->status = RS::HTTP_INTERNAL_SERVER_ERROR;
				    }
				}
				else
				{
				    $this->message = 'Device id or member not found';
				    $this->status = RS::HTTP_NOT_FOUND;
				}
			    }
			    else
			    {
				$this->message = 'Player already in the address book!';
				$this->status = RS::HTTP_BAD_REQUEST;
			    }
			}
			else
			{
			    $this->message = 'Player not found';
			    $this->status = RS::HTTP_BAD_REQUEST;
			}
			//$result = $qrcode[3];
			break;

		    case '6' : //QR Buy
			$this->code = CC::RESPONSE_SUCCESS;
			$this->results = $qrcode[3];
			break;

		    case '7' : //QR Event
			$this->code = CC::RESPONSE_SUCCESS;
			$this->results = $qrcode[3];
			break;

		    default :
			$this->message = "Can't find QRCode in the database";
			$this->status = RS::HTTP_BAD_REQUEST;
			break;
		}

		if ($this->code == CC::RESPONSE_SUCCESS)
		{
		    if ($qr_id == "")
		    {
			$qrt_id = "";
		    }
		    else
		    {
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
		}

		\DB::commit();
	    } catch (\Exception $e) {
		\DB::rollBack();

		$this->status = RS::HTTP_INTERNAL_SERVER_ERROR;
		$this->message = 'Error server ' . $e;
	    }
	}
	else
	{
	    $this->status = RS::HTTP_BAD_REQUEST;
	    $this->results = $validator->errors();
	    $this->message = 'Error Parameters';
	}
	return $this->json();
    }

    private function __encryptdecrypt($string, $action = false, $secret_key = '')
    {
	$output = false;

	/* DO NOT CHANGE THIS PART */
	$encrypt_method = "AES-256-CBC";
	if ($secret_key == '')
	    $secret_key = 'SwirfistheBest88$';
	$secret_iv = 'transporter7$';
	/* DO NOT CHANGE THIS PART */

	// hash
	$key = hash('sha256', $secret_key);

	// iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
	$iv = substr(hash('sha256', $secret_iv), 0, 16);

	if ($action == true)
	{
	    $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
	}
	else
	{
	    $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
	    $output = base64_encode($output);
	}

	return $output;
    }

    private function __grabItem($geo_id, $item_id, $collection_id, $partner_id, $element_id, $member, $latitude, $longitude)
    {
	$time = time();
	$payload = [];
	try {
	    \DB::beginTransaction();
	    //0.get item point value and user's current point
	    $query = \DB::table('tbl_point_history')
		    ->where([['poi_member_id', '=', $member]])
		    ->orderBy('poi_id', 'desc')
		    ->first();
	    if (empty($query))
	    {
		$current_point = 0;
	    }
	    else
	    {
		$current_point = $query->poi_current;
	    }
	    $query2 = \DB::table('tbl_item')
		    ->where('itm_id', $item_id)
		    ->first();
	    if (empty($query2))
	    {
		$point_value = 0;
	    }
	    else
	    {
		$point_value = $query2->itm_point_value;
	    }

	    //1.update geo_position, flag the record 
	    \DB::table('tbl_geo_position')->where('geo_id', $geo_id)->update(['geo_broadcast' => '0', 'geo_counter' => '1']);

	    //2.update tbl_item add increment on itm_counter for particular item
	    \DB::table('tbl_item')->where('itm_id', $item_id)->increment('itm_counter', 1);

	    //3.check collection is exist in tbl_collected_collection for particular member,
	    $collected = \DB::table('tbl_collected_collection')
		    ->where([['coc_member_id', '=', $member], ['coc_collection_id', '=', $collection_id]])
		    ->first();

	    //4.if not exist insert new collection
	    if (count($collected) == 0)
	    {
		\DB::table('tbl_collected_collection')->insert(
			[
			    'coc_member_id' => $member,
			    'coc_collection_id' => $collection_id,
			    'coc_datetime' => $time,
			]
		);
	    }

	    //5.insert into tbl_collected_item for particular item
	    \DB::table('tbl_collected_item')->insert(
		    [
			'col_member_id' => $member,
			'col_collection_id' => $collection_id,
			'col_item_id' => $item_id,
			'col_geoposition_id' => $geo_id,
			'col_element_id' => $element_id,
			'col_partner_id' => $partner_id,
			'col_datetime' => $time,
			'col_latitude' => $latitude,
			'col_longitude' => $longitude,
		    ]
	    );

	    //6.count items under particalr collection, if count == 6 then flag the collection as completed
	    // and insert the reward to the tbl_member_redeemable
	    $redeemable_id = '';
	    $collected_items = \DB::table('tbl_collected_item')
		    ->where([['col_member_id', '=', $member], ['col_collection_id', '=', $collection_id]])
		    ->count();
	    if ($collected_items == 6)
	    {
		\DB::table('tbl_collected_collection')
			->where([['coc_member_id', '=', $member], ['coc_collection_id', '=', $collection_id]])
			->update(['coc_flag' => '1', 'coc_update_datetime' => $time, 'coc_completed_datetime' => $time]);
		//insert to member redeemable
		$redeemable_id = \DB::table('tbl_collection')
				->where('clc_id', $collection_id)
				->first()
			->clc_redeemable_id;
		\DB::table('tbl_rel_member_redeemable')->insert(
			[
			    'rmr_member_id' => $member,
			    'rmr_redeemable_id' => $redeemable_id,
			    'rmr_datetime' => $time,
			]
		);
	    }

	    //7.add point earned into tbl_point_history
	    \DB::table('tbl_point_history')->insert(
		    [
			'poi_member_id' => $member,
			'poi_datetime' => $time,
			'poi_point_type_id' => '103', //collected item
			'poi_method' => 'C',
			'poi_value' => $point_value,
			'poi_current' => $current_point + $point_value,
		    ]
	    );


	    //8.insert into tbl_collected_geoposition and delete from tbl_geo_position
	    $geo = \DB::table('tbl_geo_position')
		    ->where('geo_id', $geo_id)
		    ->first();
	    \DB::table('tbl_collected_geoposition')->insert(
		    [
			'cog_member_id' => $member,
			'cog_datetime' => $time,
			'cog_geo_id' => $geo_id,
			'cog_geo_latitude' => $geo->geo_latitude,
			'cog_geo_longitude' => $geo->geo_longitude,
			'cog_geo_name' => $geo->geo_name,
			'cog_geo_item_id' => $geo->geo_item_id,
			'cog_geo_datetime' => $geo->geo_datetime,
			'cog_geo_counter' => $geo->geo_counter,
			'cog_geo_broadcast' => $geo->geo_broadcast,
		    ]
	    );

	    \DB::table('tbl_geo_position')
		    ->where('geo_id', $geo_id)
		    ->delete();

	    //9.return completed_flag, remaining items to complete
	    $payload = [
		'point_value' => $point_value,
		'total_point' => $current_point + $point_value,
		'remaining_items' => 6 - $collected_items, //total remaining item to collect for particular collection
		'collection_id' => $collection_id,
		'item_id' => $item_id,
		'redeemable_id' => $redeemable_id,
	    ];

	    //todo : create tbl_history_geoposition : contain all geaoposition before and after grabbed

	    \DB::commit();

	    Redis::deleteCollectedItems($member);
	    Redis::deleteRewardMember($member);
	    Redis::deleteProfileCache($member);
	} catch (\Exception $e) {
	    \DB::rollBack();
	    $payload = [];
	}
	return $payload;
    }

    private function __addNetwork($member_id, $network_id, $channel, $latitude, $longitude)
    {
	try {
	    \DB::beginTransaction();
	    //add contact to invitee
	    \DB::table('tbl_network')->insert(
		    [
			'net_member_id' => $member_id,
			'net_network_id' => $network_id,
			'net_channel' => $channel,
			'net_datetime' => \DB::raw('unix_timestamp()'),
			'net_latitude' => $latitude,
			'net_longitude' => $longitude
		    ]
	    );

	    //add contact to invited
	    \DB::table('tbl_network')->insert(
		    [
			'net_member_id' => $network_id,
			'net_network_id' => $member_id,
			'net_channel' => $channel,
			'net_datetime' => \DB::raw('unix_timestamp()'),
			'net_latitude' => $latitude,
			'net_longitude' => $longitude
		    ]
	    );

	    $payload = [
		'member_id' => $member_id,
		'network_id' => $network_id,
		'channel' => $channel,
	    ];

	    \DB::commit();

	    Redis::deleteNetworkMember($member_id);
	    Redis::deleteNetworkMember($network_id);
	    
	} catch (\Exception $e) {
	    \DB::rollBack();
	    $payload = [];
	}
	return $payload;
    }
    
    private function __checkPINOutlet($outlet_id, $pin)
    {
	$statement = 'select * from tbl_outlet where out_id = :outlet_id and out_pincode = :pin limit 0,1';
	$outlet = \DB::select($statement, ['outlet_id' => $outlet_id, 'pin' => $pin]);
	
	return (count($outlet) > 0) ?  $outlet[0] : null;
    }
    
    private function __checkRedeemableItem($reward_id, $member_id)
    {
	$statement = 'select * from tbl_rel_member_redeemable where rmr_member_id = :member_id and rmr_redeemable_id = :reward_id and rmr_redeemed != 1 limit 0,1';
	
	$redeemable = \DB::select($statement, ['member_id' => $member_id, 'reward_id' => $reward_id]);
	
	return (count($redeemable) > 0) ? $redeemable[0] : null;
    }
    
    private function __validRedeemTime($reward_id)
    {
	$statement = 'select * from tbl_redeemable where red_id = :reward_id and UNIX_TIMESTAMP() between red_start_datetime and red_end_datetime limit 0,1';
	
	$valid = \DB::select($statement, ['reward_id' => $reward_id]);
	
	return (count($valid) > 0) ? $valid[0] : null;
    }
    
    private function __redeem($reward_id, $member_redeemable_id, $previous_counter, $member_id, $outlet_id)
    {
	$counter = (int) $previous_counter + 1;
	try{
	    \DB::beginTransaction();
	    
	    $statement_1 = 'update tbl_rel_member_redeemable set'
		    . ' rmr_redeemed = 1, '
		    . ' rmr_redeemed_outlet_id = :outlet_id, '
		    . ' rmr_redeemed_datetime = UNIX_TIMESTAMP() '
		    . ' where rmr_id = :member_redeem_id';
	    
	    \DB::update($statement_1, ['member_redeem_id' => $member_redeemable_id, 'outlet_id' => $outlet_id]);
	    
	    $statement_2 = 'update tbl_redeemable set'
		    . ' red_counter = :counter '
		    . ' where red_id = :reward_id';
	    
	    \DB::update($statement_2, ['counter' => $counter, 'reward_id' => $reward_id]);
	    
	    $payload = [
		'reward_id' => $reward_id,
	    ];
	    
	    \DB::commit();
	    
	    Redis::deleteRewardMember($member_id);
	    
	} catch (Exception $ex) {
	    \DB::rollBack();
	    $payload = [];
	}
	
	return $payload;
    }

}
