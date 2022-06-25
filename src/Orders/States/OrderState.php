<?php

namespace DoubleThreeDigital\SimpleCommerce\Orders\States;

use DoubleThreeDigital\SimpleCommerce\Orders\Transitions\ToPendingTransition;
use DoubleThreeDigital\SimpleCommerce\Orders\Transitions\ToQuoteTransition;
use DoubleThreeDigital\SimpleCommerce\Orders\Transitions\ToApprovedTransition;
use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

abstract class OrderState extends State
{

	abstract public function label(): string; //for backend use

	abstract public function title(): string; //for frondend use

	//https://guides.interactivebrokers.com/tws/usersguidebook/realtimeactivitymonitoring/order_status_colors.htm?TocPath=Real-Time%20Activity%20Monitoring%7CUnderstanding%20System%20Colors%7C_____4
	abstract public function color(): string;


	abstract public function description(): string;


	abstract public function progress(): int;


	abstract public function redirect($order, $values);

	abstract public function download($order, $values);

	public static function config(): StateConfig
	{
		return parent::config()
			->default(Draft::class)
			->allowTransition(Draft::class, Quote::class, ToQuoteTransition::class)
			->allowTransition([Concept::class, Quote::class ], Draft::class)// draft
			->allowTransition(Concept::class, Draft::class)// draft
			->allowTransition(Draft::class, Concept::class)// draft
			->allowTransition(Draft::class, Pending::class,ToPendingTransition::class)
			->allowTransition(Approved::class, Pending::class)
			->allowTransition(Approved::class, Pending::class) // for testing
			->allowTransition(Pending::class, Canceled::class)
			->allowTransition([Pending::class, Canceled::class], Approved::class, ToApprovedTransition::class)
			->allowTransition(Approved::class, Shipped::class)
			->allowTransition(Approved::class,  Delivered::class);
	}
}

