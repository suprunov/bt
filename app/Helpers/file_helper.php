<?php

namespace App\Helpers;

class File
{

    /**
     * Check the directory and create it if there is no such directory.
     *
     * @param string|null $path
     * @param int $permissions
     * @return bool
     */
    public static function mkdir(?string $path, int $permissions = 0755): bool
    {
        if (! is_dir($path))
            return mkdir($path, $permissions, true);

        return (bool)$path;
    }

}
