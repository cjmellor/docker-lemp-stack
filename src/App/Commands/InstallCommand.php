<?php

namespace Saber;

use function Saber\replace;
use function Saber\buildContainer;
use Symfony\Component\Process\Process;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Exception\RuntimeException;

class InstallCommand extends Command
{
    const VERSION = 7.2;
    const DB = 'mariadb:latest';

    public function __construct()
    {
        $this->filesystem = new Filesystem();

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('install')
            ->setDescription('Installs Docker images and sets up the stack')
            ->setHelp('Installs Docker images needed to run the LEMP stack and performs other tasks to set up the stack environment')
            ->addOption('php', null, InputOption::VALUE_OPTIONAL, 'Select the version of PHP you want installed', static::VERSION)
            ->addOption('db', null, InputOption::VALUE_OPTIONAL, 'Select which database you want installed.', static::DB);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $php_version = $input->getOption('php');
        $db = $input->getOption('db');

        // First, copy the .env.example
        if (! $this->filesystem->exists('.env')) {
            $this->filesystem->copy('.env.example', '.env');
        }

        // If a user chooses a different PHP version
        if ($php_version) {
            $this->choosePhpVersion($input);
        }

        // If a user chooses a different database
        if ($db) {
            $this->chooseDb($input);
        }

        if (! $this->isInstalled()) {
            throw new RuntimeException('Saber is already installed!');
        }

        // All checks have passed, build the containers!
        buildContainer($input);
    }

    /**
     * If user supplies a PHP version, that version will be installed.
     * Default: 7.2
     *
     * @param InputInterface $input
     *
     * @return void
     */
    public function choosePhpVersion(InputInterface $input)
    {
        if (! preg_match('/(\d\.)?(\d\.)?(\d{1,2})/', $input->getOption('php'), $match)) {
            throw new RuntimeException('Invalid PHP version');
        }

        replace('PHP_VERSION=(.+)|PHP_VERSION=', 'PHP_VERSION=' . ($match[0] ?: static::VERSION), '.env');
    }

    /**
     * If a user supplies a database version, that version will be installed.
     * Default: mariadb:latest
     *
     * @param InputInterface $input
     *
     * @return void
     */
    public function chooseDb(InputInterface $input)
    {
        $this->validateDb($input);

        replace('FROM (.+)', 'FROM ' . $input->getOption('db'), 'build/db/Dockerfile');
    }

    /**
     * Validate input before creating DB Dockerfile
     *
     * @param mixed $db
     *
     * @return void
     */
    public function validateDb(InputInterface $input)
    {
        $db = $input->getOption('db');

        $db_options = ['mariadb', 'mysql'];
        $db_version = explode(':', $db);

        // If user doesn't choose 'mariadb' or 'mysql'
        if (! in_array($db_version[0], $db_options)) {
            throw new RuntimeException('Invalid database');
        }

        // If no version of the databases is provided
        if (strpos($db, ':') === false) {
            throw new RuntimeException('No version tag provided');
        }

        $this->isVersionValid($db_version[1]);
    }

    /**
     * Check if the DB version is valid
     *
     *  - first, check if the version number is numeric
     *  - second, check if it's a valid version number
     *  - third, if a string, make sure it's 'latest'
     *
     * @param string|int|double $version
     *
     * @return boolean
     */
    public function isVersionValid($version)
    {
        // List of current stable and supported versions
        $valid_db_versions = [
            '5.6', '5.7', '8.0', '10.1', '10.2', '10.3',
        ];

        if (is_numeric($version)) {
            if (! preg_match('/^(\d{1,2})(\.)?(\d)$/', $version) or (! in_array($version, $valid_db_versions))) {
                throw new RuntimeException("Invalid DB version\n\nAvailable versions:\n\n - MySQL: 5.6, 5.7, 8.0\n - MariaDB: 10.1, 10.2, 10.3");
            }

            return true;
        } elseif ($version != 'latest') {
            throw new RuntimeException("Invalid input: \"$version\"\n\n - Did you mean 'latest'?");
        }

        return true;
    }

    /**
     * Checks if a running Saber container is already running
     *  - this indicates that it's already installed.
     *
     * @return boolean
     */
    public function isInstalled()
    {
        return (new Process(['docker', 'inspect', '-f {{.State.Running}}', 'saber_nginx']))
            ->run();
    }
}
