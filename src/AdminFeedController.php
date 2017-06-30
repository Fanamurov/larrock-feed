<?php

namespace Larrock\ComponentFeed;

use Breadcrumbs;
use Cache;
use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use JsValidator;
use Alert;
use Lang;
use Larrock\ComponentCategory\CategoryComponent;
use Larrock\ComponentCategory\Models\Category;
use Larrock\Core\Component;
use Validator;
use Redirect;
use View;

class AdminFeedController extends Controller
{
	protected $config;

	public function __construct()
	{
        $component = new FeedComponent();
        $this->config = $component->shareConfig();

        Breadcrumbs::setView('larrock::admin.breadcrumb.breadcrumb');
		Breadcrumbs::register('admin.'. $this->config->name .'.index', function($breadcrumbs){
			$breadcrumbs->push($this->config->title, '/admin/'. $this->config->name);
		});
	}

	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response|View
     */
	public function index()
	{
        $data['app_category'] = new CategoryComponent();
		$data['categories'] = Category::whereComponent('feed')->whereLevel(1)->orderBy('position', 'desc')->paginate(30);
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
		if( !$category = Category::whereComponent('feed')->first()){
			Category::create(['title' => 'Новый раздел', 'url' => str_slug('Новый раздел')]);
			$category = Category::whereComponent('feed')->first();
		}
		Cache::flush();
		$test = Request::create('/admin/'. $this->config->name, 'POST', [
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
		$validator = Validator::make($request->all(), $this->config->valid);
		if($validator->fails()){
			return back()->withInput($request->except('password'))->withErrors($validator);
		}

		$data = new $this->config->model();
		$data->fill($request->all());
        foreach ($this->config->rows as $row){
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
			return Redirect::to('/admin/'. $this->config->name .'/'. $data->id .'/edit')->withInput();
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
        $data['category'] = Category::whereId($id)->with(['get_child', 'get_parent'])->first();
        $data['data'] = $this->config->model::whereCategory($data['category']->id)->orderByDesc('position')->orderByDesc('date')->paginate('30');
        $data['app_category'] = new CategoryComponent();

		Breadcrumbs::register('admin.'. $this->config->name .'.category', function($breadcrumbs, $data)
		{
			$breadcrumbs->parent('admin.'. $this->config->name .'.index');
			if($find_parent = Category::find($data->parent)){
				$breadcrumbs->push($find_parent->title, url('admin.'. $this->config->name .'.show', $find_parent->id));
				if($find_parent = Category::find($find_parent->parent)){
					$breadcrumbs->push($find_parent->title, url('admin.'. $this->config->name .'.show', $find_parent->id));
					if($find_parent2 = Category::find($find_parent->parent)){
						$breadcrumbs->push($find_parent2->title, url('admin.'. $this->config->name .'.show', $find_parent2->id));
					}
				}
			}
			$breadcrumbs->push($data->title, url('admin.feed.show', $data->id));
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
        $data['data'] = $this->config->model::with(['get_category'])->findOrFail($id);
        $data['app'] = $this->config->tabbable($data['data']);

        $validator = JsValidator::make(Component::_valid_construct($this->config, 'update', $id));
        View::share('validator', $validator);


		Breadcrumbs::register('admin.'. $this->config->name .'.edit', function($breadcrumbs, $data)
		{
			$breadcrumbs->parent('admin.'. $this->config->name .'.index');
            foreach($data->get_category->parent_tree as $item){
                $breadcrumbs->push($item->title, '/admin/'. $this->config->name .'/'. $item->id);
            }

            $breadcrumbs->push($data->title, '/admin/'. $this->config->name .'/'. $data->id);
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
		$validator = Validator::make($request->all(), Component::_valid_construct($this->config, 'update', $id));
		if($validator->fails()){
			return back()->withInput($request->except('password'))->withErrors($validator);
		}

		$data = $this->config->model::find($id);
        foreach ($this->config->rows as $row){
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
		if($data = $this->config->model::find($id)){
            $data->clearMediaCollection();
            $name = $data->title;
            $category = $data->category;
            if($data->delete()){
                Alert::add('successAdmin', Lang::get('larrock::apps.delete.success', ['name' => $name]))->flash();
                \Cache::flush();

                if($request->get('place') === 'material'){
                    return Redirect::to('/admin/'. $this->config->name .'/'. $category);
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