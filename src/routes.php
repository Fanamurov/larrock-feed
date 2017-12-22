<?php

Route::get('/feed/index', 'Larrock\ComponentFeed\FeedController@index')->name('feed.index');
Route::get('/feed/{category?}/{subcategory?}/{subsubcategory?}/{subsubcategory2?}/{subsubcategory3?}',
    'Larrock\ComponentFeed\FeedController@show')->name('feed.show');

Route::group(['prefix' => 'admin'], function(){
    Route::resource('feed', 'Larrock\ComponentFeed\AdminFeedController');
});

Breadcrumbs::register('admin.'. LarrockFeed::getName() .'.index', function($breadcrumbs){
    $breadcrumbs->push(LarrockFeed::getTitle(), '/admin/'. LarrockFeed::getName());
});

Breadcrumbs::register('admin.'. LarrockFeed::getName() .'.category', function($breadcrumbs, $data){
    $breadcrumbs->parent('admin.'. LarrockFeed::getName() .'.index');
    foreach($data->parent_tree as $item){
        $breadcrumbs->push($item->title, '/admin/'. $item->component .'/'. $item->id);
    }
});

Breadcrumbs::register('feed.index', function($breadcrumbs){
    $breadcrumbs->push('Ленты', '/feed/index');
});

Breadcrumbs::register('feed.category', function($breadcrumbs, $data){
    foreach ($data->parent_tree as $category){
        $breadcrumbs->push($category->title, $category->full_url);
    }
});

Breadcrumbs::register('feed.item', function($breadcrumbs, $data){
    foreach ($data->get_category->parent_tree as $category){
        $breadcrumbs->push($category->title, $category->full_url);
    }
    $breadcrumbs->push($data->title);
});