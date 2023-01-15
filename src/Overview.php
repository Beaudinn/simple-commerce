<?php

namespace DoubleThreeDigital\SimpleCommerce;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use DoubleThreeDigital\SimpleCommerce\Facades\Customer;
use DoubleThreeDigital\SimpleCommerce\Facades\Order;
use DoubleThreeDigital\SimpleCommerce\Facades\Product;
use DoubleThreeDigital\SimpleCommerce\Orders\States\Approved;
use DoubleThreeDigital\SimpleCommerce\Orders\States\Delivered;
use DoubleThreeDigital\SimpleCommerce\Orders\States\Draft;
use DoubleThreeDigital\SimpleCommerce\Orders\States\Pending;
use DoubleThreeDigital\SimpleCommerce\Orders\States\Quote;
use DoubleThreeDigital\SimpleCommerce\Orders\States\Shipped;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;
use Statamic\Facades\User;

class Overview
{
    protected static $widgets = [];

    public static function widgets(): array
    {
        return static::$widgets;
    }

    public static function widget(string $handle): ?array
    {
        return collect(static::$widgets)->firstWhere('handle', $handle);
    }

    public static function registerWidget(string $handle, array $config, \Closure $callback)
    {
        static::$widgets[] = array_merge($config, [
            'handle' => $handle,
            'callback' => $callback,
        ]);
    }

    public static function bootCoreWidgets()
    {
        static::registerWidget(
            'orders-chart',
            [
                'name' => 'Orders Chart',
                'component' => 'overview-orders-chart',
            ],
            function (Request $request) {
                $timePeriod = CarbonPeriod::create(now()->subDays(30)->format('Y-m-d'), now()->format('Y-m-d'));

                return collect($timePeriod)->map(function ($date) {
                    if (isset(SimpleCommerce::orderDriver()['collection'])) {
                        $query = Collection::find(SimpleCommerce::orderDriver()['collection'])
                            ->queryEntries()
	                        ->whereState('state', [Approved::class, Shipped::class, Delivered::class])
	                        ->where('locale', Site::selected()->handle())
                            ->whereDate('created_at', $date->format('d-m-Y'))
                            ->get();
                    }

                    if (isset(SimpleCommerce::orderDriver()['model'])) {
                        $orderModel = new (SimpleCommerce::orderDriver()['model']);

                        $query = $orderModel::query()
	                        ->whereState('state', [Approved::class, Shipped::class, Delivered::class])
	                        ->where('locale', Site::selected()->handle())
                            ->whereDate('created_at', $date)
                            ->get();
                    }

                    return [
                        'date' =>  $date->format('d-m-Y'),
                        'count' => $query->count(),
                    ];
                });
            }
        );

        static::registerWidget(
            'recent-orders',
            [
                'name' => 'Recent Orders',
                'component' => 'overview-recent-orders',
            ],
            function (Request $request) {
                if (isset(SimpleCommerce::orderDriver()['collection'])) {
                    $query = Collection::find(SimpleCommerce::orderDriver()['collection'])
                        ->queryEntries()
	                    ->whereState('state', [Pending::class, Approved::class, Shipped::class, Delivered::class])
                        ->orderBy('created_at', 'desc')
                        ->limit(5)
                        ->get()
                        ->map(function ($order) {
	                        return Order::find($order->id, true);
                        })
                        ->values();

                    return $query->map(function ($order) {
                        return [
                            'id' => $order->id(),
                            'order_number' => $order->orderNumber(),
                            'edit_url' => $order->resource()->editUrl(),
                            'grand_total' => Currency::parse($order->grandTotal(),  $order->site()),
                            'paid_date' => Carbon::parse($order->get('paid_date'))->format(config('statamic.system.date_format')),
                        ];
                    });
                }

                if (isset(SimpleCommerce::orderDriver()['model'])) {
                    $orderModel = new (SimpleCommerce::orderDriver()['model']);

                    $query = $orderModel::query()
                        ->where('locale', Site::selected()->handle())
	                    ->whereState('state', [Pending::class, Approved::class, Shipped::class, Delivered::class])
	                    ->orderBy('created_at', 'desc')
                        ->limit(5)
                        ->get()
                        ->map(function ($order) {
                            return Order::find($order->id, true);
                        })
                        ->values();

                    return $query->map(function ($order) use ($orderModel) {
                        return [
                            'id' => $order->id(),
                            'order_number' => $order->orderNumber(),
                            'edit_url' => cp_route('runway.edit', [
                                'resourceHandle' => \DoubleThreeDigital\Runway\Runway::findResourceByModel($orderModel)->handle(),
                                'record' => $order->resource()->{$orderModel->getRouteKeyName()},
                            ]),
                            'grand_total' => Currency::parse($order->grandTotal(), $order->site()),
                            'paid_date' => Carbon::parse($order->get('paid_date'))->format(config('statamic.system.date_format')),
                        ];
                    });
                }

                return null;
            },
        );

        static::registerWidget(
            'top-customers',
            [
                'name' => 'Top Customers',
                'component' => 'overview-top-customers',
            ],
            function (Request $request) {
                if (isset(SimpleCommerce::customerDriver()['collection'])) {
                    $query = Collection::find(SimpleCommerce::customerDriver()['collection'])
                        ->queryEntries()
                        ->get()
                        ->sortByDesc(function ($customer) {
                            return count($customer->get('orders', []));
                        })
                        ->take(5)
                        ->map(function ($entry) {
                            return Customer::find($entry->id());
                        })
                        ->values();

                    return $query->map(function ($customer) {
                        return [
                            'id' => $customer->id(),
                            'email' => $customer->email(),
                            'edit_url' => $customer->resource()->editUrl(),
                            'orders_count' => count($customer->get('orders', [])),
                        ];
                    });
                }

                if (isset(SimpleCommerce::customerDriver()['model'])) {
                    $customerModel = new (SimpleCommerce::customerDriver()['model']);

                    $query = $customerModel::query()
                        ->whereHas('orders', function ($query) {
	                        $query->where('locale', Site::selected()->handle());
                            $query ->whereState('state', [Pending::class, Approved::class, Shipped::class, Delivered::class]);
                        })
                        ->withCount('orders')
                        ->orderBy('orders_count', 'desc')
                        ->limit(5)
                        ->get()
                        ->map(function ($customer) {
                            return Customer::find($customer->id);
                        })
                        ->values();

                    return $query->map(function ($customer) use ($customerModel) {
                        return [
                            'id' => $customer->id(),
                            'email' => $customer->email(),
                            'edit_url' => cp_route('runway.edit', [
                                'resourceHandle' => \DoubleThreeDigital\Runway\Runway::findResourceByModel($customerModel)->handle(),
                                'record' => $customer->resource()->{$customerModel->getRouteKeyName()},
                            ]),
                            'orders_count' => $customer->orders()->count(),
                        ];
                    });
                }

                $query = User::all()
                    ->where('orders', '!=', null)
                    ->sortByDesc(function ($customer) {
                        return count($customer->get('orders', []));
                    })
                    ->take(5)
                    ->map(function ($user) {
                        return Customer::find($user->id());
                    })
                    ->values();

                return $query->map(function ($customer) {
                    return [
                        'id' => $customer->id(),
                        'email' => $customer->email(),
                        'edit_url' => cp_route('users.edit', [
                            'user' => $customer->id(),
                        ]),
                        'orders_count' => count($customer->get('orders', [])),
                    ];
                });
            },
        );

        static::registerWidget(
            'low-stock-products',
            [
                'name' => 'Low Stock Products',
                'component' => 'overview-low-stock-products',
            ],
            function (Request $request) {
                if (isset(SimpleCommerce::productDriver()['collection'])) {
                    $query = Collection::find(SimpleCommerce::productDriver()['collection'])
                        ->queryEntries()
                        ->where('stock', '<', config('simple-commerce.low_stock_threshold'))
                        ->orderBy('stock', 'asc')
                        ->get()
                        ->reject(function ($entry) {
                            return $entry->has('product_variants')
                                || ! $entry->has('stock');
                        })
                        ->take(5)
                        ->map(function ($entry) {
                            return Product::find($entry->id());
                        })
                        ->values();

                    return $query->map(function ($product) {
                        return [
                            'id' => $product->id(),
                            'title' => $product->get('title'),
                            'stock' => $product->stock(),
                            'edit_url' => $product->resource()->editUrl(),
                        ];
                    });
                }

                return null;
            },
        );


        //Turnover & profit
	    static::registerWidget(
		    'turnover-profit',
		    [
			    'name' => 'Turnover & profit',
			    'component' => 'overview-turnover-profit',
		    ],
		    function (Request $request) {

			    if (isset(SimpleCommerce::orderDriver()['model'])) {

				    $orderModel = new (\DoubleThreeDigital\SimpleCommerce\SimpleCommerce::orderDriver()['model']);

				    $query = $orderModel::without('customer')
					    ->whereState('state', [Approved::class, Shipped::class, Delivered::class])
					    ->whereMonth('created_at', Carbon::now()->month)
					    ->where('locale', Site::selected()->handle())
					    ->whereHas('orders', function($q){
						    $q->where('total_purchase_price', '>=', 0)->whereNotNull('total_purchase_price');
					    })
					    ->withSum('orders', 'total_purchase_price')->get();

				    $turnover = $query->sum(function ($record){
					    return $record->grand_total;
				    });

				    $profit = $query->sum(function ($record){
					    return $record->grand_total -  $record->orders_sum_total_purchase_price;
				    });

				    return [
				    	'current_month' =>  Carbon::now()->format('F'),
				    	'order_count' => $query->count(),
					    'turnover' => Currency::parse($turnover, Site::current()),
				    	'profit' => Currency::parse($profit, Site::current()),
				    ];
			    }

			    return null;
		    },
	    );
    }
}
