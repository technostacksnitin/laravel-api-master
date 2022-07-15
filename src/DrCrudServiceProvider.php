<?php

namespace DevDr\ApiCrudGenerator;

use DevDr\ApiCrudGenerator\src\Commands\CrudGenerator;
use Illuminate\Support\ServiceProvider;

class DrCrudServiceProvider extends ServiceProvider {

    /**
     * Register services.
     *
     * @return void
     */
    public function register() {
        $this->app->make('DevDr\ApiCrudGenerator\Controllers\BaseApiController');
        $this->app->make('DevDr\ApiCrudGenerator\Middleware\CheckAuth');
//        $this->commands([
//            CrudGenerator::class,
//        ]);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot() {
        
    }

}
