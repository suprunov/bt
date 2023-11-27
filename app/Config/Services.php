<?php

namespace Config;

use App\Entities\Contact;
use App\Libraries\CITransit;
use App\Libraries\Recaptcha;
use App\Libraries\Sms\Sms;
use App\Models\ContactModel;
use CodeIgniter\Config\BaseService;
use CodeIgniter\Session\Session;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends BaseService
{
    /**
     *  TEMP: trigger events from CI4 to CI1
     *
     * @param $getShared
     * @return CITransit
     */
    public static function CITransit($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('CITransit');
        }

        return new \App\Libraries\CITransit();
    }

    /**
     * Return the sms manager.
     *
     * @param $getShared
     * @return Sms
     */
    public static function sms($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('sms');
        }

        return new Sms();
    }

    /**
     * Return the session manager.
     *
     * @return Session
     */
    public static function session(?App $config = null, bool $getShared = true)
    {
        // Use a lightweight array handler instead of a heavy DB handler:
        if (is_cli() // for CLI launches
            || str_starts_with(self::uri()->getPath(), config('Api')->baseURL) // for API requests
            || str_starts_with(self::request()->getServer('REQUEST_URI'), '/xml/')  // TEMP for old feed scripts
            || self::request()->getUserAgent()->isRobot() // for bots, crawlers and 1C requests
            ) {
            config('App')->sessionDriver = 'CodeIgniter\Session\Handlers\ArrayHandler';
        }
        // Return the native session manager.
        return \CodeIgniter\Config\Services::session($config, $getShared);
    }

    /**
     * Returns the store contacts of the current location.
     *
     * @param $getShared
     * @return Contact
     */
    public static function contacts($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('contacts');
        }

        return model(ContactModel::class)
            ->query(['location' => session('geoip')['id']])
            ->first();
    }

    /**
     * Return the Recaptcha manager.
     *
     * @param bool $getShared
     * @return Recaptcha
     */
    public static function recaptcha(bool $getShared = true): Recaptcha
    {
        if ($getShared) {
            return static::getSharedInstance('recaptcha');
        }

        return new Recaptcha();
    }


}
