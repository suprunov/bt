<?php

namespace Config\Site;

use Bonfire\Config\Site as BonfireSite;

class Site extends BonfireSite
{
    /**
     * --------------------------------------------------------------------------
     * Items Per Page
     * --------------------------------------------------------------------------
     *
     * The number of items that should be displayed in content lists.
     */
    public $perPage = 25;

    /**
     * --------------------------------------------------------------------------
     * Base Site URL
     * --------------------------------------------------------------------------
     *
     * The name that should be displayed for the site.
     */
    public $siteName = 'BlackTyres.ru';

    /**
     * --------------------------------------------------------------------------
     * Site Online?
     * --------------------------------------------------------------------------
     *
     * When false, only superadmins and user groups with permission will be
     * able to view the site. All others will see the "System Offline" page.
     */
    public $siteOnline = true;

    /**
     * --------------------------------------------------------------------------
     * Site Offline View
     * --------------------------------------------------------------------------
     *
     * The view file that is displayed when the site is offline.
     */
    public $siteOfflineView = 'Bonfire\Views\site_offline';
}
