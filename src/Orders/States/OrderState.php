<?php

namespace DoubleThreeDigital\SimpleCommerce\Orders\States;

use DoubleThreeDigital\SimpleCommerce\Orders\Transitions\ToApprovedTransition;
use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

abstract class OrderState extends State
{

	abstract public function label(): string; //for backend use

	abstract public function title(): string; //for frondend use

	abstract public function color(): string;


	abstract public function description(): string;


	public static function config(): StateConfig
	{
		return parent::config()
			->default(Concept::class)
			->allowTransition(Approved::class, Concept::class) // for testing
			->allowTransition(Concept::class, Canceled::class)
			->allowTransition([Concept::class, Canceled::class], Approved::class, ToApprovedTransition::class)
			->allowTransition(Approved::class, Shipped::class)
			->allowTransition(Approved::class,  Delivered::class);
	}
}

