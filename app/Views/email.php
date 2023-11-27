<div style="display:none; font-size:1px; color:#333333; line-height:1px; max-height:0px; max-width:0px; opacity:0; overflow:hidden;">
    <!-- PREHEADER START -->
    <?= $this->renderSection('preheader') ?>
    <!-- PREHEADER ENDED -->
</div>
<div style="display:none; max-height:0px; overflow:hidden">&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌<wbr>&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌<wbr>&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌<wbr>&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌<wbr>&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌<wbr>&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌<wbr>&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌<wbr>&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌<wbr>&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌<wbr>&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌<wbr>&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌<wbr>&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌<wbr>&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌<wbr>&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;‌&nbsp;</div>
</div>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
</head>
<body style="padding:0; margin:0; font-family: Arial, Helvetica, sans-serif;">
<table border="0" cellpadding="0" cellspacing="0" width="100%" bgcolor="#8b9195">
    <tr>
        <td align="center">
            <!--[if (gte mso 9)|(gte mso 10)|(IE)]>
            <table align="center" border="0" cellspacing="0" cellpadding="0" width="750">
                <tr>
                    <td align="center" valign="top" width="750">
            <![endif]-->
            <table border="0" cellpadding="0" cellspacing="0" width="100%" bgcolor="#ffffff"
                   style="max-width: 750px; border-radius:0">
                <tr>
                    <td>
                        <table border="0" cellpadding="0" cellspacing="0" width="100%" bgcolor="#64696c" style="border-radius:0;padding-bottom: 7px;padding-top: 10px;">
                            <tr>
                                <td colspan="4" height="12"></td>
                            </tr>
                            <tr>
                                <td width="2%"></td>
                                <td width="125" align="left" valign="middle">
                                    <a href="<?= config('App')->baseURL ?>" target="_blank" style="display:block;font-size:0">
                                        <?= \App\Helpers\ImgHelper::tag(
                                            config('App')->baseURL . "media/static/mail/logo.png",
                                            ["border" => 0, "hspace" => 0, "vspace" => 0, "style" => "width:125px"],
                                            ['check_exist' => false]
                                        ) ?>
                                    </a>
                                </td>
                                <td align="right" valign="middle">
                                    <? if ($phones = service('contacts')->getPhones('forEmail')): ?>
                                        <? foreach ($phones as $phone): ?>
                                            <a href="tel:<?= \App\Helpers\PhoneHelper::format($phone->code, $phone->number, ['type' => 'clear_int']) ?>"
                                               style="text-decoration:none;color:#ffffff;font:16px Arial,sans-serif;line-height:18px">
                                                <?= \App\Helpers\PhoneHelper::format($phone->code, $phone->number) ?>
                                            </a>
                                        <? endforeach ?>
                                    <? elseif ($phones = service('contacts')->getPhones('main')): ?>
                                        <span style="display:inline-block; width:140px">
                                            <a href="tel:<?= \App\Helpers\PhoneHelper::format($phones[0]->code, $phones[0]->number, ['type' => 'clear_int']) ?>"
                                               style="text-decoration:none;color:#ffffff;font:16px Arial,sans-serif;line-height:18px">
                                                <?= \App\Helpers\PhoneHelper::format($phones[0]->code, $phones[0]->number) ?>
                                            </a>
                                        </span>
                                    <? endif ?>
                                </td>
                                <td width="2%"></td>
                            </tr>
                            <tr>
                                <td colspan="4" height="12"></td>
                            </tr>
                            <tr>
                                <td width="2%"></td>
                                <td colspan="2">
                                    <table border="0" cellpadding="0" cellspacing="0" width="100%" align="right" style="max-width:375px;">
                                        <tr>
                                            <td align="left" width="18%">
                                                <a href="<?= config('App')->baseURL ?>catalog-tyres/" style="text-decoration:none;color:#ffffff;font:14px Arial,sans-serif;line-height:16px">Шины</a>
                                            </td>
                                            <td align="center" width="23%">
                                                <a href="<?= config('App')->baseURL ?>catalog-disks/" style="text-decoration:none;color:#ffffff;font:14px Arial,sans-serif;line-height:16px">Диски</a>
                                            </td>
                                            <td align="center" width="32%">
                                                <a href="<?= config('App')->baseURL ?>catalog-mototyres/" style="text-decoration:none;color:#ffffff;font:14px Arial,sans-serif;line-height:16px">Мотошины</a>
                                            </td>
                                            <td align="right" width="27%">
                                                <a href="<?= config('App')->baseURL ?>accessories/" style="text-decoration:none;color:#ffffff;font:14px Arial,sans-serif;line-height:16px">Аксессуары</a>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                <td width="2%"></td>
                            </tr>
                            <tr>
                                <td colspan="4" height="12"></td>
                            </tr>
                        </table>
                        <table border="0" cellpadding="0" cellspacing="0" width="100%">
                            <tr>
                                <td style="padding: 0px 13px 0px 13px">

                                    <!-- CONTENT START -->
                                    <?= $this->renderSection('content') ?>
                                    <!-- CONTENT ENDED -->

                                </td>
                            </tr>
                        </table>
                        <table border="0" cellpadding="0" cellspacing="0" width="100%" bgcolor="#64696c" style="border-radius:0">
                            <tr>
                                <td height="24"></td>
                            </tr>
                            <tr>
                                <td width="2%"></td>
                                <td align="center" valign="top">
                                    <p style="display:inline;width:50%;min-width:200px;font:12px Arial,sans-serif;color:#ffffff;vertical-align:top">
                                        <a href="<?= config('App')->baseURL ?>" style="text-decoration:none;color:#ffffff;font:12px Arial,sans-serif;">
                                            2006-<?= date('Y') ?>&nbsp;&nbsp;BlackTyres - Шины &bull; Диски &bull;
                                            Сервис
                                        </a>
                                        <br/>
                                        <br/>
                                        <span style="line-height:20px">
                                            Прием заказов на сайте:<br/>
                                            Пн-Вс: круглосуточно<br/><br/>
                                        </span>
                                    </p>
                                    <p style="display:inline;width:50%;min-width:200px;font:12px Arial,sans-serif;color:#ffffff;vertical-align:top">

                                        <? if ($phones = service('contacts')->getPhones('forEmail')): ?>
                                            <? foreach ($phones as $phone): ?>
                                                <a href="tel:<?= \App\Helpers\PhoneHelper::format($phone->code, $phone->number, ['type' => 'clear_int']) ?>"
                                                   style="text-decoration:none;color:#ffffff;font:12px Arial,sans-serif;">
                                                    <?= \App\Helpers\PhoneHelper::format($phone->code, $phone->number) ?>
                                                </a>
                                            <? endforeach ?>
                                        <? elseif ($phones = service('contacts')->getPhones('main')): ?>
                                            <span style="display:inline-block; width:140px">
                                                <a href="tel:<?= \App\Helpers\PhoneHelper::format($phones[0]->code, $phones[0]->number, ['type' => 'clear_int']) ?>"
                                                   style="text-decoration:none;color:#ffffff;font:12px Arial,sans-serif;">
                                                    <?= \App\Helpers\PhoneHelper::format($phones[0]->code, $phones[0]->number) ?>
                                                </a>
                                            </span>
                                        <? endif ?>
                                        <br/>
                                        <br/>
                                        <? if ($emails = service('contacts')->getEmails()): ?>
                                            <a href="mailto:<?= $emails[0] ?>" style="font:12px Arial,sans-serif;color:#ffffff;line-height:14px" class="email">
                                                <?= $emails[0] ?>
                                            </a>
                                            <br/>
                                            <br/>
                                        <? endif ?>
                                        <span>
                                            <? /* <a href="https://www.instagram.com/blacktyres.ru/" target="_blank">
                                                <img src="<?= config('App')->baseURL ?>media/static/mail/icon_in.png" style="margin:0 1px"  />
                                            </a> */ ?>
                                            <a href="https://www.youtube.com/channel/UCnI-XQ2vynGN81OJXIWytiQ"
                                               target="_blank">
                                                <img src="<?= config('App')->baseURL ?>media/static/mail/icon_yt.png" style="margin:0 1px"/>
                                            </a>
                                            <a href="https://vk.com/clubblacktyres" target="_blank">
                                                <img src="<?= config('App')->baseURL ?>media/static/mail/icon_vk.png" style="margin:0 1px"/>
                                            </a>
                                            <? /*<a href="https://www.facebook.com/blacktyres.ru/" target="_blank">
                                                <img src="<?= config('App')->baseURL ?>media/static/mail/icon_fb.png" style="margin:0 1px" />
                                            </a>*/ ?>
                                            <a href="https://ok.ru/blacktyres" target="_blank">
                                                <img src="<?= config('App')->baseURL ?>media/static/mail/icon_ok.png" style="margin:0 1px"/>
                                            </a>
                                        </span>
                                    </p>
                                </td>
                                <td width="2%"></td>
                            </tr>
                            <tr>
                                <td height="24"></td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <!--[if (gte mso 9)|(gte mso 10)|(IE)]>
                    </td>
                </tr>
            </table>
            <![endif]-->
        </td>
    </tr>
</table>
</body>
</html>
