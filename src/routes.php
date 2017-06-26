<?php

use Larrock\ComponentFeed\AdminFeedController;

$middlewares = ['web', 'GetSeo'];
if(file_exists(base_path(). '/vendor/fanamurov/larrock-menu')){
    $middlewares[] = 'AddMenuFront';
}
if(file_exists(base_path(). '/vendor/fanamurov/larrock-blocks')){
    $middlewares[] = 'AddBlocksTemplate';
}

Route::group(['middleware' => $middlewares], function(){
    Route::get('/feed/index', [
        'as' => 'feed.index', 'uses' => 'FeedController@index'
    ]);
    Route::get('/feed/{category?}/{subcategory?}/{subsubcategory?}', [
        'as' => 'feed.show', 'uses' => 'FeedController@show'
    ]);
});

Route::group(['prefix' => 'admin', 'middleware'=> ['web', 'level:2', 'LarrockAdminMenu']], function(){
    Route::resource('feed', AdminFeedController::class);
});