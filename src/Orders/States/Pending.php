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
