<?php

namespace Pumukit\InspectionBundle\Utils;

class TestCommand
{
    //TODO: Move away
    public static function commandExists($command)
    {
        if (!function_exists('exec')) {
            return false;
        }
        // todo: find a better way (command could not be available)
        $testCommand = 'which ';
        $os = strtolower(PHP_OS);
        if (0 === strpos($os, 'win')) {
            $testCommand = 'where ';
        }
        $command = escapeshellcmd($command);
        exec($testCommand.$command.' 2>&1', $output, $code);

        return 0 === $code && count($output) > 0;
    }
}
