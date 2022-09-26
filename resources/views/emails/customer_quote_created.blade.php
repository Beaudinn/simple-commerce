@extends('email.layouts.base')

@php
    $site = $order->site();
@endphp

@section('subsubject')
    {!! trans('strings.notification.quote.created.body', ['link' => '', 'class' => 'text-brand-500 hover:underline', 'order_number' => $order->orderNumber()]) !!}
@endsection
@section('email-logo')
    <div style="margin-bottom: 24px">
        <a href="{{ url('/') }}" style="color: #0047c3; text-decoration: none">
            <img src="{{ url($site->attributes()['logo_png']) }}" alt="{{ $site->attributes()['name'] }}" width="119" style="border: 0; max-width: 100%; line-height: 100%; vertical-align: middle">
        </a>
    </div>
@endsection
@section('email-body')
    <p style="font-size: 21px; line-height: 28px; margin: 0; font-weight: 700; color: #4a5566">Hoi {{ $order->customer()->name() }} 👋</p>
    <div style="line-height: 32px">&nbsp;</div>
    <p style="font-size: 19px; line-height: 28px; margin: 0; color: #4a5566">
        {!! trans('strings.notification.quote.created.body', ['link' => '', 'class' => 'text-brand-500 hover:underline', 'order_number' => $order->orderNumber()]) !!}
    </p>
    <div style="line-height: 32px">&nbsp;</div>
    <div style="line-height: 16px">&nbsp;</div>
    <table class="sm-w-full" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td align="center" class="hover-bg-brand-600" style="mso-padding-alt: 20px 32px; border-radius: 4px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06); color: #ffffff" bgcolor="#0052e2">
                <a href="{{ $link_show }}" class="sm-block sm-text-14 sm-py-16" style="text-decoration: none; display: inline-block; font-weight: 700; font-size: 16px; line-height: 16px; padding: 20px 32px; color: #ffffff">Bekijk jouw offerte online</a>
            </td>
        </tr>
    </table>
    <div style="line-height: 16px">&nbsp;</div>
    <p style="font-size: 16px; line-height: 22px; margin: 0; color: #8492a6">
        {!! trans('strings.notification.quote.created.slot', ['link' => '', 'class' => 'text-brand-500 hover:underline', 'order_number' => $order->orderNumber()]) !!}
    </p>
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
