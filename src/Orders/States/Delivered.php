<?php

namespace DoubleThreeDigital\SimpleCommerce\Orders\States;

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


	public function description(): string
	{
		return 'Je bestelling is bezorgd';
	}
}
