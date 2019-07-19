<?php

namespace Saber;

class Docker
{
    public $cli;

    /**
     * Create new Docker instance
     *
     * @param Shell $cli
     */
    public function __construct(Shell $cli)
    {
        $this->cli = $cli;
    }

    /**
     * Build the Docker containers
     *
     * @return void
     */
    public function buildContainers()
    {
        info('Building your application environment...');

        $this->dockerCompose('up -d --build');

        success('Containers successfully built!');
    }

    /**
     * Restarts Docker containers
     *
     * @return void
     */
    public function restartContainers()
    {
        info('Restarting containers...');

        $this->dockerCompose('restart php nginx');

        success('Containers restarted!');
    }

    /**
     * Destroys Docker containers
     *
     * @return void
     */
    public function destroyContainers()
    {
        info('Shutting down containers...');

        $this->dockerCompose('down -v');
    }

    /**
     * Run a docker-compose command
     *
     * @param string $cmd
     * @return void
     */
    public function dockerCompose($cmd)
    {
        $this->cli->run('docker-compose -f ~/.config/saber/docker-compose.yml ' . $cmd, function ($errorCode, $errorMsg) {
            error($errorMsg);
        });
    }
}
