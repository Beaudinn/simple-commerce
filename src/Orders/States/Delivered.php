<?php

namespace DoubleThreeDigital\SimpleCommerce\Orders\States;

use DoubleThreeDigital\SimpleCommerce\Orders\OrderModel;
use Statamic\Facades\Blueprint as StatamicBlueprint;

class Delivered extends OrderState
{
	public function label(): string
	{
		return 'Bezorgd';
	}


	public function title(): string
	{
		return 'Bezorgd';
	}

	public function color(): string
	{
		return 'green';
	}

	public function progress(): int
	{
		return 4;
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
		return 'Je bestelling is bezorgd';
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
