<?php

namespace DoubleThreeDigital\SimpleCommerce\Orders\States;

use DoubleThreeDigital\SimpleCommerce\Orders\OrderModel;
use Statamic\Facades\Blueprint as StatamicBlueprint;

class Draft extends OrderState
{
	public function label(): string
	{
		return 'Draft';
	}


	public function title(): string
	{
		return 'Draft';
	}

	public function color(): string
	{
		return 'gray';
	}

	public function progress(): int
	{
		return 0;
	}

	public function description(): string
	{
		return 'Deze besteling is nog niet definitief';
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
