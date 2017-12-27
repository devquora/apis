<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});
$router->get('/generate', function () {
    return     str_random(32);
});

Route::group(['prefix'=>'api','as'=>'api.'], function(){
	Route::post('/authenticate', 'UserController@authenticate');
	Route::post('/users', 'UserController@create');
	Route::get('/search', 'PostController@search');
	Route::post('/users/star-profile', ['middleware'=>'auth','uses'=>'UserController@starProfile']);
	Route::get('/user-profile', ['middleware'=>'auth','uses'=>'UserController@userProfile']);
	Route::post('/users/follow', ['middleware'=>'auth','uses'=>'UserController@follow']);
	Route::post('/users/join-wall', ['middleware'=>'auth','uses'=>'UserController@joinWall']);
	Route::post('/users', ['middleware'=>'auth','uses'=>'WallController@create']);
	Route::put('/users/{id}', ['middleware'=>'auth','uses'=>'UserController@updateProfile']);
	Route::get('/walls', ['middleware'=>'auth','uses'=>'WallController@list']);
	Route::get('/walls/popular', 'WallController@getPopularWalls');
	Route::get('/get-front-walls', 'WallController@getAllWalls');
	Route::get('/walls/get-walls', 'WallController@getWalls');
	Route::get('/walls/{slug}', ['middleware'=>'auth','uses'=>'WallController@edit']);
	Route::get('/walls/search/{slug}', ['middleware'=>'auth','uses'=>'WallController@searchWalls']);
	Route::put('/walls/{id}', ['middleware'=>'auth','uses'=>'WallController@update']);
	Route::get('/walls/details/{slug}', 'WallController@getWallDetails');

	Route::get('/walls/topContributers/{slug}', 'WallController@topContributers');
	Route::get('/comment/{post_id}','CommentController@listbyPost');
	Route::post('/comment', ['middleware'=>'auth','uses'=>'CommentController@create']);
	Route::delete('/comment/{id}', ['middleware'=>'auth','uses'=>'CommentController@delete']);
	Route::post('/comment/like', ['middleware'=>'auth','uses'=>'CommentController@like']);
	Route::post('/comment/dislike', ['middleware'=>'auth','uses'=>'CommentController@disLike']);
	Route::post('/posts', ['middleware'=>'auth','uses'=>'PostController@create']);
	Route::post('/publish-link', ['middleware'=>'auth','uses'=>'PostController@publishLink']);
	Route::post('/posts/like', ['middleware'=>'auth','uses'=>'PostController@like']);
	Route::post('/posts/dislike',['middleware'=>'auth','uses'=>'PostController@disLike']);
	Route::get('/posts/article-by-wall/{slug}', 'PostController@articlesByWall');
	Route::get('/questions/latest', 'QuestionController@latestQuestions');
	Route::post('/questions',['middleware'=>'auth','uses'=>'QuestionController@create']);
	Route::get('/questions', ['middleware'=>'auth','uses'=>'QuestionController@list']);
	Route::get('/questions/{slug}', ['middleware'=>'auth','uses'=>'QuestionController@edit']);
	Route::put('/questions/{id}', ['middleware'=>'auth','uses'=>'QuestionController@update']);
	Route::get('/qa/{slug}', 'QuestionController@getQuestionDetails');
	Route::get('/relatedQuestions/{slug}', 'QuestionController@getRelatedQuestions');
	Route::get('/posts', ['middleware'=>'auth','uses'=>'PostController@list']);
	Route::post('/get-link-info', ['middleware'=>'auth','uses'=>'PostController@extractLink']);
	Route::get('/recent-articles', 'PostController@recentArticles');
	Route::get('/popular-articles', 'PostController@popularArticles');	
	Route::get('/articles/{slug}', 'PostController@getPostDetails');
	Route::get('/relatedPosts/{slug}', 'PostController@getRelatedPosts');
	Route::get('/userDetails/{slug}', 'UserController@getUserDetails');
	Route::get('/similarUsers/{slug}', 'UserController@getSimilarUsers');
	Route::get('/posts/{slug}', ['middleware'=>'auth','uses'=>'PostController@edit']);
	Route::put('/posts/{id}', ['middleware'=>'auth','uses'=>'PostController@update']);
	Route::get('/tags/search/{slug}', ['middleware'=>'auth','uses'=>'PostController@searchTags']);
	Route::post('/upload', ['middleware'=>'auth','uses'=>'UploadController@create']);
	
});
