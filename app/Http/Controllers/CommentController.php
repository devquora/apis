<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;
use Intervention\Image\Facades\Font;
use App\Models\Post;
use App\Models\Comment;
use App\Models\User;
use App\Models\PostLike;
use Auth;
class CommentController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        
    }
   	

	public function like(Request $request){
		$postLiked=PostLike::where(['user_id'=>Auth::user()->id,'post_id'=>$request->id,'action'=>'like','type'=>'comment'])->first();
		
		if($postLiked){
			$postLiked->delete();
			$status=false;
		}else{
			$like=new PostLike;
			$like->post_id=$request->id;
			$like->user_id=Auth::user()->id;
			$like->action='like';
			$like->type='comment';
			$like->save();
			$status=true;
		}
		$likeCount= PostLike::where(['post_id'=>$request->id,'action'=>'like','type'=>'comment'])->count();
		return ['status'=>$status,'count'=>$likeCount];
		
	}
	public function Dislike(Request $request){
		$postLiked=PostLike::where(['user_id'=>Auth::user()->id,'post_id'=>$request->id,'action'=>'dislike','type'=>'comment'])->first();
		
		if($postLiked){
			$postLiked->delete();
			$status=false;
		}else{
			$like=new PostLike;
			$like->post_id=$request->id;
			$like->user_id=Auth::user()->id;
			$like->action='dislike';
			$like->type='comment';
			$like->save();
			$status=true;
		}
		$dislikeCount= PostLike::where(['post_id'=>$request->id,'action'=>'dislike','type'=>'comment'])->count();
		return ['status'=>$status,'count'=>$dislikeCount];		
	}
	
	public function listbyPost($post_id){
	  return Comment::with(['user'=>function($q){
	  $q->with(['userProfile']);
		  
	  }])->withCount(['likes','dislikes'])->where(['post_id'=>$post_id])->orderBy('id','DESC')->paginate(5);
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
				$wall->excerpt=substr($request->input('excerpt'), 0, 100);
			}else{
			    $wall->excerpt=substr($request->input('excerpt'), 0, 100);
			
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
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function delete($id)
    {
        $comment= Comment::where(['id'=>$id,'user_id'=>Auth::user()->id]);
		if($comment){
		Comment::find($id)->delete();
		if(!Comment::find($id)){
			return ['status'=>'success','msg'=>"Comment deleted successfully"] ;
		}else{
			
			return ['status'=>'error','msg'=>"Error in deleting Comment"] ;
		}
		}
        return ['status'=>'error','msg'=>"You are not authorized to delete this comment"] ;
    }
	/**
     * create new users.
     *
     * @return \Illuminate\Http\Response
     */
	public function create(Request $request)
	{       
		$data = $this->validate($request, [
			'content' => 'required',
     	]);
		$comment=new Comment;
		$comment->user_id=Auth::user()->id;
		$comment->type=0;
		$comment->post_id=$request->post_id;
		$comment->content=$request->content;
		if($comment->save()){
		      return ["Comment published successfully"] ;
		}else{
			  return ["Error in posting Comment"];
		}
	}
}
