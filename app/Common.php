<?php

/**
 * The goal of this file is to allow developers a location
 * where they can overwrite core procedural functions and
 * replace them with their own. This file is loaded during
 * the bootstrap process and is called during the framework's
 * execution.
 *
 * This can be looked at as a `master helper` file that is
 * loaded early on, and may also contain additional functions
 * that you'd like to use throughout your entire application
 *
 * @see: https://codeigniter4.github.io/CodeIgniter4/
 */

if (! function_exists('tbl')) {
    /**
     * Get table name by alias.
     * The list of aliases is stored in the database config in the {$connection}Tables array.
     *
     * @param string $table
     * @param string|null $connection
     * @return string
     */
    function tbl(string $table, ?string $connection = null): string
    {
        $dbConfig = config('Database');
        $connection ??= $dbConfig->defaultGroup;
        //$dbName = $connection != 'default' ? $dbConfig->{$connection}['database'] . '.' : ''; // TEMP while old CI is still in use
        $dbName = $dbConfig->{$connection}['database'] . '.';
        return  $dbName . $dbConfig->{$connection . 'Tables'}[$table];
    }
}
