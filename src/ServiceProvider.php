<?php

namespace DoubleThreeDigital\SimpleCommerce;

use Statamic\Providers\AddonServiceProvider;
use Statamic\Statamic;

class ServiceProvider extends AddonServiceProvider
{
    protected $config = false;
    protected $translations = false;

    protected $commands = [
        Console\Commands\MakeGateway::class,
        Console\Commands\MakeShippingMethod::class,
        Console\Commands\SetupContentCommand::class,
    ];

    protected $fieldtypes = [
        Fieldtypes\MoneyFieldtype::class,
    ];

    protected $listen = [
        Events\CartCompleted::class       => [],
        Events\CartSaved::class           => [],
        Events\CartUpdated::class         => [],
        Events\CouponRedeemed::class      => [],
        Events\CustomerAddedToCart::class => [],
        Events\PostCheckout::class        => [],
        Events\PreCheckout::class         => [],
    ];

    protected $routes = [
        'actions' => __DIR__.'/../routes/actions.php',
    ];

    protected $scripts = [
        __DIR__.'/../resources/dist/js/cp.js',
    ];

    protected $tags = [
        Tags\SimpleCommerceTag::class,
    ];

    public function boot()
    {
        parent::boot();

        Statamic::booted(function () {
            $this
                ->bootVendorAssets()
                ->bootRepositories();

            Woodland::check();
        });

        SimpleCommerce::bootGateways();
        Actions\RefundAction::register();
    }

    protected function bootVendorAssets()
    {
        $this->publishes([
            __DIR__.'/../resources/dist' => public_path('vendor/simple-commerce'),
        ], 'simple-commerce');

        $this->publishes([
            __DIR__.'/../config/simple-commerce.php' => config_path('simple-commerce.php'),
        ], 'simple-commerce-config');

        $this->publishes([
            __DIR__.'/../resources/blueprints' => resource_path('blueprints'),
        ], 'simple-commerce-blueprints');

        $this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/simple-commerce'),
        ], 'simple-commerce-translations');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/simple-commerce'),
        ], 'simple-commerce-views');

        if (app()->environment() !== 'testing') {
            $this->mergeConfigFrom(__DIR__.'/../config/simple-commerce.php', 'simple-commerce');
        }

        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'simple-commerce');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'simple-commerce');

        return $this;
    }

    protected function bootRepositories()
    {
        $this->app->bind(Contracts\CartRepository::class, Repositories\CartRepository::class);
        $this->app->bind(Contracts\CouponRepository::class, Repositories\CouponRepository::class);
        $this->app->bind(Contracts\CurrencyRepository::class, Repositories\CurrencyRepository::class);
        $this->app->bind(Contracts\CustomerRepository::class, Repositories\CustomerRepository::class);
        $this->app->bind(Contracts\ProductRepository::class, Repositories\ProductRepository::class);

        return $this;
    }
}
