<?php
namespace App\Models;
use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name','last_name', 'email',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password','remember_token'
    ];
	
	public function userProfile(){
		return $this->hasOne('App\Models\UserProfile');
    }
	
	public function posts(){
		return $this->hasMany('App\Models\Post', 'user_id')->where(['type'=>'article']);
    }
	public function questions(){
		return $this->hasMany('App\Models\Post', 'user_id')->where(['type'=>'question']);
    }
	public function followers(){
		return $this->belongsToMany('App\Models\User')->withTimestamps();;
	}
	
	public function userFollowers(){
		return $this->hasMany('App\Models\Userfollower','user_id','id');
	}
	
	public function userFollowing(){
		return $this->hasMany('App\Models\Userfollower','follower_id','id');
	}
	public function userposts(){
		return $this->hasMany('App\Models\Post', 'user_id');
    }
	public function walls(){
		return $this->belongsToMany('App\Models\Wall') ->withTimestamps();;
	}
	
}
