<?php

namespace Saber;

use function Saber\output;
use function Saber\destroyContainer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UninstallCommand extends Command
{
    protected function configure()
    {
        $this->setName('uninstall')
            ->setDescription('Uninstall Saber')
            ->setHelp('Uninstalls the containers configuration files and drops the Docker containers.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        output('<comment>Uninstalling Saber...</comment>');

        destroyContainer();

        output('<info>Uninstalled</info>');
    }
}
