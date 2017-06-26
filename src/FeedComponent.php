<?php

namespace Larrock\ComponentFeed;

use Larrock\ComponentCategory\Models\Category;
use Larrock\ComponentFeed\Models\Feed;
use Larrock\Core\Helpers\FormBuilder\FormCategory;
use Larrock\Core\Helpers\FormBuilder\FormDate;
use Larrock\Core\Helpers\FormBuilder\FormInput;
use Larrock\Core\Helpers\FormBuilder\FormTextarea;
use Larrock\Core\Component;

class FeedComponent extends Component
{
    public function __construct()
    {
        $this->name = $this->table = 'feed';
        $this->title = 'Ленты';
        $this->model = Feed::class;
        $this->description = 'Страницы с привязкой к определенным разделам';
        $this->addRows()->addPositionAndActive()->isSearchable()->addPlugins();
    }

    protected function addPlugins()
    {
        $this->addPluginImages()->addPluginFiles()->addPluginSeo()->addAnonsToModule(17);
        return $this;
    }

    protected function addRows()
    {
        $row = new FormCategory('category', 'Раздел');
        $this->rows['category'] = $row->setValid('required')
            ->setConnect(Category::class, 'get_category')->setWhereConnect('component', 'feed')
            ->setMaxItems(1);

        $row = new FormInput('title', 'Заголовок');
        $this->rows['title'] = $row->setValid('max:255|required')->setTypo();

        $row = new FormTextarea('short', 'Анонс');
        $this->rows['short'] = $row->setTypo()->setHelp('выводится на странице списка материалов, а так же в начале материала');

        $row = new FormTextarea('description', 'Полный текст');
        $this->rows['description'] = $row->setTypo()->setHelp('выводится на странице материала после анонса');

        $row = new FormDate('date', 'Дата материала');
        $this->rows['date'] = $row->setTab('other', 'Дата, вес, активность');

        return $this;
    }

    public function renderAdminMenu()
    {
        $count = \Cache::remember('count-data-admin-'. $this->name, 1440, function(){
            return Feed::count(['id']);
        });
        $dropdown = Category::whereComponent('feed')->whereLevel(1)->orderBy('position', 'desc')->get(['id', 'title', 'url']);
        return view('larrock::admin.sectionmenu.types.dropdown', ['count' => $count, 'app' => $this, 'url' => '/admin/'. $this->name, 'dropdown' => $dropdown]);
    }

    public function createSitemap()
    {
        return Feed::whereActive(1)->whereHas('get_category', function ($q){
            $q->where('sitemap', '=', 1);
        })->get();
    }

    public function createRSS()
    {
        return Feed::whereActive(1)->whereHas('get_category', function ($q){
            $q->where('rss', '=', 1);
        })->get();
    }
}