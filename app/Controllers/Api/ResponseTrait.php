<?php

namespace App\Controllers\Api;

use CodeIgniter\API\ResponseTrait as CIResponseTrait;
use CodeIgniter\HTTP\Response;

trait ResponseTrait
{
    use CIResponseTrait {
        CIResponseTrait::respond as protected CIRespond;
    }

    /**
     * Stores a list of multi-status response.
     *
     * @var array
     */
    protected array $multiStatusResponse = [];

    /**
     * Call the native function + logging.
     *
     * {@inheritDoc}
     */
    protected function respond($data = null, ?int $status = null, string $message = '')
    {
        // Log all requests
        $logMessage = 'folder:api/requests;' .
            $this->request->getPath() . PHP_EOL .
            $this->request->getBody() . PHP_EOL;
        log_message('notice', $logMessage);

        // Get an API response
        $respond = $this->CIRespond($data, $status, $message);

        // Log error response
        if (! in_array($status, [200, 201, 207])) {
            $logMessage = 'folder:api/errors;' .
                $status . ' ' . $this->request->getPath() . PHP_EOL .
                $this->request->getBody() . PHP_EOL .
                'Response:' . PHP_EOL .
                $this->response->getBody();
            log_message('notice', $logMessage);
        }

        // TEMP Log all response
        $logMessage = 'folder:api/response;' .
            $status . ' ' . $this->request->getPath() . PHP_EOL .
            $this->request->getBody() . PHP_EOL .
            'Response:' . PHP_EOL .
            $this->response->getBody();
        log_message('notice', $logMessage);


        return $respond;
    }

    /**
     * Used after a bunch of resources has been updated.
     *
     * @return void
     */
    protected function addMultiStatusResponse(string $id, ?int $status = null, ?string $message = null): void
    {
        $multiStatusResponse = [
            'resource_id' => $id,
            'status'      => $status ?? $this->codes['updated'],
        ];
        if (isset($message))
            $multiStatusResponse['message'] = $message;

        $this->multiStatusResponse[] = $multiStatusResponse;
    }

    /**
     * Used after a bunch of resources has been updated.
     *
     * @param array|null $data
     *
     * @return Response
     */
    protected function respondMultiStatus(?array $data = null): Response
    {
        return $this->respond($data ?? $this->multiStatusResponse, 207);
    }
}