<?php

namespace Larrock\ComponentFeed;

use Illuminate\Routing\Controller;
use Larrock\Core\Traits\AdminMethodsCreate;
use Larrock\Core\Traits\AdminMethodsDestroy;
use Larrock\Core\Traits\AdminMethodsEdit;
use Larrock\Core\Traits\AdminMethodsStore;
use Larrock\Core\Traits\AdminMethodsUpdate;
use Larrock\Core\Traits\ShareMethods;
use View;
use Larrock\ComponentFeed\Facades\LarrockFeed;
use Larrock\ComponentCategory\Facades\LarrockCategory;

class AdminFeedController extends Controller
{
    use AdminMethodsStore, AdminMethodsUpdate, AdminMethodsDestroy, AdminMethodsCreate, AdminMethodsEdit, ShareMethods;

	public function __construct()
	{
	    $this->shareMethods();
        $this->middleware(LarrockFeed::combineAdminMiddlewares());
        $this->config = LarrockFeed::shareConfig();

        \Config::set('breadcrumbs.view', 'larrock::admin.breadcrumb.breadcrumb');
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

		return view('larrock::admin.admin-builder.categories', $data);
	}
}