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
use Dusterio\LinkPreview\Client;
class PostController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        
    }
	public function search(Request $request){
			

		
		switch ($request->type) {
			case "articles":
				$request->type='article';
				return Post::where(['type'=>$request->type,'status'=>'published','approved'=>1])->where('content', 'like', '%' .$request->q.'%')->orderBy('title','ASC')->get(['title','slug']);
				break;
			case "qa":
				$request->type='question';
				return Post::where(['type'=>$request->type,'status'=>'published','approved'=>1])->where('content', 'like', '%' .$request->q.'%')->orderBy('title','ASC')->get(['title','slug']);
				break;
			case "link":
			  return Post::where(['type'=>'link','status'=>'published','approved'=>1])->where('content', 'like', '%' .$request->q.'%')->orderBy('title','ASC')->get(['title','slug']);
		}
			
		
	}
	public function extractLink(Request $request){
		$link=$request->link;
		if (!filter_var($link, FILTER_VALIDATE_URL)) {
			return response()->json(['msg'=>'Invalid Link'], 422);
		}
		$previewClient = new Client($link);

		// Get previews from all available parsers
		$previews = $previewClient->getPreviews();

		// Get a preview from specific parser
		$preview = $previewClient->getPreview('general');
		$preview=$preview->toArray();
		
		if (!filter_var($preview['cover'], FILTER_VALIDATE_URL)) {
			$preview['cover']="";
		}
		$preview['link']=$link;
		$preview['content']=$preview['description'].' Read More <a href="'.$link.'">'.$link.'</a>';
		// Convert output to array
		return	$preview ;
	}
	public function publishLink(Request $request){
		$data = $this->validate($request, [
			'title' => 'required',
            'content' => 'required',
     	]);
		
		$post = new Post;
		if(trim($request->input('slug'))){
				$post->slug=$this->clean(trim($request->input('slug')));
		}else{
			    $post->slug=$this->clean(trim($request->input('title')));
			
		}
		$postInfo=Post::where(['slug'=>$post->slug])->first();
		
		if($postInfo){
			return ["Link Already Exists On DevQuora"];
		}
		
		
		$thumb_sizes=[['height'=>70,'width'=>70],['height'=>235,'width'=>335],['height'=>485,'width'=>730]];
		$upload_path=rtrim(app()->basePath('public/images/articles'));
		if ($request->has('title') && $request->has('content')) {
			
			$post->title=$request->input('title');
			$post->user_id=Auth::user()->id;
			$post->type='link';
			$post->content=$request->input('content');
			
			
			if(trim($request->input('image'))){
				$post->image=$request->input('image');	 
				foreach($thumb_sizes as $size){
					$thumb_name=$upload_path.'/thumbs/'.$size['width'].'x'.$size['height'].$post->image;
					$image = Image::make($upload_path.'/'.$post->image)->resize($size['width'], $size['height']);
					$image->save($thumb_name, 100);
				}
				
			
			}elseif(trim($request->input('cover'))){
					$upload_path=rtrim(app()->basePath('public/images/articles'));
					$path = trim($request->input('cover'));
					$filename = $post->slug;
					
					$image = @Image::make($path)->save($upload_path.'/'.$filename.".png");
					dd($image);
					$post->image=$filename.".png";
					if(file_exists($upload_path.'/'.$post->image)){
			        foreach($thumb_sizes as $size){
						
						$thumb_name=$upload_path.'/thumbs/'.$size['width'].'x'.$size['height'].$post->image;
						$image = Image::make($upload_path.'/'.$post->image)->resize($size['width'], $size['height']);
						$image->save($thumb_name, 100);
				      }
					}
					
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
				$post->meta_description=strip_tags(substr($request->input('content'), 0, 299));
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
			  return ["Link Posted Successfully"] ;
			}else{
			  return ["Error in posting Link"];
			}
		}else{
			return ["Invalid Request"];
		}
	}
    public function searchTags($tag){
	     return Tag::where('title', 'like', '%'.$tag.'%')
				->get(['id as value','title as display']);
	}
	public function like(Request $request){
		$postLiked=PostLike::where(['user_id'=>Auth::user()->id,'post_id'=>$request->id,'action'=>'like','type'=>'post'])->first();
		
		if($postLiked){
			$postLiked->delete();
			$status=false;
		}else{
			$like=new PostLike;
			$like->post_id=$request->id;
			$like->user_id=Auth::user()->id;
			$like->action='like';
			$like->type='post';
			$like->save();
			$status=true;
		}
		$likeCount= PostLike::where(['post_id'=>$request->id,'action'=>'like','type'=>'post'])->count();
		return ['status'=>$status,'count'=>$likeCount];
		
	}
	public function Dislike(Request $request){
		$postLiked=PostLike::where(['user_id'=>Auth::user()->id,'post_id'=>$request->id,'action'=>'dislike','type'=>'post'])->first();
		
		if($postLiked){
			$postLiked->delete();
			$status=false;
		}else{
			$like=new PostLike;
			$like->post_id=$request->id;
			$like->user_id=Auth::user()->id;
			$like->action='dislike';
			$like->type='post';
			$like->save();
			$status=true;
		}
		$dislikeCount= PostLike::where(['post_id'=>$request->id,'action'=>'dislike','type'=>'post'])->count();
		return ['status'=>$status,'count'=>$dislikeCount];
		
	}
	
	private function clean($string) {
		   $string = strtolower(str_replace(' ', '-', $string)); // Replaces all spaces with hyphens.
		   return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
	}
	protected function getRelatedSlugs($slug, $id = 0)
    {
        return Post::select('slug')->where('slug', 'like', $slug.'%')
            ->where('id', '<>', $id)->where(['type'=>'article'])->get();
    }
	
	public function getRelatedPosts($slug){
		$post = Post::with('walls')->where(['slug'=>$slug])->whereIn('type', ['article','link'])->first();
		$walls = $post->walls->modelKeys();
		return	$relatedPosts = Post::with(['tags','user','userProfile'])->whereHas('walls', function ($q) use ($walls) {
			$q->whereIn('walls.id', $walls);
		})->where('id', '<>', $post->id)->where(['status'=>'published','approved'=>1])->whereIn('type', ['article','link'])->take(3)->get();
	}
	
	
	
	public function getPostDetails($slug){
		$postInfo=Post::where(['slug'=>$slug,'status'=>'published'])->whereIn('type', ['article','link'])->first();
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
	 }])->where(['slug'=>$slug])->whereIn('type', ['article','link'])->first();

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
		}])->where(['type'=>'article','user_id'=>Auth::user()->id,'slug'=>$slug])->first();
	}
	
	public function list(){
	 
	  return Post::with(['walls'])->withCount(['likes','dislikes','comments'])->where(['type'=>'article','user_id'=>Auth::user()->id])->orderBy('id','DESC')->paginate(10);
	
	}
	public function articlesByWall($slug){
	    $wall=Wall::where(['slug'=>$slug])->first();
	    if($wall){
		
		return $posts = Post::with(['tags','user'=>function($q){$q->with('userProfile');},'userProfile','walls'])->whereHas('walls', function ($q) use ($wall) {
			
			$q->whereIn('walls.id', [$wall->id]);
		    })->withCount(['likes','comments','dislikes'])->where(['status'=>'published','approved'=>1])->orderBy('id','DESC')->paginate(6);
		 }
	
	}
	
	
	public function recentArticles(){
	 
	  return Post::with(['walls','tags','user'=>function($q){
		return  $q->with('userProfile');
	  }])->withCount(['likes','comments','dislikes'])->where(['status'=>'published','approved'=>1])->orderBy('id','DESC')->paginate(6);
	
	}
	public function popularArticles(){
	  return Post::with(['walls','tags','user'=>function($q){
		return  $q->with('userProfile');
	  }])->withCount(['likes','comments','dislikes'])->where(['status'=>'published','approved'=>1,'type'=>'article'])->orderBy('views','DESC')->paginate(6);
	
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
		$upload_path=rtrim(app()->basePath('public/images/articles'));
		if ($request->has('title') && $request->has('content')) {
			
			$post = Post::where(['type'=>'article','id'=>$id])->first();
			$post->title=$request->input('title');
			$post->user_id=Auth::user()->id;
			$post->type='article';
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
				$post->meta_description=strip_tags(substr($request->input('content'), 0, 299));
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
			  return ["Post Updated Succesfull"] ;
			}else{
			  return ["Error in Updating Post"];
			}
		}else{
			return ["Invalid Request"];
		}
	}
	/**
     * create new post.
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
		$upload_path=rtrim(app()->basePath('public/images/articles'));
		if ($request->has('title') && $request->has('content')) {
			$post = new Post;
			$post->title=$request->input('title');
			$post->user_id=Auth::user()->id;
			$post->type='article';
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
			$post->status=$request->input('status');
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
			  return ["Post Created Succesfull"] ;
			}else{
			  return ["Error in Creating New Post"];
			}
		}else{
			return ["Invalid Request"];
		}
	}
}
