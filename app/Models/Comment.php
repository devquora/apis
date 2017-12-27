<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model 
{
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'content','type','post_id'
	];
	 // this is a recommended way to declare event handlers
    protected static function boot() {
        parent::boot();

        static::deleting(function($comment) { // before delete() method call this
             $comment->commentActivities()->delete();
             // do the rest of the cleanup...
        });
    }
	public function commentActivities()
    {
        return $this->hasMany('App\Models\PostLike','post_id','id')->where(['type'=>'comment']);
    }
	public function user(){
		return $this->belongsTo('App\Models\User', 'user_id');
	}
	public function likes(){
		return $this->hasMany('App\Models\PostLike', 'post_id','id')->where(['action'=>'like','type'=>'comment']);
	}
	public function dislikes(){
		return $this->hasMany('App\Models\PostLike', 'post_id')->where(['action'=>'dislike','type'=>'comment']);
	}
   
}
