<?php

namespace Saber;

use function Saber\output;
use Symfony\Component\Finder\Finder;
use function Saber\restartContainers;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Exception\RuntimeException;

class RemoveApplicationCommand extends Command
{
    public function __construct()
    {
        $this->path = (new \SplFileInfo(''))->getRealPath();
        $this->finder = new Finder();

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('remove')
            ->setDescription('Remove a development application environment')
            ->setHelp('Removes the specified development environment')
            ->addArgument('domain', InputArgument::REQUIRED, 'Removes this development environment');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (strpos($input->getArgument('domain'), '.')) {
            throw new RuntimeException('A domain TLD is not required.');
        }

        $domain = $input->getArgument('domain').'.test';

        output('<info>Removing "' . $domain . '" configuration...</info>');

        // Removes certificates associated with application, if they exist
        if ((new Filesystem())->exists('certs/' . $domain . '.crt')) {
            output('<comment>Removing certificates...</comment>');
            $this->removeCertificates($domain);
        }

        // Remove code folder
        $this->remove('code/' . $domain);

        // Removed the applications NGINX config
        $this->removeNginxConfig($domain);

        // Removes the PHP config
        $this->removePhpConfig($domain);

        // Restart containers
        restartContainers();

        output('<info>Application has been removed</info>');
    }

    /**
     * Remove PHP configuration file.
     *
     * @param mixed $domain
     */
    private function removePhpConfig($domain)
    {
        output('<comment>Removing PHP configuration files...</comment>');

        return $this->delete([
            'lemp/php/configs/' . $domain . '.conf',
        ]);
    }

    /**
     * Remove NGINX configuration.
     *
     * @param mixed $domain
     */
    private function removeNginxConfig($domain)
    {
        output('<comment>Removing NGINX configuration files...</comment>');

        return $this->delete([
            'lemp/nginx/config/conf.d/' . $domain . '.conf',
        ]);
    }

    /**
     * Remove certificates from 'certs' folder.
     *
     * @param mixed $domain
     */
    private function removeCertificates($domain)
    {
        $this->delete([
            'certs/' . $domain . '.crt',
            'certs/' . $domain . '-key.key',
        ]);
    }

    private function delete(array $files)
    {
        return (new Filesystem())->remove($files);
    }

    /**
     * Remove files from selected folder.
     *
     * @param string $path
     * @param array        $excludeFiles
     */
    private function remove($path, array $excludeFiles = [])
    {
        if (is_array($excludeFiles)) {
            $files = $this->in($path, $excludeFiles);
        }

        $files = $this->in($path);

        foreach ($files as $file) {
            (new Filesystem)->remove($file->getRealPath());
        }

        return $this;
    }

    /**
     * Helper function to find files in a location.
     *
     * @param array|string $folder
     * @param array        $exclude
     */
    private function in($folder, array $exclude = [])
    {
        $pathToFolder = $this->path.'/'.$folder;

        if (is_array($exclude)) {
            return $this->finder->files()->in($pathToFolder)->notName($exclude);
        }

        return $this->finder->files()->in($pathToFolder);
    }
}
