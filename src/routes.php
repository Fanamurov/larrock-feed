<?php

Route::get('/feed/index', 'Larrock\ComponentFeed\FeedController@index')->name('feed.index');
Route::get('//feed/{category?}/{subcategory?}/{subsubcategory?}/{subsubcategory2?}/{subsubcategory3?}',
    'Larrock\ComponentFeed\FeedController@show')->name('feed.show');

Route::group(['prefix' => 'admin', 'middleware'=> ['web', 'level:2', 'LarrockAdminMenu', 'SaveAdminPluginsData', 'SiteSearchAdmin']], function(){
    Route::resource('feed', 'Larrock\ComponentFeed\AdminFeedController');
});