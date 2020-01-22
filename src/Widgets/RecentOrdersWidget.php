<?php

namespace DoubleThreeDigital\SimpleCommerce\Widgets;

use DoubleThreeDigital\SimpleCommerce\Models\Order;
use Statamic\Widgets\Widget;

class RecentOrdersWidget extends Widget
{
    public function html()
    {
        $orders = Order::all()
            ->sortByDesc('created_at')
            ->take(5);

        return view('commerce::widgets.recent-orders', [
            'orders' => $orders,
        ]);
    }
}