<?php

namespace Larrock\ComponentFeed\Models;

use Cache;
use Larrock\Core\Helpers\Plugins\RenderPlugins;
use Illuminate\Database\Eloquent\Model;
use Larrock\ComponentCategory\Facades\LarrockCategory;
use Larrock\Core\Traits\GetFilesAndImages;
use Larrock\Core\Traits\GetLink;
use Larrock\Core\Traits\GetSeo;
use Nicolaslopezj\Searchable\SearchableTrait;
use Larrock\Core\Component;
use DB;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use Spatie\MediaLibrary\HasMedia\Interfaces\HasMediaConversions;
use Larrock\ComponentFeed\Facades\LarrockFeed;

/**
 * Larrock\ComponentFeed\Models\Feed
 *
 * @property integer $id
 * @property string $title
 * @property string $category
 * @property string $short
 * @property string $description
 * @property string $url
 * @property string $date
 * @property integer $position
 * @property integer $active
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
 * @property-read \Illuminate\Database\Eloquent\Collection|\Spatie\MediaLibrary\Media[] $media
 * @mixin \Eloquent
 * @property integer $user_id
 * @property-read mixed $first_image
 * @property-read mixed $full_url
 * @method static \Illuminate\Database\Query\Builder|\Larrock\ComponentFeed\Models\Feed whereUserId($value)
 * @property-read mixed $get_seo_title
 * @method static \Illuminate\Database\Query\Builder|\Larrock\ComponentFeed\Models\Feed search($search, $threshold = null, $entireText = false, $entireTextOnly = false)
 * @method static \Illuminate\Database\Query\Builder|\Larrock\ComponentFeed\Models\Feed searchRestricted($search, $restriction, $threshold = null, $entireText = false, $entireTextOnly = false)
 */
class Feed extends Model implements HasMediaConversions
{
    /**
     * @var $this Component
     */
    public $config;

    use HasMediaTrait;
    use GetFilesAndImages;
    use GetSeo;
    use SearchableTrait;
    use GetLink;

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
        ]
    ];

    protected $dates = ['created_at', 'updated_at', 'date'];

    protected $guarded = ['user_id'];

    protected $casts = [
        'position' => 'integer',
        'active' => 'integer'
    ];

    public function scopeCategoryInfo()
    {
        return DB::table(LarrockFeed::getConfig()->table)
            ->leftJoin(LarrockCategory::getConfig()->table, LarrockFeed::getConfig()->table. '.category', '=', LarrockCategory::getConfig()->table. '.id')
            ->get();
    }

    public function get_category()
    {
        return $this->hasOne(LarrockCategory::getModelName(), 'id', 'category');
    }

    public function get_categoryActive()
    {
        return $this->hasOne(LarrockCategory::getModelName(), 'id', 'category')->whereActive('1');
    }

    public function getFullUrlAttribute()
    {
        return Cache::remember('url_feed'. $this->id, 1440, function() {
            $url = '/feed';
            foreach ($this->get_category()->first()->parent_tree as $category){
                $url .= '/'. $category->url;
            }
            $url .= '/'. $this->url;
            return $url;
        });
    }

    /**
     * Замена тегов плагинов на их данные
     *
     * @return mixed
     */
    public function getShortRenderAttribute()
    {
        $cache_key = 'ShortRender'. $this->config->table.'-'. $this->id;
        if(\Auth::check()){
            $cache_key .= '-'. \Auth::user()->role->first()->level;
        }

        return \Cache::remember($cache_key, 1440, function(){
            $renderPlugins = new RenderPlugins($this->short, $this);
            $render = $renderPlugins->renderBlocks()->renderImageGallery()->renderFilesGallery();
            return $render->rendered_html;
        });
    }

    /**
     * Замена тегов плагинов на их данные
     *
     * @return mixed
     */
    public function getDescriptionRenderAttribute()
    {
        $cache_key = 'DescriptionRender'. $this->config->table.'-'. $this->id;
        if(\Auth::check()){
            $cache_key .= '-'. \Auth::user()->role->first()->level;
        }

        return \Cache::remember($cache_key, 1440, function(){
            $renderPlugins = new RenderPlugins($this->description, $this);
            $render = $renderPlugins->renderBlocks()->renderImageGallery()->renderFilesGallery();
            return $render->rendered_html;
        });
    }
}