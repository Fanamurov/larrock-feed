<?php

namespace Larrock\Core\Middleware;

use Cache;
use Closure;
use Larrock\ComponentFeed\Models\Feed;

class AddSeofish
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $seofish = Cache::remember('seofish_mod', 1440, function() {
            return Feed::whereCategory(2)->whereActive(1)->orderBy('position', 'DESC')->get();
        });
        \View::share('seofish', $seofish);

        return $next($request);
    }
}
