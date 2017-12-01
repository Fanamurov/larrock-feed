<?php

namespace Larrock\ComponentFeed;

use App\Http\Controllers\Controller;
use Larrock\ComponentCategory\Facades\LarrockCategory;
use Larrock\ComponentFeed\Facades\LarrockFeed;
use Cache;
use Illuminate\Http\Request;

class FeedController extends Controller
{
	public function __construct()
	{
	    $this->middleware(LarrockFeed::combineFrontMiddlewares());
	}
	
    public function index(Request $request)
	{
		$page = $request->get('page', 1);
		$data = Cache::remember('feed_index'.$page, 1440, function() use ($page) {
			$data['category'] = LarrockCategory::getModel()->whereType('feed')->whereActive(1)->whereLevel(1)->orderBy('created_at', 'desc')->with(['get_feedActive'])->get();
			$data['data'] = LarrockFeed::getModel()->whereActive(1)->with('get_category')->orderBy('date', 'desc')->skip(($page-1)*8)->paginate(8);
			return $data;
		});

		return view('larrock::front.feed.index', $data);
	}

	public function show(Request $request)
	{
	    $params = \Route::current()->parameters();
        if(count($params) > 1 && LarrockFeed::getModel()->whereUrl(last($params))->first()){
            return $this->getItem(last($params));
        }

        //Это должен быть раздел
        $category = last($params);
		$page = $request->get('page', 1);
		$data['data'] = Cache::remember('feed_'.$category.'_'.$page, 1440, function() use ($category, $page) {
			$data = LarrockCategory::getModel()->whereUrl($category)->whereActive(1)->firstOrFail();
			$data->get_feedActive = $data->get_feedActive()->orderBy('date', 'desc')->skip(($page-1)*8)->paginate(8);
			return $data;
		});

		\View::share('sharing_type', 'category');
		\View::share('sharing_id', $data['data']->id);

		return view()->first(['larrock::front.feed.category.'. $category, 'larrock::front.feed.category'], $data);
	}

	public function getItem($item)
	{
		$data = Cache::remember(sha1('feed_item_'. $item), 1440, function() use ($item) {
			$data['data'] = LarrockFeed::getModel()->whereUrl($item)->with(['get_category'])->firstOrFail();
			return $data;
		});

		foreach ($data['data']->get_category->parent_tree as $category){
            if($category->active !== 1){
                return abort('404', 'Раздел не опубликован');
            }
        }

		\View::share('sharing_type', 'feed');
		\View::share('sharing_id', $data['data']->id);

		return view()->first(['larrock::front.feed.'. $item, 'larrock::front.feed.item'], $data);
	}
}