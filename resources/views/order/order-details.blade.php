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
            font-family: Verdana, Arial, Tahoma !important;
        }

        p {
            font-size: 10px;
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

        .revenue-print-ticket {
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

        .revenue-print-ticket__top {
            text-align: center;
        }

        .revenue-print-ticket__block {
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        .large-font-size {
            font-size: 24px;
        }


    </style>
</head>
<body>
<div class="container" style="width: 300px">
    <div class="revenue-print-ticket">
        <div>
            <div style=" text-align: center">
                <p>{!! $data['title1'] !!}</p>
                <div style="padding-top: 0px">
                    <img src="{{$data['logo']}}" alt="Logo"
                         style="width: 85px;min-height: 75px;max-height: 75px;object-fit: cover"/>
                </div><!--end of logo-wrap-->
            </div>
            <div style="text-align: left;">
                @if(!empty($data['deliveryaddress']))
                    <p style="font-weight: 600; font-size: 11px; text-align: left;">{!! $data['deliverytitle'] !!}</p>
                    @if(isset($data['deliveryaddress']) && $data['deliveryaddress'] != '')
                        <p>{{$data['deliveryaddress']}}</p>
                    @endif
                @endif
                <div style="text-align: left;margin-top: 5px">
                    @if(isset($data['title2']) && $data['title2'] != '')
                        <p>{{$data['title2']}}</p>
                    @endif
                    @if(isset($data['title3']) && $data['title3'] != '')
                        <p>{{$data['title3']}}</p>
                    @endif
                    @if(isset($data['title4']) && $data['title4'] != '')
                        <p>{{$data['title4']}}</p>
                    @endif
                    @if(isset($data['titteTime'][0]['key2']) && $data['titteTime'][0]['key2'] != '')
                    <div style="margin-top: 5px">
                        <p style="float: left">{{$data['titteTime'][0]['key2']}}</p>
                        <p style="float: right">{{$data['titteTime'][0]['value2']}}</p>
                    </div>
                    @endif
                    @if(!empty($data['kioskname']))
                        <p>{{$data['kioskname']}}</p>
                    @endif

                </div>
                <h3 style="font-size: 20px;font-weight: 800;padding-top: 30px;text-align: center">
                    {{$data['ordernumber']}}</h3>
                <div style="text-align: left;">
                    @if(!empty($data['mainreceiptordernumber']))
                        <p>{{$data['mainreceiptordernumber']}}</p>
                    @endif
                    @if(!empty($data['ordertype']))
                        <p>{{$data['ordertype']}}</p>
                    @endif
                    @if($order['method'] == 'cash')
                        <p>@companionPrintTrans('general.cash')</p>
                    @elseif($order['method'] == 'pin')
                        <p>@companionPrintTrans('general.pin')</p>
                    @endif
                    @if(!empty($data['checkoutno']))
                        <p>{{$data['checkoutno']}}</p>
                    @endif
                    @if($data['tablename'])
                        <p>@companionPrintTrans('general.table_name')</p>
                        <p style="font-size: 14px;font-weight: 600">{{$data['tablename']}}</p>
                    @endif
                    <div style="border-bottom: 1px solid #000; margin-top: 8px"></div>
                    <div style="padding: 4px 0;text-align: center">
                        <p style="font-weight: 600">
                            {{$data['typeorder']}} op {{$data['datetime']}}
                        <div style="border-bottom: 1px solid #000; margin-top: 8px"></div>
                        @if($data['customercomments'])
                            {{--                            <div class="comment-status">--}}
                            <p style="font-size: 14px; font-weight: 600;line-height: 25px">Opmerking</p>
                            <p style="padding-top: 10px">{{$data['customercomments']}}</p>
                            <div style="border-bottom: 1px solid #000; margin-top: 8px"></div>
                            {{--                            </div>--}}
                        @endif
                        <div style="margin-top: 5px">
                            <table>
                                <tr>
                                    <td style="text-align: left;width: 40px;font-size: 10px;font-weight: 600;line-height: 24px">
                                        Aantal
                                    </td>
                                    <td style="width: 160px;text-align: left;font-size: 10px;font-weight: 600;line-height: 24px">
                                        Item
                                    </td>
                                    <td style="text-align: right; font-size: 10px;font-weight: 600;line-height: 24px">
                                        Prijs
                                    </td>
                                </tr>
                                @foreach($data['items'] as $item)
                                        <tr>
                                            <td style="text-align: left;width: 40px;font-size: 10px;font-weight: 400;line-height: 18px">{{$item['qty']}}
                                                x
                                            </td>
                                            <td style="width: 160px;text-align: left;font-size: 10px;font-weight: 400;line-height: 14px">{{$item['itemname']}}
                                                <br/>
                                                @foreach($item['itemaddons'] as $itemaddon)
                                                    <p>{{$itemaddon}}</p>
                                                @endforeach
                                                @if(isset($item['mainproductcomment']) && $item['mainproductcomment'] != null && $item['mainproductcomment'] != '')
                                                    Comment : {{ $item['mainproductcomment'] }}
                                                @endif
                                                <br/>
                                            </td>
                                            <td style="text-align: right; font-size: 10px;font-weight: 400;line-height: 18px">
                                                {{$item['price']}}
                                            </td>
                                        </tr>
                                    {{--@endif--}}
                                @endforeach
                            </table>

                            @foreach($data['preSubtotalSummary'] as $key => $item)
                                @if($loop->first)
                                    <div style="border-bottom: 1px solid #000"></div>
                                @endif
                                <div style="margin-top: 10px; padding-top: 5px;"></div>
                                <div style="margin-top: 0px;">
                                    <p style="float: left">{{$item['key']}}</p>
                                    <p style="float: right">{{$item['value']}}</p>
                                </div>
                            @endforeach
                            <div style="border-bottom: 1px solid #000; margin-top: 20px"></div>
                            <p style="padding-top: 10px">
                            <div style="margin-top: 5px;font-weight: 600;">
                                @if(isset($store->storeSetting->print_total_font_size) && $store->storeSetting->print_total_font_size == 1)
                                    @foreach($data['subtotal'] as $key => $item)
                                        <p style="float: left;font-size: 18px;">{{$item['key']}}</p>
                                        <p style="float: right;font-size: 18px;">{{$item['value']}}</p>
                                    @endforeach
                                @else
                                    @foreach($data['subtotal'] as $key => $item)
                                        <p style="float: left;font-size: 14px;">{{$item['key']}}</p>
                                        <p style="float: right;font-size: 14px;">{{$item['value']}}</p>
                                    @endforeach
                                @endif
                            </div>
                            @foreach($data['summary'] as $key => $item)
                                @if($loop->first)
                                    <div style="border-bottom: 1px solid #000;margin-top: 20px"></div>
                                @else
                                    <div style="margin-top: 10px; padding-top: 5px;"></div>
                                @endif
                                <p style="padding-top: 2px">
                                <div style="margin-top: 5px;">
                                    <p style="float: left">{{$item['key']}}</p>
                                    <p style="float: right">{{$item['value']}}</p>
                                </div>
                            @endforeach

                        <div style="border-bottom: 1px solid #000; margin-top: 20px"></div>
                        <p style="padding-top: 10px">
                        <div style="margin-top: 5px;font-weight: 600;">
                        @if(isset($store->storeSetting->print_total_font_size) && $store->storeSetting->print_total_font_size == 1)
                            @foreach($data['Total'] as $key => $item)
                                <p style="float: left;font-size: 18px;">{{$item['key1']}}</p>
                                <p style="float: right;font-size: 18px;">{{$item['value1']}}</p>
                            @endforeach
                        @else
                            @foreach($data['Total'] as $key => $item)
                                <p style="float: left;font-size: 14px;">{{$item['key1']}}</p>
                                <p style="float: right;font-size: 14px;">{{$item['value1']}}</p>
                            @endforeach
                        @endif
                        </div>

                        @foreach($data['summary4'] as $key => $item)
                            @if($loop->first)
                                <div style="border-bottom: 1px solid #000;margin-top: 20px"></div>
                            @else
                                <div style="margin-top: 10px; padding-top: 5px;"></div>
                            @endif
                            <p style="padding-top: 2px">
                            <div style="margin-top: 5px;">
                                <p style="float: left">{{$item['key']}}</p>
                                <p style="float: right">{{$item['value']}}</p>
                            </div>
                        @endforeach

                        <div style="border-bottom: 1px solid #000; margin-top: 20px"></div>
                        @foreach($data['MiscellaneousSummary1'] as $key => $item)
                           @if($loop->first)
                            <div style="margin-top: 0px">
                                <table style="margin: 0;width: 100%">
                                    <tr>
                                        <td style="text-align: left;width: 40px;font-size: 10px;font-weight: 600;line-height: 20px">
                                            {{$item['column1']}}
                                        </td>
                                        <td style="width: 160px;text-align: left;font-size: 10px;font-weight: 600;line-height: 20px">
                                            {{$item['column2']}}
                                        </td>
                                        <td style="text-align: right; font-size: 10px;font-weight: 600;line-height: 20px">
                                            {{$item['column4']}}
                                        </td>
                                    </tr>
                           @else
                                    <tr>
                                        <td style="text-align: left;width: 40px;font-size: 10px;font-weight: 400;line-height: 20px">
                                            {{$item['column1']}}
                                        </td>
                                        <td style="width: 160px;text-align: left;font-size: 10px;font-weight: 400;line-height: 20px">
                                            {{$item['column2']}}
                                        </td>
                                        <td style="text-align: right; font-size: 10px;font-weight: 400;line-height: 20px">
                                            {{$item['column4']}}
                                        </td>
                                    </tr>
                           @endif
                           @if($loop->last)
                                </table>
                           <div style="border-bottom: 1px solid #000; margin-top: 10px"></div>
                           @endif

                       @endforeach

                       @foreach($data['miscellaneous'] as $key => $item)
                           <div>
                               <p>{{$item['column1']}}</p>
                           </div>
                          @if($key == count($data['miscellaneous'])-1)
                           <div style="border-bottom: 1px solid #000; margin-top: 10px"></div>
                         @endif
                       @endforeach

                        @if($data['receipt'])
                            <p>
                                @foreach($data['receipt'] as $temp)
                                    {!! $temp !!}<br/>
                                @endforeach
                            </p>
                        @endif

                            <div style="">
                                <p style="font-size: 9px; font-weight: 600">{{ $store->company_name }}</p>
                                <p style="font-size: 9px;line-height: 12px">{{$store->address}}<br/>
                                    {{$store->store_phone}}<br/>
                                    {{$store->store_email}}<br/>
                                    {!!($store->website_url) ? $store->website_url.'<br />' : '' !!}
                                    {{($store->kvk_number) ? 'KVK-'.$store->kvk_number : ''}}<br/>
                                    {{($store->btw_number) ? 'BTW-'.$store->btw_number : ''}}</p>
                                <div style="border-bottom: 1px solid #000; margin-top: 8px"></div>
                                <div style="padding-top: 15px;">
                                    <div style="display: inline-block;">
                                        @if($store->facebook_url)
                                            <a style="display: inline-block" href="{{ $store->facebook_url }}"><img
                                                        class="em_img"
                                                        src="@companionGeneralHelper('getS3File',env('COMPANION_AWS_URL').'/assets/facebook2.png')"
                                                        style="display:block" width="34" height="34" border="0"
                                                        alt="FB"/></a>
                                        @endif
                                        @if($store->instagram_url)
                                            <a style="display: inline-block" href="{{ $store->instagram_url }}"><img
                                                        class="em_img"
                                                        src="@companionGeneralHelper('getS3File',env('COMPANION_AWS_URL').'/assets/instagram.png')"
                                                        style="display:block" width="34" height="34" border="0"
                                                        alt="IG"/></a>
                                        @endif
                                    </div>
                                </div>
                                @if($store->facebook_url || $store->instagram_url)
                                    <div style="padding-top: 10px">
                                        @else
                                            <div>
                                                @endif
                                                <p>Uw order word verwerkt door Eatcard, onze<br/> partner voor takeaway
                                                </p>
                                                <img src="@companionGeneralHelper('getS3File',env('COMPANION_AWS_URL').'/assets/eatcard-logo-print.png')"
                                                     style="display:block;padding-top: 5px" width="56" height="25"
                                                     border="0" alt="eat card"/>
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
