<?php

namespace Larrock\ComponentFeed\Models;

use Cache;
use Illuminate\Database\Eloquent\Model;
use Larrock\ComponentCategory\Facades\LarrockCategory;
use Larrock\Core\Models\Seo;
use Nicolaslopezj\Searchable\SearchableTrait;
use DB;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use Spatie\MediaLibrary\HasMedia\Interfaces\HasMediaConversions;
use Larrock\ComponentFeed\Facades\LarrockFeed;
use Spatie\MediaLibrary\Media;

/**
 * App\Models\Feed
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
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Feed whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Feed whereTitle($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Feed whereCategory($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Feed whereShort($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Feed whereDescription($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Feed whereUrl($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Feed whereDate($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Feed wherePosition($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Feed whereActive($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Feed whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Feed whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Feed find($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Feed categoryInfo()
 * @property-read \Illuminate\Database\Eloquent\Collection|\Spatie\MediaLibrary\Media[] $media
 * @mixin \Eloquent
 * @property integer $user_id
 * @property-read mixed $first_image
 * @property-read mixed $full_url
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Feed whereUserId($value)
 * @property-read mixed $get_seo_title
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Feed search($search, $threshold = null, $entireText = false, $entireTextOnly = false)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Feed searchRestricted($search, $restriction, $threshold = null, $entireText = false, $entireTextOnly = false)
 */
class Feed extends Model implements HasMediaConversions
{
    use HasMediaTrait;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->fillable(LarrockFeed::addFillableUserRows(['title', 'short', 'description', 'category', 'url', 'date', 'position', 'active']));
        $this->table = LarrockFeed::getConfig()->table;
    }

    public function registerMediaConversions(Media $media = null)
    {
        $this->addMediaConversion('110x110')
            ->height(110)->width(110)
            ->performOnCollections('images');

        $this->addMediaConversion('140x140')
            ->height(140)->width(140)
            ->performOnCollections('images');

        $this->addMediaConversion('250x250')
            ->height(250)->width(250)
            ->performOnCollections('images');
    }
    

    use SearchableTrait;

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

    public function get_seo()
    {
        return $this->hasOne(Seo::class, 'seo_id_connect', 'id')->whereSeoTypeConnect('feed');
    }

    public function getImages()
    {
        return $this->hasMany('Spatie\MediaLibrary\Media', 'model_id', 'id')->where([['model_type', '=', LarrockFeed::getModelName()], ['collection_name', '=', 'images']])->orderBy('order_column', 'DESC');
    }
    public function getFirstImage()
    {
        return $this->hasOne('Spatie\MediaLibrary\Media', 'model_id', 'id')->where([['model_type', '=', LarrockFeed::getModelName()], ['collection_name', '=', 'images']])->orderBy('order_column', 'DESC');
    }

    public function getFiles()
    {
        return $this->hasMany('Spatie\MediaLibrary\Media', 'model_id', 'id')->where([['model_type', '=', LarrockFeed::getModelName()], ['collection_name', '=', 'files']])->orderBy('order_column', 'DESC');
    }

    public function getFirstImageAttribute()
    {
        return Cache::remember('image_f_feed'. $this->id, 1440, function() {
            if($get_image = $this->getMedia('images')->sortByDesc('order_column')->first()){
                return $get_image->getUrl();
            }
            return '/_assets/_front/_images/empty_big.png';
        });
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

    public function getGetSeoTitleAttribute()
    {
        return Cache::remember('seo_title'. $this->id .'_'. $this->url, 1440, function() {
            if($get_seo = Seo::whereSeoIdConnect($this->id)->first()){
                return $get_seo->seo_title;
            }
            if($get_seo = Seo::whereSeoUrlConnect($this->url)->first()){
                return $get_seo->seo_title;
            }
            return NULL;
        });
    }
}