<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserFollower;
use App\Models\UserWall;
use Intervention\Image\Facades\Image;
use Auth;
class UserController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        return 1;
    }
	
	
	public function getSimilarUsers($slug){
		$user =  User::with('walls')->where(['username'=>$slug])->first();
		$walls = $user->walls->modelKeys();
		return User::with(['userProfile','walls'])->whereHas('walls', function ($q) use ($walls) {
			$q->whereIn('walls.id', $walls);
		})->where('id', '<>', $user->id)->take(6)->get();
	}
	public function getUserDetails($slug){
		return	$user= User::with(['userProfile',
								 'walls'=>function($q){
									 return $q->limit(3);		 
								 },'posts'=>function($q){
									 return $q->with(['walls',])->withCount(['likes','comments'])->where(['status'=>'published'])->orderBy('id','DESC')->limit(4);
								},'questions'=>function($q){
									 return $q->with(['walls'])->where(['status'=>'published','anonymous'=>0])->orderBy('id','DESC')->limit(6);
								}])->withCount(['posts','questions','userFollowers','userFollowing'])->where(['username'=>$slug])->first();		
	}
	public function userProfile(){
		if(Auth::user()){
			return	$user= User::with(['userProfile',
								 'walls'=>function($q){
									 return $q->limit(9);		 
								 }])->withCount(['posts','questions','userFollowers','userFollowing'])->where(['id'=>Auth::user()->id])->first();	
		}
		
		return 0;
		
	}
	public function starProfile(Request $request){
		
		if(Auth::user()){
			$userProfile=UserProfile::where(['user_id'=>$request->id])->first();
			$userProfile->stars+=1;
			$userProfile->save();
			return $userProfile->stars;
		}
		
		return 0;
		
	}
	public function joinWall(Request $request){
		if(Auth::user()){
			$userWall=UserWall::where(['wall_id'=>$request->id,'user_id'=>Auth::user()->id])->first();
			if(!$userWall){
				$userWall=new UserWall;
				$userWall->wall_id=$request->id;
				$userWall->user_id=Auth::user()->id;
				$userWall->save();
					return 1;	
			}
				
			return 0;	
			
		}
		
		return 0;
	
	}
	public function follow(Request $request){
		
		if(Auth::user()){
			$userfollower=UserFollower::where(['user_id'=>$request->id,'follower_id'=>Auth::user()->id])->first();
			if(!$userfollower){
				$userfollower=new UserFollower;
				$userfollower->user_id=$request->id;
				$userfollower->follower_id=Auth::user()->id;
				$userfollower->save();
				return 1;	
			}else{
				$userfollower->delete();
			  return 0;	
			}
			
		}
		
		return 0;
		
	}
    /**
     * authenticate app users.
     *
     * @return \Illuminate\Http\Response
     */
    public function authenticate(Request $request)
    {		
		if ($request->has('email') && $request->has('password')) {
        $user=User::with(['walls','userProfile'=>function($q){return $q->select('image','bio','id','user_id');}])->where(['email'=>$request->input('email'),'password'=>sha1($request->input('password'))])->first();		  
        if ($user) {
          $token=str_random(32);
          $user->api_token=$token;
          $user->save();
           return $user;
        }else{
           return response(['Invalid Username or Password!'], 401);	
        }
      } else {
		
          return response(['Invalid Request!'], 401);				
      }
    }
    
	public function updateProfile(Request $request){
		$data = $this->validate($request, [
			'first_name' => 'required|string',  
			'last_name' => 'required|string',
				
		]);
		
		$userInfo=User::find(Auth::user()->id);
		$userInfo->first_name=trim($request->input('first_name'));
		$userInfo->last_name=trim($request->input('last_name'));
		$userInfo->save();
		
		$userProfile=UserProfile::where(['user_id'=>Auth::user()->id])->first();
		$upload_path=rtrim(app()->basePath('public/images/users'));
		$thumb_sizes=[['height'=>96,'width'=>96]];
		
		if(trim($request->input('image'))){
				$userProfile->image=$request->input('image');	 
				foreach($thumb_sizes as $size){
					$thumb_name=$upload_path.'/thumbs/'.$userProfile->image;
					$image = Image::make($upload_path.'/'.$userProfile->image)->resize($size['width'], $size['height']);
					$image->save($thumb_name, 100);
				}
				
			
		}
		$userProfile->location=trim($request->input('user_profile.location'));
		$userProfile->bio=trim(strip_tags($request->input('user_profile.bio')));
		$userProfile->website=trim($request->input('user_profile.website'));
		$userProfile->facebook=trim($request->input('user_profile.facebook'));
		$userProfile->twitter=trim($request->input('user_profile.twitter'));
		$userProfile->linkedIn=trim($request->input('user_profile.linkedIn'));
		$userProfile->gplus=trim($request->input('user_profile.gplus'));
		$userProfile->save();
		return $user=User::with(['walls','userProfile'=>function($q){return $q->select('image','bio','id','user_id');}])->where(['id'=>Auth::user()->id])->first();	
		
	}
	/**
     * create new users.
     *
     * @return \Illuminate\Http\Response
     */
	public function create(Request $request)
	{       
		$data = $this->validate($request, [
			'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
		]);
		if ($request->has('email') && $request->has('password')) {
			$user = new User;
			$user->email=$request->input('email');
			$user->password=sha1($request->input('password'));
			$user->email=$request->input('email');
			$user->api_token=str_random(32);
			if($user->save()){
				 $userProfile = new UserProfile([]);
				 $user->userProfile()->save($userProfile);
			  return ["Registration Succesfull"] ;
			}else{
				
			  return "Error in Registring user";
			  
			}
		}else{
			return "Invalid Request";
		}
	}
}
