<?php ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
    <title>Document</title>

    <style type="text/css" media="all">
        body {
            margin: 0 auto !important;
            padding: 0 !important;
            -webkit-text-size-adjust: 100% !important;
            -ms-text-size-adjust: 100% !important;
            -webkit-font-smoothing: antialiased !important;
            font-family:Verdana, Arial, Tahoma !important;
        }
        *{
            margin: 0 auto;
            padding: 0;
            -webkit-box-sizing: border-box;
            -moz-box-sizing: border-box;
            box-sizing: border-box;
        }
        img {
            border: 0 !important;
            outline: none !important;
        }
        p {
            margin: 0px !important;
            padding: 0px !important;
        }
        table {
            border-collapse: collapse;
            mso-table-lspace: 0px;
            mso-table-rspace: 0px;
        }
        td, a, span {
            border-collapse: collapse;
            mso-line-height-rule: exactly;
        }
        a[x-apple-data-detectors] {
            color: inherit !important;
            text-decoration: none !important;
            font-size: inherit !important;
            font-family: inherit !important;
            font-weight: inherit !important;
            line-height: inherit !important;
        }
        .revenue-print-ticket{
            width: 100%;
            height: auto;
            position: relative;
            max-width: 244px;
            padding: 13px 0 30px;
            margin: 0 auto;
            font-size: 12px;
            line-height: 15px;
            font-weight: 400;
        }
        .revenue-print-ticket__top{
            text-align: center;
        }

        .revenue-print-ticket__block{
            padding-bottom: 10px;
            margin-bottom: 10px;
        }


    </style>
</head>
<body>
<div class="container">
    <div class="revenue-print-ticket">
        <div>
            <div style="padding-top: 0px; text-align: center">
                <p class="" style="font-size: 12px">{{__companionPrintTrans('general.day_revenue')}}</p>
                <div style="margin-top: 10px;border-bottom: 2px solid #000;"></div>
                <p style="font-size: 21px;font-weight: 800;line-height: 27px">{{$store->store_name}}</p>
                <p style="font-weight: 400;font-size: 10px;line-height: 12px">{{$store->address}}<br />
                    {{$store->store_phone}}<br />
                    {{$store->store_email}}</p>
                <div style="margin-top: 10px;border-bottom: 2px solid #000;"></div>
                <div style="margin-top: 5px;font-size: 11px;font-weight: 400;line-height: 12px;text-align: left">
                    <p>{{__companionPrintTrans('general.print_date')}}: {{$data['current_date_time']}}</p>
                    <p>{{__companionPrintTrans('general.from_date')}}: {{$data['start_date']}}</p>
                    <p>{{__companionPrintTrans('general.to_date')}}: {{$data['end_date']}}</p>
                    <div style="margin-top: 5px;border-bottom: 2px solid #000;"></div>
                    <div style="font-size: 11px;line-height: 12px;padding-top: 3px">
                        <h3 style="text-align: left">{{__companionPrintTrans('general.cash_title')}}</h3>
                        <div style="padding-top: 5px">
                            <div style="text-align: left;display: inline-block;float: left">
                                <p>{{__companionPrintTrans('general.cash')}}:</p>
                                <p>{{__companionPrintTrans('general.cash_difference')}}:</p>
                            </div>
                            <div style="display: inline-block;text-align: right;float: right">
                                <p>€{{($data['total_cash_amount'])}}</p>
                                <p>€0,00</p>
                            </div>
                        </div>
                        <div style="border-bottom: 2px solid #000; width: 85px;margin-left: 159px;margin-top: 27px"></div>
                        <div style="padding-top: 5px">
                            <div style="display: inline-block;text-align: left;float: left">
                                <p>{{__companionPrintTrans('general.total_cash_registered')}}:</p>
                            </div>
                            <div style="display: inline-block;text-align: right;float: right">
                                <p>€{{($data['total_cash_amount'])}}</p>
                            </div>
                        </div>
                        <div style="border-bottom: 1px dashed #000; margin-top: 15px"></div>
                        <div style="font-size: 11px;line-height: 12px;padding-top: 5px">
                            <h3>{{__companionPrintTrans('general.wireless_payment')}}</h3>
                            <div style="margin-top: 5px">
                                <div style="display: inline-block;text-align: left;float: left">
                                    <p>{{__companionPrintTrans('general.pin')}}:</p>
                                    <p>{{__companionPrintTrans('general.online_payment')}}:</p>
                                </div>
                                <div style="display: inline-block;text-align: right;float: right">
                                    <p>€{{($data['total_pin_amount'])}}</p>
                                    <p>€{{($data['total_ideal_amount'])}}</p>
                                </div>
                            </div>
                            <div style="border-bottom: 2px solid #000; width: 85px;margin-left: 159px;margin-top: 30px"></div>
                            <div style="padding-top: 2px">
                                <div style="display: inline-block;text-align: left;float: left">
                                    <p>{{__companionPrintTrans('general.digital_payment')}}:</p>
                                    <p style="margin-top: 5px">{{__companionPrintTrans('general.total_registered')}}:</p>
                                </div>
                                <div style="display: inline-block;text-align: right;float: right">
                                    <p>€{{($data['total_pin_ideal_amount'])}}</p>
                                    <p style="margin-top: 5px">€{{($data['total_pin_ideal_cash_amount'])}}</p>
                                </div>
                            </div>
                            <div style="border-bottom: 1px dashed #000; margin-top: 28px"></div>
                        </div>
                        <div style="font-size: 11px;line-height: 12px;padding-top: 0px;">
                            <div style="padding-top: 5px">
                                <div style="display: inline-block;float: left;text-align: left">
                                    <p>{{__companionPrintTrans('general.number_receipt')}}:</p>
                                    <p>{{__companionPrintTrans('general.number_of_cashdrawer_open')}}:</p>
                                    <p>{{__companionPrintTrans('general.total_cash')}}:</p>
                                    <p>{{__companionPrintTrans('general.total_pin')}}:</p>
                                    <p>{{__companionPrintTrans('general.gift_card_count')}} ontvangen:</p>
                                    <p>{{__companionPrintTrans('general.gift_card_count')}} used:</p>
                                    <p>{{__companionPrintTrans('general.total_online')}}:</p>
                                    @if((isset($data['third_party_print_status']) && $data['third_party_print_status']))
                                        <p>Thuisbezorgd:</p>
                                        <p>Ubereats:</p>
                                    @endif
                                    <p>{{__companionPrintTrans('general.total_products')}}:</p>
                                    <p>{{__companionPrintTrans('general.average_spending')}}:</p>
                                    <p>Discount:</p>
                                    <p>Plastic Bag Fee:</p>
                                    <p>Additional Fee:</p>
                                    <p>Delivery Fee:</p>
                                    @if($data['on_the_house_status'])
                                        <p>On The House:</p>
                                    @endif
                                    <p>Deposit:</p>
                                    <p>Reservation Deposit Deducted</p>
                                    <p>Tip</p>
                                </div>
                                <div style="display: inline-block;text-align: right;float: right;">
                                    <p>{{$data['total_orders']}}</p>
                                    <p>{{$data['number_of_cashdrawer_open']}}</p>
                                    <p>{{$data['total_cash_orders']}}</p>
                                    <p>{{$data['total_pin_orders']}}</p>
                                    <p>{{$data['gift_card_order_count']}}</p>
                                    <p>€@if($data['coupon_price'] > 0) -{{ ($data['coupon_price']) }} @else {{ ($data['coupon_price']) }} @endif</p>
                                    <p>{{$data['total_ideal_orders']}}</p>
                                    @if((isset($data['third_party_print_status']) && $data['third_party_print_status']))
                                        <p>{{$data['thusibezorgd_orders']}}</p>
                                        <p>{{$data['ubereats_orders']}}</p>
                                    @endif
                                    <p>{{$data['products_count']}}</p>
                                    <p>€{{($data['avg'])}}</p>
                                    {{--                                    <p>€{{changePriceFormat($data['discount_amt'])}}</p>--}}
                                    <p>€{{($data['discount_inc_amt'])}}</p>
                                    <p>€{{($data['plastic_bag_fee'])}}</p>
                                    <p>€{{($data['additional_fee'])}}</p>
                                    <p>€{{($data['delivery_fee'])}}</p>
                                    @if($data['on_the_house_status'])
                                        <p>€{{($data['on_the_house_total'])}}</p>
                                    @endif
                                    <p>€{{ ($data['deposit_total']) }}</p>
                                    <p>€{{($data['reservation_deducted_total'])}}</p>
                                    <p>€{{($data['tip_amount'])}}</p>
                                </div>
                            </div>
                            {{--                            <div style="border-bottom: 1px dashed #000; margin-top: 138px"></div>--}}
                            @if((isset($data['third_party_print_status']) && $data['third_party_print_status']) && $data['on_the_house_status'])
                                <div style="border-bottom: 1px dashed #000; margin-top: 230px"></div>
                            @elseif((isset($data['third_party_print_status']) && $data['third_party_print_status']))
                                <div style="border-bottom: 1px dashed #000; margin-top: 220px"></div>
                            @elseif($data['on_the_house_status'])
                                <div style="border-bottom: 1px dashed #000; margin-top: 190px"></div>
                            @else
                                <div style="border-bottom: 1px dashed #000; margin-top: 182px"></div>
                            @endif
                        </div>
                        {{--                        <div style="font-size: 11px;line-height: 12px;padding-top: 303px;">--}}
                        {{--@if((isset($data['third_party_print_status']) && $data['third_party_print_status']) && $data['on_the_house_status'])--}}
                        {{--<div style="font-size: 11px;line-height: 12px;padding-top: 0px;"> --}}{{-- 70 + 290 --}}
                        {{--@elseif((isset($data['third_party_print_status']) && $data['third_party_print_status']))--}}
                        {{--<div style="font-size: 11px;line-height: 12px;padding-top: 0px"> --}}{{-- 57 + 290 --}}
                        {{--@elseif($data['on_the_house_status'])--}}
                        {{--<div style="font-size: 11px;line-height: 12px;padding-top: 0px"> --}}{{-- 45 + 290 --}}
                        {{--@else--}}
                        <div style="font-size: 11px;line-height: 12px;padding-top: 0px;"> {{-- 37 + 290 --}}
                            {{--@endif--}}
                            <div style="padding-top: 15px">
                                <div style="">
                                    <div style="display: inline-block;width: 24%">
                                        <p>{{__companionPrintTrans('general.percentage')}}</p>
                                        <p>0%</p>
                                        <p>9%</p>
                                        <p>21%</p>
                                    </div>
                                    <div style="display: inline-block;width: 24%">
                                        <p>{{__companionPrintTrans('general.revenue_ex_tax')}}</p>
                                        <p>€{{ ($data['total_0_without_tax_subtotal']) }}</p>
                                        <p>€{{($data['total_9_without_tax_discount_subtotal'])}}</p>
                                        <p>€{{($data['total_21_without_tax_discount_subtotal'])}}</p>
                                    </div>
                                    <div style="display: inline-block;width: 24%">
                                        <p>{{__companionPrintTrans('general.tax_amount')}}</p>
                                        <p>€0,00</p>
                                            <p>€{{($data['total_9_tax'])}}</p>
                                        <p>€{{($data['total_21_tax'])}}</p>
                                    </div>
                                    <div style="display: inline-block;width: 24%;text-align: right">
                                        <p>{{__companionPrintTrans('general.revenue_with_tax')}}</p>
                                        <p>€{{ ($data['total_0_without_tax_subtotal']) }}</p>
                                        <p>€{{($data['total_9_inc_tax_without_discount_subtotal'])}}</p>
                                        <p>€{{($data['total_21_inc_tax_without_discount_subtotal'])
                                        }}</p>
                                    </div>
                                </div>
                            </div>
                            {{--                            <div style="border-bottom: 1px dashed #000; margin-top: 55px"></div>--}}
                            <div style="border-bottom: 1px dashed #000; margin-top: 0px"></div>
                        </div>
                        {{--                        <div style="font-size: 11px;line-height: 12px;padding-top: 360px">--}}
                        {{--@if((isset($data['third_party_print_status']) && $data['third_party_print_status']) && $data['on_the_house_status'])--}}
                        {{--<div style="font-size: 11px;line-height: 12px;padding-top: 414px">--}}
                        {{--@elseif((isset($data['third_party_print_status']) && $data['third_party_print_status'] == 5))--}}
                        {{--<div style="font-size: 11px;line-height: 12px;padding-top: 404px">--}}
                        {{--@elseif($data['on_the_house_status'])--}}
                        {{--<div style="font-size: 11px;line-height: 12px;padding-top: 392px">--}}
                        {{--@else--}}
                        <div style="font-size: 11px;line-height: 12px;padding-top: 5px">
                            {{--@endif--}}
                            <h3>{{__companionPrintTrans('general.total_sales_per_channel')}}</h3>
                            <div style="padding-top: 5px">
                                <div style="display: inline-block;float: left">
                                    <p>{{__companionPrintTrans('general.takeaway')}}:</p>
                                    @foreach($store->kioskDevices as $device)
                                        <p>{{$device->name}}:</p>
                                    @endforeach
                                    <p>{{__companionPrintTrans('general.dine_in_revenue')}}:</p>
                                    <p>{{__companionPrintTrans('general.gift_card_revenue')}}:</p>
                                    <p>Reservation Deposited</p>
                                    @if((isset($data['third_party_print_status']) && $data['third_party_print_status']))
                                        <p>Thuisbezorgd:</p>
                                        <p>Ubereats:</p>
                                    @endif
                                    <div style="border-bottom: 2px solid #fff; width: 85px;margin-left: 0px;margin-top: 3px;margin-bottom: 3px"></div>
                                    <p style="margin-top: 20px">{{__companionPrintTrans('general.total_sumup_from')}}:</p>
                                    <div style="border-bottom: 2px solid #000;width: 122px;margin-top: 5px"></div>
                                </div>
                                <div style="display: inline-block;text-align: right;float: right">
                                    <p>€{{($data['total_takeaway'])}}</p>
                                    @foreach($store->kioskDevices as $device)
                                        <p>€{{($data['kioskTotal'][$device->name])}}</p>
                                    @endforeach
                                    <p>€{{($data['total_dine_in'])}}</p>
                                    <p>€{{($data['total_gift_card_amount'])}}</p>
                                    <p>€{{($data['reservation_received_total'])}}</p>
                                    @if((isset($data['third_party_print_status']) && $data['third_party_print_status']))
                                        <p>€{{($data['thusibezorgd_amount'])}}</p>
                                        <p>€{{($data['ubereats_amount'])}}</p>
                                    @endif
                                    <div style="border-bottom: 2px solid #000; width: 85px;margin-left: 37px;margin-top: 3px;margin-bottom: 3px"></div>
                                    <p style="padding-top: 20px">€{{($data['final_total'])}}</p>
                                    <div style="border-bottom: 2px solid #000;width: 122px;margin-top: 5px"></div>
                                </div>
                            </div>
                            <div style="font-size:16px; font-weight: bold; text-align: center; line-height: 24px;padding-top: 10px">{{__companionPrintTrans('general.end_report')}}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
