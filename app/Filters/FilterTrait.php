<?php

namespace App\Filters;

trait FilterTrait
{
    /**
     * Convert a string array of the format '<arg>=<value>' to an associative array.
     *
     * @param ?string[] $args string array in the format '<arg>=<value>'
     * @return array
     */
    protected function extractArguments(?array $args): array
    {
        $argsExtracted = [];
        if (is_array($args)) {
            foreach ($args as $arg) {
                if (str_contains($arg, '=')) {
                    list($argName, $argValue) = explode('=', $arg);
                    $argsExtracted[$argName] = $argValue;
                } else {
                    $argsExtracted[$arg] = $arg;
                }
            }
        }
        return $argsExtracted;
    }
}