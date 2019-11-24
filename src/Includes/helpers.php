<?php

namespace Saber;

use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Filesystem\Filesystem;

define('SABER_HOME_CONFIG_PATH', realpath(__DIR__ . '/../../'));
define('DEFAULT_PHP_VERSION', 7.3);
define('DEFAULT_DATABASE_IMAGE', 'mariadb:latest');


/**
 * Replace text within a file.
 *
 * @param string $original
 * @param string $new
 * @param array|string $filename
 */
function replace($original, $new, $filename)
{
    $file = preg_replace('/' . $original . '/', $new, file_get_contents($filename));

    (new Filesystem())->dumpFile($filename, $file);
}

/**
 * Output text related to a successful action
 *
 * @param string $output
 * @return void
 */
function success($output)
{
    output("<fg=green;options=bold>$output</>");
}

/**
 * Output text related to giving valuble information
 *
 * @param string $output
 * @return void
 */
function info($output)
{
    output("<comment>$output</comment>");
}

/**
 * Output text related to a failed action
 *
 * @param string $output
 * @return void
 */
function error($output)
{
    output("<error>$output</error>");
}

/**
 * Output text to the console.
 *
 * @param string $output
 */
function output($output)
{
    return (new ConsoleOutput())->writeln($output);
}

/**
 * Check if a string is contained within' another string
 *
 * @param string $haystack
 * @param string|array $needles
 * @return bool
 */
function contains($haystack, $needles)
{
    foreach ((array) $needles as $needle) {
        if ($needle !== '' && mb_strpos($haystack, $needle) !== false) {
            return true;
        }
    }
    return false;
}
