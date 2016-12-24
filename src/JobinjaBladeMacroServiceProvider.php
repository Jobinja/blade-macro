<?php

namespace JobinjaTeam\BladeMacro;

use Illuminate\Contracts\Foundation\Application;
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
        if ($this->clearsViews()) {
            $this->app->call('view:clear');
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
            return new BladeExtensionBuilder($app['view.finder'], $app['files']);
        });
    }

    /**
     * Indicates that views should be cleared on each call or not.
     *
     * @return bool
     */
    private function clearsViews()
    {
        return false;
    }
}