<?php

namespace DoubleThreeDigital\SimpleCommerce\Orders\States;

use DoubleThreeDigital\SimpleCommerce\Orders\OrderModel;
use Statamic\Facades\Blueprint as StatamicBlueprint;

class Shipped extends OrderState
{
	public function label(): string
	{
		return 'Verzonden';
	}

	public function title(): string
	{
		return 'Verzonden';
	}

	public function color(): string
	{
		return 'green';
	}

	public function progress(): int
	{
		return 3;
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
		return 'Je bestelling is overgedragen aan de bezorgdienst'; //Je bestelling is onderweg naar de afhaallocatie
	}

	public function blueprint(OrderModel $order = NULL)
	{
		return StatamicBlueprint::make()->setContents([
			'sections' => [
				'main' => [
					'fields' => [
					],
				],
			],

		]);
	}
}
