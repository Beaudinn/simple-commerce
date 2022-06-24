<?php

namespace DoubleThreeDigital\SimpleCommerce\Orders\States;

use DoubleThreeDigital\SimpleCommerce\Orders\OrderModel;
use Statamic\Facades\Blueprint as StatamicBlueprint;
use Webhoek\WebhoekShop\Models\Order;

class Approved extends OrderState
{


	public function label(): string
	{
		return 'Productie';
	}

	public function title(): string
	{
		return 'Productie';
	}

	public function color(): string
	{
		return 'green';
	}

	public function progress(): int
	{
		return 2;
	}


	public function description(): string
	{
		return 'Je bestelling wordt geproduceerd en verpakt';
	}

	public function blueprint(OrderModel $order = null)
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
							[
								'handle' => 'send_confirmation_mail',
								'field' => [
									'type' => 'toggle',
									'width' => 100,
									'default' => true,
									'display' => __('Send mail'),
									'instructions' => 'Send order confirmation email',
									'validate' => 'required',
								],
							],
							[
								'handle' => 'create_supplier_order',
								'field' => [
									'type' => 'toggle',
									'width' => 100,
									'default' => true,
									'display' => __('Create supplier order'),
									'validate' => 'required',
								],
							],
						],
					]
				]
			]
		);
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
