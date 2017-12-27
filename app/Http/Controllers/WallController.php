<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;
use Intervention\Image\Facades\Font;
use App\Models\Wall;
use App\Models\User;
use Auth;
class WallController extends Controller
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
	protected function getRelatedSlugs($slug, $id = 0)
    {
        return Wall::select('slug')->where('slug', 'like', $slug.'%')
            ->where('id', '<>', $id)
            ->get();
    }
	public function edit($slug){
		
		return Wall::where(['user_id'=>Auth::user()->id,'slug'=>$slug])->first();
	}
	
	public function list(){
	  return Wall::withCount(['posts'=>function($q){
							$q->where(['status'=>'published','type'=>'article']);
						},'questions'=>function($q){
							$q->where(['status'=>'published','type'=>'question']);
						},'users'])->where(['user_id'=>Auth::user()->id])->orderBy('id','DESC')->paginate(10);
	}
	
	public function searchWalls($key){
	     return Wall::where('title', 'like', '%'.$key.'%')
				->get(['id as value','title as display']);
	}
	public function getPopularWalls(){
		return	Wall::withCount(['posts'=>function($q){
							$q->where(['status'=>'published','type'=>'article']);
						},'users'])->where(['status'=>'published'])->orderBy('posts_count', 'desc')->take(8)->get();
			
	}
	public function getAllWalls(){
		return	Wall::withCount(['posts'=>function($q){
							$q->where(['status'=>'published','type'=>'article']);
						},'users'])->where(['status'=>'published'])->orderBy('posts_count', 'desc')->paginate(16);
			
	}
	public function getWalls(){
		return	Wall::withCount(['posts'=>function($q){
							$q->where(['status'=>'published','type'=>'article']);
						}])->where(['status'=>'published'])->orderBy('posts_count', 'desc')->take(16)->get();
			
	}
	public function topContributers($slug){
		
		if($slug!='home'){
		$wall =  Wall::with('posts')->where(['slug'=>$slug])->first();
		if(!$wall){
			 return response()->json(['msg'=>'Sorry,Page you are looking for does not exist'], 404);
		}
		$posts = $wall->posts->modelKeys();
	
		return $wall =  Wall::with(['users'=>function($q)use($posts){
			$q->with(['walls','userProfile'])->withCount(['posts'=>function($q){
							$q->where(['status'=>'published','type'=>'article']);
						},'questions'=>function($q){							
							$q->where(['status'=>'published','type'=>'question']);
						},'userposts'=>function($query)use ($posts){
				$query->whereIn('id', $posts);
							
			}])->orderBy('userposts_count', 'desc')->take(6);
			
		}])->where(['slug'=>$slug])->first();
	 
	 	}else{
		 return $users=	User::with(['userProfile'])->withCount(['posts'=>function($q){
							$q->where(['status'=>'published','type'=>'article']);
						}])->where(['status'=>1])->orderBy('posts_count', 'desc')->take(12)->get();
						
					}
	}
	public function getWallDetails($slug){
		$wall =  Wall::where(['slug'=>$slug])->first();
		if(!$wall){
			 return response()->json(['msg'=>'Sorry,Page you are looking for does not exist'], 404);
		}
		return	Wall::withCount(['posts'=>function($q){
							$q->where(['status'=>'published','type'=>'article']);
						},'users','questions'=>function($q){							
							$q->where(['status'=>'published','type'=>'question']);
						}])->where(['slug'=>$slug])->first();
		
	}
	/**
     * update wall.
     *
     * @return \Illuminate\Http\Response
     */
	public function update(Request $request,$id)
	{       
		$data = $this->validate($request, [
			'title' => 'required',
            'content' => 'required',
     	]);
		$thumb_sizes=[['height'=>70,'width'=>70]];
		$upload_path=rtrim(app()->basePath('public/images/walls'));
		if ($request->has('title') && $request->has('content')) {
			
			$wall = Wall::find($id);
			$wall->title=$request->input('title');
			$wall->user_id=Auth::user()->id;
			if(trim($request->input('image'))){
				$wall->image=$request->input('image');	 
				foreach($thumb_sizes as $size){
						$thumb_name=$upload_path.'/thumbs/'.$size['width'].'x'.$size['height'].$wall->image;
						$image = Image::make($upload_path.'/'.$wall->image)->resize($size['width'], $size['height']);
						$image->save($thumb_name, 100);
				}							
			}
			$wall->content=$request->input('content');
			if(trim($request->input('slug'))){
				$wall->slug=$this->clean(trim($request->input('slug')));
			}else{
			    $wall->slug=$this->clean(trim($request->input('title')));
			
			}
			if(trim($request->input('excerpt'))){
				$wall->excerpt=substr(strip_tags($request->input('excerpt')), 0, 100);
			}else{
			    $wall->excerpt=substr(strip_tags($request->input('content')), 0, 100);
			
			}
			
			$allSlugs =$this->getRelatedSlugs($wall->slug,$id);
			
			// If we haven't used it before then we are all good.
			if ($allSlugs->contains('slug', $wall->slug)){
				for ($i = 1; $i <= 100; $i++) {
					$newSlug = $wall->slug.'-'.$i;
					if (!$allSlugs->contains('slug', $newSlug)) {
						$wall->slug= $newSlug;
						break;
					}
			     }
			}
			// Just append numbers like a savage until we find not used.
			
			if(trim($request->input('meta_title'))){
     			$wall->meta_title=$request->input('meta_title');
			}else{
				$wall->meta_title=substr($request->input('title'), 0, 69);
			}
			if(trim($request->input('meta_title'))){
     			$wall->meta_description=$request->input('meta_description');
			}else{
				$wall->meta_description=substr($request->input('meta_description'), 0, 159);
			}
			;
			$wall->status=$request->input('status');
			if($wall->save()){
				
			  return ["Wall Updated Succesfull"] ;
			}else{
			  return ["Error in Updating Wall"];
			}
		}else{
			return ["Invalid Request"];
		}
	}
	/**
     * create new users.
     *
     * @return \Illuminate\Http\Response
     */
	public function create(Request $request)
	{       
		$data = $this->validate($request, [
			'title' => 'required',
            'content' => 'required',
     	]);
		$thumb_sizes=[['height'=>70,'width'=>70]];
		$upload_path=rtrim(app()->basePath('public/images/walls'));
		if ($request->has('title') && $request->has('content')) {
			$wall = new Wall;
			$wall->title=$request->input('title');
			$wall->user_id=Auth::user()->id;
			$wall->content=$request->input('content');
			if(trim($request->input('image'))){
				$wall->image=$request->input('image');	 
				foreach($thumb_sizes as $size){
					$thumb_name=$upload_path.'/thumbs/'.$size['width'].'x'.$size['height'].$wall->image;
					$image = Image::make($upload_path.'/'.$wall->image)->resize($size['width'], $size['height']);
					$image->save($thumb_name, 100);
				}
				
			
			}
			if(trim($request->input('slug'))){
				$wall->slug=$this->clean(trim($request->input('slug')));
			}else{
			    $wall->slug=$this->clean(trim($request->input('title')));
			
			}
			if(trim($request->input('excerpt'))){
				$wall->excerpt=substr(strip_tags($request->input('excerpt')), 0, 100);
			}else{
			    $wall->excerpt=substr(strip_tags($request->input('content')), 0, 100);
			
			}
			
			$allSlugs =$this->getRelatedSlugs($wall->slug,0);
			
			// If we haven't used it before then we are all good.
			if ($allSlugs->contains('slug', $wall->slug)){
				for ($i = 1; $i <= 100; $i++) {
					$newSlug = $wall->slug.'-'.$i;
					if (!$allSlugs->contains('slug', $newSlug)) {
						$wall->slug= $newSlug;
						break;
					}
			     }
			}
			// Just append numbers like a savage until we find not used.
			
			if(trim($request->input('meta_title'))){
     			$wall->meta_title=$request->input('meta_title');
			}else{
				$wall->meta_title=substr($request->input('title'), 0, 69);
			}
			if(trim($request->input('meta_title'))){
     			$wall->meta_description=$request->input('meta_description');
			}else{
				$wall->meta_description=substr($request->input('content'), 0, 159);
			}
			;
			$wall->status=$request->input('status');
			if($wall->save()){
				
			  return ["Wall Created Succesfull"] ;
			}else{
			  return ["Error in Creating New Wall"];
			}
		}else{
			return ["Invalid Request"];
		}
	}
}
