<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class AuthBasic extends BaseConfig
{
    /**
     * Enable basic authentication for the entire site.
     * This is useful for development.
     *
     * @var bool
     */
    public bool $enabled = true;

    /**
     * User-password list.
     *
     * @var array<string, string>
     */
    public array $users = [
        'apiImport1C' => '',
    ];
}
