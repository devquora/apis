<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Post extends Model 
{
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title', 'content','excerpt','meta_title','meta_description','slug','status','user_id','type','anonymous'
	];
	public function user(){
		return $this->belongsTo('App\Models\User', 'user_id');
	}
	public function likes(){
		return $this->hasMany('App\Models\PostLike', 'post_id')->where(['action'=>'like','type'=>'post']);
	}
	public function comments(){
		return $this->hasMany('App\Models\Comment', 'post_id');
	}
	public function dislikes(){
		return $this->hasMany('App\Models\PostLike', 'post_id')->where(['action'=>'dislike','type'=>'post']);
	}
	
	public function userProfile(){
		return $this->belongsTo('App\Models\UserProfile', 'user_id','user_id');
	}
    public function walls(){
		return $this->belongsToMany('App\Models\Wall') ->withTimestamps();;
	}
	public function tags(){
		return $this->belongsToMany('App\Models\Tag') ->withTimestamps();;
	}
}
