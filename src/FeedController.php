<?php

namespace Larrock\ComponentFeed;

use Illuminate\Routing\Controller;
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

    /**
     * @param Request $request
     * @return array|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
	{
		$page = $request->get('page', 1);
		$data = Cache::rememberForever('feed_index'.$page, function() use ($page) {
			$data['category'] = LarrockCategory::getModel()->whereType('feed')->whereActive(1)->whereLevel(1)
                ->orderBy('created_at', 'desc')->with(['get_feedActive'])->get();
			$data['data'] = LarrockFeed::getModel()->whereActive(1)->with('get_category')->orderBy('date', 'desc')->skip(($page-1)*8)->paginate(8);
			return $data;
		});
		return view(config('larrock.views.feed.index', 'larrock::front.feed.index'), $data);
	}

    /**
     * @param Request $request
     * @return mixed
     * @throws \Exception
     */
	public function show(Request $request)
	{
	    $params = \Route::current()->parameters();
        if(\count($params) > 1 && LarrockFeed::getModel()->whereUrl(last($params))->first()){
            return $this->getItem(last($params));
        }

        //Это должен быть раздел
        $category = last($params);
		$page = $request->get('page', 1);
		$data['data'] = Cache::rememberForever('feed_'.$category.'_'.$page, function() use ($category, $page) {
			$data = LarrockCategory::getModel()->whereUrl($category)->whereActive(1)->firstOrFail();
			$data->get_feedActive = $data->get_feedActive()->orderBy('date', 'desc')->skip(($page-1)*8)->paginate(8);
			return $data;
		});

		\View::share('sharing_type', 'category');
		\View::share('sharing_id', $data['data']->id);
		return view()->first([config('larrock.views.feed.categoryUniq.'. $category, 'larrock::front.feed.category.'. $category),
            config('larrock.views.feed.category', 'larrock::front.feed.category')], $data);
	}

    /**
     * @param $item
     * @return mixed
     * @throws \Exception
     */
	public function getItem($item)
	{
		$data = Cache::rememberForever(sha1('feed_item_'. $item), function() use ($item) {
			$data['data'] = LarrockFeed::getModel()->whereUrl($item)->with(['get_category'])->firstOrFail();
			return $data;
		});

		foreach ($data['data']->get_category->parent_tree as $category){
            if($category->active !== 1){
                throw new \Exception('Раздел '. $category->title .' не опубликован', 404);
            }
        }

		\View::share('sharing_type', 'feed');
		\View::share('sharing_id', $data['data']->id);
		return view()->first([config('larrock.views.feed.itemUniq.'. $item, 'larrock::front.feed.'. $item), config('larrock.views.feed.item', 'larrock::front.feed.item')], $data);
	}
}