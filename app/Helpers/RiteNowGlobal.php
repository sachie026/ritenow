<?php

namespace App\Helpers;


use App\Connection;
use App\Connectrequest;
use App\User;

use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;
use FCM;

class RiteNowGlobal {


    public static function isValidToken($fbid, $userToken)
    {

        $userData = User::where('fbid', $fbid)->get();
		if($userToken != $userData[0]->remember_token)
			return false;
		else
			return true;

	}

	
	
	public static function getCheckIfConnected($toid, $fromid){
		try {
			$connections = Connection::find($fromid);
			$ids = $connections->connections;
						

			$requested = Connectrequest::where('fbid', $toid)->where('from', $fromid)->count();
			$accept = Connectrequest::where('fbid', $fromid)->where('from', $toid)->count();
		
			//$secondUserRequests = Connectrequest::where('fbid', $fromid)->get();

			$ids_array = explode("->",$ids);
		
			if(in_array($toid, $ids_array)){
				return 0; //"Connected";
			}
			
			if($requested > 0)
			{
				//return "Requested";
				return 2; //"Requested";
			}
			
			if($accept > 0){
				return 1; // "Accept";
			}
			else{
				return 3; //"send Connect Request";
			}			
		}
		catch(Exception $ex){
			return -1;
		}
	}

	public static function sendNotificationToDevice( $tokens, $msg){

		try{
			$optionBuilder = new OptionsBuilder();
			$optionBuilder->setTimeToLive(60*20);
	
			$notificationBuilder = new PayloadNotificationBuilder('Ritenow');
			$notificationBuilder->setBody($msg)
								->setSound('default');
	
			$dataBuilder = new PayloadDataBuilder();
			$dataBuilder->addData(['a_data' => ""]);
	
			$dataBuilder->addData(['content-available' => "1"]);
	
			$option = $optionBuilder->build();
			$notification = $notificationBuilder->build();
			$data = $dataBuilder->build();
	
			$downstreamResponse = FCM::sendTo($tokens, $option, $notification, $data);
	
			$downstreamResponse->numberSuccess();
			$downstreamResponse->numberFailure();
			$downstreamResponse->numberModification();
	
			//return Array - you must remove all this tokens in your database
			$downstreamResponse->tokensToDelete();
	
			//return Array (key : oldToken, value : new token - you must change the token in your database )
			$downstreamResponse->tokensToModify();
	
			//return Array - you should try to resend the message to the tokens in the array
			$downstreamResponse->tokensToRetry();
	
		}
		catch(Exception $e){

		}

		// return Array (key:token, value:errror) - in production you should remove from your database the tokens
	}


	
}