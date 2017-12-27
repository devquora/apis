<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;
//use App\Models\Upload;
use Auth;

class UploadController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        
    }
   	
	private function clean($string) {
		   $string = strtolower(str_replace(' ', '-', $string)); // Replaces all spaces with hyphens.
		   return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
	}
	
	
	public function list(){
	 // return Wall::where(['user_id'=>Auth::user()->id])->orderBy('id','DESC')->paginate(10);
	}

	/**
     * create new users.
     *
     * @return \Illuminate\Http\Response
     */
	public function create(Request $request)
	{   
    	
		//$thumb_sizes=[['height'=>70,'width'=>70],['height'=>235,'width'=>335],['height'=>485,'width'=>730]];
	    $upload_path=rtrim(app()->basePath('public/images/'.$request->image_type));
		foreach($request->file as $image){
		if($image->getClientOriginalExtension()){
			$imageName = time().'.'.$image->getClientOriginalExtension();
		   
			   $image->move($upload_path.'/', $imageName);
			/*
			   $img = Image::make($upload_path.'/'.$imageName);
			    
				// write text at position
			    
				if($request->image_type=='articles'){	
					$img->text('DevQuora.com',150, 150, function($font) {
						$font->file(rtrim(app()->basePath('public/fonts/Oswald-Bold.ttf'))); 
						$font->size(48);
						$font->color('#00ab6b');
						$font->align('center');  
						$font->valign('bottom');  
						$font->angle(0); 
						
					});
					// draw transparent text
					
					
					$img->save($upload_path.'/'.$imageName,100);
				}*/
			/*foreach($thumb_sizes as $size){
				$thumb_name=$upload_path.'/'.$size['width'].'x'.$size['height'].$imageName;
				$image = Image::make($upload_path.'/'.$imageName)->resize($size['width'], $size['height']);
                $image->save($thumb_name, 100);
			}
			*/
		}
    	}
		 return ["msg"=>"Image uploaded Succesfully",'image_url'=>$imageName] ;
		
	}
}
