<?php
/**
 * @var string $link
 * @var string $name
 */
?>
<?= $this->extend('email') ?>


<?= $this->section('preheader') ?>
Перейдите по ссылке для подтверждения email-адреса
<?= $this->endSection() ?>


<?= $this->section('content') ?>
<table border="0" cellpadding="0" cellspacing="0" width="100%" style="color: black;">
    <tr><td colspan="3" height="30"></td></tr>
    <tr>
        <td>
            <span style="font:22px Arial,sans-serif;"><b>Подтвердите электронную почту</b></span>
        </td>
    </tr>
    <tr><td colspan="3" height="34"></td></tr>
    <tr>
        <td>
            <span style="font:14px Arial,sans-serif;">Здравствуйте, <?= $name ?>!</span>
        </td>
    </tr>
    <tr><td colspan="3" height="24"></td></tr>
    <tr>
        <td>
            <span style="font:14px Arial,sans-serif;">
                Перейдите по ссылке для подтверждения email-адреса: <a href="<?= $link ?>/" target="_blank" style="font-weight:700!important;font:14px Arial,sans-serif;color:#104f6e">подтвердить</a>
            </span>
        </td>
    </tr>
    <tr><td colspan="3" height="24"></td></tr>
    <tr>
        <td>
            <span style="font:14px Arial,sans-serif;">
                C уважанием, команда Blacktyres.
            </span>
        </td>
    </tr>
    <tr><td colspan="3" height="30"></td></tr>
</table>
<?= $this->endSection() ?>
