<?php

namespace App\Libraries;

use App\Helpers\HTMLHelper;

class Recaptcha
{
    const SITE_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';
    public bool $enabled = false;
    protected string $privateKey = '';
    protected string $publicKey = '';

    public function __construct()
    {
        $this->enabled = config('App')->captchaEnabled;
        $this->privateKey = config('App')->captchaPrivateKey;
        $this->publicKey = config('App')->captchaPublicKey;
        helper('common'); // TOTO TEMP delete after layout branch merge
    }

    public function check($response, $remoteIp = null) // TODO move response and remoteip inside this function
    {
        if (!$this->enabled)
            return true;

        return $this->verify($response, $remoteIp)->success;
    }

    public function verify($response, $remoteIp = null)
    {
        // Discard empty solution submissions
        if (empty($response)) {
            return $this->Response(false, array('missing-input-response'));
        }

        $rawResponse = $this->submit(array(
            'secret'   => $this->privateKey,
            'response' => $response,
            'remoteip' => $remoteIp,
        ));

        $logMessage = 'folder:captcha;' . print_r([
                'remoteIp'       => $remoteIp,
                'remoteResponse' => $response,
                'request'        => $_REQUEST,
                'response'       => $rawResponse,
                'server'         => $_SERVER
            ], true);
        log_message('notice', $logMessage);

        return $this->fromJson($rawResponse);
    }

    public function Response($success, array $errorCodes = array())
    {
        return (object)array(
            'success'    => $success,
            'errorCodes' => $errorCodes
        );
    }

    public function submit($params)
    {
        /**
         * PHP 5.6.0 changed the way you specify the peer name for SSL context options.
         * Using "CN_name" will still work, but it will raise deprecated errors.
         */
        $peer_key = version_compare(PHP_VERSION, '5.6.0', '<') ? 'CN_name' : 'peer_name';
        $options = array(
            'http' => array(
                'header'      => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'      => 'POST',
                'content'     => http_build_query($params, '', '&'),
                // Force the peer to validate (not needed in 5.6.0+, but still works
                'verify_peer' => true,
                // Force the peer validation to use www.google.com
                $peer_key     => 'www.google.com',
            ),
        );
        $context = stream_context_create($options);
        return file_get_contents(self::SITE_VERIFY_URL, false, $context);
    }

    public function fromJson($json)
    {
        $responseData = json_decode($json, true);

        if (!$responseData) {
            return $this->Response(false, array('invalid-json'));
        }

        if (isset($responseData['success']) && $responseData['success'] == true) {
            return $this->Response(true);
        }

        if (isset($responseData['error-codes']) && is_array($responseData['error-codes'])) {
            return $this->Response(false, $responseData['error-codes']);
        }

        return $this->Response(false);
    }

    public function view($idElement = false)
    {
        $tagCaptcha = ['class' => 'g-recaptcha form-control', 'data-sitekey' => $this->publicKey, 'data-callback' => 'onRecaptchaSubmit'];
        if ($idElement)
            $tagCaptcha['id'] = $idElement;

        return $this->enabled ?
            HTMLHelper::tag(
                'div',
                ['class' => 'g-recaptcha-wrap has-validation'],
                HTMLHelper::tag(
                    'div',
                    $tagCaptcha
                ) .
                HTMLHelper::tag(
                    'div',
                    ['class' => 'invalid-feedback'],
                    'Обязательно для заполнения. Установите галочку "Я не робот".'
                )
            )
            : '';
    }

    public function script()
    {
        return "<script>
            class RecaptchaInitiator {
              constructor() {
                this.loaded = false;
                this.enabled = " . ($this->enabled ? 'true' : 'false') . ";
              }
              init() {
                if (!this.enabled || this.loaded) 
                    return;
                const script = document.createElement('script');
                script.src = 'https://www.google.com/recaptcha/api.js?hl=ru';
                document.head.appendChild(script);
                this.loaded = true;
              }
            }
            const Recaptcha = new RecaptchaInitiator();
        </script>";
    }
}
