<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Wall extends Model 
{
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title', 'content','excerpt','meta_title','meta_description','slug','status','user_id'
	];
	
	public function posts(){
		
		return $this->belongsToMany('App\Models\Post')->withTimestamps();;
	}
	public function questions(){
		
		return $this->belongsToMany('App\Models\Post')->withTimestamps();;
	}
	public function users(){
		
		return $this->belongsToMany('App\Models\User')->withTimestamps();;

	}
   
}
