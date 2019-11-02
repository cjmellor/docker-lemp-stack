<?php

namespace Saber;

class Config
{
    public $file;
    public $cli;

    public function __construct(File $file, Shell $cli)
    {
        $this->file = $file;
        $this->cli = $cli;
    }
    
    /**
     * Let's the user supply the PHP version they want to use.
     *
     * @param int $php_version
     * @return void
     */
    public function selectPhpVersion($php_version)
    {
        if (! preg_match('/(\d\.)?(\d\.)?(\d{1,2})/', $php_version, $match)) {
            error('Invalid PHP version');
        }

        replace('PHP_VERSION=(.+)|PHP_VERSION=', 'PHP_VERSION=' . ($match[0] ?: DEFAULT_PHP_VERSION), '.env');

        info("Installing PHP version: <fg=white>$php_version</>");
    }

    /**
     * Choose which databse is used
     *
     * @param string $database_image
     * @return void
     */
    public function selectDatabase($database_image)
    {
        $this->validateDatabase($database_image);

        replace('DATABASE_NAME=(.+)|DATABASE_NAME=', 'DATABASE_NAME=' . ($database_image ?: DEFAULT_DATABASE_IMAGE), '.env');

        info("Installing database: <fg=white>$database_image</>");
    }

    /**
     * Validate the chosen database to make sure it's a real option
     *
     * @param string $database_image
     * @return boolean
     */
    public function validateDatabase($database_image)
    {
        $db_options = ['mariadb', 'mysql'];
        $db_version = explode(':', $database_image);

        // If user doesn't choose 'mariadb' or 'mysql'
        if (! in_array($db_version[0], $db_options, true)) {
            error('Invalid database');
        }

        // If no version of the databases is provided
        if (strpos($database_image, ':') === false) {
            error('No version tag provided');
        }

        $this->isVersionValid($db_version[1]);
    }

    /**
     * Validate the supplied database version
     *
     * @param string $version
     * @return boolean
     */
    public function isVersionValid($version)
    {
        // List of current stable and supported versions
        $valid_db_versions = [
            '5.6', '5.7', '8.0', '10.1', '10.2', '10.3',
        ];

        if (is_numeric($version)) {
            if (! preg_match('/^(\d{1,2})(\.)?(\d)$/', $version) or (! in_array($version, $valid_db_versions, true))) {
                error("Invalid DB version\n\nAvailable versions:\n\n - MySQL: 5.6, 5.7, 8.0\n - MariaDB: 10.1, 10.2, 10.3");
            }

            return true;
        } elseif ($version != 'latest') {
            error("Invalid input: \"$version\"\n\n - Did you mean 'latest'?");
        }

        return true;
    }
}
