<?php

namespace DoubleThreeDigital\SimpleCommerce\Orders\States;

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
								'default' => $this->createOrderNumber($order),
							],
						],
					],
				],
			],

		]);
	}

	/**
	 * Create an order number.
	 */
	protected function createOrderNumber($orderModal): string
	{
		$order = \DoubleThreeDigital\SimpleCommerce\Facades\Order::find($orderModal->id);
		$site = $order->site();
		$prefix = $site->attributes()['order_number_prefix'];
		$number = $site->attributes()['order_number_range'];


		if (!empty($number)) {
			do {
				$number++;

				$count = OrderModel::where('order_number', $prefix . $number)->count();

			} while ($count);
		}

		return $prefix . $number;
	}
}
