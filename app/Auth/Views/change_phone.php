<div class="login-wrap">
    <div class="card-body">

        <!-- Approve form -->
        <div id="frm-approve">

            <form action="<?= route_to('change-phone-send-code') ?>/" method="post" novalidate>
                <?= csrf_field() ?>

                <div class="">
                    <?= lang('Auth.changePhoneTitle', [\App\Helpers\PhoneHelper::format('', auth()->user()->phone, ['type' => 'format_7'])]) ?>
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
                'formAction'   => route_to('change-phone-check-code', 'phone') . '/',
                'phone'        => \App\Helpers\PhoneHelper::format('', auth()->user()->phone, ['type' => 'format_7']),
                'showBackward' => 'hidden'
            ]) ?>

        </div>

        <!-- Enter phone form -->
        <div id="frm-enter-phone" style="display: none">

            <form action="<?= route_to('change-phone-send-code-2nd') ?>/" method="post" novalidate>
                <?= csrf_field() ?>

                <!-- Phone -->
                <div class="note wide mb-3"><?= lang('Auth.changePhoneLabel') ?></div>
                <div class="form-floating mb-3 has-validation is-valid">
                    <input required="" data-validate="phone_masked" data-mask="phone" inputmode="numeric" placeholder="phone" class="form-control" id="phone" name="phone" value="" data-phone_number>
                    <label for="phone"><?= lang('Form.phoneLabel') ?></label>
                    <div class="invalid-feedback">
                        <?= lang('Form.required') ?> <?= lang('Form.phoneInvalid') ?>
                    </div>
                    <div class="mb-3">
                        <div class="error-message" data-error_message></div>
                    </div>
                </div>

                <div class="">
                    <button type="submit" class="button big w100p"><?= lang('Auth.getCode') ?></button>
                </div>

            </form>
        </div>

    </div>
</div>

<script>
    jQuery(function($){
        const formName = 'frm-change-phone',
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
            checkCodePhone = checkCodeTab.querySelector('[data-phone_number]'),

            /* Enter phone tab */
            enterPhoneTab = form.querySelector('#frm-enter-phone'),
            enterPhoneForm = enterPhoneTab.querySelector('form'),
            enterPhonePhone = enterPhoneForm.querySelector('[data-phone_number]');

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
                data: {code: code.value, phone: enterPhonePhone.value},
                dataType: 'json'
            }).done(function(response){
                if (response.step == 1) {
                    $(checkCodeTab).hide();
                    $(enterPhoneTab).show();
                    resizeModal(formName);
                    formTitle.innerHTML = 'Введите номер телефона';
                } else if (response.step == 2) {
                    $('[data-user_phone]').html(enterPhonePhone.value);
                    removeModal(formName);
                }
            }).fail(function(response){
                confirmationError.innerHTML = response.responseJSON && response.responseJSON.messages ?
                    Object.values(response.responseJSON.messages).join('<br>') : 'Ошибка ответа сервера';
                confirmationBlock.classList.add('is-invalid');
            });
        });

        $(checkCodeTab).find('[data-backward]').on('click', function(e) {
            e.preventDefault();

            $(checkCodeTab).hide();
            $(enterPhoneTab).show();
            resizeModal(formName);
            for (let i = 0; i < confirmationInputs.length; i++) {
                confirmationInputs[i].value = '';
            }
        });

        $(repeatCodeButton).on('click', function(e) {
            e.preventDefault();
            if (checkCodeForm.action === location.origin + '<?= route_to('change-phone-check-code', 'phone') ?>/')
                $(approveForm).submit();
            else
                $(enterPhoneForm).submit();
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

        $(enterPhoneForm).on('submit', function(e) {
            e.preventDefault();

            const error = this.querySelector('[data-error_message]');

            if (!processValidate(enterPhonePhone))
                return false;

            $.post({
                url: this.getAttribute('action'),
                data: {phone: enterPhonePhone.value},
                dataType: 'json'
            }).done(function(response){
                $(enterPhoneTab).hide();
                $(checkCodeTab).show();
                $(checkCodeTab).find('[data-backward]').show();
                resizeModal(formName);
                formTitle.innerHTML = 'Введите код из СМС';

                checkCodePhone.innerHTML = $BT.f.formatPhone(enterPhonePhone.value);
                checkCodeForm.action = '<?= route_to('change-phone-check-code-2nd') ?>/';

                $(confirmationInputs).val('');
                confirmationInputs[0].focus();
                timer.restart(response.timeout ?? 59);
                if (response.timeout ?? true) {
                    repeatCodeBlock.classList.remove('time-off');
                }
            }).fail(function(response){
                error.innerHTML = response.responseJSON && response.responseJSON.messages ?
                    Object.values(response.responseJSON.messages).join('<br>') : 'Ошибка ответа сервера';
            });
        });

    })
</script>
<style>.g-recaptcha-wrap{min-height:auto}</style> <? //TEMP till invisible captcha ?>
