<?php

declare(strict_types=1);

namespace App\Auth\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Shield\Auth;

/**
 * Auth Rate-Limiting Filter.
 *
 * Provides rated limiting intended for Auth routes.
 */
class AuthRates implements FilterInterface
{
    /**
     * Intened for use on auth form pages to restrict the number
     * of attempts that can be generated.
     *
     * @param array|null $arguments
     *
     * @return RedirectResponse|void
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! $request instanceof IncomingRequest) {
            return;
        }

        $throttler = service('throttler');

        // Restrict an IP address to no more than 12 requests per an hour.
        if ($throttler->check(md5($request->getIPAddress()), 12, 60 * MINUTE, 1) === false) {
            return service('response')->setStatusCode(
                429,
                $message = lang('Auth.requestLimit')
            )->setJSON(['messages' => [$message]]);
         }
    }

    /**
     * We don't have anything to do here.
     *
     * @param Response|ResponseInterface $response
     * @param array|null                 $arguments
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {
        // Nothing required
    }
}
