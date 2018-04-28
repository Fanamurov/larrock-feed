<?php

namespace Larrock\ComponentFeed\Models;

use DB;
use Cache;
use Larrock\Core\Traits\GetAdminLink;
use LarrockFeed;
use LarrockCategory;
use Larrock\Core\Component;
use Larrock\Core\Traits\GetSeo;
use Larrock\Core\Traits\GetLink;
use Spatie\MediaLibrary\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Larrock\Core\Traits\GetFilesAndImages;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Nicolaslopezj\Searchable\SearchableTrait;
use Larrock\Core\Helpers\Plugins\RenderPlugins;

/**
 * Larrock\ComponentFeed\Models\Feed.
 *
 * @property int $id
 * @property string $title
 * @property string $category
 * @property string $short
 * @property string $description
 * @property string $url
 * @property string $date
 * @property int $position
 * @property int $active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Query\Builder|\Larrock\ComponentFeed\Models\Feed whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\Larrock\ComponentFeed\Models\Feed whereTitle($value)
 * @method static \Illuminate\Database\Query\Builder|\Larrock\ComponentFeed\Models\Feed whereCategory($value)
 * @method static \Illuminate\Database\Query\Builder|\Larrock\ComponentFeed\Models\Feed whereShort($value)
 * @method static \Illuminate\Database\Query\Builder|\Larrock\ComponentFeed\Models\Feed whereDescription($value)
 * @method static \Illuminate\Database\Query\Builder|\Larrock\ComponentFeed\Models\Feed whereUrl($value)
 * @method static \Illuminate\Database\Query\Builder|\Larrock\ComponentFeed\Models\Feed whereDate($value)
 * @method static \Illuminate\Database\Query\Builder|\Larrock\ComponentFeed\Models\Feed wherePosition($value)
 * @method static \Illuminate\Database\Query\Builder|\Larrock\ComponentFeed\Models\Feed whereActive($value)
 * @method static \Illuminate\Database\Query\Builder|\Larrock\ComponentFeed\Models\Feed whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Larrock\ComponentFeed\Models\Feed whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Larrock\ComponentFeed\Models\Feed find($value)
 * @method static \Illuminate\Database\Query\Builder|\Larrock\ComponentFeed\Models\Feed categoryInfo()
 * @property-read \Illuminate\Database\Eloquent\Collection|\Spatie\MediaLibrary\Models\Media[] $media
 * @mixin \Eloquent
 * @property int $user_id
 * @property mixed $short_render
 * @property mixed $description_render
 * @property mixed $getCategory
 * @property mixed $getCategoryActive
 * @property-read mixed $first_image
 * @property-read mixed $full_url
 * @method static \Illuminate\Database\Query\Builder|\Larrock\ComponentFeed\Models\Feed whereUserId($value)
 * @property-read mixed $get_seo_title
 * @method static \Illuminate\Database\Query\Builder|\Larrock\ComponentFeed\Models\Feed search($search, $threshold = null, $entireText = false, $entireTextOnly = false)
 * @method static \Illuminate\Database\Query\Builder|\Larrock\ComponentFeed\Models\Feed searchRestricted($search, $restriction, $threshold = null, $entireText = false, $entireTextOnly = false)
 */
class Feed extends Model implements HasMedia
{
    /** @var $this Component */
    protected $config;

    use GetFilesAndImages, GetSeo, SearchableTrait, GetLink, GetAdminLink;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->fillable(LarrockFeed::addFillableUserRows([]));
        $this->config = LarrockFeed::getConfig();
        $this->table = LarrockFeed::getTable();
    }

    // no need for this, but you can define default searchable columns:
    protected $searchable = [
        'columns' => [
            'feed.title' => 10,
            'feed.short' => 5,
            'feed.description' => 1,
        ],
    ];

    protected $dates = ['created_at', 'updated_at', 'date'];

    protected $guarded = ['user_id'];

    protected $casts = [
        'position' => 'integer',
        'active' => 'integer',
    ];

    public function getConfig()
    {
        return $this->config;
    }

    public function scopeCategoryInfo()
    {
        return DB::table(LarrockFeed::getConfig()->table)
            ->leftJoin(LarrockCategory::getConfig()->table, LarrockFeed::getConfig()->table.'.category', '=', LarrockCategory::getConfig()->table.'.id')
            ->get();
    }

    public function getCategory()
    {
        return $this->hasOne(LarrockCategory::getModelName(), 'id', 'category');
    }

    public function getCategoryActive()
    {
        return $this->hasOne(LarrockCategory::getModelName(), 'id', 'category')->whereActive('1');
    }

    public function getFullUrlAttribute()
    {
        return Cache::rememberForever('url_feed'.$this->id, function () {
            $url = '/feed';
            foreach ($this->getCategory()->first()->parent_tree as $category) {
                $url .= '/'.$category->url;
            }
            $url .= '/'.$this->url;

            return $url;
        });
    }

    /**
     * Замена тегов плагинов на их данные.
     * @return mixed
     * @throws \Throwable
     */
    public function getShortRenderAttribute()
    {
        $cache_key = 'ShortRender'.$this->config->table.'-'.$this->id;
        if (\Auth::check()) {
            $cache_key .= '-'.\Auth::user()->role->first()->level;
        }

        return Cache::rememberForever($cache_key, function () {
            $renderPlugins = new RenderPlugins($this->short, $this);
            $render = $renderPlugins->renderBlocks()->renderImageGallery()->renderFilesGallery();

            return $render->rendered_html;
        });
    }

    /**
     * Замена тегов плагинов на их данные.
     * @return mixed
     * @throws \Throwable
     */
    public function getDescriptionRenderAttribute()
    {
        $cache_key = 'DescriptionRender'.$this->config->table.'-'.$this->id;
        if (\Auth::check()) {
            $cache_key .= '-'.\Auth::user()->role->first()->level;
        }

        return Cache::rememberForever($cache_key, function () {
            $renderPlugins = new RenderPlugins($this->description, $this);
            $render = $renderPlugins->renderBlocks()->renderImageGallery()->renderFilesGallery();

            return $render->rendered_html;
        });
    }

    /**
     * Перезаписываем метод из HasMediaTrait, добавляем кеш.
     * @param string $collectionName
     * @return mixed
     */
    public function loadMedia(string $collectionName)
    {
        $cache_key = sha1('loadMediaCache'.$collectionName.$this->id.$this->getConfig()->getModelName());

        return Cache::rememberForever($cache_key, function () use ($collectionName) {
            $collection = $this->exists
                ? $this->media
                : collect($this->unAttachedMediaLibraryItems)->pluck('media');

            return $collection->filter(function (Media $mediaItem) use ($collectionName) {
                if ($collectionName === '') {
                    return true;
                }

                return $mediaItem->collection_name === $collectionName;
            })->sortBy('order_column')->values();
        });
    }
}
