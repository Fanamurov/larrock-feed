<?php

namespace Larrock\ComponentFeed;

use Cache;
use LarrockFeed;
use LarrockCategory;
use Larrock\Core\Component;
use Larrock\Core\Helpers\Tree;
use Larrock\ComponentFeed\Models\Feed;
use Larrock\ComponentCategory\Models\Category;
use Larrock\Core\Helpers\FormBuilder\FormDate;
use Larrock\Core\Helpers\FormBuilder\FormTags;
use Larrock\Core\Helpers\FormBuilder\FormInput;
use Larrock\Core\Helpers\FormBuilder\FormHidden;
use Larrock\Core\Helpers\FormBuilder\FormCategory;
use Larrock\Core\Helpers\FormBuilder\FormTextarea;

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
        $this->setRow($row->setValid('required')->setConnect(Category::class, 'getCategory')
            ->setWhereConnect('component', 'feed')->setMaxItems(1)->setFillable());

        $row = new FormInput('title', 'Заголовок');
        $this->setRow($row->setValid('max:255|required')->setTypo()->setFillable());

        $row = new FormTextarea('short', 'Анонс');
        $this->setRow($row->setTypo()->setHelp('выводится на странице списка материалов, а так же в начале материала')
            ->setFillable());

        $row = new FormTextarea('description', 'Полный текст');
        $this->setRow($row->setTypo()->setHelp('выводится на странице материала после анонса')->setFillable());

        $row = new FormTags('link', 'Связь');
        $this->setRow($row->setModels($this->model, Feed::class));

        $row = new FormDate('date', 'Дата материала');
        $this->setRow($row->setFillable()->setCssClassGroup('uk-width-1-3'));

        $row = new FormHidden('user_id', 'user_id');
        $this->setRow($row->setFillable();

        return $this;
    }

    public function renderAdminMenu()
    {
        $count = Cache::rememberForever('count-data-admin-'.LarrockFeed::getName(), function () {
            return LarrockFeed::getModel()->count(['id']);
        });
        $dropdown = Cache::rememberForever('dropdownAdminMenu'.LarrockFeed::getName(), function () {
            return Category::whereComponent('feed')->whereLevel(1)
                ->orderBy('position', 'desc')->get(['id', 'title', 'url']);
        });

        return view('larrock::admin.sectionmenu.types.dropdown', ['count' => $count, 'app' => LarrockFeed::getConfig(),
            'url' => '/admin/'.LarrockFeed::getName(), 'dropdown' => $dropdown, ]);
    }

    public function toDashboard()
    {
        $data = Cache::rememberForever('LarrockFeedItemsDashboard', function () {
            return LarrockFeed::getModel()->latest('updated_at')->take(5)->get();
        });

        return view('larrock::admin.dashboard.feed', ['component' => LarrockFeed::getConfig(), 'data' => $data]);
    }

    public function createSitemap()
    {
        $tree = new Tree();

        if ($activeCategory = $tree->listActiveCategories(LarrockCategory::getModel()->whereActive(1)
            ->whereComponent('feed')->whereParent(null)->get())) {
            $table = LarrockCategory::getConfig()->table;

            return LarrockFeed::getModel()->whereActive(1)->whereHas('getCategory', function ($q) use ($activeCategory, $table) {
                $q->where($table.'.sitemap', '=', 1)->whereIn($table.'.id', $activeCategory);
            })->get();
        }

        return [];
    }

    public function search($admin = null)
    {
        return Cache::rememberForever('search'.$this->name.$admin, function () use ($admin) {
            $data = [];
            if ($admin) {
                $items = LarrockFeed::getModel()->with(['getCategory'])->get(['id', 'title', 'category', 'url']);
            } else {
                $items = LarrockFeed::getModel()->whereActive(1)->with(['getCategoryActive'])->get(['id', 'title', 'category', 'url']);
            }
            foreach ($items as $item) {
                $data[$item->id]['id'] = $item->id;
                $data[$item->id]['title'] = $item->title;
                $data[$item->id]['full_url'] = $item->full_url;
                $data[$item->id]['component'] = $this->name;
                $data[$item->id]['category'] = null;
                if ($admin) {
                    if ($item->getCategory) {
                        $data[$item->id]['category'] = $item->getCategory->title;
                    }
                } else {
                    if ($item->getCategoryActive) {
                        $data[$item->id]['category'] = $item->getCategoryActive->title;
                    }
                }
            }
            if (\count($data) === 0) {
                return null;
            }

            return $data;
        });
    }
}
