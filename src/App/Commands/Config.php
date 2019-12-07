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
     * @param  int  $phpVersion
     * @param  bool  $force
     * @return void
     */
    public function selectPhpVersion($phpVersion, $force = false)
    {
        if (!$this->checkValidVersion($phpVersion, $force)) {
            error('Invalid PHP version');
            exit;
        }

        replace(
            'PHP_VERSION=(.+)|PHP_VERSION=',
            'PHP_VERSION='.($phpVersion ?: DEFAULT_PHP_VERSION),
            SABER_HOME_CONFIG_PATH.'/.env'
        );

        info("Installing PHP version: <fg=white>$phpVersion</>");
    }

    /**
     * Check if the supplied version of PHP is valid
     *
     * @param  float  $phpVersion
     * @param  bool  $force
     * @return bool
     */
    public function checkValidVersion($phpVersion, $force = false)
    {
        if (!$force) {
            $validVersions = [7.2, 7.3, 7.4];

            if (!in_array($phpVersion, $validVersions)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Choose which database is used
     *
     * @param  string  $database_image
     * @return void
     */
    public function selectDatabase($database_image)
    {
        $this->validateDatabase($database_image);

        replace(
            'DATABASE_NAME=(.+)|DATABASE_NAME=',
            'DATABASE_NAME='.($database_image ?: DEFAULT_DATABASE_IMAGE),
            SABER_HOME_CONFIG_PATH.'/.env'
        );

        info("Installing database: <fg=white>$database_image</>");
    }

    /**
     * Validate the chosen database to make sure it's a real option
     *
     * @param  string  $database_image
     * @return void
     */
    public function validateDatabase($database_image)
    {
        $db_options = ['mariadb', 'mysql'];
        $db_version = explode(':', $database_image);

        // If user doesn't choose 'mariadb' or 'mysql'
        if (!in_array($db_version[0], $db_options, true)) {
            error('Invalid database');
            exit;
        }

        // If no version of the databases is provided
        if (strpos($database_image, ':') === false) {
            error('No version tag provided');
            exit;
        }

        if (!$this->isVersionValid($db_version[1])) {
            error("Invalid DB version\n\nAvailable versions:\n\n - MySQL: 5.6, 5.7, 8.0\n - MariaDB: 10.1, 10.2, 10.3");
            exit;
        }
    }

    /**
     * Validate the supplied database version
     *
     * @param  string  $version
     * @return bool
     */
    public function isVersionValid($version)
    {
        // List of current stable and supported versions
        $valid_db_versions = [
            '5.6',
            '5.7',
            '8.0',
            '10.1',
            '10.2',
            '10.3',
            'latest',
        ];

        if (!in_array($version, $valid_db_versions, true)) {
            return false;
        }

        return true;
    }
}
