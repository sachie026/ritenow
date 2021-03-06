<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Otp;
use App\User;
use App\Profile;
use App\Connectrequest;
use App\Connection;
use Hash;

use App\Helpers\RiteNowGlobal;

class UserController extends Controller
{
    //
	
	public function createNewUserProfile($fbid, $name, $picture){
		try{
    		$present = Profile::where('fbid', $fbid)->count() == 1 ? true : false;
    		if(!$present){
		        $profile = new Profile;
		        $profile->name = $name;
		        $profile->fbid = $fbid;
		        $profile->pic = $picture;		        
		        $saved = $profile->save();
//		        return $saved ? 1 : 0;    			
    		}
//    		return 2;
    	}
    	catch(Exception $ex){
    		return -1;
    	}		
	}
	
	public function getUsersForSearchText(Request $request){
		try{
			$searchedString = isset($request->uname) ? $request->uname : null;
			$fbid = isset($request->fbid) ? $request->fbid : null;
			$token = isset($request->token) ? $request->token : null;
//			$fbid = "9970016888";
			
			if($fbid == null || $searchedString == null)
				return 5;
			
			if(!RiteNowGlobal::isValidToken($fbid, $token))
				return 401;	// unauthorized or invalid token
			
				//$searchedString = $request->uname;
			$searchValues = preg_split('/\s+/', $searchedString, -1, PREG_SPLIT_NO_EMPTY); 
 
			$users = User::where(function ($q) use ($searchValues) {
			  foreach ($searchValues as $value) {
				$q->orWhere('users.name', 'like', "%{$value}%");
			  }
			})
			->join("profiles", 'users.fbid', '=','profiles.fbid')
			->select('users.fbid', 'users.name', 'profiles.pic', 'profiles.lives_in', 'profiles.from_address' )
			->get();

			$connections = Connection::where('fbid', $fbid)->get();
			$connectedUsers = [];
			//return $users;
			foreach ($users as $user) {
				if($fbid != $user->fbid){
					$checkUser =  strpos($connections[0]->connections, '->'.$user->fbid.'->');
					if ($checkUser == false ) {
						if((string)$checkUser == '0'){
						}
						else{
							array_push($connectedUsers, $user);
						}
					}	
				}
			  }

			
			return $connectedUsers;	
		}
		catch(Exception $ex){
			return -1;
		}

	}
	
	public function createNewConnectionEntry($fbid){
		try{
			$present = Connection::where('fbid', $fbid)->count() == 1 ? true : false;
    		if(!$present){
		        $connection = new Connection;
		        $connection->fbid = $fbid;
		        $saved = $connection->save();
			}
		}
		catch(Exception $ex){
			return -1;
		}
	}

	public function deleteUser(Request $request){
		$fbid = isset($request->fbid) ? $request->fbid : null;
		$token = isset($request->token) ? $request->token : null;
		
		if($fbid == null || $userid == null)
			return 5;
		
		if(!RiteNowGlobal::isValidToken($fbid, $token))
			return 401;	// unauthorized or invalid token

	}
	
	public function checkAndAddNewUser(Request $request){
		try{
		//	return $request;
			$fbid = isset($request->fbid) ? $request->fbid : null;
			if(!$fbid)
				return 3;


			$name = isset($request->name) ? $request->name : null;
			
			$email = isset($request->email) ? $request->email : null;
			$picture = isset($request->picture) ? $request->picture : null;
			$token = isset($request->token) ? $request->token : null;

			
		/*	$fbid = "321";
			$name = "Sachie";
			$email = "jadhavsachin321@gmail.com";
			$picture = "asdf.jpg";
			$token = "s321";
			*/
			if($token == null || $name == null || $email == null)
				return 5; // provide valid token / name / email
			
	//		if(!RiteNowGlobal::isValidToken($fbid, $token))
	//			return 401;	// unauthorized or invalid token
			
			
    		$present = User::where('fbid', $fbid)->count() == 1 ? true : false;
    		if(!$present){
		        $User = new User;
		        $User->name = $name;
		        $User->fbid = $fbid;
		        $User->emailid = $email;	
				$User->remember_token = $token;	
		        $saved = $User->save();
				if($saved == 1){
					$this->createNewUserProfile($fbid, $name, $picture);
					$this->createNewConnectionEntry($fbid);
				}	
		        return $saved ? 1 : 0;    			
    		}
    		return 2;
    	}
    	catch(Exception $ex){
    		return -1;
    	}    
	}
	
	public function signinUser(){
		try{
			$mbl = '9970016888';
			$pswd = 'secret';
			$token = 'token';
			
			$userPresent = User::where('mbl', $mbl)->get();
			if($userPresent->count() <= 0)		// user not present
				return 2;
 
			$hashedPassword = Hash::make($userPresent[0]->password);
	
			if (Hash::check($pswd, $hashedPassword))
			{
				$user = User::find('id', $userPresent[0]->id);
				$user->remember_token = $token;				
				$saved = $user->save();
				if($saved)
					return 1;
				else
					return 0;	
			}
			else{
				return 3;
			}
		}
		catch(Exception $ex){
			return -1;
		}
	}
	
	
	
	
	public function signOutUser(){
		try{
			$mbl = '9970016888';
			$token = 'token';
			$userPresent = User::where('mbl', $mbl)->get();
		
			if($userPresent->count() > 0)
			{
				$user = User::find('id', $userPresent[0]->id);
				$user->remember_token = NULL;				
				$saved = $user->save();

				if($saved)
					return 1;
				else
					return 0;	

			}
			else{
				return 2;
			}
		}
		catch(Exception $ex){
			return -1;
		}
	}
	

	public function sendOTP(Request $request){
		try{
			$mbl = '9970016888';
			$digits = '1234';

			$userPresent = User::where('mbl', $mbl)->count() == 1 ? true : false;
			if($userPresent)
				return 2;

			$row = Otp::where('mbl', $mbl)->get();
			$present = $row->count() == 1 ? true : false;	

			if($present){
				$otpRow = Otp::find($row[0]->id)	;
				$otpRow->delete();
			}
			$otp = new Otp;
			$otp->mbl = $mbl;
			$otp->digits = $digits;
			$otp->expires_at = date("Y-m-d H:i:s", time() + 30);
			
			$saved = $otp->save();
			return $saved ? $digits : 0;
		}
		catch(Exception $ex){
			return -1;
		}				
	}

	
	public function verifyOTP(){
		try{
			$mbl = '9970016888';
			$digits = '1234';
			
			$userPresent = User::where('mbl', $mbl)->count() == 1 ? true : false;
			if($userPresent)
				return 2;
			
			$row = Otp::where('mbl', $mbl)->where('expires_at', '>', date("Y-m-d H:i:s"))->get();
			$present = $row->count() == 1 ? true : false;	
			
			if($present){
				if($row[0]->digits === $digits)
					return 1;
				else
					0;
			}
			return 0;
		}
		catch(Exception $ex){
			return -1;
		}				
	}


	public function postAddFCMToken(Request $request)
	{
		try{

			$fbid = isset($request->fbid) ? $request->fbid : null;
			$token = isset($request->token) ? $request->token : null;
			
			if($fbid == null)
				return 5;
			
			if(!RiteNowGlobal::isValidToken($fbid, $token))
				return 401;	// unauthorized or invalid token
			
			$recordRow = user::where('fbid',$fbid)->get();
			
			if($recordRow->count() <= 0)
				return 2;

			$fcmtoken = isset($request->fcmtoken) ? $request->fcmtoken : null;

			$record = User::find($recordRow[0]->id);	
			$record->fcm_token = $fcmtoken;
			$saved = $record->save();

			return $saved ? 1 : 0;    
		}
		catch(Exception $ex){
			return -1;
		}
	}
/*
	public function isValidToken($fbid, $userToken){
		$userData = User::where('fbid', $fbid)->get();
		if($userToken != $userData[0]->remember_token)
			return false;
		else
			return true;		
	}	

*/	
}
