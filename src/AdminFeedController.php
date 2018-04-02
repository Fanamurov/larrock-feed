<?php

namespace Larrock\ComponentFeed;

use LarrockFeed;
use Illuminate\Routing\Controller;
use Larrock\Core\Traits\AdminMethods;
use Larrock\Core\Traits\AdminMethodsShow;

class AdminFeedController extends Controller
{
    use AdminMethodsShow, AdminMethods;

    public function __construct()
    {
        $this->shareMethods();
        $this->middleware(LarrockFeed::combineAdminMiddlewares());
        $this->config = LarrockFeed::shareConfig();
        \Config::set('breadcrumbs.view', 'larrock::admin.breadcrumb.breadcrumb');
    }
}
