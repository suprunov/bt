<?php

return [
    // Buttons
    'send'                  => 'Отправить',
    'accept'                => 'Подтвердить',
    // Login form
    'phoneLabel'            => 'Введите номер телефона. Мы отправим вам код по СМС.',
    'rememberMe'            => 'Чужой компьютер',
    'getCode'               => 'Получить код',
    'alreadyLoggedIn'       => 'Вы уже авторизованы',
    'smsNotSent'            => 'Не получилось отправить SMS',
    'userIsBlocked'         => 'Ваша учетная запись заблокирована',
    'requestLimit'          => 'Превышен лимит запроса проверочного кода.',
    // Check code form
    'codeSentLabel'         => 'Введите код. Мы отправили СМС с кодом подтверждения на номер',
    'requestCodeAgainIn'    => 'Получить новый код можно через',
    'requestCodeAgain'      => 'Запросить код повторно',
    'incorrectCode'         => 'Неверно введен код',
    'getCodeRepeat'         => 'Запросить код повторно',
    'userNotFound'          => 'Пользователь не найден',
    'codeExpired'           => 'Срок действия кода истек',
    // Basic registration form
    'register'              => 'Зарегистрироваться',
    'unauthorized'          => 'Вы не авторизованы',
    'emailIsAlreadyTaken'   => 'Данный email уже привязан к другой учетной записи',
    'INNIsAlreadyTaken'     => 'Компания с таким ИНН уже зарегистрирована',
    // Email verification
    'incorrectLink'            => 'Неверная или истекшая ссылка',
    'emailVerifySuccess'       => 'Ваша электронная почта <b style="white-space: nowrap">{0}</b> успешно подтверждена.',
    'continue'                 => 'Отправить',
    'verifyEmailTitle'         => 'Введите адрес электронной почты. Мы отправим вам письмо для подтверждения.',
    'emailSentToYou'           => 'Вам отправлено письмо. Для подтверждения email пройдите из него по ссылке.',
    'emailSentError'           => 'Не удалось отправить email.',
    'emailAlreadyVerified'     => 'Ваша почта уже подтверждена.',

    // Change phone form
    'changePhoneTitle'       => 'Подтвердите смену номера телефона <b style="white-space: nowrap">{0}</b>.<br>Мы отправим вам код по СМС для подтверждения.',
    'changePhoneLabel'       => 'Введите новый номер телефона для авторизации в личном кабинете на сайте.',
    'phoneIsAlreadyInUse'    => 'Данный номер уже привязан к другой учетной записи',
    'changePhoneCodeExpired' => 'Время подтверждения истекло.<br>Вам необходимо заново подтвердить ваш текущий номер телефона.',

    // Change email form
    'changeEmailTitle'       => 'Подтвердите смену электронной почты.<br>Мы отправим вам код по СМС для подтверждения.',
    'changeEmailLabel'       => 'Введите новый адрес электронной почты. Мы отправим вам письмо для подтверждения.',

    // TODO:
    // Exceptions
    'unknownAuthenticator'  => '{0} is not a valid authenticator.',
    'unknownUserProvider'   => 'Unable to determine the User Provider to use.',
    'invalidUser'           => 'Unable to locate the specified user.',
    'badAttempt'            => 'Unable to log you in. Please check your credentials.',
    'noPassword'            => 'Cannot validate a user without a password.',
    'invalidPassword'       => 'Unable to log you in. Please check your password.',
    'noToken'               => 'Every request must have a bearer token in the Authorization header.',
    'badToken'              => 'The access token is invalid.',
    'oldToken'              => 'The access token has expired.',
    'noUserEntity'          => 'User Entity must be provided for password validation.',
    'invalidEmail'          => 'Unable to verify the email address matches the email on record.',
    'unableSendEmailToUser' => 'Sorry, there was a problem sending the email. We could not send an email to "{0}".',
    'throttled'             => 'Too many requests made from this IP address. You may try again in {0} seconds.',


    'email'           => 'Email Address',
    'username'        => 'Username',
    'password'        => 'Password',
    'passwordConfirm' => 'Password (again)',
    'haveAccount'     => 'Already have an account?',

    // Registration
    'registerDisabled' => 'Registration is not currently allowed.',
    'registerSuccess'  => 'Welcome aboard!',

    // Login
    'login'              => 'Login',
    'needAccount'        => 'Need an account?',
    'forgotPassword'     => 'Forgot your password?',
    'useMagicLink'       => 'Use a Login Link',
    'magicLinkSubject'   => 'Your Login Link',
    'magicTokenNotFound' => 'Unable to verify the link.',
    'magicLinkExpired'   => 'Sorry, link has expired.',
    'checkYourEmail'     => 'Check your email!',
    'magicLinkDetails'   => 'We just sent you an email with a Login link inside. It is only valid for {0} minutes.',
    'successLogout'      => 'You have successfully logged out.',

    // Passwords
    'errorPasswordLength'       => 'Passwords must be at least {0, number} characters long.',
    'suggestPasswordLength'     => 'Pass phrases - up to 255 characters long - make more secure passwords that are easy to remember.',
    'errorPasswordCommon'       => 'Password must not be a common password.',
    'suggestPasswordCommon'     => 'The password was checked against over 65k commonly used passwords or passwords that have been leaked through hacks.',
    'errorPasswordPersonal'     => 'Passwords cannot contain re-hashed personal information.',
    'suggestPasswordPersonal'   => 'Variations on your email address or username should not be used for passwords.',
    'errorPasswordTooSimilar'   => 'Password is too similar to the username.',
    'suggestPasswordTooSimilar' => 'Do not use parts of your username in your password.',
    'errorPasswordPwned'        => 'The password {0} has been exposed due to a data breach and has been seen {1, number} times in {2} of compromised passwords.',
    'suggestPasswordPwned'      => '{0} should never be used as a password. If you are using it anywhere change it immediately.',
    'errorPasswordEmpty'        => 'A Password is required.',
    'passwordChangeSuccess'     => 'Password changed successfully',
    'userDoesNotExist'          => 'Password was not changed. User does not exist',
    'resetTokenExpired'         => 'Sorry. Your reset token has expired.',

    // 2FA
    'email2FATitle'       => 'Two Factor Authentication',
    'confirmEmailAddress' => 'Confirm your email address.',
    'emailEnterCode'      => 'Confirm your Email',
    'emailConfirmCode'    => 'Enter the 6-digit code we just sent to your email address.',
    'email2FASubject'     => 'Your authentication code',
    'email2FAMailBody'    => 'Your authentication code is:',
    'invalid2FAToken'     => 'The code was incorrect.',
    'need2FA'             => 'You must complete a two-factor verification.',
    'needVerification'    => 'Check your email to complete account activation.',

    // Activate
    'emailActivateTitle'    => 'Email Activation',
    'emailActivateBody'     => 'We just sent an email to you with a code to confirm your email address. Copy that code and paste it below.',
    'emailActivateSubject'  => 'Your activation code',
    'emailActivateMailBody' => 'Please use the code below to activate your account and start using the site.',
    'invalidActivateToken'  => 'The code was incorrect.',

    // Groups
    'unknownGroup' => '{0} is not a valid group.',
    'missingTitle' => 'Groups must have a title.',

    // Permissions
    'unknownPermission' => '{0} is not a valid permission.',
];
