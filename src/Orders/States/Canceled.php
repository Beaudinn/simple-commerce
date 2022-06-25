<?php

namespace DoubleThreeDigital\SimpleCommerce\Orders\States;

use Statamic\Facades\Blueprint as StatamicBlueprint;

class Canceled extends OrderState
{
	public function label(): string
	{
		return 'Canceled';
	}

	public function title(): string
	{
		return 'Canceled';
	}

	public function color(): string
	{
		return 'red';
	}

	public function description(): string
	{
		return 'Je bestelling is geannuleerd';
	}

	public function progress(): int
	{
		return 0;
	}

	public function redirect($order, $values)
	{
		return false;
	}

	public function download($order, $values)
	{
		return false;
	}

	public function blueprint()
	{
		return StatamicBlueprint::make()->setContents([
				'sections' => [
					'main' => [
						'fields' => [
							[
								'handle' => 'reason',
								'field' => [
									'type' => 'bard',
									'width' => 100,
									'display' => __('Reason'),
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
