<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model 
{
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'title','slug'
	];
	
	public function posts(){
		
		return $this->belongsToMany('App\Models\Post')->withTimestamps();;
	}
   
}
