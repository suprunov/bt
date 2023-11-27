<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

/**
 * Basic Auth filter
 */
class AuthBasicFilter implements FilterInterface
{
    use FilterTrait;

    /**
     * Provide basic http authentication.
     *
     * @param array|null $arguments ['user=<user>']
     *
     * @return \CodeIgniter\HTTP\Response|void
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        helper('setting');

        if (! $request instanceof IncomingRequest || ! setting('AuthBasic.enabled')) {
            return;
        }

        $config = $this->extractArguments($arguments);

        $user = null;
        $password = null;
        $credentials = explode(':', setting('AuthBasic.users')[$config['user']]);
        if (count($credentials) === 2) {
            list($user, $password) = $credentials;
        }

        if ($user === null || $user != $request->getServer('PHP_AUTH_USER') || $password != $request->getServer('PHP_AUTH_PW')) {
            return Services::response()
                ->setJSON(['messages' => ['error' => $request->getServer('PHP_AUTH_USER') ? 'Wrong credentials' : 'Login required']])
                ->setStatusCode(401);
        }
    }

    /**
     * We don't have anything to do here.
     *
     * @param array|null $arguments
     *
     * @return void
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {
    }
}
