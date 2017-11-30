<?php

namespace Larrock\ComponentFeed;

use Cache;
use Larrock\ComponentCategory\Facades\LarrockCategory;
use Larrock\ComponentCategory\Models\Category;
use Larrock\ComponentFeed\Facades\LarrockFeed;
use Larrock\ComponentFeed\Models\Feed;
use Larrock\Core\Helpers\FormBuilder\FormCategory;
use Larrock\Core\Helpers\FormBuilder\FormDate;
use Larrock\Core\Helpers\FormBuilder\FormInput;
use Larrock\Core\Helpers\FormBuilder\FormTagsLink;
use Larrock\Core\Helpers\FormBuilder\FormTextarea;
use Larrock\Core\Component;
use Larrock\Core\Helpers\Tree;

class FeedComponent extends Component
{
    public function __construct()
    {
        $this->name = $this->table = 'feed';
        $this->title = 'Ленты';
        $this->model = \config('larrock.models.feed', Feed::class);
        $this->description = 'Страницы с привязкой к определенным разделам';
        $this->addRows()->addPositionAndActive()->isSearchable()->addPlugins();
    }

    protected function addPlugins()
    {
        $this->addPluginImages()->addPluginFiles()->addPluginSeo()->addAnonsToModule(config('larrock.feed.anonsCategory'));
        return $this;
    }

    protected function addRows()
    {
        $row = new FormCategory('category', 'Раздел');
        $this->rows['category'] = $row->setValid('required')
            ->setConnect(Category::class, 'get_category')->setWhereConnect('component', 'feed')
            ->setMaxItems(1)->setFillable();

        $row = new FormInput('title', 'Заголовок');
        $this->rows['title'] = $row->setValid('max:255|required')->setTypo()->setFillable();

        $row = new FormTextarea('short', 'Анонс');
        $this->rows['short'] = $row->setTypo()->setHelp('выводится на странице списка материалов, а так же в начале материала')
            ->setFillable();

        $row = new FormTextarea('description', 'Полный текст');
        $this->rows['description'] = $row->setTypo()->setHelp('выводится на странице материала после анонса')->setFillable();

        $row = new FormDate('date', 'Дата материала');
        $this->rows['date'] = $row->setTab('other', 'Дата, вес, активность')->setFillable();

        $row = new FormTagsLink('link', 'Связь');
        $this->rows['link'] = $row->setModelParent($this->model)->setModelChild('Larrock\ComponentFeed\Models\Feed');

        return $this;
    }

    public function renderAdminMenu()
    {
        $count = \Cache::remember('count-data-admin-'. LarrockFeed::getName(), 1440, function(){
            return LarrockFeed::getModel()->count(['id']);
        });
        $dropdown = Category::whereComponent('feed')->whereLevel(1)->orderBy('position', 'desc')->get(['id', 'title', 'url']);
        return view('larrock::admin.sectionmenu.types.dropdown', ['count' => $count, 'app' => LarrockFeed::getConfig(), 'url' => '/admin/'. LarrockFeed::getName(), 'dropdown' => $dropdown]);
    }

    public function createSitemap()
    {
        $tree = new Tree();
        
        if($activeCategory = $tree->listActiveCategories(LarrockCategory::getModel()->whereActive(1)->whereComponent('feed')->whereParent(NULL)->get())){
            $table = LarrockCategory::getConfig()->table;
            return LarrockFeed::getModel()->whereActive(1)->whereHas('get_category', function ($q) use ($activeCategory, $table){
                $q->where($table .'.sitemap', '=', 1)->whereIn($table .'.id', $activeCategory);
            })->get();
        }
        return [];
    }

    public function search($admin = NULL)
    {
        return Cache::remember('search'. $this->name. $admin, 1440, function() use ($admin){
            $data = [];
            if($admin){
                $items = LarrockFeed::getModel()->with(['get_category'])->get(['id', 'title', 'category', 'url']);
            }else{
                $items = LarrockFeed::getModel()->whereActive(1)->with(['get_categoryActive'])->get(['id', 'title', 'category', 'url']);
            }
            foreach ($items as $item){
                $data[$item->id]['id'] = $item->id;
                $data[$item->id]['title'] = $item->title;
                $data[$item->id]['full_url'] = $item->full_url;
                $data[$item->id]['component'] = $this->name;
                $data[$item->id]['category'] = NULL;
                if($admin){
                    if($item->get_category){
                        $data[$item->id]['category'] = $item->get_category->title;
                    }
                }else{
                    if($item->get_categoryActive){
                        $data[$item->id]['category'] = $item->get_categoryActive->title;
                    }
                }
            }
            if(count($data) === 0){
                return NULL;
            }
            return $data;
        });
    }
}