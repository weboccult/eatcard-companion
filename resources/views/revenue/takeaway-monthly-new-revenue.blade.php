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
            font-family:Verdana, Arial, Tahoma;
        }
        * {
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
            max-width: 406px;
            padding: 13px 0 30px;
            margin: 0 auto;
            font-size: 12px;
            line-height: 15px;
            font-weight: 400;
        }
        .revenue-print-ticket h3{
            font-size: 14px;
            line-height: 16px;
            font-weight: 700;
            margin: 0 auto;
        }
        .revenue-print-ticket__top{
            text-align: center;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        .revenue-print-ticket__top h2{
            font-size: 24px;
            line-height: 24px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .revenue-print-ticket__top span{
            font-size: 14px;
            line-height: 20px;
            font-weight: 400;
            display: block;
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
        <div class="" style="text-align: center">
            <p class="revenue-print-ticket__block" style="font-size: 12px">Maandinkomen</p>
            <div style="margin-top: 10px;border-bottom: 2px solid #000;"></div>
            <div style="padding-top: 0px">
                <p style="font-size: 21px;font-weight: 800;line-height: 27px">{{$store->store_name}}</p>
                <p style="font-weight: 400;font-size: 10px;line-height: 12px">{{$store->address}}<br />
                    {{$store->store_phone}}<br />
                    {{$store->store_email}}</p>
                <div style="margin-top: 10px;border-bottom: 2px solid #000;"></div>
                <div style="text-align: left">
                    <div style="padding-top: 5px;font-size: 11px;font-weight: 400;line-height: 12px">
                        <p>@companionPrintTrans('general.print_date'): {{$data['current_date_time']}}</p>
                        <p>@companionPrintTrans('general.from_date'): {{$data['start_date']}}</p>
                        <p>@companionPrintTrans('general.to_date'): {{$data['end_date']}}</p>
                        <div style="margin-top: 10px;border-bottom: 2px solid #000;"></div>
                    </div>
                    <div style="font-size: 11px;line-height: 12px;padding-top: 5px">
                        <h3>@companionPrintTrans('general.cash_title')</h3>
                        <div style="padding-top: 0px">
                            <div style="display: inline-block;float: left">
                                <p>@companionPrintTrans('general.cash'):</p>
                                <p>@companionPrintTrans('general.cash_difference'):</p>
                            </div>
                            <div style="display: inline-block;float: right;text-align: right">
                                <p>€{{$data['total_cash_amount']}}</p>
                                <p>€0,00</p>
                            </div>
                        </div>
                        <div style="border-bottom: 2px solid #000; width: 150px;margin-left: 255px;margin-top: 25px"></div>
                        <div style="padding-top: 5px">
                            <div style="display: inline-block;float: left">
                                <p>@companionPrintTrans('general.total_cash_registered'):</p>
                            </div>
                            <div style="display: inline-block;float: right;text-align: right">
                                <p>€{{$data['total_cash_amount']}}</p>
                            </div>
                        </div>
                        <div style="border-bottom: 1px dashed #000; margin-top: 15px"></div>
                        <h3 style="padding-top: 3px">@companionPrintTrans('general.on_invoice_title')</h3>
                        <div style="padding-top: 3px">
                            <div style="display: inline-block;float: left">
                                <p>@companionPrintTrans('general.on_invoice'):</p>
                            </div>
                            <div style="display: inline-block;float: right;text-align: right">
                                <p>€{{$data['total_on_invoice_amount']}}</p>
                            </div>
                        </div>
                        <div style="border-bottom: 2px solid #000; width: 150px;margin-left: 255px;margin-top:15px"></div>
                        <div style="padding-top: 5px">
                            <div style="display: inline-block;float: left">
                                <p>@companionPrintTrans('general.total_cash_registered'):</p>
                            </div>
                            <div style="display: inline-block;float: right;text-align: right">
                                <p>€{{$data['total_on_invoice_amount']}}</p>
                            </div>
                        </div>
                        <div style="border-bottom: 1px dashed #000; margin-top: 15px"></div>
                        <div style="font-size: 11px;line-height: 12px;padding-top: 5px">
                            <h3>@companionPrintTrans('general.wireless_payment')</h3>
                            <div style="padding-top: 5px">
                                <div style="display: inline-block;float: left">
                                    <p>@companionPrintTrans('general.pin'):</p>
                                    <p>@companionPrintTrans('general.online_payment'):</p>
                                </div>
                                <div style="display: inline-block;float: right;text-align: right">
                                    <p>€{{$data['total_pin_amount']}}</p>
                                    <p>€{{$data['total_ideal_amount']}}</p>
                                </div>
                            </div>
                            <div style="border-bottom: 2px solid #000; width: 150px;margin-left: 255px;margin-top: 25px"></div>
                            <div style="padding-top: 3px">
                                <div style="display: inline-block;float: left">
                                    <p>@companionPrintTrans('general.digital_payment'):</p>
                                    <p>@companionPrintTrans('general.total_registered'):</p>
                                </div>
                                <div style="display: inline-block;float: right;text-align: right">
                                    <p>€{{ $data['total_pin_ideal_amount'] }}</p>
                                    <p>€{{$data['total_pin_ideal_cash_amount']}}</p>
                                </div>
                            </div>
                            <div style="border-bottom: 1px dashed #000; margin-top: 30px"></div>
                            <div style="font-size: 11px;line-height: 12px;padding-top: 0px">
                                <div style="padding-top: 5px">
                                    <div style="display: inline-block;float: left">
                                        <p>@companionPrintTrans('general.number_receipt'):</p>
                                        <p>@companionPrintTrans('general.number_of_cashdrawer_open'):</p>
                                        <p>@companionPrintTrans('general.total_cash'):</p>
                                        <p>@companionPrintTrans('general.total_pin'):</p>
                                        <p>@companionPrintTrans('general.total_on_invoice'):</p>
                                        <p>@companionPrintTrans('general.gift_card_count') ontvangen:</p>
                                        <p>@companionPrintTrans('general.gift_card_count') used:</p>
                                        <p>@companionPrintTrans('general.total_online'):</p>
                                        @if(isset($data['third_party_print_status']) && $data['third_party_print_status'])
                                        <p>Thuisbezorgd:</p>
                                        <p>Ubereats:</p>
                                        <p>Deliveroo:</p>
                                        @endif
                                        <p>@companionPrintTrans('general.total_products'):</p>
                                        <p>@companionPrintTrans('general.average_spending'):</p>
                                        <p>Discount:</p>
                                        <p>Plastic Bag Fee:</p>
                                        <p>Additional Fee:</p>
                                        <p>Delivery Fee:</p>
                                        @if($data['on_the_house_status'])
                                            <p>On The House:</p>
                                        @endif
                                        <p>Deposit:</p>
                                        <p>Reservation Deposit Deducted :</p>
                                        <p>Reservation Refund :</p>
                                        <p>Tip :</p>
                                    </div>
                                    <div style="display: inline-block;float: right;text-align: right">
                                        <p>{{$data['total_orders']}}</p>
                                        <p>{{$data['number_of_cashdrawer_open']}}</p>
                                        <p>{{$data['total_cash_orders']}}</p>
                                        <p>{{$data['total_pin_orders']}}</p>
                                        <p>{{$data['total_on_invoice_order']}}</p>
                                        <p>{{$data['gift_card_order_count']}}</p>
                                        <p>€{{$data['coupon_price']}}</p>
                                        <p>{{$data['total_ideal_orders']}}</p>
                                        @if(isset($data['third_party_print_status']) && $data['third_party_print_status'])
                                        <p>{{$data['thusibezorgd_orders']}}</p>
                                        <p>{{$data['ubereats_orders']}}</p>
                                        <p>{{$data['deliveroo_orders']}}</p>
                                        @endif
                                        <p>{{$data['products_count']}}</p>
                                        <p>€{{$data['avg']}}</p>
                                        <p>€{{$data['discount_inc_amt']}}</p>
                                        <p>€{{$data['plastic_bag_fee']}}</p>
                                        <p>€{{$data['additional_fee']}}</p>
                                        <p>€{{$data['delivery_fee']}}</p>
                                        @if($data['on_the_house_status'])
                                            <p>€{{$data['on_the_house_total']}}</p>
                                        @endif
                                        <p>€{{$data['deposit_total']}}</p>
                                        <p>€{{$data['reservation_deducted_total']}}</p>
                                        <p>€{{$data['reservation_refund_total']}}</p>
                                        <p>€{{$data['tip_amount']}}</p>
                                    </div>
                                </div>
{{--                                <div style="border-bottom: 1px dashed #000; margin-top: 150px"></div>--}}
                                @if(isset($data['third_party_print_status']) && $data['third_party_print_status'] && $data['on_the_house_status'])
                                    <div style="border-bottom: 1px dashed #000; margin-top: 263px"></div>
                                @elseif(isset($data['third_party_print_status']) && $data['third_party_print_status'])
                                    <div style="border-bottom: 1px dashed #000; margin-top: 253px"></div>
                                @elseif(isset($data['on_the_house_status']))
                                    <div style="border-bottom: 1px dashed #000; margin-top: 230px"></div>
                                @else
                                    <div style="border-bottom: 1px dashed #000; margin-top: 215px"></div>
                                @endif
                                <div style="font-size: 11px;line-height: 10px;padding-top: 0px">
                                    <h3>@companionPrintTrans('general.day_wise_data')</h3>
                                    <table style="padding-top: 5px;">
                                        <tr>
                                            <td style="text-align: left;font-size: 10px">@companionPrintTrans('general.date')</td>
                                            <td style="text-align: right;padding: 0 7px 0 0;font-size: 10px">@companionPrintTrans('general.revenue_with_tax')</td>
                                            <td style="text-align: right;padding: 0 7px 0 0;font-size: 10px">Tax (21%)</td>
                                            <td style="text-align: right;padding: 0 7px 0 0;font-size: 10px">Tax (9%)</td>
                                            <td style="text-align: right;padding: 0 7px 0 0;font-size: 10px">@companionPrintTrans('general.tax_amount')</td>
                                            <td style="text-align: right;padding: 0 7px 0 0;font-size: 10px">@companionPrintTrans('general.revenue_ex_tax')</td>
                                            <td style="text-align: right;padding: 0 0px 0 0;font-size: 10px">@companionPrintTrans('general.discount_price')</td>
                                            <td style="text-align: right;padding: 0 0px 0 0;font-size: 10px">Gift cards</td>
                                        </tr>
                                        @foreach($order_detail as $key => $order_data)
                                            <tr>
                                                <td style="text-align: left;font-size: 10px">{{ $order_data['date'] }}</td>
                                                <td style="text-align: right;font-size: 10px">€{{ $order_data['total_turnover_with_tax'] }}</td>
                                                <td style="text-align: right;font-size: 10px">€{{ $order_data['tax2_amount'] }}</td>
                                                <td style="text-align: right;font-size: 10px">€{{ $order_data['tax1_amount'] }}</td>
                                                <td style="text-align: right;font-size: 10px">€{{ $order_data['total_tax_amount'] }}</td>
                                                <td style="text-align: right;font-size: 10px">€{{ $order_data['total_turnover_without_tax'] }}</td>
                                                <td style="text-align: right;font-size: 10px">€{{ $order_data['total_discount'] }}</td>
                                                {{--<td style="text-align: right;font-size: 10px">€{{ $order_data['total_discount_inc_tax'] }}</td>--}}
                                                <td style="text-align: right;font-size: 10px">€{{ $order_data['coupon_price'] }}</td>
                                            </tr>
                                        @endforeach
                                    </table>
                                    <div style="border-bottom: 1px dashed #000; margin-top: 7px"></div>
                                    <div style="font-size: 11px;line-height: 12px;margin-top: 5px">
                                        <h3>@companionPrintTrans('general.total_sales_per_channel')</h3>
                                        <div style="padding-top: 5px">
                                            <div style="">
                                                <div>
                                                    <span style="float: left;float:left;"><p>@companionPrintTrans('general.takeaway'):</p></span>
                                                    <span style="text-align: right;float: right;"><p>€{{($data['total_takeaway'])}}</p></span>
                                                </div>
                                                @foreach($store->kioskDevices as $device)
                                                    <div style="padding-top: 13px">
                                                        <span style="float: left;float:left;"><p>{{$device->name}}:</p></span>
                                                        <span style="text-align: right;float: right;"><p>€{{($data['kioskTotal'][$device->name])}}</p></span>
                                                    </div>
                                                @endforeach
                                                <div style="padding-top: 13px">
                                                    <span style="float: left;float:left;"><p>@companionPrintTrans('general.grab_and_go'):</p></span>
                                                    <span style="text-align: right;float: right;"><p>€{{($data['grab_and_go_total_amount'])}}</p></span>
                                                </div>
                                                <div style="padding-top: 13px">
                                                    <span style="float: left;float:left;"><p>@companionPrintTrans('general.dine_in_revenue'):</p></span>
                                                    <span style="text-align: right;float: right;"><p>€{{($data['total_dine_in'])}}</p></span>
                                                </div>
                                                <div style="padding-top: 13px">
                                                    <span style="float: left;float:left;"><p>@companionPrintTrans('general.gift_card_revenue'):</p></span>
                                                    <span style="text-align: right;float: right;"><p>€{{$data['total_gift_card_amount']}}</p></span>
                                                </div>
                                                <div style="padding-top: 13px">
                                                    <span style="float: left;float:left;"><p>Reservation Deposited:</p></span>
                                                    <span style="text-align: right;float: right;"><p>€{{$data['reservation_received_total']}}</p></span>
                                                </div>
{{--                                                <div style="padding-top: 13px">--}}
{{--                                                    <span style="float: left;float:left;"><p>Reservation Refunded:</p></span>--}}
{{--                                                    <span style="text-align: right;float: right;"><p>€{{$data['reservation_refund_total']}}</p></span>--}}
{{--                                                </div>--}}
                                                @if(isset($data['third_party_print_status']) && $data['third_party_print_status'])
                                                    <div style="padding-top: 13px">
                                                    <span style="float: left;float:left;"><p>Thuisbezorgd:</p></span>
                                                    <span style="text-align: right;float: right;"><p>€{{($data['thusibezorgd_amount'])}}</p></span>
                                                </div>
                                                    <div style="padding-top: 13px">
                                                    <span style="float: left;float:left;"><p>Ubereats:</p></span>
                                                    <span style="text-align: right;float: right;"><p>€{{($data['ubereats_amount'])}}</p></span>
                                                </div>
                                                    <div style="padding-top: 13px">
                                                        <span style="float: left;float:left;"><p>Deliveroo:</p></span>
                                                        <span style="text-align: right;float: right;"><p>€{{($data['deliveroo_orders_amount'])}}</p></span>
                                                    </div>
                                                @endif
                                                <div style="padding-top: 13px">
                                                    <span style="float: left;float:left;"><p>@companionPrintTrans('general.counter'):</p></span>
                                                    <span style="text-align: right;float: right;"><p>€0,00</p></span>
                                                </div>
                                                <div style="border-bottom: 2px solid #000; margin-left: 250px;width: 157px;margin-top: 13px;margin-bottom: 3px"></div>
                                                <div style="padding-top: 2px">
                                                    <span style="float: left;float:left;"><p>9%:</p></span>
                                                    <span style="text-align: right;float: right;"><p>€{{ $data['total_9_tax'] }}</p></span>
                                                </div>
                                                <div style="padding-top: 13px">
                                                    <span style="float: left;float:left;"><p>21%:</p></span>
                                                    <span style="text-align: right;float: right;"><p>€{{ $data['total_21_tax'] }}</p></span>
                                                </div>
                                                <div style="padding-top: 13px">
                                                    <span style="float: left;float:left;"><p>@companionPrintTrans('general.total_sumup_from'):</p></span>
                                                    <span style="text-align: right;float: right;"><p>€{{$data['final_total']}}</p></span>
                                                </div>
                                                <div style="border-bottom: 2px solid #000; margin-left: 0px;margin-top: 15px;"></div>
                                            </div>
                                            <div style="font-size:16px; font-weight: bold; text-align: center;
                                            line-height: 24px;margin-top: 20px">@companionPrintTrans('general.end_report')</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
