<?php

namespace DoubleThreeDigital\SimpleCommerce\Orders\States;

use DoubleThreeDigital\SimpleCommerce\Facades\Order;
use DoubleThreeDigital\SimpleCommerce\Orders\OrderModel;
use Statamic\Facades\Blueprint as StatamicBlueprint;

class Pending extends OrderState
{
	public function label(): string
	{
		return 'Pending';
	}


	public function title(): string
	{
		return 'Pending';
	}

	public function color(): string
	{
		return 'blue';
	}

	public function progress(): int
	{
		return 1;
	}

	public function redirect($order, $values)
	{
		return false;
	}

	public function download($order, $values)
	{
		return false;
	}

	public function description(): string
	{
		return 'Je bestelling is geplaatst en wordt voorbereid';
	}

	public function blueprint(OrderModel $order = NULL)
	{
		$order = \DoubleThreeDigital\SimpleCommerce\Facades\Order::find($order->id, true);

		return StatamicBlueprint::make()->setContents([
			'sections' => [
				'main' => [
					'fields' => [
						[
							'handle' => 'new_order_number',
							'field' => [
								'type' => 'text',
								'width' => 100,
								'display' => __('Order number'),
								'validate' => 'required',
								'default' => Order::createOrderNumber($order),
							],
						],
					],
				],
			],

		]);
	}


}
