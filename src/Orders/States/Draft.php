<?php

namespace DoubleThreeDigital\SimpleCommerce\Orders\States;

use DoubleThreeDigital\SimpleCommerce\Orders\OrderModel;
use Statamic\Facades\Blueprint as StatamicBlueprint;

class Concept extends OrderState
{
	public function label(): string
	{
		return 'Concept';
	}


	public function title(): string
	{
		return 'Concept';
	}

	public function color(): string
	{
		return 'gray';
	}

	public function description(): string
	{
		return 'Deze besteling is nog niet defenetief';
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
