<?php
namespace DoubleThreeDigital\SimpleCommerce\Orders\Transitions;

use DoubleThreeDigital\SimpleCommerce\Events\OrderApproved as OrderApprovedEvent;
use DoubleThreeDigital\SimpleCommerce\Events\QuoteCreated;
use DoubleThreeDigital\SimpleCommerce\Orders\Order;
use DoubleThreeDigital\SimpleCommerce\Orders\OrderModel;
use DoubleThreeDigital\SimpleCommerce\Orders\States\Approved;
use DoubleThreeDigital\SimpleCommerce\Orders\States\Quote;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Spatie\ModelStates\Transition;
use Webhoek\P4sSupplier\Events\ToShippedTransitionEvent;

class ToQuoteTransition extends Transition
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

		event(new QuoteCreated($this->order, $this->values));

		//$this->order->resource()->state = Approved::class; //Dont transition again
		//$this->order->resource()->save();

		$orderModel = $this->order->resource();
		$orderModel->state = Quote::class;

		$orderModel->reference = $this->values['reference'];
		$orderModel->expiration_date = $this->values['expiration_date'];
		$orderModel->note = $this->values['note'];

		$orderModel->save();

		//if($this->values['create_supplier_order']){
		//	//$this->createProboOrder();
		//}
		//
		////$this->order->state = new Purchased($this->supplier_order);
		//$this->order->set('order_number', $this->values['new_order_number']);
		//$this->order->save();
		//
		//event(new OrderApprovedEvent($this->order, $this->values));
		//
		//$this->order->resource()->state = Approved::class;
		//$this->order->resource()->save();

		return $orderModel;
	}

}



