<?php

namespace DoubleThreeDigital\SimpleCommerce\Gateways\Builtin;

use DoubleThreeDigital\SimpleCommerce\Contracts\Gateway;
use DoubleThreeDigital\SimpleCommerce\Contracts\Order;
use DoubleThreeDigital\SimpleCommerce\Facades\Order as OrderFacade;
use DoubleThreeDigital\SimpleCommerce\Gateways\BaseGateway;
use DoubleThreeDigital\SimpleCommerce\Gateways\Prepare;
use DoubleThreeDigital\SimpleCommerce\Gateways\Purchase;
use DoubleThreeDigital\SimpleCommerce\Gateways\Response;
use Illuminate\Http\Request;
use Statamic\Facades\Site;

class PostPaymentGateway extends BaseGateway implements Gateway
{
    public function name(): string
    {
        return 'Post payment';
    }

    public function prepare(Prepare $data): Response
    {
        return new Response(true, []);
    }

    public function purchase(Purchase $data): Response
    {
       // $this->markOrderAsPaid($data->order());

        return new Response(true, [

        ]);
    }

    public function purchaseRules(): array
    {
        return [

        ];
    }

    public function getCharge(Order $entry): Response
    {
        return new Response(true, [

        ]);
    }

    public function refundCharge(Order $entry): Response
    {
        return new Response(true, []);
    }

    public function webhook(Request $request)
    {
        return null;
    }

	public function callback(Request $request): bool
    {

	    $order = OrderFacade::find($request->get('_order_id'));

	    $order->setPendingState();
	    return true;
    }



    public function paymentDisplay($value): array
    {
        return [
            'text' => $this->name(),
            'url' => null,
        ];
    }
}
