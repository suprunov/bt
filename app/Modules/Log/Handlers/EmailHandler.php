<?php

namespace App\Modules\Log\Handlers;

use CodeIgniter\Log\Handlers\BaseHandler;
use DateTime;

/**
 * Log error messages to emails.
 */
class EmailHandler extends BaseHandler
{
    /**
     * Default log label. All logs without specifying a label will be marked so.
     *
     * @var string
     */
    protected string $defaultLabel = 'system';

    /**
     * Admin emails.
     *
     * @var array
     */
    protected array $adminEmails;

    /**
     * Constructor
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);

        if (isset(config('Email')->adminEmails)) {
            $this->adminEmails = explode(',', config('Email')->adminEmails);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function canHandle(string $level): bool
    {
        return isset($this->adminEmails) && parent::canHandle($level);
    }

    /**
     * {@inheritDoc}
     */
    public function handle($level, $message): bool
    {
        // If $message starts with "folder:<label>;" then it'll be marked with this label
        if (str_starts_with($message, 'folder:')) {
            preg_match('/folder:(?<folder>[^;]+);/', $message, $matches);
            if (isset($matches['folder'])) {
                $message = ltrim(substr($message, strlen("folder:{$matches['folder']};")));
            }
        }
        $label = $matches['folder'] ?? $this->defaultLabel;

        $eol = '<br>';
        $message =
            'ERROR:'   . $eol . '<pre>' . $message . '</pre>' . $eol .
            'SERVER:'  . $eol . '<pre>' . print_r($_SERVER, true) . '</pre>' . $eol .
            'REQUEST:' . $eol . '<pre>' . print_r($_REQUEST, true) . '</pre>' . $eol .
            'SESSION:' . $eol . '<pre>' . print_r( session()->get() ?? 'Cannot get session!', true) . '</pre>' . $eol;

        // Instantiating DateTime with microseconds appended to initial date is needed for proper support of this format
        if (str_contains($this->dateFormat, 'u')) {
            $microtimeFull  = microtime(true);
            $microtimeShort = sprintf('%06d', ($microtimeFull - floor($microtimeFull)) * 1_000_000);
            $date           = new DateTime(date('Y-m-d H:i:s.' . $microtimeShort, (int) $microtimeFull));
            $date           = $date->format($this->dateFormat);
        } else {
            $date = date($this->dateFormat);
        }

        // Send the user an email with the code
        $email = \Config\Services::email();
        $email->setFrom(setting('Email.fromEmail'), setting('Email.fromName') ?? '');
        $email->setTo($this->adminEmails);
        $email->setSubject($label . ' - ' . strtoupper($level) . ' - ' . $date);
        $email->setMessage($message);
        //$email->setMailType('html'); // TODO why does not this work ?
        $email->send(false);
        // Clear the email
        $email->clear();

        return true; // Always true to give control to the next handler
    }

}
