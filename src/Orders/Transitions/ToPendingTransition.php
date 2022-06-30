<?php
namespace DoubleThreeDigital\SimpleCommerce\Orders\Transitions;

use Carbon\Carbon;
use DoubleThreeDigital\SimpleCommerce\Events\OrderPending as OrderPendingEvent;
use DoubleThreeDigital\SimpleCommerce\Orders\Order;
use DoubleThreeDigital\SimpleCommerce\Orders\OrderModel;
use DoubleThreeDigital\SimpleCommerce\Orders\States\Approved;
use DoubleThreeDigital\SimpleCommerce\Orders\States\Pending;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Spatie\ModelStates\Transition;

class ToPendingTransition extends Transition
{


	private \DoubleThreeDigital\SimpleCommerce\Orders\Order  $order;

	private array $values;

	public function __construct(OrderModel $order, array $values = [])
	{
		$order = \DoubleThreeDigital\SimpleCommerce\Facades\Order::find($order->id);

		$this->order = $order;

		$this->values = $values;


	}

	public function handle(): OrderModel
	{

		$orderModel = $this->order->resource();
		$orderModel->ordered_at = Carbon::now();
		if(isset($this->values['new_order_number'])){
			$orderModel->order_number = $this->values['new_order_number'];
		}

		event(new OrderPendingEvent($this->order, $this->values));

		$orderModel->state = Pending::class;
		$orderModel->save();

		return $orderModel;
	}


	protected function createProboOrder(){



	}
}



