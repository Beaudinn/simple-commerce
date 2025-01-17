<?php

namespace DoubleThreeDigital\SimpleCommerce;

use Barryvdh\Debugbar\Facade as Debugbar;
use DoubleThreeDigital\SimpleCommerce\Events\OrderApproved as OrderApprovedEvent;
use DoubleThreeDigital\SimpleCommerce\Events\QuoteCreated;
use DoubleThreeDigital\SimpleCommerce\Tags\CustomerTags;
use Statamic\Events\EntryBlueprintFound;
use Statamic\Facades\Collection;
use Statamic\Facades\CP\Nav;
use Statamic\Facades\Permission;
use Statamic\Providers\AddonServiceProvider;
use Statamic\Stache\Stache;
use Statamic\Statamic;

class ServiceProvider extends AddonServiceProvider
{
    protected $config = false;
    protected $translations = false;

    protected $actions = [
        Actions\MarkAsPaid::class,
        Actions\RefundAction::class,
	    Actions\RecalculateAction::class,
	    Actions\RecalculateCustomAction::class,
    ];

    protected $commands = [
        Console\Commands\CartCleanupCommand::class,
	    Console\Commands\CartBotCleanupCommand::class,
	    Console\Commands\CartDeliveryResetCommand::class,
        Console\Commands\MakeGateway::class,
        Console\Commands\MakeShippingMethod::class,
        Console\Commands\InstallCommand::class,
        Console\Commands\MigrateOrdersToDatabase::class,
        Console\Commands\SwitchToDatabase::class,
    ];

    protected $fieldtypes = [
        Fieldtypes\CountryFieldtype::class,
        Fieldtypes\GatewayFieldtype::class,
        Fieldtypes\MoneyFieldtype::class,
        Fieldtypes\ProductVariantFieldtype::class,
        Fieldtypes\ProductVariantsFieldtype::class,
        Fieldtypes\RegionFieldtype::class,
        Fieldtypes\ShippingMethodFieldtype::class,
        Fieldtypes\TaxCategoryFieldtype::class,

        Fieldtypes\Variables\LineItemTax::class,
    ];

    protected $listen = [
        EntryBlueprintFound::class  => [
            Listeners\EnforceBlueprintFields::class,
            Listeners\AddHiddenFields::class,
        ],
        Events\PostCheckout::class => [
            Listeners\TidyTemporaryGatewayData::class,
        ],
	    OrderApprovedEvent::class => [
            Listeners\SendConfiguredNotifications::class,
        ],
	    Events\OrderPaid::class => [
            Listeners\SendConfiguredNotifications::class,
        ],
        Events\OrderPaymentFailed::class => [
            Listeners\SendConfiguredNotifications::class,
        ],
        Events\OrderShipped::class => [
            Listeners\SendConfiguredNotifications::class,
        ],
        Events\StockRunningLow::class => [
            Listeners\SendConfiguredNotifications::class,
        ],
        Events\StockRunOut::class => [
            Listeners\SendConfiguredNotifications::class,
        ],
	    QuoteCreated::class => [
		    Listeners\SendConfiguredNotifications::class,
	    ],
    ];

    protected $modifiers = [
        Modifiers\Currency::class,
    ];

    protected $routes = [
        'actions' => __DIR__ . '/../routes/actions.php',
        'cp'      => __DIR__ . '/../routes/cp.php',
    ];

    protected $stylesheets = [
        __DIR__ . '/../resources/dist/css/cp.css',
    ];

    protected $scripts = [
        __DIR__ . '/../resources/dist/js/cp.js',
    ];

    protected $tags = [
        Tags\SimpleCommerceTag::class,
        Tags\TotalIncludingTax::class,
	    CustomerTags::class,
    ];

    protected $updateScripts = [
        UpdateScripts\v2_3\AddBlueprintFields::class,
        UpdateScripts\v2_3\MigrateConfig::class,
        UpdateScripts\v2_3\MigrateLineItemMetadata::class,

        UpdateScripts\v2_4\AddTaxFieldToOrderLineItems::class,
        // UpdateScripts\v2_4\MigrateGatewayDataToNewFormat::class,
        UpdateScripts\v2_4\MigrateSingleCartConfig::class,
        UpdateScripts\v2_4\MigrateTaxConfiguration::class,

        UpdateScripts\v3_0\AddNewFieldsToOrderBlueprint::class,
        UpdateScripts\v3_0\ConfigureTitleFormats::class,
        UpdateScripts\v3_0\ConfigureWhitelistedFields::class,
        UpdateScripts\v3_0\UpdateContentRepositoryReferences::class,

        UpdateScripts\v3_2\RenameCouponValueField::class,
    ];

    public function boot()
    {
        parent::boot();

        Statamic::booted(function () {
            $this
                ->bootVendorAssets()
                ->bindContracts()
                ->bootCartDrivers();
        });

        SimpleCommerce::bootGateways();
        SimpleCommerce::bootProductTypes();
        SimpleCommerce::bootTaxEngine();
        SimpleCommerce::bootShippingMethods();

        Overview::bootCoreWidgets();

        Statamic::booted(function () {
            $this
                ->bootStacheStores()
                ->createNavItems()
                ->registerPermissions();
        });

        Filters\OrderStatusFilter::register();

        if (class_exists('Barryvdh\Debugbar\ServiceProvider') && config('debugbar.enabled', false) === true) {
            //Debugbar::addCollector(new DebugbarDataCollector('simple-commerce'));
        }
    }

    protected function bootVendorAssets()
    {
        $this->publishes([
            __DIR__ . '/../resources/dist' => public_path('vendor/simple-commerce'),
        ], 'simple-commerce');

        $this->publishes([
            __DIR__ . '/../config/simple-commerce.php' => config_path('simple-commerce.php'),
        ], 'simple-commerce-config');

        $this->publishes([
            __DIR__ . '/../resources/blueprints' => resource_path('blueprints'),
        ], 'simple-commerce-blueprints');

        $this->publishes([
            __DIR__ . '/../resources/lang' => resource_path('lang/vendor/simple-commerce'),
        ], 'simple-commerce-translations');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/simple-commerce'),
        ], 'simple-commerce-views');

        if (app()->environment() !== 'testing') {
            $this->mergeConfigFrom(__DIR__ . '/../config/simple-commerce.php', 'simple-commerce');
        }

        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'simple-commerce');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'simple-commerce');

        return $this;
    }

    protected function bindContracts()
    {
        $bindings = [
            Contracts\GatewayManager::class     => Gateways\Manager::class,
            Contracts\ShippingManager::class    => Shipping\Manager::class,
            Contracts\Calculator::class         => Orders\Calculator::class,
        ];

        if (isset(SimpleCommerce::couponDriver()['repository'])) {
            $bindings[Contracts\CouponRepository::class] = SimpleCommerce::couponDriver()['repository'];
        }

	    if (isset(SimpleCommerce::upsellDriver()['repository'])) {
		    $bindings[Contracts\UpsellRepository::class] = SimpleCommerce::upsellDriver()['repository'];
	    }

        if (isset(SimpleCommerce::customerDriver()['repository'])) {
            $bindings[Contracts\CustomerRepository::class] = SimpleCommerce::customerDriver()['repository'];
        }


        if (isset(SimpleCommerce::orderDriver()['repository'])) {
            $bindings[Contracts\OrderRepository::class] = SimpleCommerce::orderDriver()['repository'];
        }

        if (isset(SimpleCommerce::productDriver()['repository'])) {
            $bindings[Contracts\ProductRepository::class] = SimpleCommerce::productDriver()['repository'];
        }

        collect($bindings)->each(function ($concrete, $abstract) {
            if (! $this->app->bound($abstract)) {
                Statamic::repository($abstract, $concrete);
            }
        });

        $this->app->bind(Contracts\Order::class, Orders\Order::class);
        $this->app->bind(Contracts\Coupon::class, Coupons\Coupon::class);
        $this->app->bind(Contracts\Upsell::class, Upsells\Upsell::class);
        $this->app->bind(Contracts\Customer::class, Customers\Customer::class);
        $this->app->bind(Contracts\Product::class, Products\Product::class);

        return $this;
    }

    protected function bootCartDrivers()
    {
        if (! $this->app->bound(Contracts\CartDriver::class)) {
            $this->app->bind(
                Contracts\CartDriver::class,
                config('simple-commerce.cart.driver', \DoubleThreeDigital\SimpleCommerce\Orders\Cart\Drivers\CookieDriver::class)
            );
        }

        return $this;
    }

    protected function bootStacheStores()
    {
        if (SimpleCommerce::isUsingStandardTaxEngine()) {
            $taxCategoryStore = new Tax\Standard\Stache\TaxCategory\TaxCategoryStore;
            $taxCategoryStore->directory(base_path('content/simple-commerce/tax-categories'));

            $taxRateStore = new Tax\Standard\Stache\TaxRate\TaxRateStore;
            $taxRateStore->directory(base_path('content/simple-commerce/tax-rates'));

            $taxZoneStore = new Tax\Standard\Stache\TaxZone\TaxZoneStore;
            $taxZoneStore->directory(base_path('content/simple-commerce/tax-zones'));

            app(Stache::class)->registerStore($taxCategoryStore);
            app(Stache::class)->registerStore($taxRateStore);
            app(Stache::class)->registerStore($taxZoneStore);

            $this->app->bind(Contracts\TaxCategoryRepository::class, function () {
                return new Tax\Standard\Stache\TaxCategory\TaxCategoryRepository(app('stache'));
            });

            $this->app->bind(Contracts\TaxRateRepository::class, function () {
                return new Tax\Standard\Stache\TaxRate\TaxRateRepository(app('stache'));
            });

            $this->app->bind(Contracts\TaxZoneRepository::class, function () {
                return new Tax\Standard\Stache\TaxZone\TaxZoneRepository(app('stache'));
            });
        }

        return $this;
    }

    protected function createNavItems()
    {
        Nav::extend(function ($nav) {
            $nav->create(__('Overview'))
                ->section(__('Sales'))
                ->route('simple-commerce.overview')
                ->can('view simple commerce overview')
                ->icon('charts');

            if (isset(SimpleCommerce::orderDriver()['collection'])) {
                $nav->create(__('Orders'))
                    ->section(__('Sales'))
                    ->route('collections.show', SimpleCommerce::orderDriver()['collection'])
                    ->can('view', SimpleCommerce::orderDriver()['collection'])
                    ->icon(SimpleCommerce::svg('shop'));
            } elseif (isset(SimpleCommerce::orderDriver()['model'])) {
                $orderModelClass = SimpleCommerce::orderDriver()['model'];
                $orderResource = \DoubleThreeDigital\Runway\Runway::findResourceByModel(new $orderModelClass);

                $nav->create(__('Orders'))
                    ->section(__('Sales'))
                    ->route('runway.index', ['resourceHandle' => $orderResource->handle()])
                    ->can("View {$orderResource->plural()}")
                    ->icon(SimpleCommerce::svg('shop'));
            }

            if (isset(SimpleCommerce::customerDriver()['collection'])) {
                $nav->create(__('Customers'))
                    ->section(__('Sales'))
                    ->route('collections.show', SimpleCommerce::customerDriver()['collection'])
                    ->can('view', SimpleCommerce::customerDriver()['collection'])
                    ->icon('user');
            } elseif (isset(SimpleCommerce::customerDriver()['model'])) {
                $customerModelClass = SimpleCommerce::customerDriver()['model'];
                $customerResource = \DoubleThreeDigital\Runway\Runway::findResourceByModel(new $customerModelClass);

                $nav->create(__('Customers'))
                    ->section(__('Sales'))
                    ->route('runway.index', ['resourceHandle' => $customerResource->handle()])
                    ->can("View {$customerResource->plural()}")
                    ->icon('user');
            }

            $nav->create(__('Products'))
                ->section(__('Sales'))
                ->route('collections.show', SimpleCommerce::productDriver()['collection'])
                ->can('view', SimpleCommerce::productDriver()['collection'])
                ->icon('entries');

            $nav->create(__('Coupons'))
                ->section(__('Sales'))
                ->route('collections.show', SimpleCommerce::couponDriver()['collection'])
                ->can('view', SimpleCommerce::couponDriver()['collection'])
                ->icon('tags');

            if (SimpleCommerce::isUsingStandardTaxEngine()) {
                $nav->create(__('Tax'))
                    ->section(__('Sales'))
                    ->route('simple-commerce.tax')
                    ->can('view tax rates')
                    ->icon(SimpleCommerce::svg('money-cash-file-dollar'));
            }

            // Drop any collection items from 'Collections' nav
            $collections = $nav->content('Collections');
	        $collections = $nav->content('Collections');

	        $children = $collections->children()()
		        ->reject(function ($child) {
			        return in_array(
				        $child->name(),
				        collect(config('simple-commerce.content'))
					        ->pluck('collection')
					        ->filter()
					        ->reject(function ($collectionHandle) {
						        return is_null(Collection::find($collectionHandle));
					        })
					        ->map(function ($collectionHandle) {
						        return __(Collection::find($collectionHandle)->title());
					        })
					        ->toArray(),
			        );
		        });

	        $collections->children(function () use ($children) {
		        return $children;
	        });
        });

        return $this;
    }

    protected function registerPermissions()
    {
        Permission::register('view simple commerce overview')
            ->label('View Sales Overview');

        if (SimpleCommerce::isUsingStandardTaxEngine()) {
            Permission::register('view tax rates', function ($permission) {
                $permission->children([
                    Permission::make('edit tax rates')->children([
                        Permission::make('create tax rates'),
                        Permission::make('delete tax rates'),
                    ]),
                ]);
            });

            Permission::register('view tax categories', function ($permission) {
                $permission->children([
                    Permission::make('edit tax categories')->children([
                        Permission::make('create tax categories'),
                        Permission::make('delete tax categories'),
                    ]),
                ]);
            });

            Permission::register('view tax zones', function ($permission) {
                $permission->children([
                    Permission::make('edit tax zones')->children([
                        Permission::make('create tax zones'),
                        Permission::make('delete tax zones'),
                    ]),
                ]);
            });
        }

        return $this;
    }
}
