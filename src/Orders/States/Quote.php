<?php

namespace DoubleThreeDigital\SimpleCommerce\Orders\States;

use DoubleThreeDigital\SimpleCommerce\Orders\OrderModel;
use Statamic\Facades\Blueprint as StatamicBlueprint;

class Quote extends OrderState
{
	public function label(): string
	{
		return 'Quote';
	}


	public function title(): string
	{
		return 'Quotation';
	}

	public function color(): string
	{
		return '#FF65FF';
	}

	public function progress(): int
	{
		return 0;
	}


	public function description(): string
	{
		return 'Quotation';
	}

	public function redirect($order, $values)
	{
		return false;
	}

	public function download($order, $values)
	{
		return false;
	}

	public function blueprint(OrderModel $order = NULL)
	{
		return StatamicBlueprint::make()->setContents([
			'sections' => [
				'main' => [
					'fields' => [
						[
							'handle' => 'send_notifications',
							'field' => [
								'type' => 'toggle',
								'width' => 100,
								'default' => true,
								'display' => __('Send notifications'),
								'instructions' => 'Send quote email to customer including url to quote',
								'validate' => 'required',
							],
						],
						[
							'handle' => 'reference',
							'field' => [
								'type' => 'text',
								'width' => 50,
								'display' => __('Reference'),
								'validate' => 'required',
							],
						],
						[
							'handle' => 'expiration_date',
							'field' => [
								'type' => 'date',
								'inline' => true,
								'width' => 50,
								'display' => __('Expiration date'),
							],
						],
						[
							'handle' => 'note',
							'field' => [
								'type' => 'textarea',
								'width' => 100,
								'default' => '* Let op: Levertijd en verzendkosten in overleg \n * Prijzen alleen geldig voor deze aantallen en formaten, afname in één keer  \n * Bij offertes groter dan €5000,-, vragen wij een aanbetaling van minimaal 30% \n * Deze offerte is gebaseerd op de aantallen en informatie die jullie aan ons hebben doorgegeven, in geval van wijzigingen zal dit gezien worden als een nieuwe aanvraag..',
								'display' => __('Note'),
							],
						],
					],
				],
			],

		]);
	}

}
