@extends('email.layouts.base')

@php
    $site = $order->site();
@endphp

@section('subsubject')
    Bedankt voor je bestelling!
@endsection
@section('email-logo')
    <div style="margin-bottom: 24px">
        <a href="{{ url('/') }}" style="color: #0047c3; text-decoration: none">
            <img src="{{ url($site->attributes()['logo_png']) }}" alt="{{ $site->attributes()['name'] }}" width="119" style="border: 0; max-width: 100%; line-height: 100%; vertical-align: middle">
        </a>
    </div>
@endsection
@section('email-body')
    <p style="font-size: 21px; line-height: 28px; margin: 0; font-weight: 700; color: #4a5566">{{ $order->customer()->name() }}, bedankt voor je bestelling! 💳</p>
    <p style="font-size: 19px; line-height: 28px; margin: 0; color: #4a5566">
        {!! trans('strings.notification.order.confirmation.body', ['link' => '', 'class' => 'text-brand-500 hover:underline', 'order_number' => $order->orderNumber()]) !!}
    </p>
    {{--Zodra de bestelling verzonden is, krijg je een verzendbevestiging--}}
    <div style="line-height: 32px">&nbsp;</div>
    <p style="font-size: 16px; line-height: 22px; margin-top: 0; margin-bottom: 16px; color: #8492a6">Overzicht van de
        bestelling:</p>
    @foreach ($order->lineItems() as $lineItem)
        @if(!$loop->first)
            <div style="line-height: 24px">&nbsp;</div>
        @endif
        <table style="width: 100%" cellpadding="0" cellspacing="0" role="presentation">
            <tr>
                <td style="vertical-align: top; width: 72px" valign="top">
                    @if($lineItem->product()->get('image'))
                        <img src="{{ Statamic::tag('glide')->path($lineItem->product()->resource()->augmented()->get('image'))->square(48)->fit("contain")->absolute(true)->format('png') }}" alt="{{  $lineItem->product()->get('title') }}" width="48"
                             style="border: 0; max-width: 100%; line-height: 100%; vertical-align: middle">
                    @endif
                </td>
                <td class="sm-w-auto" style="text-align: left; vertical-align: top; width: 488px" align="left"
                    valign="top">
                    <p style="font-weight: 700; font-size: 16px; margin-top: 0; margin-bottom: 8px; color: #4a5566">
                        {{  $lineItem->product()->get('title') }}</p>
                    @if($lineItem->initial())
                        <p style="font-size: 13px; line-height: 15px; {{ $loop->first ? ' margin: 0;' : 'margin: 4px 0 0;' }} color: #8492a6"> Formaat :  {{$lineItem->initial() }} </p>
                    @endif
                    @forelse($lineItem->options() ?? [] as $name => $value)
                        <p style="font-size: 13px; line-height: 15px; {{ $loop->first ? ' margin: 0;' : 'margin: 4px 0 0;' }} color: #8492a6">{{$name }}
                            : {{ $value  }}</p>
                    @endforeach
                </td>
                <td style="text-align: right; vertical-align: top; width: 88px" align="right" valign="top">
                    <p style="font-weight: 700; font-size: 16px; line-height: 22px; margin: 0; color: #4a5566">{{ \DoubleThreeDigital\SimpleCommerce\Currency::parse($lineItem->total(), $site) }}</p>
                </td>
            </tr>
        </table>
    @endforeach
    <div style="line-height: 24px">&nbsp;</div>
    @foreach ($order->upsells() as $lineItem)
        @if(!$loop->first)
            <div style="line-height: 24px">&nbsp;</div>
        @endif
        <table style="width: 100%" cellpadding="0" cellspacing="0" role="presentation">
            <tr>
                <td style="vertical-align: top; width: 72px" valign="top">
                    @if($lineItem->product()->get('image'))
                    <img src="{{ Statamic::tag('glide')->path(asset($lineItem->product()->get('image')))->square(48)->fit("contain")->format('png') }}" alt="{{  $lineItem->product()->get('title') }}" width="48"
                         style="border: 0; max-width: 100%; line-height: 100%; vertical-align: middle">
                    @endif
                </td>
                <td class="sm-w-auto" style="text-align: left; vertical-align: top; width: 488px" align="left"
                    valign="top">
                    <p style="font-weight: 700; font-size: 16px; margin-top: 0; margin-bottom: 8px; color: #4a5566">
                        {{  $lineItem->product()->get('title') }}
                    </p>
                </td>
                <td style="text-align: right; vertical-align: top; width: 88px" align="right" valign="top">
                    <p style="font-weight: 700; font-size: 16px; line-height: 22px; margin: 0; color: #4a5566">{{ \DoubleThreeDigital\SimpleCommerce\Currency::parse($lineItem->total(), $site) }}</p>
                </td>
            </tr>
        </table>
    @endforeach
    <table style="width: 100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td style="padding-top: 12px; padding-bottom: 12px">
                <div style="background-color: #e1e1ea; height: 2px; line-height: 2px">&nbsp;</div>
            </td>
        </tr>
    </table>
    <div align="right" class="sm-text-left">
        <table class="sm-w-full" cellpadding="0" cellspacing="0" role="presentation">
            @if($order->get('rush_total'))
                <tr>
                    <td style="font-size: 12px; line-height: 16px; color: #8492a6; width: 102px">Spoed kosten</td>
                    <td style="font-size: 21px; line-height: 28px; text-align: right; color: #4a5566; width: 200px"
                        align="right">{{ \DoubleThreeDigital\SimpleCommerce\Currency::parse($order->rushTotal(), $site) }}
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="height: 12px"></td>
                </tr>
            @endif
            <tr>
                <td style="font-size: 12px; line-height: 16px; color: #8492a6; width: 102px">Verzendkosten</td>
                <td style="font-size: 21px; line-height: 28px; text-align: right; color: #4a5566; width: 200px"
                    align="right"> {{ \DoubleThreeDigital\SimpleCommerce\Currency::parse($order->shippingTotal(), $site) }}
                </td>
            </tr>
            <tr>
                <td colspan="2" style="height: 12px"></td>
            </tr>
            <tr>
                <td style="font-size: 12px; line-height: 16px; color: #8492a6; width: 72px">Btw</td>
                <td style="font-size: 21px; line-height: 28px; text-align: right; color: #4a5566; width: 200px"
                    align="right">
                    {{ \DoubleThreeDigital\SimpleCommerce\Currency::parse($order->taxTotal(), $site) }}
                </td>
            </tr>
            <tr>
                <td colspan="2" style="height: 12px"></td>
            </tr>
            <tr>
                <td style="font-size: 12px; line-height: 16px; color: #8492a6; width: 102px">Totaal incl. btw</td>
                <td style="font-weight: 700; font-size: 21px; line-height: 28px; text-align: right; color: #4a5566; width: 200px"
                    align="right">{{ \DoubleThreeDigital\SimpleCommerce\Currency::parse($order->grandTotal(), $site) }}
                </td>
            </tr>
            <tr>
                <td colspan="2" style="height: 12px"></td>
            </tr>
        </table>
    </div>
    <div style="line-height: 64px">&nbsp;</div>
    <table style="width: 100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td>
                <h2 style="font-weight: 400; font-size: 28px; line-height: 30px; margin: 0 0 32px; color: #4a5566">
                    Order informatie</h2>
                <table style="width: 100%" cellpadding="0" cellspacing="0" role="presentation">

                    <tr>
                        <td class="sm-inline-block sm-w-full sm-px-0"
                            style="padding-right: 3px; padding-bottom: 32px; vertical-align: top; width: 50%"
                            valign="top">


                            @if($order->get('shipping_method'))

                                @php
                                    $shipping_method =\App\Models\ShippingMethods::where('code', $order->get('shipping_method'))->first();
                                @endphp
                                @if($shipping_method)
                                    @if($shipping_method->pickup)
                                        <div class="sm-inline-block sm-w-full sm-px-0"
                                             style=" padding-bottom: 32px;"
                                             valign="top">
                                            <h4 style="font-size: 16px; line-height: 22px; margin: 0 0 8px; color: #8492a6">{{ $shipping_method->name }}</h4>
                                            <p style="font-size: 16px; line-height: 22px; margin: 0; color: #8492a6">{{ $shipping_method->pickup_address }}</p>
                                            <br>
                                            <h4 style="font-size: 16px; line-height: 22px; margin: 0 0 8px; color: #8492a6">Afhaal Datum</h4>
                                            <p style="font-size: 16px; line-height: 22px; margin: 0; color: #8492a6">{{ ucfirst(\Carbon\Carbon::parse($order->get('delivery_at'))->format('l j F Y')) }}</p
                                        </div>
                                    @else
                                        <div style="padding-bottom: 32px; ">
                                            <h4 style="font-size: 16px; line-height: 22px; margin: 0 0 8px; color: #8492a6">
                                                Bezorgadres</h4>
                                            <p style="font-size: 16px; line-height: 22px; margin: 0; color: #8492a6">
                                                {{ $order->get('shipping_first_name') }} {{ $order->get('shipping_last_name') }}<br>
                                                @if($order->get('shipping_company_name'))
                                                    {{ $order->get('shipping_company_name') }}<br>
                                                @endif
                                                {{ $order->shippingAddress()->getLine() }}
                                            </p>
                                        </div>
                                        <div class="sm-inline-block sm-w-full sm-px-0"
                                             style="padding-bottom: 32px; vertical-align: top;"
                                             valign="top">
                                            <h4 style="font-size: 16px; line-height: 22px; margin: 0 0 8px; color: #8492a6">
                                                Bezorgmethode</h4>
                                            <p style="font-size: 15px; line-height: 20px; margin: 0; color: #8492a6">{{ $shipping_method->name }}</p>
                                            <p style="font-size: 15px; line-height: 20px; margin: 0; color: #8492a6">{{ $shipping_method->description }}</p>
                                            <br>

                                            <h4 style="font-size: 16px; line-height: 22px; margin: 0 0 8px; color: #8492a6">
                                                Bezorgdatum</h4>
                                            <p style="font-size: 16px; line-height: 22px; margin: 0; color: #8492a6">{{ ucfirst(\Carbon\Carbon::parse($order->get('delivery_at'))->format('l j F Y')) }}</p>
                                        </div>
                                    @endif
                                @endif
                            @endif
                        </td>
                        <td class="sm-inline-block sm-w-full sm-px-0"
                            style="padding-left: 3px; padding-bottom: 32px; vertical-align: top; width: 50%"
                            valign="top">
                            <h4 style="font-size: 16px; line-height: 22px; margin: 0 0 8px; color: #8492a6">Factuur adres</h4>
                            <div style="padding-bottom: 32px; ">
                                <p style="font-size: 16px; line-height: 22px; margin: 0; color: #8492a6">
                                    {{ $order->get('billing_first_name') }} {{ $order->get('billing_last_name') }}<br>
                                    @if($order->get('billing_company_name'))
                                        {{ $order->get('billing_company_name') }}<br>
                                    @endif
                                    {{ $order->billingAddress()->getLine() }}
                                </p>
                            </div>
                            {{--                            @if($order_data['payment_details'])--}}
                            {{--                                <h4 style="font-size: 16px; line-height: 22px; margin: 0 0 8px; color: #8492a6">Betaling--}}
                            {{--                                    method</h4>--}}
                            {{--                                <table cellpadding="0" cellspacing="0" role="presentation">--}}
                            {{--                                    @foreach($order_data['payment_details'] as $key => $detail)--}}
                            {{--                                        <tr>--}}
                            {{--                                            <td style="">--}}

                            {{--                                                <p style="font-size: 16px; line-height: 22px; margin: 0; color: #8492a6">--}}
                            {{--                                                    {{ $detail }}</p>--}}
                            {{--                                            </td>--}}
                            {{--                                        </tr>--}}
                            {{--                                    @endforeach--}}
                            {{--                                </table>--}}
                            {{--                            @endif--}}

                        </td>

                    </tr>
                    <tr>

                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <div style="text-align: left">
        <table style="width: 100%" cellpadding="0" cellspacing="0" role="presentation">
            <tr>
                <td style="padding-bottom: 16px; padding-top: 64px">
                    <div style="background-color: #e1e1ea; height: 1px; line-height: 1px">&nbsp;</div>
                </td>
            </tr>
        </table>
        <p style="font-size: 12px; line-height: 16px; margin: 0; color: #8492a6">Als u vragen heeft, beantwoord dan
            deze e-mail of neem contact met ons op <a href="mailto:studio@print4sign.nl" class="hover-underline"
                                                      style="text-decoration: none; color: #0052e2">studio@print4sign.nl</a>
        </p>
        <table style="width: 100%" cellpadding="0" cellspacing="0" role="presentation">
            <tr>
                <td>
                    <p style="font-size: 12px; line-height: 16px; margin: 16px 0 0; color: #8492a6">
                        &copy; {{ date('Y') }}
                        {{ $site->attributes()['name'] }}. Alle rechten voorbehouden.</p>
                </td>
            </tr>
        </table>
    </div>

@endsection
