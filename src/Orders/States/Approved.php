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

	public function blueprint(OrderModel $order = NULL)
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
							[
								'handle' => 'invoice_section',
								'field' => [
									'type' => 'section',
									'width' => 100,
									'display' => __('Invoice'),
								],
							],
							[
								'handle' => 'create_invoice',
								'field' => [
									'type' => 'toggle',
									'width' => 33,
									'default' => true,
									'display' => __('Create invoice'),
									'validate' => 'required',
								],
							],
							[
								'handle' => 'status',
								'field' => [
									'display' => 'Status',
									'type' => 'select',
									'max_items' => 1,
									'width' => 33,
									'options' => [
										0 => 'Concept',
										2 => 'Wacht op betaling',
										3 => 'Deels betaald',
										4 => 'Betaald',
										8 => 'Creditfactuur',
									],
									'validate' => 'required',
									'if' => [
										'create_invoice' => 'is true',
									],
								],
							],
							[
								'handle' => 'send',
								'field' => [
									'display' => 'Send invoice mail to customer',
									'type' => 'toggle',
									'default' => true,
									'width' => 33,
									'if' => [
										'status' => 'contains_any 2, 3, 8',
									],
								],
							],
						],
					],
				],
			]
		);
	}

}
