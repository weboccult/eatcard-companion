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
<style type="text/css">
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
		.em_width45 {width: 45% !important;}
		.em_width10 {width: 10% !important;}
		.em_height {height:auto !important;}
		.em_hide {display: none !important;	}
		.em_padding {padding-left: 10px !important; padding-right: 10px !important;	}
		.em_font16 {font-size: 11px !important; }
		u + .em_body .em_full_wrap { width: 100% !important; width: 100vw !important;}
	}

</style>
</head>

<body class="em_body" style="margin:0px auto; padding:0px; background-color:#dddddd">
<table width="100%" border="0" cellspacing="0" cellpadding="0" class="em_full_wrap">
	<tr>
		<td align="center" valign="top">
			<table class="em_main_table" align="center" width="600" border="0" cellspacing="0" cellpadding="0"  style="width:600px; table-layout:fixed; background-color:#FFFFFF">
				<tr>
					<td align="center">
						<table class="em_wrapper" align="center" width="600" border="0" cellspacing="0" cellpadding="0" style="width:600px; background-color:#FFFFFF">
							<tr>
								<td align="center" style="padding:0 10px 14px 10px; border-bottom:1px solid #D5D5D5">
									<table class="em_wrapper" align="center" width="520" border="0" cellspacing="0" cellpadding="0" style="width:520px;">
										<tr>
											<td class="em_width2" align="left" valign="top" width="50%">
												<table class="em_wrapper" align="left" width="100%" border="0" cellspacing="0" cellpadding="0" style="width:100%">
													<tr>
														<td class="em_font16" align="left" style="font-family:Arial, Tahoma; font-size:16px; line-height:19px; font-weight:bold; font-style:normal; color:#000000; padding:22px 0 0 0">
															Nieuwe reservering
														</td>
													</tr>
												</table>
											</td>
											<td class="em_width1" align="right" valign="top" width="5%"></td>

										</tr>
									</table>
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
									<table class="em_wrapper" align="center" width="478" border="0" cellspacing="0" cellpadding="0" style="width:478px;">
										<tr>
											<td align="center" style="padding:20px 0 0 0">
                                                @if($store->page_logo)
												    <img src="{{ getS3File($store->email_page_logo) }}" style="display:block" {{--width="auto" height="80"--}} border="0" alt="Eatcard"/>
											    @endif
                                            </td>
										</tr>
										<tr>
											<td align="left" style="font-family:Arial, Tahoma; font-size:18px; line-height:28px; font-weight:normal; font-style:normal; color:#000000; padding:40px 0 36px 0">
												Beste {{@$store->store_name}},<br /><br />

												Er is een reservering geplaatst. Hieronder vindt u een overzicht van de gemaakte reservering.

											</td>
										</tr>
									</table>
								</td>
							</tr>

                            <tr>
                                <td align="center" style="padding:0 10px 0 10px">
                                    <table class="em_wrapper" align="center" width="520" border="0" cellspacing="0" cellpadding="0" style="width:520px;">
                                         @if(isset($storeRes->meal) && $storeRes->meal->meal_image)
                                        <tr>
                                            <td align="center" style="padding:0 0 0 0">
                                                @if($storeRes->meal->meal_image)
                                                <img class="em_img" src="{{asset('imagecache/BookingMailMealImage'.getCachedImagePath($storeRes->meal->meal_image))}}" style="display:block" width="520" height="174" border="0" alt="Image"/>
                                                @endif
                                            </td>
                                        </tr>
                                         @endif
                                        <tr>
                                            <td align="right" style="font-family:Arial, Tahoma; font-size:12px; line-height:24px; font-weight:normal; font-style:normal; color:#000000; padding:2px 0 0 0">
                                                Reserverings ID: {{ $storeRes->reservation_id }}
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                            <tr>
                                <td align="center" style="padding:20px 10px 0 10px">
                                    <table class="em_wrapper" align="center" width="478" border="0" cellspacing="0" cellpadding="0" style="width:478px;">
                                        <tr>
                                            <td class="em_width45" align="left" valign="top" width="45%">
                                                <table class="em_wrapper" align="left" width="100%" border="0" cellspacing="0" cellpadding="0" style="width:100%">
                                                    <tr>
                                                        <td align="left" style="font-family:Arial, Tahoma; font-size:14px; line-height:24px; font-weight:normal; font-style:normal; color:#000000; padding:0 0 0 0">
                                                            Email<br />
                                                            <span style="font-size:18px; font-weight:bold">{{ $storeRes->email  }}</span>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                            <td class="em_width10" align="right" valign="top" width="10%"></td>
                                            <td class="em_width45" align="right" valign="top" width="45%">
                                                <table class="em_wrapper" align="left" width="100%" border="0" cellspacing="0" cellpadding="0" style="width:100%">
                                                    <tr>
                                                        <td align="left" style="font-family:Arial, Tahoma; font-size:14px; line-height:24px; font-weight:normal; font-style:normal; color:#000000; padding:0 0 0 0">
                                                            Contact number<br />
                                                            <span style="font-size:18px; font-weight:bold">{{ $storeRes->gsm_no }}</span>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td align="center" style="padding:20px 10px 0 10px">
                                    <table class="em_wrapper" align="center" width="478" border="0" cellspacing="0" cellpadding="0" style="width:478px;">
                                        <tr>
                                            <td class="em_width45" align="left" valign="top" width="45%">
                                                <table class="em_wrapper" align="left" width="100%" border="0" cellspacing="0" cellpadding="0" style="width:100%">
                                                    <tr>
                                                        <td align="left" style="font-family:Arial, Tahoma; font-size:14px; line-height:24px; font-weight:normal; font-style:normal; color:#000000; padding:0 0 0 0">
                                                            Datum<br />
                                                            <span style="font-size:18px; font-weight:bold">{{ $storeRes->res_date  }}</span>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                            <td class="em_width10" align="right" valign="top" width="10%"></td>
                                            <td class="em_width45" align="right" valign="top" width="45%">
                                                <table class="em_wrapper" align="left" width="100%" border="0" cellspacing="0" cellpadding="0" style="width:100%">
                                                    <tr>
                                                        <td align="left" style="font-family:Arial, Tahoma; font-size:14px; line-height:24px; font-weight:normal; font-style:normal; color:#000000; padding:0 0 0 0">
                                                            Aantal personen<br />
                                                            <span style="font-size:18px; font-weight:bold">{{ $storeRes->person }}</span>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                            <tr>
                                <td align="center" style="padding:33px 10px 24px 10px">
                                    <table class="em_wrapper" align="center" width="478" border="0" cellspacing="0" cellpadding="0" style="width:478px;">
                                        <tr>
                                            <td class="em_width45" align="left" valign="top" width="45%">
                                                <table class="em_wrapper" align="left" width="100%" border="0" cellspacing="0" cellpadding="0" style="width:100%">
                                                    <tr>
                                                        <td align="left" style="font-family:Arial, Tahoma; font-size:14px; line-height:24px; font-weight:normal; font-style:normal; color:#000000; padding:0 0 0 0">
                                                            Arrangement<br />
                                                            <span style="font-size:18px; font-weight:bold">{{ @$storeRes->meal->name }}
                                                                 @if(@$storeRes->limit_hour != 5)
                                                                     <div>{{@$storeRes->limit_hour}}</div>
                                                                 @else
                                                                     <div>@companionTrans('reservation.unlimited_time')</div>
                                                                 @endif
                                                             </span>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                            <td class="em_width10" align="right" valign="top" width="10%"></td>
                                            <td class="em_width45" align="right" valign="top" width="45%">
                                                <table class="em_wrapper" align="left" width="100%" border="0" cellspacing="0" cellpadding="0" style="width:100%">
                                                    <tr>
                                                        <td align="left" style="font-family:Arial, Tahoma; font-size:14px; line-height:24px; font-weight:normal; font-style:normal; color:#000000; padding:0 0 0 0">
                                                            Tijd<br />
                                                            <span style="font-size:18px; font-weight:bold">{{ $storeRes->from_time }}</span>
                                                        </td>
                                                    </tr>
                                                    @if($store->display_end_time)
                                                    <tr>
                                                        <td align="left" style="padding:3px 0 0 0;">
                                                            <table align="left" width="132" border="0" cellspacing="0" cellpadding="0"  style="width:132px; background-color:#FFFFFF; border-radius:8px">
                                                                <tr>
                                                                    <td align="center" style="font-family:Ubuntu, Helvetica, Arial, sans-serif; font-size:14px; line-height:16px; font-weight:normal; font-style:normal; color:#000000; padding:4px 2px 3px 2px; border:1px solid #000000; display:block; border-radius:8px ">
                                                                        Eindtijd {{@$storeRes->end_time}} uur
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                    @endif
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td align="center" style="padding:33px 10px 24px 10px">
                                    <table class="em_wrapper" align="center" width="478" border="0" cellspacing="0" cellpadding="0" style="width:478px;">
                                        <tr>
                                            <td class="em_width45" align="left" valign="top" width="45%">
                                                <table class="em_wrapper" align="left" width="100%" border="0" cellspacing="0" cellpadding="0" style="width:100%">
                                                    <tr>
                                                        <td align="left" style="font-family:Arial, Tahoma; font-size:14px; line-height:24px; font-weight:normal; font-style:normal; color:#000000; padding:0 0 0 0">
                                                            Naam<br />
                                                            <span style="font-size:18px; font-weight:bold">{{ (isset($storeRes->voornaam) && !empty($storeRes->voornaam)) ? $storeRes->voornaam : ''  }}  {{ (isset($storeRes->achternaam) && !empty($storeRes->achternaam)) ? $storeRes->achternaam : ''  }}</span>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                             @if($storeRes->total_price)
                            <tr>
                                <td align="center" style="padding:0 10px 20px 10px">
                                    <table class="em_wrapper" align="center" width="478" border="0" cellspacing="0" cellpadding="0" style="width:478px;">
                                        <tr>
                                            <td align="center" style="border-top:1px solid #D1D1D1; border-bottom:1px solid #D1D1D1; padding:9px 0 9px 0">
                                                <table class="em_wrapper" align="center" width="478" border="0" cellspacing="0" cellpadding="0" style="width:478px;">
                                                    <tr>
                                                        <td align="left" valign="top" width="70%">
                                                            <table class="em_wrapper" align="left" width="100%" border="0" cellspacing="0" cellpadding="0" style="width:100%">
                                                                <tr>
                                                                    <td align="left" style="font-family:Arial, Tahoma; font-size:14px; line-height:17px; font-weight:normal; font-style:normal; color:#000000; padding:0 0 0 0">
                                                                        <b>Totaal</b> aanbetaling ({{ $storeRes->person }}x€{{changePriceFormat(@$storeRes->meal->price)}})
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                        <td align="left" valign="top" width="25%">
                                                            <table class="em_wrapper" align="left" width="100%" border="0" cellspacing="0" cellpadding="0" style="width:100%">
                                                                <tr>
                                                                    <td align="right" style="font-family:Arial, Tahoma; font-size:14px; line-height:17px; font-weight:bold; font-style:normal; color:#000000; padding:0 0 0 0">
                                                                        €{{changePriceFormat($storeRes->total_price)}}
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
                             @endif
                             @if(!empty($storeRes->comments))
                            <tr>
                                <td align="center" style="padding:0 10px 20px 10px; ">
                                    <table class="em_wrapper" align="center" width="478" border="0" cellspacing="0" cellpadding="0" style="width:478px; background-color:#F7F7F7; border-radius:12px">
                                        <tr>
                                            <td align="center" style="padding:20px 0 0 0">
                                                <a href=""><img src="{{ env('AWS_URL').'assets/btn2.png' }}" style="display:block;" width="98" height="24" border="0" alt="Opmerking"/></a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center" style="font-family:Arial, Tahoma; font-size:14px; line-height:24px; font-weight:normal; font-style:normal; color:#000000; padding:6px 10px 14px 10px">
                                                 {{ !empty($storeRes->comments) ? $storeRes->comments : 'Er zijn geen opmerkingen.' }}
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                             @endif

                            @if($storeRes->is_household_check == 0)
                                <tr>
                                    <td align="center" style="padding:0 10px 20px 10px">
                                        <table class="em_wrapper" align="center" width="478" border="0" cellspacing="0" cellpadding="0" style="width:478px; background-color:#FFFFFF; border-radius:12px">
                                            <tr>
                                                <td align="center" style="padding:0 0 0 0; border:1px solid #D1D1D1; display:block; border-radius:12px ">
                                                    <table class="em_wrapper" align="center" width="100%" border="0" cellspacing="0" cellpadding="0" style="width:100%; border-radius:12px;">
                                                        <tr>
                                                            <td align="left" style="font-family:Arial, Tahoma; font-size:12px; line-height:18px; font-weight:bold; font-style:normal; color:#000000; padding:9px 10px 0 10px">
                                                                @companionTrans('reservation.multiple_household')
                                                            </td>
                                                        </tr>
                                                        {{--<tr>--}}
                                                        {{--<td align="left" style="font-family:Arial, Tahoma; font-size:12px; line-height:18px; font-weight:normal; font-style:normal; color:#000000; padding:6px 10px 8px 10px">--}}
                                                        {{--{!! $store->manual_check_msg !!}--}}
                                                        {{--</td>--}}
                                                        {{--</tr>--}}
                                                    </table>
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
                    <td align="center">
                        <table class="em_wrapper" align="center" width="600" border="0" cellspacing="0" cellpadding="0" style="width:600px; background-color:#FFFFFF">
                            <tr>
                                <td align="center" style="padding:0 10px 0 10px">
                                    <table class="em_wrapper" align="center" width="478" border="0" cellspacing="0" cellpadding="0" style="width:478px;">
                                        <tr>
                                            <td align="center" style="font-family:Arial, Tahoma; font-size:12px; line-height:15px; font-weight:normal; font-style:normal; color:#000000; padding:22px 0 0 0; border-top:1px solid #D1D1D1">
                                                <b>{{ $store->store_name }}</b>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center" style="font-family:Arial, Tahoma; font-size:12px; line-height:14px; font-weight:normal; font-style:normal; color:#000000; padding:4px 0 0 0">
                                                {{ $store->address }}<br />
                                                {{ $store->store_phone }}<br />
                                                <a href="mailto:{{ $store->store_email }}" style="text-decoration:none; color:#000000">{{ $store->store_email }}</a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center" style="padding:0 0 0 0">
                                                <table class="em_wrapper" align="center" width="350" border="0" cellspacing="0" cellpadding="0" style="width:350px;">

                                                    <tr>
                                                        <td align="center" style="padding:20px 0 20px 0">
                                                            <table align="center" border="0" cellspacing="0" cellpadding="0" >
                                                                <tr>
                                                                    @if($store->facebook_url)
                                                                    <td align="center">
                                                                        <a href="{{ $store->facebook_url }}"><img class="em_img" src="{{ env('AWS_URL').'assets/facebook2.png' }}" style="display:block" width="24" height="24" border="0" alt="FB"/></a>
                                                                    </td>
                                                                    @endif
                                                                    <td width="5"></td>
                                                                    @if($store->instagram_url)
                                                                    <td align="center">
                                                                        <a href="{{ $store->instagram_url }}"><img class="em_img" src="{{ env('AWS_URL').'assets/instagram.png' }}" style="display:block" width="24" height="24" border="0" alt="IG"/></a>
                                                                    </td>
                                                                    @endif
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

                <tr>
                    <td align="center" style="border-top:1px solid #D1D1D1;">
                        <table class="em_wrapper" align="center" width="600" border="0" cellspacing="0" cellpadding="0" style="width:600px; background-color:#FFFFFF">
                            <tr>
                                <td align="center" style="padding:0 10px 0 10px">
                                    <table class="em_wrapper" align="center" width="478" border="0" cellspacing="0" cellpadding="0" style="width:478px;">
                                        <tr>
                                            <td align="center" style="font-family:Arial, Tahoma; font-size:12px; line-height:18px; font-weight:normal; font-style:normal; color:#000000; padding:18px 0 0 0">
                                                Uw reservering wordt verwerkt door Eatcard, onze<br class="em_hide"/>
                                                partner voor online reserveren.
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center" style="padding:10px 0 30px 0">
                                                <img src="{{ env('AWS_URL').'assets/eat_card.png' }}" style="display:block" width="56" height="25" border="0" alt="et card"/>
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
