<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"
      xmlns:v="urn:schemas-microsoft-com:vml"
      xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <!--[if gte mso 9]><xml>
        <o:OfficeDocumentSettings>
            <o:AllowPNG/>
            <o:PixelsPerInch>96</o:PixelsPerInch>
        </o:OfficeDocumentSettings>
    </xml><![endif]-->
    <title></title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0 " />
    <meta name="format-detection" content="telephone=no"/>
    <style type="text/css" media="all">
    	body {
    		margin: 0;
    		padding: 0;
    		-webkit-text-size-adjust: 100% !important;
    		-ms-text-size-adjust: 100% !important;
    		-webkit-font-smoothing: antialiased !important;
    	}
    	img {
    		border: 0 !important;
    		outline: none !important;
    	}
    	p {
    		Margin: 0px !important;
    		Padding: 0px !important;
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

    	.ExternalClass * {
    		line-height: 100%;
    	}
    	.em_defaultlink a {
    		color: inherit !important;
    		text-decoration: none !important;
    	}
    	.em_g_img + div {
    		display: none;
    	}

    	@media only screen and (max-width: 640px){
    		.em_img { width: 100% !important; height: auto !important; }
    		.em_main_table {width: 100% !important;}
    		.em_wrapper {width: 100% !important;}
    		.em_width1 {width: 55% !important;}
    		.em_width2 {width: 5% !important;}
    		.em_width3 {width: 40% !important;}
    		.em_width4 {width: 3% !important;}
    		.em_width5 {width: 20% !important;}
    		.em_width6 {width: 54% !important;}
    		.em_width7 {width: 20% !important;}
    		.em_height {height:auto !important;}
    		.em_hide {display: none !important;	}
    		.em_padding {padding-left: 10px !important; padding-right: 10px !important;	}
    		.em_font {font-size: 10px !important; line-height: 14px !important;	}
    		u + .em_body .em_full_wrap { width: 100% !important; width: 100vw !important;}
    	}
        .paid{
            background-color: #31DD2A;
        }
        .canceled{
            background-color: #f44336;
        }
        .failed{
            background-color: #f44336;
        }
    </style>
</head>

<body class="em_body" style="margin:0px auto; padding:0px; background-color:#F0F0F0">
<table width="100%" border="0" cellspacing="0" cellpadding="0" class="em_full_wrap">
    <tr>
        <td align="center" valign="top" style="padding:0 10px">
            <table class="em_main_table" align="center" width="600" border="0" cellspacing="0" cellpadding="0"  style="width:600px; table-layout:fixed">

                <tr>
                    <td align="center" style="padding:22px 0 0 0">
                        <table class="em_wrapper" align="center" width="600" border="0" cellspacing="0" cellpadding="0"
                               style="width:600px; background-color:#F5F5F5; border-radius:12px 12px 0px 0px">
                            <tr>
                                <td align="center"
                                    style="font-family:'Roboto Slab', Arial, Tahoma; font-size:14px; line-height:19px; font-weight:normal; font-style:normal; color:#575757; padding:25px 10px 25px 10px">
                                    Dit is een automatisch gegenereerde e-mail. Wij kunnen een antwoord op deze e-mail
                                    niet beantwoorden.
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td align="center">
                        <table class="em_wrapper @if($order['is_paylater_order'] == 1) paid @else {{$order['status']}}@endif" align="center" width="600" border="0"
                               cellspacing="0" cellpadding="0" style="width:600px; @if($order['is_paylater_order'] == 1) background-color: orange @endif ">
                            <tr>
                                <td align="center" style="font-family:Verdana, Arial, Tahoma; font-size:12px; line-height:15px; font-weight:bold; font-style:normal; color:#FFFFFF; padding:5px 10px 5px 10px">
                                    @if($order['is_paylater_order'] == 1)
                                        Uw bestelling is geplaatst. Nog te voldoen
                                    @else
                                        {!! $data['title1'] !!}
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>


                <tr>
                    <td align="center">
                        <table class="em_wrapper" align="center" width="600" border="0" cellspacing="0" cellpadding="0" style="width:600px; background-color:#FFFFFF">
                            <tr>
                                <td align="center" style="padding:0 10px 0 10px">
                                    <table class="em_wrapper" align="center" width="400" border="0" cellspacing="0" cellpadding="0" style="width:400px;">
                                        <tr>
                                            <td align="center" style="padding:32px 0 0 0">
                                                @if($data['logo'])
                                                    <img src="{{$data['logo']}}" style="display:block"
                                                         border="0" alt="Logo"/>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center" style="padding:48px 0 18px 0">
                                                <table class="em_wrapper" align="center" width="400" border="0" cellspacing="0" cellpadding="0" style="width:400px;">
                                                    <tr>
                                                        <td class="em_width1" align="left" valign="top" width="150">
                                                            <table class="em_wrapper" align="left" width="100%" border="0" cellspacing="0" cellpadding="0" style="width:100%">
                                                                <tr>
                                                                    <td align="left" style="font-family:Verdana, Arial, Tahoma; font-size:12px; line-height:15px; font-weight:normal; font-style:normal; color:#000000; padding:5px 0 0 0">
                                                                        <b>Klantgegevens</b><br />
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td align="left" style="font-family:Verdana, Arial, Tahoma; font-size:12px; line-height:15px; font-weight:normal; font-style:normal; color:#000000; padding:2px 0 0 0">
                                                                        {{$data['title2']}}<br />
                                                                        {{$data['title3']}}<br />
                                                                        <a href="mailto:wing@pixel2code.nl" style="text-decoration:none; color:#000000">{{$data['title4']}}</a><br /><br />

                                                                        Besteldatum:<br />
                                                                        Bestellingnummer
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                        <td class="em_width2" align="right" valign="top" width="130"></td>
                                                        <td class="em_width3" align="right" valign="top" width="120">
                                                            <table class="em_wrapper" align="left" width="100%" border="0" cellspacing="0" cellpadding="0" style="width:100%">
                                                                <tr>
                                                                    <td align="left" style="font-family:Verdana, Arial, Tahoma; font-size:12px; line-height:15px; font-weight:normal; font-style:normal; color:#000000; padding:5px 0 0 0">
                                                                        @if($order['order_type'] == 'delivery')<b>Bezorgen</b>@endif<br />
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td align="left" style="font-family:Verdana, Arial, Tahoma; font-size:12px; line-height:15px; font-weight:normal; font-style:normal; color:#000000; padding:2px 0 5px 0">
                                                                        {{$order['delivery_address']}}<br />
                                                                        {{$order['delivery_postcode']}}<br />
                                                                        {{$order['delivery_place']}}<br /><br />

                                                                        {{$data['titteTime'][0]['value2'] ?? ''}}<br />
                                                                        {{$data['title5']}}
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td align="center" style="font-family:Verdana, Arial, Tahoma; font-size:12px; line-height:15px; font-weight:bold; font-style:normal; color:#000000; padding:10px 10px 10px 10px; border-top:1px solid #000000; border-bottom:1px solid #000000">
                                                {{$data['typeorder']}} op {{$data['datetime']}}
                                            </td>
                                        </tr>
                                        @if($data['customercomments'])
                                            <tr>
                                                <td align="center" style="font-family:Verdana, Arial, Tahoma; font-size:12px; line-height:18px; font-weight:normal; font-style:normal; color:#000000; padding:10px 10px 10px 10px; border-top:1px solid #000000; border-bottom:1px solid #000000">
                                                    <b>Opmerking</b><br />
                                                    {{$data['customercomments']}}
                                                </td>
                                            </tr>
                                        @endif
                                        <tr>
                                            <td align="center" style="padding:20px 0 12px 0">
                                                <table class="em_wrapper" align="center" width="400" border="0" cellspacing="0" cellpadding="0" style="width:400px;">
                                                    <tr>
                                                        <td class="em_width5" align="left" valign="top" width="100">
                                                            <table class="em_wrapper" align="left" width="100%" border="0" cellspacing="0" cellpadding="0" style="width:100%">
                                                                <tr>
                                                                    <td align="left" style="font-family:Verdana, Arial, Tahoma; font-size:12px; line-height:15px; font-weight:normal; font-style:normal; color:#000000; padding:0 0 0 0">
                                                                        Aantal
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                        <td class="em_width4" align="right" valign="top" width="8"></td>
                                                        <td class="em_width6"align="right" valign="top" width="200">
                                                            <table class="em_wrapper" align="left" width="100%" border="0" cellspacing="0" cellpadding="0" style="width:100%">
                                                                <tr>
                                                                    <td align="left" style="font-family:Verdana, Arial, Tahoma; font-size:12px; line-height:15px; font-weight:normal; font-style:normal; color:#000000; padding:0 0 0 0">
                                                                        Item
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                        <td class="em_width4" align="right" valign="top" width="20"></td>
                                                        <td class="em_width7" align="right" valign="top" width="72">
                                                            <table class="em_wrapper" align="left" width="100%" border="0" cellspacing="0" cellpadding="0" style="width:100%">
                                                                <tr>
                                                                    <td align="right" style="font-family:Verdana, Arial, Tahoma; font-size:12px; line-height:15px; font-weight:normal; font-style:normal; color:#000000; padding:0 0 0 0">
                                                                        Prijs
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <?php $show_discount_note = ''; ?>
                                        @foreach($data['items'] as $item)
                                            @if(isset($item['void_id']) && $item['void_id'] > 0)
                                                {{--If order item void then does't show in mail print--}}
                                            @else
                                                <tr>
                                                    <td align="center" style="padding:0px 0 10px 0">
                                                        <table class="em_wrapper" align="center" width="400" border="0" cellspacing="0" cellpadding="0" style="width:400px;">
                                                            <tr>
                                                                <td class="em_width5" align="left" valign="top" width="100">
                                                                    <table class="em_wrapper" align="left" width="100%" border="0" cellspacing="0" cellpadding="0" style="width:100%">
                                                                        <tr>
                                                                            <td align="left" style="font-family:Verdana, Arial, Tahoma; font-size:12px; line-height:15px; font-weight:normal; font-style:normal; color:#000000; padding:0 0 0 0">
                                                                                {{$item['qty']}}x
                                                                            </td>
                                                                        </tr>
                                                                    </table>
                                                                </td>
                                                                <td class="em_width4" align="right" valign="top" width="8"></td>
                                                                <td class="em_width6" align="right" valign="top" width="200">
                                                                    <table class="em_wrapper" align="left" width="100%" border="0" cellspacing="0" cellpadding="0" style="width:100%">
                                                                        <tr>
                                                                            <td align="left" style="font-family:Verdana, Arial, Tahoma; font-size:12px; line-height:15px; font-weight:normal; font-style:normal; color:#000000; padding:0 0 0 0">
                                                                                {{$item['itemname']}}
                                                                                <br/>
                                                                                @foreach($item['itemaddons'] as $itemaddon)
                                                                                    <p>{{$itemaddon}}</p>
                                                                                @endforeach
                                                                                @if(isset($item['mainproductcomment']) && $item['mainproductcomment'] != null && $item['mainproductcomment'] != '')
                                                                                    Comment : {{ $item['mainproductcomment'] }}
                                                                                @endif
                                                                                <br/>
                                                                            </td>
                                                                        </tr>
                                                                    </table>
                                                                </td>
                                                                <td class="em_width4" align="right" valign="top" width="20"></td>
                                                                <td class="em_width7" align="right" valign="top" width="72">
                                                                    <table class="em_wrapper" align="left" width="100%" border="0" cellspacing="0" cellpadding="0" style="width:100%">
                                                                        <tr>
                                                                            <td align="right" style="font-family:Verdana, Arial, Tahoma; font-size:12px; line-height:15px; font-weight:normal; font-style:normal; color:#000000; padding:0 0 0 0">
                                                                                € {{$item['price']}}<br />

                                                                            </td>
                                                                        </tr>
                                                                    </table>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach

                                        @foreach($data['preSubtotalSummary'] as $key => $item)
                                        @if($loop->first)
                                            <tr>
                                                <td align="center" style="padding:10px 0 10px 0; border-top:1px solid #000000; border-bottom:1px solid #000000">
                                                    <table class="em_wrapper" align="center" width="400" border="0" cellspacing="0" cellpadding="0" style="width:400px;">
                                        @endif
                                                        <tr>
                                                           <td align="left" valign="top" width="50%">
                                                               <table class="em_wrapper" align="left" width="100%" border="0" cellspacing="0" cellpadding="0" style="width:100%">
                                                                   <tr>
                                                                       <td align="left" style="font-family:Verdana, Arial, Tahoma; font-size:12px; line-height:15px; font-weight:normal; font-style:normal; color:#000000; padding:0 0 0 0">
                                                                           {{$item['key']}}
                                                                       </td>
                                                                   </tr>
                                                               </table>
                                                           </td>
                                                           <td align="left" valign="top" width="50%">
                                                               <table class="em_wrapper" align="left" width="100%" border="0" cellspacing="0" cellpadding="0" style="width:100%">
                                                                   <tr>
                                                                       <td align="right" style="font-family:Verdana, Arial, Tahoma; font-size:12px; line-height:15px; font-weight:normal; font-style:normal; color:#000000; padding:0 0 0 0">
                                                                           € {{$item['value']}}
                                                                       </td>
                                                                   </tr>
                                                               </table>
                                                           </td>
                                                       </tr>
                                        @if($loop->last)
                                                    </table>
                                                </td>
                                            </tr>
                                        @endif
                                        @endforeach

                                        @foreach($data['subtotal'] as $key => $item)
                                        @if($loop->first)
                                            <tr>
                                                <td align="center" style="padding:10px 0 10px 0; border-top:1px solid #000000; border-bottom:1px solid #000000">
                                                    <table class="em_wrapper" align="center" width="400" border="0" cellspacing="0" cellpadding="0" style="width:400px;">
                                        @endif
                                                        <tr>
                                                           <td align="left" valign="top" width="50%">
                                                               <table class="em_wrapper" align="left" width="100%" border="0" cellspacing="0" cellpadding="0" style="width:100%">
                                                                   <tr>
                                                                       <td align="left" style="font-family:Verdana,Arial, Tahoma; font-size:12px; line-height:15px; font-weight:bold; font-style:normal; color:#000000; padding:0 0 0 0">
                                                                           {{$item['key']}}
                                                                       </td>
                                                                   </tr>
                                                               </table>
                                                           </td>
                                                           <td align="left" valign="top" width="50%">
                                                               <table class="em_wrapper" align="left" width="100%" border="0" cellspacing="0" cellpadding="0" style="width:100%">
                                                                   <tr>
                                                                       <td align="right" style="font-family:Verdana, Arial, Tahoma; font-size:12px; line-height:15px; font-weight:bold; font-style:normal; color:#000000; padding:0 0 0 0">
                                                                           € {{$item['value']}}
                                                                       </td>
                                                                   </tr>
                                                               </table>
                                                           </td>
                                                       </tr>
                                        @if($loop->last)
                                                    </table>
                                                </td>
                                            </tr>
                                        @endif
                                        @endforeach

                                        @foreach($data['summary'] as $key => $item)
                                        @if($loop->first)
                                            <tr>
                                                <td align="center" style="padding:10px 0 10px 0; border-top:1px solid #000000; border-bottom:1px solid #000000">
                                                    <table class="em_wrapper" align="center" width="400" border="0" cellspacing="0" cellpadding="0" style="width:400px;">
                                        @endif
                                                        <tr>
                                                           <td align="left" valign="top" width="50%">
                                                               <table class="em_wrapper" align="left" width="100%" border="0" cellspacing="0" cellpadding="0" style="width:100%">
                                                                   <tr>
                                                                       <td align="left" style="font-family:Verdana, Arial, Tahoma; font-size:12px; line-height:15px; font-weight:normal; font-style:normal; color:#000000; padding:0 0 0 0">
                                                                           {{$item['key']}}
                                                                       </td>
                                                                   </tr>
                                                               </table>
                                                           </td>
                                                           <td align="left" valign="top" width="50%">
                                                               <table class="em_wrapper" align="left" width="100%" border="0" cellspacing="0" cellpadding="0" style="width:100%">
                                                                   <tr>
                                                                       <td align="right" style="font-family:Verdana, Arial, Tahoma; font-size:12px; line-height:15px; font-weight:normal; font-style:normal; color:#000000; padding:0 0 0 0">
                                                                           € {{$item['value']}}
                                                                       </td>
                                                                   </tr>
                                                               </table>
                                                           </td>
                                                       </tr>
                                        @if($loop->last)
                                                    </table>
                                                </td>
                                            </tr>
                                        @endif
                                        @endforeach

                                        @foreach($data['Total'] as $key => $item)
                                        @if($loop->first)
                                            <tr>
                                                <td align="center" style="padding:10px 0 10px 0; border-top:1px solid #000000; border-bottom:1px solid #000000">
                                                    <table class="em_wrapper" align="center" width="400" border="0" cellspacing="0" cellpadding="0" style="width:400px;">
                                        @endif
                                                        <tr>
                                                           <td align="left" valign="top" width="50%">
                                                               <table class="em_wrapper" align="left" width="100%" border="0" cellspacing="0" cellpadding="0" style="width:100%">
                                                                   <tr>
                                                                       <td align="left" style="font-family:Verdana,Arial, Tahoma; font-size:12px; line-height:15px; font-weight:bold; font-style:normal; color:#000000; padding:0 0 0 0">
                                                                           {{$item['key1']}}
                                                                       </td>
                                                                   </tr>
                                                               </table>
                                                           </td>
                                                           <td align="left" valign="top" width="50%">
                                                               <table class="em_wrapper" align="left" width="100%" border="0" cellspacing="0" cellpadding="0" style="width:100%">
                                                                   <tr>
                                                                       <td align="right" style="font-family:Verdana, Arial, Tahoma; font-size:12px; line-height:15px; font-weight:bold; font-style:normal; color:#000000; padding:0 0 0 0">
                                                                           € {{$item['value1']}}
                                                                       </td>
                                                                   </tr>
                                                               </table>
                                                           </td>
                                                       </tr>
                                        @if($loop->last)
                                                    </table>
                                                </td>
                                            </tr>
                                        @endif
                                        @endforeach


                                        @foreach($data['MiscellaneousSummary1'] as $key => $item)
                                            @if($loop->first)
                                                <tr>
                                                    <td align="center" style="padding:20px 0 12px 0">
                                                        <table class="em_wrapper" align="center" width="400" border="0" cellspacing="0" cellpadding="0" style="width:400px;">
                                            @endif

                                                            <tr>
                                                                <td class="em_width5" align="left" valign="top" width="100">
                                                                    <table class="em_wrapper" align="left" width="100%" border="0" cellspacing="0" cellpadding="0" style="width:100%">
                                                                        <tr>
                                                                            <td align="left" style="font-family:Verdana, Arial, Tahoma; font-size:12px; line-height:15px; font-weight:normal; font-style:normal; color:#000000; padding:0 0 0 0">
                                                                                {{$item['column1']}}
                                                                            </td>
                                                                        </tr>
                                                                    </table>
                                                                </td>
                                                                <td class="em_width4" align="right" valign="top" width="8"></td>
                                                                <td class="em_width6"align="right" valign="top" width="200">
                                                                    <table class="em_wrapper" align="left" width="100%" border="0" cellspacing="0" cellpadding="0" style="width:100%">
                                                                        <tr>
                                                                            <td align="left" style="font-family:Verdana, Arial, Tahoma; font-size:12px; line-height:15px; font-weight:normal; font-style:normal; color:#000000; padding:0 0 0 0">
                                                                                {{$item['column2']}}
                                                                            </td>
                                                                        </tr>
                                                                    </table>
                                                                </td>
                                                                <td class="em_width4" align="right" valign="top" width="20"></td>
                                                                <td class="em_width7" align="right" valign="top" width="72">
                                                                    <table class="em_wrapper" align="left" width="100%" border="0" cellspacing="0" cellpadding="0" style="width:100%">
                                                                        <tr>
                                                                            <td align="right" style="font-family:Verdana, Arial, Tahoma; font-size:12px; line-height:15px; font-weight:normal; font-style:normal; color:#000000; padding:0 0 0 0">
                                                                                {{$item['column4']}}
                                                                            </td>
                                                                        </tr>
                                                                    </table>
                                                                </td>
                                                            </tr>
                                            @if($loop->last)
                                                        </table>
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach

                                        @foreach($data['miscellaneous'] as $key => $item)
                                            <tr>
                                                <td align="center" style="padding:0 0 0 0; border-top:1px solid #000000;border-bottom:1px solid #000000">
                                                    <table class="em_wrapper" align="center" width="330" border="0" cellspacing="0" cellpadding="0" style="width:380px;">
                                                        <tr>
                                                            <td align="center" style="font-family:Verdana, Arial, Tahoma; font-size:12px; line-height:15px; font-weight:normal; font-style:normal; color:#000000; padding:20px 0 0 0">
                                                                {{$item['column1']}}
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        @endforeach
                                        <tr>
                                            <td align="center" style="padding:0 0 0 0;padding:10px 0 10px 0; border-top:1px solid #000000; border-bottom:1px solid #000000">
                                                <table class="em_wrapper" align="center" width="350" border="0" cellspacing="0" cellpadding="0" style="width:350px;">
                                                    <tr>
                                                        <td align="center" style="font-family:Verdana, Arial, Tahoma; font-size:12px; line-height:15px; font-weight:normal; font-style:normal; color:#000000; padding:20px 0 0 0">
                                                            <b>{{ $store->store_name }}</b>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td align="center" style="font-family:Verdana, Arial, Tahoma; font-size:12px; line-height:15px; font-weight:normal; font-style:normal; color:#000000; padding:2px 0 0 0">
                                                            {{$store->address}}<br />
                                                            {{$store->store_phone}}<br />
                                                            <a href="{{$store->store_email}}" style="text-decoration:none; color:#000000">{{$store->store_email}}</a>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td align="center" style="padding:16px 0 0 0">
                                                            <a href="http://maps.google.com/maps?q=<?=$store->address?>"><img src="{{ env('COMPANION_AWS_URL').'/assets/show-on-map.png' }}" style="display:block" width="115" height="42" border="0" alt="Toon op kaart"/></a>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td align="center" style="padding:20px 0 20px 0">
                                                            <table align="center" border="0" cellspacing="0" cellpadding="0" >
                                                                <tr>
                                                                    @if($store->facebook_url)
                                                                        <td align="center">
                                                                            <a href="{{ $store->facebook_url }}"><img class="em_img" src="{{ env('COMPANION_AWS_URL').'/assets/facebook2.png' }}" style="display:block" width="34" height="34" border="0" alt="FB"/></a>
                                                                        </td>
                                                                    @endif
                                                                    <td width="10"></td>
                                                                    @if($store->instagram_url)
                                                                        <td align="center">
                                                                            <a href="{{ $store->instagram_url }}"><img class="em_img" src="{{ env('COMPANION_AWS_URL').'/assets/instagram.png' }}" style="display:block" width="34" height="34" border="0" alt="IG"/></a>
                                                                        </td>
                                                                    @endif
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                    @if($order['order_type'] == 'delivery' && $store && $store->is_delivery_app_enabled)
                                                    <tr>
                                                        <td align="center" style="padding:0 0 20px 0; ">
                                                            <table class="em_wrapper" align="center" width="478" border="0" cellspacing="0" cellpadding="0" style="width:478px; background-color:#F7F7F7; border-radius:12px">
                                                                <tr>
                                                                    <td align="center" style="font-family:Arial, Tahoma; font-size:16px; line-height:24px; font-weight:normal; font-style:normal; color:#000000; padding:18px 10px 18px 10px">
                                                                        Track Your Order <br class="em_hide" />
                                                                        Get updates about your Order status by click
                                                                        <a href="{{ env('ADMIN_APP_URL').'/order-detail/'}}@phpEncrypt($order['id'])" style="color:#1B68AA;
                                                                        font-weight:bold">here.</a>
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                    @elseif($order['order_type'] == 'pickup' )
                                                        <tr>
                                                            <td align="center" style="padding:0 0 20px 0; ">
                                                                <table class="em_wrapper" align="center" width="478" border="0" cellspacing="0" cellpadding="0" style="width:478px; background-color:#F7F7F7; border-radius:12px">
                                                                    <tr>
                                                                        <td align="center" style="font-family:Arial, Tahoma; font-size:16px; line-height:24px; font-weight:normal; font-style:normal; color:#000000; padding:18px 10px 18px 10px">
                                                                            Check Your Order <br class="em_hide" />
                                                                            Get updates about your Order status by click <a href="{{ env('ADMIN_APP_URL').'/order-detail/'}}@phpEncrypt($order['id'])" style="color:#1B68AA; font-weight:bold">here.</a>
                                                                        </td>
                                                                  </tr>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                    @endif
                                                </table>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td align="center" style="padding:0 0 0 0; border-top:1px solid #000000">
                                                <table class="em_wrapper" align="center" width="330" border="0" cellspacing="0" cellpadding="0" style="width:380px;">
                                                    <tr>
                                                        <td align="center" style="font-family:Verdana, Arial, Tahoma; font-size:12px; line-height:15px; font-weight:normal; font-style:normal; color:#000000; padding:20px 0 0 0">
                                                            Uw order word verwerkt door Eatcard, onze partner voor Takeaway
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td align="center" style="padding:15px 0 80px 0">
                                                            <a href=""><img src="{{ env('COMPANION_AWS_URL').'/assets/eat_card.png' }}" style="display:block" width="56" height="25" border="0" alt="eat card"/></a>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>
