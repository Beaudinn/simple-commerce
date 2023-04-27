<?php

namespace DoubleThreeDigital\SimpleCommerce\Http\Controllers\CP;

use Carbon\Carbon;
use DoubleThreeDigital\SimpleCommerce\Currency;
use DoubleThreeDigital\SimpleCommerce\Http\Requests\CP\OverviewRequest;
use DoubleThreeDigital\SimpleCommerce\Orders\States\Approved;
use DoubleThreeDigital\SimpleCommerce\Orders\States\Delivered;
use DoubleThreeDigital\SimpleCommerce\Orders\States\Shipped;
use DoubleThreeDigital\SimpleCommerce\Overview;
use DoubleThreeDigital\SimpleCommerce\SimpleCommerce;
use Illuminate\Http\Request;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;

class OverviewController
{
    public function index(OverviewRequest $request)
    {
        if ($request->wantsJson()) {
            $data = collect($request->get('widgets'))
                ->mapWithKeys(function ($widgetHandle) use ($request) {
                    $widget = Overview::widget($widgetHandle);

                    return [
                        $widgetHandle => $widget['callback']($request),
                    ];
                })
                ->toArray();

            return ['data' => $data];
        }

        $showEntriesWarning = $request->user()->isSuper()
            && isset(SimpleCommerce::orderDriver()['collection'])
            && Collection::find(SimpleCommerce::orderDriver()['collection'])->queryEntries()->count() > 5000;


        return view('simple-commerce::cp.overview', [
            'widgets' => Overview::widgets(),
            'showEntriesWarning' => $showEntriesWarning,
        ]);
    }

    public function turnoverProfit(Request $request){
	    $orderModel = new (\DoubleThreeDigital\SimpleCommerce\SimpleCommerce::orderDriver()['model']);

	    $query = $orderModel::without('customer')
		    ->whereState('state', [Approved::class, Shipped::class, Delivered::class])
		    ->whereBetween('created_at', [$request->get('range')['start'], $request->get('range')['end']])
		    ->where('locale', Site::selected()->handle())
		    ->withSum('orders', 'total_purchase_price')->get();

	    //->whereHas('orders', function($q){
	    //    $q->where('total_purchase_price', '>', 0);
	    //})

		$profit = $query->sum(function ($record){
			return $record->profit;
		});

	    $turnover = $query->sum(function ($record){
		    return $record->total;
	    });

	    //$profit = $query->sum(function ($record){
		//    if(!$record->orders_sum_total_purchase_price)
		//	    return 0;
		//
		//    return $record->total -  $record->orders_sum_total_purchase_price;
	    //});

	    return [
		    'order_count' => $query->count(),
		    'turnover' => Currency::parse($profit, Site::current()),
		    'profit' => Currency::parse($profit, Site::current()),
	    ];
    }
}
