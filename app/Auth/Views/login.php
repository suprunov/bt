<link href="/media/plugin/dadata/suggestions.css" type="text/css" rel="stylesheet"/>
<script type="text/javascript" src="/media/plugin/dadata/jquery.suggestions.min.js" ></script>

<div class="login-wrap">
    <div class="card-body">

        <!-- Enter phone form -->
        <div id="frm-enter-phone">

            <form action="<?= route_to('login-send-code') ?>/" method="post" novalidate>
                <?= csrf_field() ?>

                <!-- Phone -->
                <div class="note lh1 mb-3"><?= lang('Auth.phoneLabel') ?></div>
                <!--  <div class="error-message mb-3" >Наблюдаются проблемы с отправкой SMS оператором MТС. Для оформления заказа обратитесь в колл-центр <a class="mgo-number noblur" href="tel:+74952152068" style="text-wrap: nowrap;">+7 (495) 215-20-68</a></div>-->
                <div class="form-floating mb-3 has-validation is-valid">
                    <input required="" data-validate="phone_masked" data-mask="phone" inputmode="numeric" placeholder="phone"
                           autocomplete="off" class="form-control" id="phone" name="phone" value="" data-phone_number>
                    <label for="phone"><?= lang('Form.phoneLabel') ?></label>
                    <div class="invalid-feedback">
                        <?= lang('Form.required') ?> <?= lang('Form.phoneInvalid') ?>
                    </div>
                    <div class="mb-3">
                        <div class="error-message" data-error_message></div>
                    </div>
                </div>

                <div class="mb-3">
                    <?= service('recaptcha')->view() ?>
                </div>

                <div class="mb-3">
                    <button type="submit" class="button big w100p"><?= lang('Auth.getCode') ?></button>
                </div>

                <!-- Remember me -->
                <? if (setting('Auth.sessionConfig')['allowRemembering']): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="checkRemember" name="remember" data-remember>
                        <label class="form-check-label" for="checkRemember"><?= lang('Auth.rememberMe') ?></label>
                    </div>
                <? endif ?>

            </form>
        </div>

        <!-- Code verification form -->
        <div id="frm-check-code" style="display: none">

            <?= view(config('Auth')->views['pa-check-code'], [
                'formAction'   => route_to('login-check-code') . '/',
                'showBackward' => true
            ]) ?>

        </div>

        <!-- Basic register form -->
        <div id="frm-register" style="display: none">

            <form action="<?= route_to('login-register') ?>/" method="post" novalidate>
                <?= csrf_field() ?>

                <div class="d-flex flex-row mb-3">
                    <div class="form-check mr-3">
                        <input class="form-check-input" type="radio" name="legal_status" id="flexRadioDefault1" value="fiz" checked>
                        <label class="form-check-label" for="flexRadioDefault1">
                            Физическое лицо
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="legal_status" id="flexRadioDefault2" value="yur">
                        <label class="form-check-label" for="flexRadioDefault2">
                            Юридическое лицо
                        </label>
                    </div>
                </div>

                <div class="form-floating bt-form-floating mb-3 has-validation is-valid">
                    <input required="" data-validate="enru" data-validate_input="name" data-legal type="text" class="form-control" id="name"
                           placeholder="Имя" name="name" value="" minlength="1" maxlength="50">
                    <label for="name">Имя</label>
                    <div class="invalid-feedback">
                        Обязательно для заполнения. Напишите имя на кириллице или латинице.
                    </div>
                </div>
                <div class="form-floating bt-form-floating mb-3 has-validation is-valid" style="display: none">
                    <input required="" data-validate="123" data-mask="numeric" data-legal class="form-control" id="inn" inputmode="numeric"
                           minlength="10" maxlength="12" name="inn" value="" placeholder="ИНН">
                    <label for="order_client_form_org_inn">ИНН</label>
                    <div class="invalid-feedback">
                        Обязательно для заполнения. Напишите 10 или 12 (для ИП) цифр ИНН.
                    </div>
                </div>

                <div class="form-floating bt-form-floating has-validation is-valid">
                    <input required="" data-validate="email" type="email" class="form-control" id="email" placeholder="Электронная почта"
                           name="email" value="" maxlength="50">
                    <label for="email">Электронная почта</label>
                    <div class="invalid-feedback">
                        Требуется ввести email в формате sample@domain.ru
                    </div>
                </div>

                <div class="">
                    <div class="error-message" data-error_message></div>
                </div>

                <div class="mt-3 mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="mailAgree" name="mail_agree" checked>
                        <label class="form-check-label" for="mailAgree">Согласен(на) на рассылку уведомлений и акций</label>
                    </div>
                </div>

                <div class="mb-3">
                    <?= view('form/agreement', ['agreementId' => 'login']) ?>
                </div>

                <div class="mb-3">
                    <button type="submit" class="button big w100p" data-agreement_bond="login"><?= lang('Auth.register') ?></button>
                </div>

            </form>

            <div><a href="#" class="backward" data-backward><?= lang('Form.backward') ?></a></div>

        </div>

    </div>
</div>

<script>
    jQuery(function ($) {
        $BT.plugin.recaptcha.init(); // TODO layout
        const formName = 'frm-login',
            form = document.getElementById(formName),
            formTitle = form.querySelector('.modal-title .title') || form.querySelector('.modal-title'), // TODO layout: '.modal-title .title' remove

            /* Enter phone tab */
            enterPhoneTab = form.querySelector('#frm-enter-phone'),
            enterPhoneForm = enterPhoneTab.querySelector('form'),
            phone = enterPhoneForm.querySelector('[data-phone_number]'),
            remember = enterPhoneForm.querySelector('[data-remember]'),

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
                if (typeof resizeModal === 'function') { // TODO layout
                    resizeModal(formName);
                }
            }),
            phoneCheckCode = checkCodeTab.querySelector('[data-phone_number]'),
            code = checkCodeForm.querySelector('[data-phone_code]'),

            /* Register tab */
            registerTab = form.querySelector('#frm-register'),
            registerForm = registerTab.querySelector('form');

        function sendEnterForm(launchingForm) {
            const errorHtml = launchingForm.querySelector('[data-error_message]');
            const captcha = launchingForm.querySelector('.g-recaptcha-response');

            let validated = true;
            [phone, captcha].forEach(function (item) {
                if (item && !processValidate(item))
                    validated = false;
            });
            if (!validated)
                return;

            fetch(enterPhoneForm.getAttribute('action'), {
                method: 'POST',
                headers: {'X-Requested-With': 'XMLHttpRequest', 'Content-type': 'application/x-www-form-urlencoded'},
                body: `phone=${phone.value}&remember=${remember.checked ? 1 : 0}&g-recaptcha-response=${captcha ? captcha.value : ''}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.messages) {
                        let error = new Error('Ошибка');
                        error.messages = Object.values(data.messages).join('<br>');
                        throw error;
                    }
                    enterPhoneTab.style.display = 'none';
                    checkCodeTab.style.display = 'block';
                    if (typeof resizeModal === 'function') { // TODO layout
                        resizeModal(formName);
                    }
                    phoneCheckCode.innerHTML = phone.value;
                    confirmationInputs[0].focus();
                    timer.restart(data.timeout ?? 59);
                    repeatCodeBlock.classList.remove('time-off');
                    if (captcha) {
                        grecaptcha.reset();
                    }
                })
                .catch(error => {
                    errorHtml.innerHTML = error.messages ?? 'Ошибка ответа сервера';
                });
        }

        $(enterPhoneForm).on('submit', function (event) {
            event.preventDefault();
            sendEnterForm(this);
        });

        $(checkCodeTab).find('[data-backward]').on('click', function (e) {
            e.preventDefault();

            $(checkCodeTab).hide();
            $(enterPhoneTab).show();
            if (typeof resizeModal === 'function') { // TODO layout
                resizeModal(formName);
            }
            for (let i = 0; i < confirmationInputs.length; i++) {
                confirmationInputs[i].value = '';
            }
            const captcha = enterPhoneForm.querySelector('.g-recaptcha-response');
            if (captcha) {
                grecaptcha.reset();
            }
        });

        $(checkCodeForm).on('submit', function (e) {
            e.preventDefault();

            fetch(this.getAttribute('action'), {
                method: 'POST',
                headers: {'X-Requested-With': 'XMLHttpRequest', 'Content-type': 'application/x-www-form-urlencoded'},
                body: `phone=${phone.value}&code=${code.value}&remember=${remember.checked ? 1 : 0}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.messages) {
                        let error = new Error('Ошибка');
                        error.messages = Object.values(data.messages).join('<br>');
                        throw error;
                    }
                    if (data.result === 'registration_required') {
                        checkCodeTab.style.display = 'none';
                        registerTab.style.display = 'block';
                        if (typeof resizeModal === 'function') { // TODO layout
                            resizeModal(formName);
                        }
                        formTitle.innerHTML = 'Зарегистрироваться';
                    } else {
                        window.location.reload();
                    }
                })
                .catch(error => {
                    confirmationError.innerHTML = error.messages ?? 'Ошибка ответа сервера';
                    confirmationBlock.classList.add('is-invalid');
                });

        });

        $(repeatCodeButton).on('click', function (e) {
            e.preventDefault();
            sendEnterForm(checkCodeForm);
        });

        // Verification code field
        for (let i = 0; i < confirmationInputs.length; i++) {

            $(confirmationInputs[i]).on('input', function (event) {
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

            $(confirmationInputs[i]).on('focus', function () {
                for (let j = 0; j < confirmationInputs.length; j++) {
                    if (confirmationInputs[j].value === '') {
                        confirmationInputs[j].focus();
                        break;
                    }
                }
                if (confirmationInputs[confirmationInputs.length - 1].value !== '') {
                    confirmationInputs[confirmationInputs.length - 1].focus();
                }
            });

            $(confirmationInputs[i]).on('keydown', function (event) {
                if (!(event.keyCode >= 48 && event.keyCode <= 57) &&
                    !(event.keyCode >= 96 && event.keyCode <= 105) &&
                    !(event.ctrlKey && event.keyCode === 86)) {
                    event.preventDefault();
                }
                if (event.key === 'Backspace') {
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

        $(registerForm).on('submit', function (e) {
            e.preventDefault();

            const errorHtml = this.querySelector('[data-error_message]'),
                name = this.querySelector('[name="name"]'),
                inn = this.querySelector('[name="inn"]'),
                email = this.querySelector('[name="email"]'),
                mailAgree = this.querySelector('[name="mail_agree"]'),
                dataToValidate = this.querySelectorAll('input[data-validate]'),
                legalStatus = this.querySelector('[name="legal_status"]:checked');

            let validate = true;
            for (let i = 0; i < dataToValidate.length; i++) {
                if (dataToValidate[i].offsetHeight > 0 && dataToValidate[i].offsetWidth > 0) {
                    if (!processValidate(dataToValidate[i]))
                        validate = false
                }
            }
            if (!validate)
                return;

            fetch(this.getAttribute('action'), {
                method: 'POST',
                headers: {'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded'},
                body: `phone=${phone.value}&code=${code.value}&remember=${remember.checked ? 1 : 0}&name=${name.value}&inn=${inn.value}&email=${email.value}&mailAgree=${mailAgree.checked ? 1 : 0}&legalStatus=${legalStatus.value}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.messages) {
                        let error = new Error('Ошибка');
                        error.messages = Object.values(data.messages).join('<br>');
                        throw error;
                    }
                    window.location.reload();
                })
                .catch(error => {
                    errorHtml.innerHTML = error.messages ?? 'Ошибка ответа сервера';
                });
        });

        $(registerForm).find('[name="legal_status"]').each(function (i, element) {
            $(element).on('change', function (e) {
                const legalDataTabs = registerForm.querySelectorAll('[data-legal]');
                legalDataTabs.forEach(function (element) {
                    const parent = element.closest('.form-floating');
                    if (parent) {
                        parent.style.display = parent.style.display === 'none' ? 'block' : 'none';
                    }
                });
            });
        });

        registerForm.querySelectorAll('input').forEach(function (element) {
            const error = registerForm.querySelector('[data-error_message]');
            element.addEventListener('input', function (e) {
                error.innerHTML = '';
            });
        });

        $(registerTab).find('[data-backward]').on('click', function (e) {
            e.preventDefault();

            $(registerTab).hide();
            $(enterPhoneTab).show();
            formTitle.innerHTML = 'Войти';
            if (typeof resizeModal === 'function') { // TODO layout
                resizeModal(formName);
            }
            for (let i = 0; i < confirmationInputs.length; i++) {
                confirmationInputs[i].value = '';
            }
        });

        $('[name="inn"]').suggestions({
            noCache: true,
            deferRequestBy: 20,
            serviceUrl: "https://suggestions.dadata.ru/suggestions/api/4_1/rs",
            token: "293375e9ae409aa7f07db1d5df4aba939623f30a",
            type: "PARTY",
            onSelect: function (suggestion) {
                const data = suggestion.data;
                if (data) {
                    registerForm.querySelector('[name="inn"]').value = data.inn;
                }
            }
        });

    })
</script>
