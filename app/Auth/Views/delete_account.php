<div class="login-wrap">
    <div class="card-body">

        <!-- Approve form -->
        <div id="frm-approve">

            <form action="<?= route_to('change-phone-send-code') ?>/" method="post" novalidate>
                <?= csrf_field() ?>

                <div class="mb-3">
                    Подтвердите удаление аккаунт <?= \App\Helpers\PhoneHelper::format('', auth()->user()->phone, ['type' => 'format_7']) ?>.<br>
                    Мы отправим вам код по СМС для подтверждения.
                </div>
                <div class="mb-3">
                    <b>Обратите внимание!</b> Вместе с удалением аккаунта с сайта будут полностью удалены ваши данные: заказы, персональная информация, договора сезонного хранения и автомобили.
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
                'formAction'   => route_to('delete-account-action') . '/',
                'phone'        => \App\Helpers\PhoneHelper::format('', auth()->user()->phone, ['type' => 'format_7']),
                'showBackward' => false
            ]) ?>

        </div>

    </div>
</div>

<script>
    jQuery(function($){
        const formName = 'frm-delete-account',
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
            code = checkCodeForm.querySelector('[data-phone_code]');

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
                window.location.reload();
            }).fail(function(response){
                confirmationError.innerHTML = response.responseJSON && response.responseJSON.messages ?
                    Object.values(response.responseJSON.messages).join('<br>') : 'Ошибка ответа сервера';
                confirmationBlock.classList.add('is-invalid');
            });
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

    })
</script>
