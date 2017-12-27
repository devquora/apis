<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model 
{
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
	 
    protected $fillable = [
        'user_id','location','bio','website','facebook','twitter','linkedIn','gplus','image'
	];

   
}
