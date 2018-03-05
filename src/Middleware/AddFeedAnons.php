<?php

namespace Larrock\ComponentFeed\Middleware;

use Cache;
use Closure;
use LarrockFeed;

class AddFeedAnons
{
    /**
     * Handle an incoming request.
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $anons = Cache::rememberForever('feedAnons_mod', function() {
            return LarrockFeed::getModel()->whereCategory(config('larrock.feed.anonsCategory'))->whereActive(1)
                ->take(config('larrock.feed.anonsCategoryLimit', 10))->orderBy('position', 'DESC')->get();
        });

        if(config('larrock.feed.anonsCategory') === NULL){
            \Session::push('message.danger', 'larrock.anonsCategory не задан!');
        }

        \View::share('anons', $anons);
        return $next($request);
    }
}