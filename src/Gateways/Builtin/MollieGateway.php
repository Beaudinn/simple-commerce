<?php

namespace DoubleThreeDigital\SimpleCommerce\Gateways\Builtin;

use DoubleThreeDigital\SimpleCommerce\Contracts\Gateway;
use DoubleThreeDigital\SimpleCommerce\Contracts\Order;
use DoubleThreeDigital\SimpleCommerce\Currency;
use DoubleThreeDigital\SimpleCommerce\Exceptions\OrderNotFound;
use DoubleThreeDigital\SimpleCommerce\Exceptions\PayPalDetailsMissingOnOrderException;
use DoubleThreeDigital\SimpleCommerce\Facades\Order as OrderFacade;
use DoubleThreeDigital\SimpleCommerce\Gateways\BaseGateway;
use DoubleThreeDigital\SimpleCommerce\Gateways\Prepare;
use DoubleThreeDigital\SimpleCommerce\Gateways\Response;
use DoubleThreeDigital\SimpleCommerce\Products\ProductType;
use DoubleThreeDigital\SimpleCommerce\SimpleCommerce;
use DoubleThreeDigital\SimpleCommerce\Tax\Standard\TaxEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\MethodCollection;
use Mollie\Api\Types\PaymentStatus;
use PayPalCheckoutSdk\Orders\OrdersGetRequest;
use Statamic\Facades\Site;
use Statamic\Statamic;

class MollieGateway extends BaseGateway implements Gateway
{
	protected $mollie;

	public function name(): string
	{
		return 'Mollie';
	}

	public function prepare(Prepare $data): Response
	{
		$this->setupMollie();

		$order = $data->order();

		$payment = $this->mollie->payments->create([
			'amount' => [
				'currency' => Currency::get(Site::current())['code'],
				'value'    => (string) substr_replace($order->grandTotal(), '.', -2, 0),
			],
			'description' => "Order {$order->get('title')}",
			'redirectUrl' => $this->callbackUrl([
				'_order_id' => $data->order()->id(),
			]),
			'method' => $data->request()['method'] ?? null,
			'webhookUrl'  => $this->webhookUrl(),
			'metadata'    => [
				'order_id' => $order->id,
			],
		]);
		//
		//$payment = $this->mollie->orders->create([
		//	'amount' => [
		//		'currency' => Currency::get(Site::current())['code'],
		//		'value'    =>  (string) $this->mollieAmount($order->grandTotal() / 100),
		//	],
		//	'billingAddress' => [
		//		'givenName' => $order->get('billing_first_name') ? $order->get('billing_first_name') : 'Niet bekent',
		//		'familyName' => $order->get('billing_last_name') ? $order->get('billing_last_name') : 'Niet bekent',
		//		'streetAndNumber' => $order->get('billing_street'). ', ' .$order->get('billing_house_number') . $order->get('billing_addition'),
		//		'city' => $order->get('billing_city'),
		//		'postalCode' => $order->get('billing_postal_code'),
		//		'country' => $order->get('billing_country'),
		//		'email' => $order->customer()->email(),
		//	],
		//	'shippingAddress' => [
		//		'givenName' => $order->get('shipping_first_name') ? $order->get('shipping_first_name') : 'Niet bekent',
		//		'familyName' => $order->get('shipping_last_name') ? $order->get('shipping_last_name') : 'Niet bekent',
		//		'streetAndNumber' => $order->get('shipping_street'). ', ' .$order->get('shipping_house_number') . $order->get('shipping_addition'),
		//		'city' => $order->get('shipping_city'),
		//		'postalCode' => $order->get('shipping_postal_code'),
		//		'country' => $order->get('shipping_country'),
		//		'email' => $order->customer()->email(),
		//	],
		//	//metadata
		//	'orderNumber' => "18475",
		//	'locale' => Site::current()->locale(),
		//	'lines' => $this->mapItems($order),
		//	//'description' => "Order {$order->get('title')}",
		//	'redirectUrl' => $this->callbackUrl([
		//		'_order_id' => $data->order()->id(),
		//	]),
		//	'webhookUrl'  => $this->webhookUrl(),
		//	'metadata'    => [
		//		'order_id' => $order->id,
		//	],
		//]);

		return new Response(true, [
			'id' => $payment->id,
		], $payment->getCheckoutUrl());
	}

	/**
	 * The line items to need to be send in a specific format.
	 *
	 * https://docs.mollie.com/reference/v2/orders-api/create-order#order-lines-details
	 */
	private function mapItems($order): array
	{
		//Examples
		//https://github.com/mollie/Shopware/blob/26e43071bf15cc4aa33569aba2a0d8e87b4bf4b1/Components/TransactionBuilder/Services/ItemBuilder/TransactionItemBuilder.php
		//https://github.com/QualityWorks/mollie/blob/62e9461585de86baa534b1dd26c1f072e02fb656/catalog/controller/payment/mollie.php

		$items = $order->lineItems()->map(function ($item) use($order) {

			$name = $item->product()->toAugmentedArray()['title']->raw();

			if($item->product()->purchasableType() == ProductType::PROBO()){
				$name = $name . ' ' . $item->initial();
			}

			//TODO make dynamic
			$taxRate =  21;

			# this line is from the Mollie API
			# it tells us how the vat amount has to be calculated
			# https://docs.mollie.com/reference/v2/orders-api/create-order
			$totalAmount = $item->total() / 100;
			$vatAmount = $totalAmount * ($taxRate / ($taxRate + 100));
			# also round in the end!
			//$vatAmount = round($vatAmount, 2);

			//$item->tax()
			/*array(3) {
			  ["amount"]=>
			  int(2577)
			  ["rate"]=>
			  int(21)
			  ["price_includes_tax"]=>
			  bool(false)
			}
			*/

			$tax = (new TaxEngine)->calculate($order, $item->toArray());
			var_dump($tax); die();
			return [
				'type' => 'physical',
				'sku' => $item->product()->id(),
				//'productUrl' => $item->product()->toAugmentedArray()['url']->raw(),
				'name' => $name,
				'imageUrl' => isset($item->product()->toAugmentedArray()['image']) ? url($item->product()->toAugmentedArray()['image']) : NULL,
				'quantity' => $item->quantity(),
				'vatRate' =>  (string) "21.00",
				'unitPrice' => [
					'currency' => Currency::get(Site::current())['code'],
					'value' => (string) $this->mollieAmount(($item->total() /100) / $item->quantity()),
				],
				'totalAmount' => [
					'currency' => Currency::get(Site::current())['code'],
					'value' => (string)$this->mollieAmount(($item->total() /100)),
				],
				'vatAmount' => [
					'currency' => Currency::get(Site::current())['code'],
					'value' =>(string) $this->mollieAmount($item->tax()['amount']),
				],
			];
		});


		//if ($rushLine) {
		//	$items->push([
		//		'type' => 'shipping_fee',
		//		'name' => 'RUSHPRICE'. $rushLine['production_hours'],
		//		'quantity' => 1,
		//		'vatRate' => 21.00,
		//		'unitPrice' => [
		//			'currency' => config('shop.currency_isoCode'),
		//			'value' => $this->mollieAmount($rushLine['sales_price_incl_vat']),
		//		],
		//		'totalAmount' => [
		//			'currency' => config('shop.currency_isoCode'),
		//			'value' => $this->mollieAmount($rushLine['sales_price_incl_vat']),
		//		],
		//		'vatAmount' => [
		//			'currency' => config('shop.currency_isoCode'),
		//			'value' => $this->mollieAmount($rushLine['sales_price_incl_vat'] - $rushLine['sales_price']),
		//		],
		//	]);
		//}



		$items = $items->toArray();
		return $items;
		return $this->addShippingToLineItems($items, $shippings);
	}

	private function mollieAmount(string $value): string
	{
		$value = str_replace(',', '.', $value);

		return number_format($value, 2, '.', '');
	}
	public function getCharge(Order $order): Response
	{
		$this->setupMollie();

		$payment = $this->mollie->payments->get($order->gateway()['data']['id']);

		return new Response(true, [
			'id'                              => $payment->id,
			'mode'                            => $payment->mode,
			'amount'                          => $payment->amount,
			'settlementAmount'                => $payment->settlementAmount,
			'amountRefunded'                  => $payment->amountRefunded,
			'amountRemaining'                 => $payment->amountRemaining,
			'description'                     => $payment->description,
			'method'                          => $payment->method,
			'status'                          => $payment->status,
			'createdAt'                       => $payment->createdAt,
			'paidAt'                          => $payment->paidAt,
			'canceledAt'                      => $payment->canceledAt,
			'expiresAt'                       => $payment->expiresAt,
			'failedAt'                        => $payment->failedAt,
			'profileId'                       => $payment->profileId,
			'sequenceType'                    => $payment->sequenceType,
			'redirectUrl'                     => $payment->redirectUrl,
			'webhookUrl'                      => $payment->webhookUrl,
			'mandateId'                       => $payment->mandateId,
			'subscriptionId'                  => $payment->subscriptionId,
			'orderId'                         => $payment->orderId,
			'settlementId'                    => $payment->settlementId,
			'locale'                          => $payment->locale,
			'metadata'                        => $payment->metadata,
			'details'                         => $payment->details,
			'restrictPaymentMethodsToCountry' => $payment->restrictPaymentMethodsToCountry,
			'_links'                          => $payment->_links,
			'_embedded'                       => $payment->_embedded,
			'isCancelable'                    => $payment->isCancelable,
			'amountCaptured'                  => $payment->amountCaptured,
			'authorizedAt'                    => $payment->authorizedAt,
			'expiredAt'                       => $payment->expiredAt,
			'customerId'                      => $payment->customerId,
			'countryCode'                     => $payment->countryCode,
		]);
	}

	public function refundCharge(Order $order): Response
	{
		$this->setupMollie();

		$payment = $this->mollie->payments->get($order->gateway()['data']['id']);
		$payment->refund([]);

		return new Response(true, []);
	}

	public function webhook(Request $request)
	{
		$this->setupMollie();
		$mollieId = $request->get('id');

		$payment = $this->mollie->payments->get($mollieId);

		if ($payment->status === PaymentStatus::STATUS_PAID) {
			$order = null;

			if (isset(SimpleCommerce::orderDriver()['collection'])) {
				// TODO: refactor this query
				$order = collect(OrderFacade::all())
					->filter(function ($entry) use ($mollieId) {
						return isset($entry->data()->get('mollie')['id'])
							&& $entry->data()->get('mollie')['id']
							=== $mollieId;
					})
					->map(function ($entry) {
						return OrderFacade::find($entry->id());
					})
					->first();
			}

			if (isset(SimpleCommerce::orderDriver()['model'])) {
				$order = (new (SimpleCommerce::orderDriver()['model']))
					->query()
					->where('data->mollie->id', $mollieId)
					->first();

				$order = OrderFacade::find($order->id);
			}

			if (! $order) {
				throw new OrderNotFound("Order related to Mollie transaction [{$mollieId}] could not be found.");
			}

			if ($order->isPaid() === true) {
				return;
			}

			$this->markOrderAsPaid($order);
		}
	}

	public function isOffsiteGateway(): bool
	{
		return true;
	}

	public function paymentDisplay($value): array
	{

		if (! isset($value['data']['id'])) {
			return ['text' => 'Unknown', 'url' => null];
		}

		$this->setupMollie();

		$molliePayment = $value['data']['id'];
		$mollieOrganisation = Cache::get('SimpleCommerce::MollieGateway::OrganisationId');

		return [
			'text' => $molliePayment,
			'url' => "https://www.mollie.com/dashboard/{$mollieOrganisation}/payments/{$molliePayment}",
		];
	}


	public function orderDisplay($value): array
	{

		if (! isset($value['data']['id'])) {
			return [];
		}

		$molliePayment = $value['data']['id'];

		return [
			'PaymentMethod' => 'ideal', //TODO get payment method from data
			'TransactionID' => $molliePayment,
		];
	}

	protected function setupMollie()
	{
		$this->mollie = new MollieApiClient();
		$sites = $this->config()->get("sites");
		$this->mollie->setApiKey($sites[Site::current()->handle()]['key']);

		$this->mollie->addVersionString('Statamic/' . Statamic::version());
		$this->mollie->addVersionString('SimpleCommerce/' . SimpleCommerce::version());

		Cache::rememberForever('SimpleCommerce::MollieGateway::OrganisationId', function () {
			$currentProfile = $this->mollie->profiles->getCurrent();

			$profileDashboardUrl = $currentProfile->_links->dashboard->href;

			return explode('/', parse_url($profileDashboardUrl, PHP_URL_PATH))[2];
		});
	}

	public function methods($data): MethodCollection
	{
		$order = $data->order();

		$this->setupMollie();

		return $this->mollie->methods->allActive( $order->grandTotal() ? ['amount' => [
			'currency' => Currency::get(Site::current())['code'],
			'value' => (string)substr_replace( $order->grandTotal(), '.', -2, 0),
		]] : []);

	}

	public function callback(Request $request): bool
	{
		$this->setupMollie();

		$order = OrderFacade::find($request->get('_order_id'));

		if (! $order) {
			return false;
		}

		$moloieOrderId = $order->get('mollie')['id'];

		if (! $moloieOrderId) {
			throw new PayPalDetailsMissingOnOrderException("Order [{$order->id()}] does not have a Mollie Order ID.");
		}

		$payment = $this->mollie->payments->get($moloieOrderId);

		switch ($payment->status)  {
			case 'paid':
				$this->markOrderAsPaid($order);
				//$this->isPaid($order, Carbon::parse($payment->paidAt), $payment->method);
				return true;
				break;
			case 'authorized':
				$order->setPendingState();
				return true;
				//$this->isAuthorized($order, Carbon::parse($payment->authorizedAt), $payment->method);
				break;
			case 'completed':
				$order->setPendingState();
				return true;
				//$this->isCompleted($order, Carbon::parse($payment->completedAt), $payment->method);
				break;
			case 'expired':
				return false;
				//$this->isExpired($order, Carbon::parse($payment->expiredAt));
				break;
			case 'canceled':
				return false;
				//$this->isCanceled($order, Carbon::parse($payment->canceledAt));
				break;
		}

	}
}
