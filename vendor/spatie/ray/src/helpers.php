<?php

use Illuminate\Contracts\Container\BindingResolutionException;
use Spatie\CraftRay\Ray as CraftRay;
use Spatie\LaravelRay\Ray as LaravelRay;
use Spatie\Ray\Ray;

use Spatie\Ray\Settings\SettingsFactory;
use Spatie\RayBundle\Ray as SymfonyRay;
use Spatie\WordPressRay\Ray as WordPressRay;
use Spatie\YiiRay\Ray as YiiRay;

if (! function_exists('ray')) {
    /**
     * @param mixed ...$args
     *
     * @return \Spatie\Ray\Ray|LaravelRay|WordPressRay|YiiRay|SymfonyRay
     */
    function ray(...$args)
    {
        if (class_exists(LaravelRay::class)) {
            try {
                return app(LaravelRay::class)->send(...$args);
            } catch (BindingResolutionException $exception) {
                // this  exception can occur when requiring spatie/ray in an Orchestra powered
                // testsuite without spatie/laravel-ray's service provider being registered
                // in `getPackageProviders` of the base test suite
            }
        }

        if (class_exists(CraftRay::class) && class_exists(Yii::class)) {
            return Yii::$container->get(CraftRay::class)->send(...$args);
        }

        if (class_exists(YiiRay::class) && class_exists(Yii::class)) {
            return Yii::$container->get(YiiRay::class)->send(...$args);
        }

        $rayClass = Ray::class;

        if (class_exists(WordPressRay::class)) {
            $rayClass = WordPressRay::class;
        }

        if (class_exists(SymfonyRay::class)) {
            $rayClass = SymfonyRay::class;
        }

        $settings = SettingsFactory::createFromConfigFile();

        return (new $rayClass($settings))->send(...$args);
    }

    register_shutdown_function(function () {
        ray()->throwExceptions();
    });
}

if (! function_exists('rd')) {
    function rd(...$args)
    {
        ray(...$args)->die();
    }
}
