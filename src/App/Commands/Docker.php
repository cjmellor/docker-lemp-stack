<?php

namespace Saber;

use Symfony\Component\Process\Process;

class Docker
{
    public $cli;

    /**
     * Create new Docker instance
     *
     * @param boolean $verbose
     * @param Shell $cli
     */
    public function __construct(Shell $cli)
    {
        $this->cli = $cli;
    }

    /**
     * Build the Docker containers
     *
     * @param boolean $verbose
     * @return void
     */
    public function buildContainers($verbose = false)
    {
        info('Building your application...');

        $this->dockerCompose('up -d --build', $verbose ?? true);

        success('Containers successfully built!');
    }

    /**
     * Restarts Docker containers
     *
     * @param boolean $verbose
     * @return void
     */
    public function restartContainers($verbose = false)
    {
        info('Restarting containers...');

        $this->dockerCompose('restart php nginx', $verbose ?? true);

        success('Containers restarted!');
    }

    /**
     * Destroys Docker containers
     *
     * @param boolean $verbose
     * @return void
     */
    public function destroyContainers($verbose = false)
    {
        info('Shutting down containers...');

        $this->dockerCompose('down --rmi all', $verbose ?? true);

        success('Containers removed ✅');
    }

    /**
     * Run a Docker command
     *
     * @param string $cmd
     * @param boolean $verbose
     * @return void
     */
    public function docker($cmd, $verbose = false)
    {
        return $this->cli->run(
            'docker ' . $cmd,
            function ($errorCode, $errorMsg) {
                error($errorMsg);
            },
            $verbose
        );
    }

    /**
     * Run a docker-compose command
     *
     * @param string $cmd
     * @param boolean $verbose
     * @return void
     */
    public function dockerCompose($cmd, $verbose = false)
    {
        // If errors, remove 'return'
        return $this->cli->run(
            'docker-compose -f ' . SABER_HOME_CONFIG_PATH . '/docker-compose.yml ' . $cmd,
            function ($errorCode, $errorMsg) {
                error($errorMsg);
            },
            $verbose
        );
    }

    /**
     * List all downloaded Docker images
     *
     * @return array
     */
    public function listImages()
    {
        $images = $this->docker("images --format '\"{{.Repository}}:{{.Tag}}\"' --filter=reference='*' | jq -r");

        $image = explode("\n", substr($images, 0, -1));

        return $image;
    }

    /**
     * Upgrade a Docker image
     *
     * @param array $images
     * @return void
     */
    public function upgradeImages($images, $verbose = false)
    {
        $imagesUpdated = $this->pull($images, $verbose ?? true);

        if ($imagesUpdated > 0) {
            $this->rebuild();
        }
    }

    /**
     * Pull the latest contaier image
     *
     * @param string $image
     * @return void
     */
    public function pull($images, $verbose = false)
    {
        // Count how many containers will be attempted to be updated
        $countImages = count($images);
        $imagesUpdated = 0;

        info("Updrading $countImages containers...");

        foreach ($images as $key => $image) {
            $key = $key + 1;

            // Is there a new version of the image?
            if ($this->isNewVersion($image)) {
                info("($key/$countImages) Getting latest image of '$image'");

                $this->docker('pull ' . $image, $verbose ?? true);

                $imagesUpdated++;
            } else {
                success("'$image' is at the latest version ✅");
            }
        }

        return $imagesUpdated;
    }

    /**
     * Re-build the containers - done after an image upgrade
     *
     * @return void
     */
    public function rebuild()
    {
        // Clean up unused images
        $this->clean();

        // Shut down the containters
        $this->destroyContainers();

        // Re-build new containers
        $this->buildContainers();
    }

    /**
     * Prune dangling Docker images
     *
     * @return void
     */
    public function clean()
    {
        return $this->docker('image prune --force');
    }

    /**
     * Check if a new version of a container image is available
     *
     * @param string $image
     * @return bool
     */
    public function isNewVersion($image)
    {
        $cmdOutput = (new Process($this->docker('pull ' . $image)));
        $cmdOutput->start();

        $cmdOutput->wait(function ($type, $buffer) {
            if (contains($buffer, 'Image is up to date')) {
                return false;
            }

            return true;
        });
    }
}
