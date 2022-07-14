<?php
namespace DoubleThreeDigital\SimpleCommerce\Orders\Transitions;

use DoubleThreeDigital\SimpleCommerce\Events\OrderApproved as OrderApprovedEvent;
use DoubleThreeDigital\SimpleCommerce\Orders\Order;
use DoubleThreeDigital\SimpleCommerce\Orders\OrderModel;
use DoubleThreeDigital\SimpleCommerce\Orders\States\Approved;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Spatie\ModelStates\Transition;

class ToApprovedTransition extends Transition
{


	private \DoubleThreeDigital\SimpleCommerce\Orders\Order  $order;

	private array $values;

	public function __construct(OrderModel $order, array $values = [])
	{
		$order = \DoubleThreeDigital\SimpleCommerce\Facades\Order::find($order->id, true);

		$this->order = $order;

		$this->values = $values;


	}

	public function handle()
	{


		if(isset($this->values['new_order_number'])){
			$this->order->set('order_number', $this->values['new_order_number']);
		}
		$this->order->save();

		event(new OrderApprovedEvent($this->order, $this->values));


		$this->order->resource()->state = Approved::class; //Dont transition again
		$this->order->resource()->save();

		$this->order = $this->order->fresh();


		return $this->order->resource();
		return 'Order is approved';
	}

}



