<?php

namespace App\Modules\Log\Handlers;

use CodeIgniter\Log\Handlers\FileHandler as CI_FileHandler;
use DateTime;

/**
 * Log error messages to file system
 */
class FileHandler extends CI_FileHandler
{

    /**
     * Default sub folder to hold logs. All logs without specifying a folder will be placed here.
     *
     * @var string
     */
    protected string $subPath = 'system';

    /**
     * {@inheritDoc}
     *
     * @param string $level
     * @param string $message If $message starts with "folder:<subFolder>;" then it'll be written to this subFolder.
     */
    public function handle($level, $message): bool
    {
        // If $message starts with "folder:<subFolder>;" then it'll be written to this subFolder
        if (str_starts_with($message, 'folder:')) {
            preg_match('/folder:(?<folder>[^;]+);/', $message, $matches);
            if (isset($matches['folder'])) {
                $message = ltrim(substr($message, strlen("folder:{$matches['folder']};")));
            }
        }
        $subPath = $matches['folder'] ?? $this->subPath;

        $folderPath = $this->path . $subPath . '/' . date('Y-m');
        $filepath   = $folderPath . '/' . date('Y-m-d') . '.' . $this->fileExtension;

        $msg = '';

        if (! is_file($filepath)) {
            $newfile = true;

            // Only add protection to php files
            if ($this->fileExtension === 'php') {
                $msg .= "<?php defined('SYSTEMPATH') || exit('No direct script access allowed'); ?>\n\n";
            }

            // Check if folder not exists
            helper('file');
            if (! \App\Helpers\File::mkdir($folderPath)) {
                return false;
            }
        }

        if (! $fp = @fopen($filepath, 'ab')) {
            return false;
        }

        // Instantiating DateTime with microseconds appended to initial date is needed for proper support of this format
        if (str_contains($this->dateFormat, 'u')) {
            $microtimeFull  = microtime(true);
            $microtimeShort = sprintf('%06d', ($microtimeFull - floor($microtimeFull)) * 1_000_000);
            $date           = new DateTime(date('Y-m-d H:i:s.' . $microtimeShort, (int) $microtimeFull));
            $date           = $date->format($this->dateFormat);
        } else {
            $date = date($this->dateFormat);
        }

        $msg .= strtoupper($level) . ' - ' . $date . ' --> ' . $message . "\n";

        flock($fp, LOCK_EX);

        $result = null;

        for ($written = 0, $length = strlen($msg); $written < $length; $written += $result) {
            if (($result = fwrite($fp, substr($msg, $written))) === false) {
                // if we get this far, we'll never see this during travis-ci
                // @codeCoverageIgnoreStart
                break;
                // @codeCoverageIgnoreEnd
            }
        }

        flock($fp, LOCK_UN);
        fclose($fp);

        if (isset($newfile) && $newfile === true) {
            chmod($filepath, $this->filePermissions);
        }

        return is_int($result);
    }

}
