<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use App\Models\Post;
use App\Models\Tag;
use App\Models\Wall;
use App\Models\UserFollower;
use App\Models\PostLike;
use Intervention\Image\Facades\Image;
use Intervention\Image\Facades\Font;
use Auth;

class QuestionController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        
    }
    public function searchTags($tag){
	     return Tag::where('title', 'like', '%'.$tag.'%')
				->get(['id as value','title as display']);
	}
	
	private function clean($string) {
		   $string = strtolower(str_replace(' ', '-', $string)); // Replaces all spaces with hyphens.
		   return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
	}
	protected function getRelatedSlugs($slug, $id = 0)
    {
        return Post::select('slug')->where('slug', 'like', $slug.'%')
            ->where('id', '<>', $id)->where(['type'=>'question'])->get();
    }
	
	public function getRelatedQuestions($slug){
		$post = Post::with('walls')->where(['slug'=>$slug])->where(['type'=>'question','status'=>'published','approved'=>1])->first();
		$walls = $post->walls->modelKeys();
		return	$relatedPosts = Post::with(['tags','user','userProfile'])->whereHas('walls', function ($q) use ($walls) {
			$q->whereIn('walls.id', $walls);
		})->where('id', '<>', $post->id)->where(['type'=>'question'])->take(3)->get();
	}
	public function getQuestionDetails($slug){
	 $postInfo=Post::where(['slug'=>$slug,'status'=>'published','type'=>'question'])->first();
		if(!$postInfo){
			 return response()->json(['msg'=>'Sorry,Page you are looking for does not exist'], 404);
		}
	 $postInfo->views+=1;
	 $postInfo->save();
	 $postData= Post::with(['user','userProfile','walls','tags'=>function ($q){
		 return $q->select('id','title','slug');
		 
	 }])->withCount(['likes'=>function($q)use ($postInfo){
		 return $q->where(['post_id'=>$postInfo->id]);
	 },'dislikes'=>function($q)use ($postInfo){
		 return $q->where(['post_id'=>$postInfo->id]);
	 }])->where(['slug'=>$slug])->where(['type'=>'question'])->first();

	if(Auth::user()){
		
	 $following=UserFollower::where(['follower_id'=>Auth::user()->id,'user_id'=>$postInfo->user_id])->count();
	
    
	}else{
	 $following = 0;
	}
	
	return $postData->setAttribute('following',$following);
	
		
	}
	public function edit($slug){
		
		return Post::with(['tags'=>function($q){
						return	$q->select('id as value','title as display');			
		},'walls'=>function($q){
						return	$q->select('id as value','title as display');			
		}])->where(['type'=>'question','user_id'=>Auth::user()->id,'slug'=>$slug])->first();
	}
	
	public function latestQuestions(){
	  return Post::with(['tags','user','userProfile'])->where(['type'=>'question','status'=>'published','approved'=>1])->orderBy('id','DESC')->paginate(6);
	}
	
	public function list(){
	  return Post::with(['walls'])->withCount(['likes','comments'])->where(['type'=>'question','user_id'=>Auth::user()->id])->orderBy('id','DESC')->paginate(10);
	}
	
	/**
     * update post.
     *
     * @return \Illuminate\Http\Response
     */
	public function update(Request $request,$id)
	{     

		$data = $this->validate($request, [
			'title' => 'required',
            'content' => 'required',
     	]);
		$thumb_sizes=[['height'=>70,'width'=>70],['height'=>235,'width'=>335],['height'=>485,'width'=>730]];
		$upload_path=rtrim(app()->basePath('public/images/questions'));
		if ($request->has('title') && $request->has('content')) {
			
			$post = Post::where(['type'=>'question','id'=>$id])->first();
			$post->title=$request->input('title');
			$post->user_id=Auth::user()->id;
			$post->type='question';
			if($request->input('anonymous'))
			$post->anonymous=1;
		    else
			$post->anonymous=0;
			if(trim($request->input('image'))){
				$post->image=$request->input('image');	 
				foreach($thumb_sizes as $size){
					$thumb_name=$upload_path.'/thumbs/'.$size['width'].'x'.$size['height'].$post->image;
					$image = Image::make($upload_path.'/'.$post->image)->resize($size['width'], $size['height']);
					$image->save($thumb_name, 100);
				}
				
			
			}
			$post->content=$request->input('content');
			if(trim($request->input('slug'))){
				$post->slug=$this->clean(trim($request->input('slug')));
			}else{
			    $post->slug=$this->clean(trim($request->input('title')));
			
			}
			if(trim($request->input('excerpt'))){
				$post->excerpt=strip_tags(substr($request->input('excerpt'), 0, 100));
			}else{
			    $post->excerpt=strip_tags(substr($request->input('content'), 0, 100));
			
			}
			
			$allSlugs =$this->getRelatedSlugs($post->slug,$id);
			
			// If we haven't used it before then we are all good.
			if ($allSlugs->contains('slug', $post->slug)){
				for ($i = 1; $i <= 100; $i++) {
					$newSlug = $post->slug.'-'.$i;
					if (!$allSlugs->contains('slug', $newSlug)) {
						$post->slug= $newSlug;
						break;
					}
			     }
			}
			// Just append numbers like a savage until we find not used.
			
			if(trim($request->input('meta_title'))){
     			$post->meta_title=strip_tags($request->input('meta_title'));
			}else{
				$post->meta_title=strip_tags(substr($request->input('title'), 0, 69));
			}
			if(trim($request->input('meta_description'))){
     			$post->meta_description=strip_tags($request->input('meta_description'));
			}else{
				$post->meta_description=strip_tags(substr($request->input('content'), 0, 159));
			}
			;
			$post->status=$request->input('status');
			
			if($post->save()){
				if(!empty($request->input('walls'))){
					foreach($request->input('walls') as $wall){
					$wallData=	Wall::find($wall['value']);
					if(!$post->walls->contains($wallData))
						$post->walls()->attach($wallData);
					}
				
				}
				if(!empty($request->input('tags'))){
					$post->tags()->detach();
					foreach($request->input('tags') as $tag){
					$tagData=	Tag::find($tag['value']);
					if(!$tagData){
						$tagData=Tag::create(['title'=>trim($tag['value']),'slug'=>$this->clean(trim($tag['value']))]);
					}
					
					if(!$post->tags->contains($tagData))
						$post->tags()->attach($tagData);
					}
				
				}
			  return ["Question Updated Succesfull"] ;
			}else{
			  return ["Error in Updating Post"];
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
		$thumb_sizes=[['height'=>70,'width'=>70],['height'=>235,'width'=>335],['height'=>485,'width'=>730]];
		$upload_path=rtrim(app()->basePath('public/images/questions'));
		if ($request->has('title') && $request->has('content')) {
			$post = new Post;
			$post->title=$request->input('title');
			if($request->input('anonymous'))
			$post->anonymous=1;
		    
			$post->user_id=Auth::user()->id;
			$post->type='question';
			$post->content=$request->input('content');
			if(trim($request->input('image'))){
				$post->image=$request->input('image');	 
				foreach($thumb_sizes as $size){
					$thumb_name=$upload_path.'/thumbs/'.$size['width'].'x'.$size['height'].$post->image;
					$image = Image::make($upload_path.'/'.$post->image)->resize($size['width'], $size['height']);
					$image->save($thumb_name, 100);
				}
				
			
			}
			if(trim($request->input('slug'))){
				$post->slug=$this->clean(trim($request->input('slug')));
			}else{
			    $post->slug=$this->clean(trim($request->input('title')));
			
			}
			if(trim($request->input('excerpt'))){
				$post->excerpt=strip_tags(substr($request->input('excerpt'), 0, 100));
			}else{
			    $post->excerpt=strip_tags(substr($request->input('content'), 0, 100));
			
			}
			
			$allSlugs =$this->getRelatedSlugs($post->slug,0);
			
			// If we haven't used it before then we are all good.
			if ($allSlugs->contains('slug', $post->slug)){
				for ($i = 1; $i <= 100; $i++) {
					$newSlug = $post->slug.'-'.$i;
					if (!$allSlugs->contains('slug', $newSlug)) {
						$post->slug= $newSlug;
						break;
					}
			     }
			}
			// Just append numbers like a savage until we find not used.
			
			if(trim($request->input('meta_title'))){
     			$post->meta_title=strip_tags($request->input('meta_title'));
			}else{
				$post->meta_title=strip_tags(substr($request->input('title'), 0, 69));
			}
			if(trim($request->input('meta_description'))){
     			$post->meta_description=strip_tags($request->input('meta_description'));
			}else{
				$post->meta_description=strip_tags(substr($request->input('content'), 0, 159));
			}
			;
			$post->status='published';
			if($post->save()){
			  if(!empty($request->input('walls'))){
					foreach($request->input('walls') as $wall){
						$wallData=	Wall::find($wall['value']);
						if($wallData)
						if(!$post->walls->contains($wallData))
							$post->walls()->attach($wallData);
					}
				
				}
				if(!empty($request->input('tags'))){
					$post->tags()->detach();
					foreach($request->input('tags') as $tag){
					$tagData=	Tag::find($tag['value']);
					if(!$tagData){
						$tagData=Tag::create(['title'=>trim($tag['value']),'slug'=>$this->clean(trim($tag['value']))]);
					}
					
					if(!$post->tags->contains($tagData))
						$post->tags()->attach($tagData);
					}
				
				}
			  return ["Thanks you question posted successfully"] ;
			}else{
			  return ["Error in posting new question"];
			}
		}else{
			return ["Invalid Request"];
		}
	}
}
