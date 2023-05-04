<?php
namespace DoubleThreeDigital\SimpleCommerce\Orders\Transitions;

use Carbon\Carbon;
use DoubleThreeDigital\SimpleCommerce\Events\OrderPending as OrderPendingEvent;
use DoubleThreeDigital\SimpleCommerce\Orders\Order;
use DoubleThreeDigital\SimpleCommerce\Orders\OrderModel;
use DoubleThreeDigital\SimpleCommerce\Orders\States\Approved;
use DoubleThreeDigital\SimpleCommerce\Orders\States\Pending;
use DoubleThreeDigital\SimpleCommerce\Products\Product;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\ModelStates\Transition;
use Statamic\Facades\Site;

class ToPendingTransition extends Transition
{


	private \DoubleThreeDigital\SimpleCommerce\Orders\Order  $order;

	private array $values;

	public function __construct(OrderModel $order, array $values = [])
	{
		$order = \DoubleThreeDigital\SimpleCommerce\Facades\Order::find($order->id, true);

		$this->order = $order;

		$this->values = $values;


	}

	public function handle(): OrderModel
	{


		$orderModel = $this->order->resource();

		if($orderModel->state == Pending::class){
			return $orderModel;
		}

		$orderModel->ordered_at = Carbon::now();
		if(isset($this->values['new_order_number'])){
			$orderModel->order_number = $this->values['new_order_number'];
		}

		$site = Site::get($orderModel->locale);
		$prefix = $site->attributes()['order_number_prefix'];
		$year = $orderModel->created_at->format('Y');
		$original_path = "$prefix/01 Orders/$year/$orderModel->order_number";
		if (! Storage::disk('pf4')->exists($original_path)) {
			Storage::disk('pf4')->makeDirectory($original_path);
		}

		$orderModel->items = collect($orderModel->items)->each(function ($item, $key) use ($original_path){

			$product = \DoubleThreeDigital\SimpleCommerce\Facades\Product::find($item['product']);
			$item['initial'] = isset($item['initial']) ? $item['initial'] : "*";
			$sufix = str_replace(' ', '_', $item['initial']);
			$number = str_pad($key, 2, '0', STR_PAD_LEFT);
			$product_title = strtolower($product->get('title'));
			$path = "$original_path/{$number}-{$product_title}-$sufix-{$item['quantity']}x";

			if(isset($item['uploader'], $item['uploader']['products'])){
				foreach ($item['uploader']['products'] as $productKey => $productFile){

					$number = str_pad($productKey, 2, '0', STR_PAD_LEFT);

					if(isset($productFile['front_side'], $productFile['front_side']['file']['original_file_url'])){
						$side = $productFile['front_side']['file'];
						//'https://s3-eu-west-1.amazonaws.com/proboprodbucket/
						$url = "https://s3-eu-west-1.amazonaws.com/proboprodbucket/".$side['original_file_url'];
						$_PATH_INFO = pathinfo($url);

						$_EXTENSTION = $_PATH_INFO['extension'];

						$contents = file_get_contents($url);
						//$side['file_name']
						$file_path = "{$number}-{$product_title}-$sufix-{$item['quantity']}x-{$productFile['amount']}x.{$_EXTENSTION}";
						Storage::disk('pf4')->put("$path/{$file_path}", $contents);
					}

					if(isset($productFile['back_side'], $productFile['back_side']['file']['original_file_url'])){
						$side = $productFile['back_side']['file'];
						//'https://s3-eu-west-1.amazonaws.com/proboprodbucket/
						$url = "https://s3-eu-west-1.amazonaws.com/proboprodbucket/".$side['original_file_url'];
						$_PATH_INFO = pathinfo($url);

						$_EXTENSTION = $_PATH_INFO['extension'];

						$contents = file_get_contents($url);
						$file_path = "{$number}-{$product_title}-$sufix-{$item['quantity']}x-{$productFile['amount']}x.{$_EXTENSTION}";
						Storage::disk('pf4')->put("$path/{$file_path}", $contents);
					}
				}
			}

			return $item;
		});

		//Storage::disk('orders')->url($packing_slip_path)

		event(new OrderPendingEvent($this->order, $this->values));

		$orderModel->state = Pending::class;
		$orderModel->save();

		return $orderModel;
	}
}



