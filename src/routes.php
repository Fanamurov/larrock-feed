<?php

use Larrock\ComponentFeed\AdminFeedController;
use Larrock\ComponentFeed\FeedController;

$middlewares = ['web', 'GetSeo'];
if(file_exists(base_path(). '/vendor/fanamurov/larrock-menu')){
    $middlewares[] = 'AddMenuFront';
}
if(file_exists(base_path(). '/vendor/fanamurov/larrock-blocks')){
    $middlewares[] = 'AddBlocksTemplate';
}

Route::group(['middleware' => $middlewares], function(){
    Route::get('/feed/index', [
        'as' => 'feed.index', 'uses' => FeedController::class .'@index'
    ]);
    Route::get('/feed/{category?}/{subcategory?}/{subsubcategory?}', [
        'as' => 'feed.show', 'uses' => FeedController::class .'@show'
    ]);
});

Route::group(['prefix' => 'admin', 'middleware'=> ['web', 'level:2', 'LarrockAdminMenu']], function(){
    Route::resource('feed', AdminFeedController::class);
});