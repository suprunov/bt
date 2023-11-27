<?php

namespace Config;

use CodeIgniter\Database\Config;

/**
 * Database Configuration
 */
class Database extends Config
{
    /**
     * The directory that holds the Migrations
     * and Seeds directories.
     *
     * @var string
     */
    public $filesPath = APPPATH . 'Database' . DIRECTORY_SEPARATOR;

    /**
     * Lets you choose which connection group to
     * use if no other is specified.
     *
     * @var string
     */
    public $defaultGroup = 'default';


    /**
     * The database connection to the old base.
     *
     * @var array
     */
    public $old = [
        'DSN'      => '',
        'hostname' => 'localhost',
        'username' => '',
        'password' => '',
        'database' => '',
        'DBDriver' => 'MySQLi',
        'DBPrefix' => '',
        'pConnect' => false,
        'DBDebug'  => (ENVIRONMENT !== 'production'),
        'charset'  => 'utf8',
        'DBCollat' => 'utf8_general_ci',
        'swapPre'  => '',
        'encrypt'  => false,
        'compress' => false,
        'strictOn' => false,
        'failover' => [],
        'port'     => 3306,
    ];

    /**
     * This database connection is used when
     * running PHPUnit database tests.
     *
     * @var array
     */
    public $tests = [
        'DSN'         => '',
        'hostname'    => '127.0.0.1',
        'username'    => '',
        'password'    => '',
        'database'    => ':memory:',
        'DBDriver'    => 'SQLite3',
        'DBPrefix'    => 'db_',  // Needed to ensure we're working correctly with prefixes live. DO NOT REMOVE FOR CI DEVS
        'pConnect'    => false,
        'DBDebug'     => (ENVIRONMENT !== 'production'),
        'charset'     => 'utf8',
        'DBCollat'    => 'utf8_general_ci',
        'swapPre'     => '',
        'encrypt'     => false,
        'compress'    => false,
        'strictOn'    => false,
        'failover'    => [],
        'port'        => 3306,
        'foreignKeys' => true,
    ];

    /**
     * The default database connection.
     *
     * @var array
     */
    public $default = [
        'DSN'      => '',
        'hostname' => 'localhost',
        'username' => '',
        'password' => '',
        'database' => '',
        'DBDriver' => 'MySQLi',
        'DBPrefix' => '',
        'pConnect' => false,
        'DBDebug'  => (ENVIRONMENT !== 'production'),
        'charset'  => 'utf8',
        'DBCollat' => 'utf8_general_ci',
        'swapPre'  => '',
        'encrypt'  => false,
        'compress' => false,
        'strictOn' => false,
        'failover' => [],
        'port'     => 3306,
    ];

    /**
     * List of 'default' database tables.
     *
     * @var array
     */
    public array $defaultTables = [
        'auth_groups_users'               => 'auth_groups_users',
        'auth_identities'                 => 'auth_identities',
        'auth_logins'                     => 'auth_logins',
        'auth_permissions_users'          => 'auth_permissions_users',
        'auth_remember_tokens'            => 'auth_remember_tokens',
        'auth_token_logins'               => 'auth_token_logins',
        'banks'                           => 'banks',
        'brands'                          => 'brands',
        'brands_extras'                   => 'brands_extras',
        'categories'                      => 'categories',
        'clients'                         => 'clients',
        'clients_addresses'               => 'clients_addresses',
        'clients_bank_accounts'           => 'clients_bank_accounts',
        'clients_cars'                    => 'clients_cars',
        'clients_storage_contracts'       => 'clients_storage_contracts',
        'clients_users'                   => 'clients_users',
        'colors'                          => 'colors',
        'colors_groups'                   => 'colors_groups',
        'countries'                       => 'countries',
        'features'                        => 'features',
        'features_filters'                => 'features_filters',
        'features_groups'                 => 'features_groups',
        'features_values'                 => 'features_values',
        'meta_info'                       => 'meta_info',
        'migrations'                      => 'migrations',
        'models'                          => 'models',
        'models_features_values'          => 'models_features_values',
        'models_pictures'                 => 'models_pictures',
        'models_reviews'                  => 'models_reviews',
        'pictures'                        => 'pictures',
        'pictures_types'                  => 'pictures_types',
        'pictures_types_variations'       => 'pictures_types_variations',
        'pictures_variations'             => 'pictures_variations',
        'prices'                          => 'prices',
        'products'                        => 'products',
        'products_categories'             => 'products_categories',
        'products_deliveries'             => 'products_deliveries',
        'products_deliveries_grouped'     => 'products_deliveries_grouped',
        'products_features_values'        => 'products_features_values',
        'products_locations'              => 'products_locations',
        'products_pictures'               => 'products_pictures',
        'products_prices'                 => 'products_prices',
        'products_promotions'             => 'products_promotions',
        'products_recent'                 => 'products_recent',
        'products_storages'               => 'products_storages',
        'products_types'                  => 'products_types',
        'sessions'                        => 'sessions',
        'settings'                        => 'settings',
        'sitemap_exceptions'              => 'sitemap_exceptions',
        'sitemap_filters'                 => 'sitemap_filters',
        'storages'                        => 'storages',
        'storages_locations'              => 'storages_locations',
        'users'                           => 'users',
        'users_addresses'                 => 'users_addresses',
        'users_contact_persons'           => 'users_contact_persons',
        'vehicles_brands'                 => 'vehicles_brands',
        'vehicles_models'                 => 'vehicles_models',
        'vehicles_modifications'          => 'vehicles_modifications',
        'vehicles_wheels'                 => 'vehicles_wheels',
        'vehicles_wheels_features_values' => 'vehicles_wheels_features_values',
    ];

    /**
     * List of 'old' database tables.
     *
     * @var array
     */
    public array $oldTables = [
        'promotions' => 'promotions',
    ];

    public function __construct()
    {
        parent::__construct();

        // Ensure that we always set the database group to 'tests' if
        // we are currently running an automated test suite, so that
        // we don't overwrite live data on accident.
        if (ENVIRONMENT === 'testing') {
            $this->defaultGroup = 'tests';
        }
    }
}
