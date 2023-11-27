<?php
/**
 * @var string $formAction
 * @var string $phone
 * @var string $showBackward
 */
?>
<div class="mb-3">
    <?= lang('Auth.codeSentLabel') ?> <span data-phone_number><?= $phone ?? '' ?></span>
</div>

<form action="<?= $formAction ?>" method="post">
    <?= csrf_field() ?>

    <!-- Phone code -->
    <div class="confirmation-form form-floating has-validation" data-confirmation >
        <input type="text" inputmode="numeric" class="form-control confirmation-input" maxlength="6" data-confirmation_input autocomplete="off" autofocus="autofocus" name="otp" />
        <input type="text" inputmode="numeric" class="form-control confirmation-input" maxlength="1" data-confirmation_input autocomplete="off" />
        <input type="text" inputmode="numeric" class="form-control confirmation-input" maxlength="1" data-confirmation_input autocomplete="off" />
        <input type="text" inputmode="numeric" class="form-control confirmation-input" maxlength="1" data-confirmation_input autocomplete="off" />
        <input type="text" inputmode="numeric" class="form-control confirmation-input" maxlength="1" data-confirmation_input autocomplete="off" />
        <input type="text" inputmode="numeric" class="form-control confirmation-input" maxlength="1" data-confirmation_input autocomplete="off" />
        <input type="hidden" name="code" data-phone_code/>
    </div>

    <div class="text-center" style="">
        <div class="error-message" data-error_message></div>
    </div>

    <div class="repeat-code mt-3 mb-3" data-repeat_code>
        <div class="repeat-code-timer text-center">
            <?= lang('Auth.requestCodeAgainIn') ?> <span id="timer" data-timer>01:00</span>
        </div>
        <div class="repeat-code-button">
            <div class="mt-3 mb-3">
                <?= service('recaptcha')->view() ?>
            </div>
            <div class="mb-3">
                <div class="error-message" data-error_message></div>
            </div>
            <button type="button" class="button big w100p" data-submit><?= lang('Auth.getCodeRepeat') ?></button>
        </div>
    </div>

</form>

<? if ($showBackward ?? false): ?>
    <div><a href="#" class="backward" data-backward <? if ($showBackward === 'hidden'): ?>style="display: none"<? endif ?> ><?= lang('Form.backward') ?></a></div>
<? endif ?>