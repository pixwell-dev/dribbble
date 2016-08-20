<?php namespace Devdojo\Dribbble;

use Illuminate\Support\ServiceProvider;

class DribbbleServiceProvider extends ServiceProvider
{
    
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('Devdojo\Dribbble\Dribbble', function ($app) {
            return new Dribbble(array_get($app['config'], 'services.dribbble'));
        });
    }
    
}
