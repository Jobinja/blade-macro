<?php

namespace JobinjaTeam\BladeMacro;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;

class JobinjaBladeMacroServiceProvider extends ServiceProvider
{
    /**
     * Boot method
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'blade_macro.php' => $this->app->make('path.config').DIRECTORY_SEPARATOR.'blade_macro.php',
        ]);

        if ($this->clearsViews()) {
            Artisan::call('view:clear');
        }

        /** @var BladeCompiler $compilerInstance */
        $compilerInstance = $this->app['blade.compiler'];

        $compilerInstance->extend(function ($value) {

            /** @var BladeExtensionBuilder $instance */
            $instance = $this->app['blade_macro.builder'];

            return $instance->processForMacro($value);
        });
    }

    /**
     * Register
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('blade_macro.builder', function (Application $app) {
            return new BladeExtensionBuilder($app['view']->getFinder(), $app['files']);
        });
    }

    /**
     * Indicates that views should be cleared on each call or not.
     *
     * @return bool
     */
    private function clearsViews()
    {
        $config = $this->app['config'];

        if (!$config['app.debug']) {
            return false;
        }

        return $this->app['config']->get('blade_macro.clear_views_on_development', true);
    }
}