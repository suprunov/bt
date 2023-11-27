<?php
/**
 * @var strign $email
 */
?>
<div class="login-wrap">

    <form action="<?= route_to('verify-email-send') ?>/" method="post" novalidate>
        <?= csrf_field() ?>

        <div class="mb-3"><?= lang('Auth.verifyEmailTitle') ?></div>

        <div class="form-floating bt-form-floating has-validation is-valid">
            <input required="" data-validate="email" type="email" class="form-control" id="email" placeholder="Электронная почта" name="email" value="<?= esc($email) ?>" maxlength="50">
            <label for="email">Электронная почта</label>
            <div class="invalid-feedback">
                Требуется ввести email в формате sample@domain.ru
            </div>
            <div class="mb-3">
                <div class="error-message" data-error_message></div>
            </div>
        </div>

        <div class="">
            <button type="submit" class="button big w100p"><?= lang('Auth.continue') ?></button>
        </div>

    </form>

</div>

