<?php

namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

// Load the system's routing file first, so that the app and ENVIRONMENT
// can override as needed.
if (is_file(SYSTEMPATH . 'Config/Routes.php')) {
    require SYSTEMPATH . 'Config/Routes.php';
}

/*
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('App\Controllers');

// Route to the empty controller for all routes not found before.
// The case when control is delegated to the old CI engine.
$routes->setDefaultController('Home');
$routes->setDefaultMethod('empty');

$routes->setTranslateURIDashes(false);
$routes->set404Override();
// The Auto Routing (Legacy) is very dangerous. It is easy to create vulnerable apps
// where controller filters or CSRF protection are bypassed.
// If you don't want to define all routes, please use the Auto Routing (Improved).
// Set `$autoRoutesImproved` to true in `app/Config/Feature.php` and set the following to true.
//$routes->setAutoRoute(false);

/*
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */
// API: OPTIONS
/*$routes->group('api', ['namespace' => '\App\Controllers'], function($routes) {
    $routes->options('(:any)', 'ApiController::options');
});*/
// API: cron // TODO auth or cli restriction
$routes->group('schedule', ['namespace' => '\App\Controllers'], function($routes) {
    $routes->get('feeds/(:segment)/location/(:segment)', 'FeedController::create/$1/$2');
});
// API: admin // TODO auth
$routes->group('api/v1', ['namespace' => '\App\Controllers\Api\v1'], function($routes) {
    $routes->get('product-management/products/(:num)', 'ProductController::getById/$1');
    $routes->post('product-management/products/(:num)', 'ProductController::saveById/$1');
    $routes->post('product-management/products/search', 'ProductController::get');

    $routes->post('product-management/product-types/search', 'ProductTypeController::get');

    $routes->get('product-management/brands/(:num)', 'BrandController::getById/$1');
    $routes->post('product-management/brands/(:num)', 'BrandController::saveById/$1');
    $routes->post('product-management/brands/search', 'BrandController::get');

    $routes->get('product-management/models/(:num)', 'ModelController::getById/$1');
    $routes->post('product-management/models/(:num)', 'ModelController::saveById/$1');
    $routes->post('product-management/models/search', 'ModelController::get');
    $routes->post('product-management/models/reviews/search', 'ModelReviewController::get');

    $routes->get('vehicle-management/brands/(:num)', 'VehicleController::getById/$1');
    $routes->post('vehicle-management/brands/(:num)', 'VehicleController::saveBrandById/$1');
    $routes->post('vehicle-management/brands/search', 'VehicleController::get');

    $routes->post('storage-management/locations/search', 'LocationController::get');

    $routes->post('media-management/pictures/(:num)', 'MediaController::savePicture/$1');
});
// API: 1C import
$routes->group('api/v1', ['namespace' => '\App\Controllers\Api\v1', 'filter' => ['authBasicFilter:user=apiImport1C', 'throttle']], function($routes) {
    $routes->put('product-management/categories/(:segment)', 'ProductTypeController::save/$1');
    $routes->put('product-management/color-groups/(:segment)', 'ColorController::saveGroup/$1');
    $routes->put('product-management/color-groups/(:segment)/colors/(:segment)', 'ColorController::save/$1/$2');
    $routes->put('product-management/brands/(:segment)', 'BrandController::save/$1');
    $routes->put('product-management/brands/(:segment)/models/(:segment)', 'ModelController::save/$1/$2');
    $routes->put('product-management/features/(:segment)', 'FeatureController::save/$1');
    $routes->put('product-management/features/(:segment)/values/(:segment)', 'FeatureController::saveValue/$1/$2');
    $routes->put('product-management/products/(:segment)', 'ProductController::save/$1');

    $routes->put('storage-management/deliveries/(:segment)', 'DeliveryController::save/$1');
    $routes->put('storage-management/storages/(:segment)', 'StorageController::save/$1');
    $routes->put('storage-management/storages/(:segment)/schedules/location/(:segment)', 'StorageController::saveDeliverySchedules/$1/$2');
    $routes->put('storage-management/prices/(:segment)', 'PriceController::save/$1');
    $routes->put('storage-management/products/(:segment)/prices/(:segment)', 'ProductPriceController::save/$1/$2');
    $routes->put('storage-management/products/(:segment)/locations', 'ProductLocationController::save/$1');
    $routes->put('storage-management/product-prices', 'ProductPriceController::saveBatch');
    $routes->put('storage-management/product-storages', 'ProductStorageController::saveBatch');
    $routes->put('storage-management/product-locations', 'ProductLocationController::saveBatch');

    $routes->put('car-management/brands/(:segment)', 'VehicleController::saveBrand/$1');
    $routes->put('car-management/brands/(:segment)/models/(:segment)', 'VehicleController::saveModel/$1/$2');
    $routes->put('car-management/brands/models/(:segment)/modifications/(:segment)', 'VehicleController::saveModification/$1/$2');

    $routes->put('client-management/banks/(:segment)', 'BankController::save/$1');
    $routes->put('client-management/users/(:segment)', 'UserController::save/$1');
    $routes->put('client-management/clients/(:segment)', 'ClientController::saveClient/$1');
    $routes->put('client-management/clients/(:segment)/bank-accounts/(:segment)', 'ClientController::saveBankAccount/$1/$2');
    $routes->put('client-management/clients/(:segment)/cars/(:segment)', 'ClientController::saveCar/$1/$2');
    $routes->put('client-management/clients/(:segment)/storage-contracts/(:segment)', 'ClientController::saveStorageContract/$1/$2');

    $routes->add('(:any)', '\App\Errors::show404'); // TODO
});

// Authentication Routes
$routes->group('pa', ['namespace' => '\App\Auth\Controllers'], static function ($routes) {
/*    $routes->get('register', 'RegisterController::registerView');
    $routes->post('register', 'RegisterController::registerAction');*/
    $routes->get('login', 'LoginController::loginView', ['as' => 'login-view']);
    $routes->post('login-send-code', 'LoginController::sendCodeAction', ['as' => 'login-send-code', 'filter' => ['throttle-auth']]); // TODO
    $routes->post('login-check-code', 'LoginController::checkCodeAction', ['as' => 'login-check-code'/*, 'filter' => ['throttle-auth']*/]); // TODO
    $routes->post('login-register', 'LoginController::registerAction', ['as' => 'login-register']);
    $routes->get('logout', 'LoginController::logoutAction', ['as' => 'logout']);

    $routes->get('verify-email', 'LoginController::verifyEmailView');
    $routes->post('verify-email-send', 'LoginController::verifyEmailSend', ['as' => 'verify-email-send'/*, 'filter' => ['throttle-auth']*/]); // TODO
    $routes->get('verify-email-check/(:segment)/(:segment)', 'LoginController::verifyEmail/$1/$2', ['as' => 'verify-email-check'/*, 'filter' => ['throttle-auth']*/]); // TODO

    $routes->get('change-phone', 'ChangePhoneController::changePhoneView', ['as' => 'change-phone-view']);
    $routes->get('change-phone-send-code', 'ChangePhoneController::sendCodeAction', ['as' => 'change-phone-send-code', 'filter' => ['throttle-auth']]);
    $routes->post('change-phone-check-code/(phone|email)', 'ChangePhoneController::checkCodeAction/$1', ['as' => 'change-phone-check-code']);
    $routes->post('change-phone-send-code-2nd', 'ChangePhoneController::sendCode2ndStepAction', ['as' => 'change-phone-send-code-2nd', 'filter' => ['throttle-auth']]);
    $routes->post('change-phone-check-code-2nd', 'ChangePhoneController::checkCode2ndStepAction', ['as' => 'change-phone-check-code-2nd']);

    $routes->get('change-email', 'ChangeEmailController::changeEmailView', ['as' => 'change-email-view']);

    $routes->get('delete-account', 'RegisterController::deleteAccountView', ['as' => 'delete-account-view']);
    $routes->post('delete-account', 'RegisterController::deleteAccountAction', ['as' => 'delete-account-action']);

    /*    $routes->get('login/magic-link', 'MagicLinkController::loginView', ['as' => 'magic-link']);
        $routes->post('login/magic-link', 'MagicLinkController::loginAction');
        $routes->get('login/verify-magic-link', 'MagicLinkController::verify', ['as' => 'verify-magic-link']);*/
});
service('auth')->routes($routes, ['except' => ['login', 'register']]);

// Rewrite Bonfire asset routes in /vendor/lonnieezell/bonfire/src/Assets/Config/Routes.php
// Fix: Handle assets with .min suffix in their filenames. #127
$routes->get('assets/(:any)', '\App\Controllers\Bonfire\AssetController::serve/$1');

// We get a performance increase by specifying the default
// route since we don't have to scan directories.
// $routes->get('/', 'Home::index');

// Route to the empty controller for all routes not found before.
// The case when control is delegated to the old CI engine.
$routes->add('(:any)', '\App\Controllers\Home::empty');

/*
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 *
 * There will often be times that you need additional routing and you
 * need it to be able to override any defaults in this file. Environment
 * based routes is one such time. require() additional route files here
 * to make that happen.
 *
 * You will have access to the $routes object within that file without
 * needing to reload it.
 */
if (is_file(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
