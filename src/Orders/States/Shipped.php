<?php

namespace DoubleThreeDigital\SimpleCommerce\Orders\States;

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

	public function description(): string
	{
		return 'Je bestelling is overgedragen aan de bezorgdienst'; //Je bestelling is onderweg naar de afhaallocatie
	}
}
