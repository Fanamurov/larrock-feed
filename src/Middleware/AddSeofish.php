<?php

namespace Larrock\ComponentFeed\Middleware;

use Cache;
use Closure;
use LarrockFeed;

class AddSeofish
{
    /**
     * Handle an incoming request.
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $seofish = Cache::rememberForever('seofish_mod', function () {
            return LarrockFeed::getModel()->whereCategory(config('larrock.feed.seofish_category_id'))->whereActive(1)->orderBy('position', 'DESC')->get();
        });

        if (config('larrock.feed.seofish_category_id') === null) {
            \Session::push('message.danger', 'larrock.feed.seofish_category_id не задан!');
        }

        \View::share('seofish', $seofish);

        return $next($request);
    }
}
