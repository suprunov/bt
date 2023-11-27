<?php

namespace App\Controllers;

use App\Controllers\Api\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use Psr\Log\LoggerInterface;

/**
 * Base controller for API.
 */
class ApiController extends ResourceController
{
    use ResponseTrait;

    /**
     * {@inheritDoc}
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        // Disable the query cache so that it only works on demand.
        $db = \Config\Database::connect();
        $db->query('SET @@session.query_cache_type = DEMAND');
    }

}
