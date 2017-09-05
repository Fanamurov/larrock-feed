<?php

namespace Larrock\ComponentFeed;

use Breadcrumbs;
use Cache;
use Illuminate\Http\Request;

use JsValidator;
use Alert;
use Lang;
use Larrock\ComponentCategory\CategoryComponent;
use Larrock\ComponentCategory\Models\Category;
use Larrock\Core\Component;
use Validator;
use Redirect;
use View;
use Larrock\ComponentFeed\Facades\LarrockFeed;
use Larrock\ComponentCategory\Facades\LarrockCategory;
use Larrock\Core\AdminController;

class AdminFeedController extends AdminController
{
	public function __construct()
	{
        $this->config = LarrockFeed::shareConfig();

        \Config::set('breadcrumbs.view', 'larrock::admin.breadcrumb.breadcrumb');
		Breadcrumbs::register('admin.'. LarrockFeed::getName() .'.index', function($breadcrumbs){
			$breadcrumbs->push(LarrockFeed::getTitle(), '/admin/'. LarrockFeed::getName());
		});
	}

	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response|View
     */
	public function index()
	{
        $data['app_category'] = LarrockCategory::getConfig();
		$data['categories'] = LarrockCategory::getModel()->whereComponent('feed')->whereLevel(1)->orderBy('position', 'desc')->paginate(30);
		return view('larrock::admin.admin-builder.categories', $data);
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @param Request                     $request
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function create(Request $request)
	{
		if( !$category = LarrockCategory::getModel()->whereComponent('feed')->first()){
            LarrockCategory::getModel()->create(['title' => 'Новый раздел', 'url' => str_slug('Новый раздел')]);
			$category = LarrockCategory::getModel()->whereComponent('feed')->first();
		}
		Cache::flush();
		$test = Request::create('/admin/'. LarrockFeed::getName(), 'POST', [
			'title' => 'Новый материал',
			'url' => str_slug('novyy-material'),
			'category' => $request->get('category', $category->id),
			'active' => 0
		]);
		return $this->store($test);
	}

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     * @throws \Illuminate\Database\Eloquent\MassAssignmentException
     */
	public function store(Request $request)
	{
		$validator = Validator::make($request->all(), LarrockFeed::getValid());
		if($validator->fails()){
			return back()->withInput($request->except('password'))->withErrors($validator);
		}

		$data = LarrockFeed::getModel()->fill($request->all());
        foreach (LarrockFeed::getRows() as $row){
            if(in_array($row->name, $data->getFillable())){
                if(get_class($row) === 'Larrock\Core\Helpers\FormBuilder\FormCheckbox'){
                    $data->{$row->name} = $request->input($row->name, NULL);
                }
                if(get_class($row) === 'Larrock\Core\Helpers\FormBuilder\FormDate'){
                    $data->{$row->name} = $request->input('date', date('Y-m-d'));
                }
            }
        }
		$data->user_id = \Auth::user()->id;

		if($data->save()){
            Alert::add('successAdmin', Lang::get('larrock::apps.create.success-temp'))->flash();
			return Redirect::to('/admin/'. LarrockFeed::getName() .'/'. $data->id .'/edit')->withInput();
		}

        Alert::add('errorAdmin', Lang::get('larrock::apps.create.error'));
        return back()->withInput();
	}

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response|View
     */
	public function show($id)
	{
        $data['category'] = LarrockCategory::getModel()->whereId($id)->with(['get_child', 'get_parent'])->first();
        $data['data'] = LarrockFeed::getModel()->whereCategory($data['category']->id)->orderByDesc('position')->orderByDesc('date')->paginate('30');
        $data['app_category'] = LarrockCategory::getConfig();

		Breadcrumbs::register('admin.'. LarrockFeed::getName() .'.category', function($breadcrumbs, $data)
		{
			$breadcrumbs->parent('admin.'. LarrockFeed::getName() .'.index');
            foreach($data->parent_tree as $item){
                $breadcrumbs->push($item->title, '/admin/'. $item->component .'/'. $item->id);
            }
		});

		return view('larrock::admin.admin-builder.categories', $data);
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response|View
     */
	public function edit($id)
	{
        $data['data'] = LarrockFeed::getModel()->with(['get_category'])->findOrFail($id);
        $data['app'] = LarrockFeed::tabbable($data['data']);

        $validator = JsValidator::make(Component::_valid_construct(LarrockFeed::getConfig(), 'update', $id));
        View::share('validator', $validator);

		Breadcrumbs::register('admin.'. LarrockFeed::getName() .'.edit', function($breadcrumbs, $data)
		{
			$breadcrumbs->parent('admin.'. LarrockFeed::getName() .'.index');
            foreach($data->get_category->parent_tree as $item){
                $breadcrumbs->push($item->title, '/admin/'. LarrockFeed::getName() .'/'. $item->id);
            }

            $current_level = LarrockFeed::getModel()->whereCategory($data->get_category->first()->id)->orderBy('updated_at', 'DESC')->take('15')->get();
            $breadcrumbs->push($data->title, '/admin/'. LarrockFeed::getName() .'/'. $data->id, ['current_level' => $current_level]);
		});

		return view('larrock::admin.admin-builder.edit', $data);
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  int  $id
	 * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
	public function update(Request $request, $id)
	{
		$validator = Validator::make($request->all(), Component::_valid_construct(LarrockFeed::getConfig(), 'update', $id));
		if($validator->fails()){
			return back()->withInput($request->except('password'))->withErrors($validator);
		}

		$data = LarrockFeed::getModel()->find($id);
        foreach (LarrockFeed::getRows() as $row){
            if(in_array($row->name, $data->getFillable())){
                if(get_class($row) === 'Larrock\Core\Helpers\FormBuilder\FormCheckbox'){
                    $data->{$row->name} = $request->input($row->name, NULL);
                }
                if(get_class($row) === 'Larrock\Core\Helpers\FormBuilder\FormDate'){
                    $data->{$row->name} = $request->input('date', date('Y-m-d'));
                }
            }
        }
		$data->user_id = $request->user()->id;

		if($data->fill($request->all())->save()){
            Alert::add('successAdmin', Lang::get('larrock::apps.update.success', ['name' => $request->input('title')]))->flash();
			\Cache::flush();
			return back();
		}

        Alert::add('warning', Lang::get('larrock::apps.update.nothing', ['name' => $request->input('title')]))->flash();
		return back()->withInput();
	}

    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     * @param  int $id
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
	public function destroy(Request $request, $id)
	{
		if($data = LarrockFeed::getModel()->find($id)){
            $data->clearMediaCollection();
            $name = $data->title;
            $category = $data->category;
            if($data->delete()){
                Alert::add('successAdmin', Lang::get('larrock::apps.delete.success', ['name' => $name]))->flash();
                \Cache::flush();

                if($request->get('place') === 'material'){
                    return Redirect::to('/admin/'. LarrockFeed::getName() .'/'. $category);
                }
            }else{
                Alert::add('errorAdmin', Lang::get('larrock::apps.delete.error', ['name' => $name]))->flash();
            }
        }else{
            Alert::add('errorAdmin', 'Такого материала больше нет')->flash();
        }

        return back();
	}
}