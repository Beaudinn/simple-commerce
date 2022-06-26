<?php

namespace DoubleThreeDigital\SimpleCommerce\Orders\States;

use DoubleThreeDigital\SimpleCommerce\Orders\OrderModel;
use Statamic\Facades\Blueprint as StatamicBlueprint;
use Webhoek\WebhoekShop\Models\Order;

class Approved extends OrderState
{


	public function label(): string
	{
		return 'Approved';
	}

	public function title(): string
	{
		return 'Approved';
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

	public function redirect($order, $values)
	{
		return false;
	}

	public function download($order, $values)
	{
		return false;
	}

	public function blueprint(OrderModel $order = null)
	{
		return StatamicBlueprint::make()->setContents([
				'sections' => [
					'main' => [
						'fields' => [
							[
								'handle' => 'send_notifications',
								'field' => [
									'type' => 'toggle',
									'width' => 100,
									'default' => true,
									'display' => __('Send notifications'),
									'instructions' => 'order confermation to customer ',
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

}
