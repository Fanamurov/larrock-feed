<?php

namespace Larrock\ComponentFeed\Models;

use Cache;
use Illuminate\Database\Eloquent\Model;
use Larrock\ComponentCategory\Models\Category;
use Larrock\Core\Models\Seo;
use Nicolaslopezj\Searchable\SearchableTrait;
use DB;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use Spatie\MediaLibrary\HasMedia\Interfaces\HasMedia;
use Spatie\MediaLibrary\HasMedia\Interfaces\HasMediaConversions;
use Larrock\ComponentFeed;

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

    public function registerMediaConversions()
    {
        $this->addMediaConversion('110x110')
            ->setManipulations(['w' => 110, 'h' => 110])
            ->performOnCollections('images');

        $this->addMediaConversion('140x140')
            ->setManipulations(['w' => 140, 'h' => 140])
            ->performOnCollections('images');

        $this->addMediaConversion('250x250')
            ->setManipulations(['w' => 250, 'h' => 250])
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

    protected $table = 'feed';

	protected $fillable = ['title', 'short', 'description', 'category', 'url', 'date', 'position', 'active'];

    protected $dates = ['created_at', 'updated_at', 'date'];

	protected $guarded = ['user_id'];

	protected $casts = [
		'position' => 'integer',
		'active' => 'integer'
	];

	public function scopeCategoryInfo()
	{
		return DB::table('feed')
			->leftJoin('category', 'feed.category', '=', 'category.id')
			->get();
	}

	public function get_category()
	{
		return $this->hasOne(Category::class, 'id', 'category');
	}

	public function get_seo()
	{
		return $this->hasOne(Seo::class, 'id_connect', 'id')->whereTypeConnect('feed');
	}

    public function getImages()
    {
        $config = new ComponentFeed\FeedComponent();
        return $this->hasMany('Spatie\MediaLibrary\Media', 'model_id', 'id')->where([['model_type', '=', $config->model], ['collection_name', '=', 'images']])->orderBy('order_column', 'DESC');
    }
    public function getFirstImage()
    {
        $config = new ComponentFeed\FeedComponent();
        return $this->hasOne('Spatie\MediaLibrary\Media', 'model_id', 'id')->where([['model_type', '=', $config->model], ['collection_name', '=', 'images']])->orderBy('order_column', 'DESC');
    }

    public function getFiles()
    {
        $config = new ComponentFeed\FeedComponent();
        return $this->hasMany('Spatie\MediaLibrary\Media', 'model_id', 'id')->where([['model_type', '=', $config->model], ['collection_name', '=', 'files']])->orderBy('order_column', 'DESC');
    }

	public function getFirstImageAttribute()
	{
		$value = Cache::remember('image_f_feed'. $this->id, 1440, function() {
			if($get_image = $this->getMedia('images')->sortByDesc('order_column')->first()){
				return $get_image->getUrl();
			}
            return '/_assets/_front/_images/empty_big.png';
		});
		return $value;
	}

	public function getFullUrlAttribute()
	{
		$full_url = Cache::remember('url_feed'. $this->id, 1440, function() {
			if($search_parent = Category::whereId($this->get_category->parent)->first()){
				if($search_parent_2 = Category::whereId($search_parent->parent)->first()){
					if($search_parent_3 = Category::whereId($search_parent->parent_2)->first()){
						return '/feed/'. $search_parent_3->url .'/'. $search_parent_2->url .'/' . $search_parent->url .'/'. $this->get_category->url .'/'. $this->url;
					}
                    return '/feed/'. $search_parent_2->url .'/' . $search_parent->url .'/'. $this->get_category->url .'/'. $this->url;
				}
                return '/feed/' . $search_parent->url .'/'. $this->get_category->url .'/'. $this->url;
			}
            return '/feed/'. $this->get_category->url .'/'. $this->url;
		});
		return $full_url;
	}

	public function getGetSeoTitleAttribute()
	{
        $value = Cache::remember('seo_title'. $this->id .'_'. $this->url, 1440, function() {
            if($get_seo = Seo::whereIdConnect($this->id)->first()){
                return $get_seo->seo_title;
            }
            if($get_seo = Seo::whereUrlConnect($this->url)->first()){
                return $get_seo->seo_title;
            }
            return NULL;
        });
        return $value;
	}
}