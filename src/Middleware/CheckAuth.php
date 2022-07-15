<?php

namespace DevDr\ApiCrudGenerator\Middleware;

use Closure;
use DevDr\ApiCrudGenerator\Controllers\BaseApiController;

class CheckAuth extends BaseApiController {

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next) {
        $user = $this->_checkAuth();
        $request->attributes->add(['users' => $user]);
        return $next($request);
    }

}
