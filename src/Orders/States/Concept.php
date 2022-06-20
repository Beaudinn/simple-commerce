<?php

namespace DoubleThreeDigital\SimpleCommerce\Orders\States;

use DoubleThreeDigital\SimpleCommerce\Orders\OrderModel;
use Statamic\Facades\Blueprint as StatamicBlueprint;

class Concept extends OrderState
{
	public function label(): string
	{
		return 'Besteld';
	}


	public function title(): string
	{
		return 'Besteld';
	}

	public function color(): string
	{
		return 'gray';
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
					],
				],
			],

		]);
	}

}
