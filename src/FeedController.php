<?php

namespace Larrock\ComponentFeed;

use App\Http\Controllers\Controller;
use Larrock\ComponentCategory\Models\Category;
use Larrock\ComponentFeed\Models\Feed;
use Larrock\Core\Helpers\Plugins\RenderGallery;
use Breadcrumbs;
use Cache;
use Illuminate\Http\Request;

class FeedController extends Controller
{
	public function __construct()
	{
        Breadcrumbs::register('feed.index', function($breadcrumbs)
        {
            $breadcrumbs->push('Ленты', '/feed/index');
        });
	}
	
    public function index(Request $request)
	{
		$page = $request->get('page', 1);
		$data = Cache::remember('feed_index'.$page, 1440, function() use ($page) {
			$data['category'] = Category::whereType('feed')->whereActive(1)->whereLevel(1)->orderBy('created_at', 'desc')->with(['get_feedActive'])->get();
			$data['data'] = Feed::whereActive(1)->with('get_category')->orderBy('date', 'desc')->skip(($page-1)*8)->paginate(8);
			return $data;
		});

		return view('larrock::front.feed.index', $data);
	}

	public function show(Request $request)
	{
        $RenderGallery = new RenderGallery;

	    $params = \Route::current()->parameters();
        if(Feed::whereUrl(last($params))->first()){
            //Это статья
            return $this->getItem(last($params));
        }

        //Это должен быть раздел
        $category = last($params);
		$page = $request->get('page', 1);
		$data['data'] = Cache::remember('feed_'.$category.'_'.$page, 1440, function() use ($category, $page, $RenderGallery) {
			//$data['categorys'] = Category::whereType('feed')->whereActive(1)->whereLevel(1)->orderBy('created_at', 'desc')->with(['get_feedActive'])->get();
			$data = Category::whereUrl($category)->whereActive(1)->firstOrFail();
			$data->get_feedActive = $data->get_feedActive()->orderBy('date', 'desc')->skip(($page-1)*8)->paginate(8);
			foreach ($data->get_feedActive as $key => $value){
                $data->get_feedActive->{$key} = $RenderGallery->renderGallery($value);
            }
			return $data;
		});

        Breadcrumbs::register('feed.category', function($breadcrumbs) use ($data)
        {
            $breadcrumbs->parent('feed.index');
            $breadcrumbs->push($data['data']->title);
        });

		\View::share('sharing_type', 'category');
		\View::share('sharing_id', $data['data']->id);

		return view('larrock::front.feed.category', $data);
	}

	public function getItem($item)
	{
        $RenderGallery = new RenderGallery();

		$data = Cache::remember(sha1('feed_item_'. $item), 1440, function() use ($item, $RenderGallery) {
			$data['data'] = Feed::whereUrl($item)->with(['get_category', 'getImages'])->firstOrFail();
            $data['data'] = $RenderGallery->renderGallery($data['data']);
			return $data;
		});

        Breadcrumbs::register('feed.item', function($breadcrumbs) use ($data)
        {
        	if(count($data['data']->get_category->get_parent) > 0){
        		if(count($data['data']->get_category->get_parent->get_parent) > 0){
					$breadcrumbs->push($data['data']->get_category->get_parent->get_parent->title, $data['data']->get_category->get_parent->get_parent->full_url);
				}
				$breadcrumbs->push($data['data']->get_category->get_parent->title, $data['category']->get_parent->full_url);
			}
            $breadcrumbs->push($data['data']->get_category->title, $data['data']->get_category->full_url);
            $breadcrumbs->push($data['data']->title);

        });

		\View::share('sharing_type', 'feed');
		\View::share('sharing_id', $data['data']->id);

		if(\View::exists('larrock::front.feed.'. $item)){
			return view('larrock::front.feed.'. $item, $data);
		}
        return view('larrock::front.feed.item', $data);
	}
}
