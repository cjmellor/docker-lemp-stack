<?php

namespace Saber;

use Symfony\Component\Process\Process;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Builds the container.
 *
 * @param InputInterface $input
 */
function buildContainer(InputInterface $input)
{
    $output = new ConsoleOutput();

    $progress = new ProgressBar($output, 100);
    $progress->setBarCharacter('<comment>.</comment>');
    $progress->setBarWidth(50);
    $progress->setEmptyBarCharacter('');
    $progress->setFormat('%message% %bar%');
    $progress->setProgressCharacter('.');

    $dockerCompose = new Process(['docker-compose', 'up', '-d', '--build']);

    output('<comment>Building your application environment...</comment>');

    try {
        $progress->setMessage('<comment>Building containers</comment>');

        if ($input->getOption('verbose')) {
            $dockerCompose->setTimeout(180)->mustRun(function ($type, $line) {
                output($line);
            });
        } else {
            $progress->start();

            $dockerCompose->setTimeout(180)->mustRun(function () use ($progress) {
                $progress->advance();
            });
        }

        if ($dockerCompose->isSuccessful()) {
            if (! $input->getOption('verbose')) {
                $progress->finish();
                echo PHP_EOL;
            }

            output('<info>Containers successfully built!</info>');
        }
    } catch (ProcessFailedException $error) {
        echo $error->getMessage();
    }
}

/**
 * Restart PHP and NGINX containers
 *
 * @return void
 */
function restartContainers()
{
    output('<comment>Restarting containers...</comment>');

    $dockerRestartContainer = new Process(['docker-compose', 'restart', 'php', 'nginx']);
    $dockerRestartContainer->run();

    if (! $dockerRestartContainer->isSuccessful()) {
        throw new ProcessFailedException($dockerRestartContainer);
    }
    output('<comment>Containers restarted!</comment>');
}

/**
 * Shut down and destroy the Docker container and remove the Network.
 */
function destroyContainer()
{
    $dockerComposerDown = new Process(['docker-compose', 'down', '-v']);
    $dockerComposerDown->run();

    if (! $dockerComposerDown->isSuccessful()) {
        throw new ProcessFailedException($dockerComposerDown);
    }
}

/**
 * Replace text within a file
 *
 * @param string $original
 * @param string $new
 * @param array|string $filename
 *
 * @return void
 */
function replace($original, $new, $filename)
{
    $file = preg_replace('/'.$original.'/', $new, file_get_contents($filename));

    (new Filesystem)->dumpFile($filename, $file);

    return;
}

/**
 * Output text to the console
 *
 * @param string $output
 *
 * @return void
 */
function output($output)
{
    return (new ConsoleOutput)->writeln($output);
}
