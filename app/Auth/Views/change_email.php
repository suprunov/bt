<div class="login-wrap">
    <div class="card-body">

        <!-- Approve form -->
        <div id="frm-approve">

            <form action="<?= route_to('change-phone-send-code') ?>/" method="post" novalidate>
                <?= csrf_field() ?>

                <div class="">
                    <?= lang('Auth.changeEmailTitle') ?>
                </div>
                <div class="mb-3">
                    <div class="error-message" data-error_message></div>
                </div>

                <div class="">
                    <button type="submit" class="button big w100p"><?= lang('Auth.accept') ?></button>
                </div>
            </form>

        </div>

        <!-- Check code form -->
        <div id="frm-check-code" style="display: none">

            <?= view(config('Auth')->views['pa-check-code'], [
                'formAction'   => route_to('change-phone-check-code', 'email') . '/',
                'phone'        => \App\Helpers\PhoneHelper::format('', auth()->user()->phone, ['type' => 'format_7']),
                'showBackward' => false
            ]) ?>

        </div>

        <!-- Enter email form -->
        <div id="frm-enter-email" style="display: none">

            <form action="<?= route_to('verify-email-send') ?>/" method="post" novalidate>
                <?= csrf_field() ?>

                <!-- Email -->
                <div class="note wide mb-3"><?= lang('Auth.changeEmailLabel') ?></div>
                <div class="form-floating bt-form-floating has-validation is-valid">
                    <input required="" data-validate="email" type="email" class="form-control" id="email" placeholder="Электронная почта" name="email" value="" maxlength="50" data-email>
                    <label for="email"><?= lang('Form.emailLabel') ?></label>
                    <div class="invalid-feedback">
                        <?= lang('Form.emailInvalid') ?>
                    </div>
                    <div class="mb-3">
                        <div class="error-message" data-error_message></div>
                    </div>
                </div>

                <div class="">
                    <button type="submit" class="button big w100p"><?= lang('Auth.send') ?></button>
                </div>

            </form>
        </div>

    </div>
</div>

<script>
    jQuery(function($){
        const formName = 'frm-change-email',
            form = document.getElementById(formName),
            formTitle = form.querySelector('.modal-title .title'),

            /* Approve tab */
            approveTab = form.querySelector('#frm-approve'),
            approveForm = approveTab.querySelector('form'),

            /* Check code tab */
            checkCodeTab = form.querySelector('#frm-check-code'),
            checkCodeForm = checkCodeTab.querySelector('form'),
            confirmationBlock = checkCodeForm.querySelector('[data-confirmation]'),
            confirmationInputs = confirmationBlock.querySelectorAll(['[data-confirmation_input]']),
            confirmationError = checkCodeForm.querySelector('[data-error_message]'),
            repeatCodeBlock = checkCodeForm.querySelector('[data-repeat_code]'),
            repeatCodeButton = repeatCodeBlock.querySelector('[data-submit]'),
            repeatCodeTimer = repeatCodeBlock.querySelector('[data-timer]'),
            timer = new Timer('timer', () => {
                repeatCodeBlock.classList.add('time-off');
                repeatCodeTimer.innerHTML = '01:00';
            }),
            code = checkCodeForm.querySelector('[data-phone_code]'),

            /* Enter email tab */
            enterEmailTab = form.querySelector('#frm-enter-email'),
            enterEmailForm = enterEmailTab.querySelector('form'),
            enterEmailEmail = enterEmailForm.querySelector('[data-email]');

        $(approveForm).on('submit', function (e) {
            e.preventDefault();

            const error = this.querySelector('[data-error_message]');

            $.get({
                url: this.getAttribute('action'),
                dataType: 'json'
            }).done(function (response) {
                $(approveTab).hide();
                $(checkCodeTab).show();
                resizeModal(formName);
                formTitle.innerHTML = 'Введите код из СМС';

                confirmationInputs[0].focus();
                timer.restart(response.timeout ?? 59);
                if (response.timeout ?? true) {
                    repeatCodeBlock.classList.remove('time-off');
                }
            }).fail(function (response) {
                error.innerHTML = response.responseJSON && response.responseJSON.messages ?
                    Object.values(response.responseJSON.messages).join('<br>') : 'Ошибка ответа сервера';
            });
        });

        $(checkCodeForm).on('submit', function(e) {
            e.preventDefault();

            $.ajax({
                url: this.getAttribute('action'),
                method: 'post',
                data: {code: code.value},
                dataType: 'json'
            }).done(function(response){
                $(checkCodeTab).hide();
                $(enterEmailTab).show();
                resizeModal(formName);
                formTitle.innerHTML = 'Введите электронную почту';
            }).fail(function(response){
                confirmationError.innerHTML = response.responseJSON && response.responseJSON.messages ?
                    Object.values(response.responseJSON.messages).join('<br>') : 'Ошибка ответа сервера';
                confirmationBlock.classList.add('is-invalid');
            });
        });

        $(repeatCodeButton).on('click', function(e) {
            e.preventDefault();
            $(approveForm).submit();
            repeatCodeBlock.classList.toggle('time-off');
        });

        // Verification code field
        for (let i = 0; i < confirmationInputs.length; i++) {

            $(confirmationInputs[i]).on('input', function(event) {
                if (this.value.length === 1) {
                    if (i < confirmationInputs.length - 1) {
                        confirmationInputs[i + 1].focus();
                    }
                }
                // Paste phone code
                if (this.value.length > 1) {
                    const pastedData = this.value;
                    this.value = '';
                    if (i === 0 && pastedData.length === confirmationInputs.length) {
                        for (let i = 0; i < pastedData.length; i++) {
                            confirmationInputs[i].value = pastedData.charAt(i);
                        }
                        confirmationInputs[confirmationInputs.length - 1].focus();
                    }
                }
                // Send verification code
                if (confirmationInputs[confirmationInputs.length - 1].value) {
                    let confirmationCode = '';
                    for (let j = 0; j < confirmationInputs.length; j++) {
                        confirmationCode += confirmationInputs[j].value;
                    }
                    if (confirmationCode.length === confirmationInputs.length) {
                        code.value = confirmationCode;
                        $(checkCodeForm).submit();
                    }
                }
            });

            $(confirmationInputs[i]).on('focus', function() {
                for (let j = 0; j < confirmationInputs.length; j++) {
                    if (confirmationInputs[j].value === '') {
                        confirmationInputs[j].focus();
                        break;
                    }
                }
                if (confirmationInputs[confirmationInputs.length-1].value !== '') {
                    confirmationInputs[confirmationInputs.length-1].focus();
                }
            });

            $(confirmationInputs[i]).on('keydown', function(event) {
                if (! (event.keyCode >= 48 && event.keyCode <= 57) &&
                    ! (event.keyCode >= 96 && event.keyCode <= 105) &&
                    ! (event.ctrlKey && event.keyCode === 86)) {
                    event.preventDefault();
                }
                if (event.key === 'Backspace' ) {
                    if (i > 0) {
                        if (i === confirmationInputs.length - 1 && this.value)
                            confirmationInputs[i].value = '';
                        else {
                            confirmationInputs[i - 1].value = '';
                            confirmationInputs[i - 1].focus();
                        }
                    }
                    if (confirmationInputs[0].value === '') {
                        confirmationError.innerHTML = '';
                        confirmationBlock.classList.remove('is-invalid');
                    }
                }
            });
        }

        $(enterEmailForm).on('submit', function(e) {
            e.preventDefault();

            const error = this.querySelector('[data-error_message]');

            if (!processValidate(enterEmailEmail))
                return false;

            $.post({
                url: this.getAttribute('action'),
                data: {email: enterEmailEmail.value},
                dataType: 'json'
            }).done(function(response){
                removeModal(formName);
                $('[data-user_email]').html(enterEmailEmail.value);
                $('[data-user_email_verified]').show();
                $('[data-verify_email]').show();
                $('[data-change-email]').hide();
            }).fail(function(response){
                error.innerHTML = response.responseJSON && response.responseJSON.messages ?
                    Object.values(response.responseJSON.messages).join('<br>') : 'Ошибка ответа сервера';
            });
        });

    })
</script>
<style>.g-recaptcha-wrap{min-height:auto}</style> <? //TEMP till invisible captcha ?>
