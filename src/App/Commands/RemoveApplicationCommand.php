<?php

namespace Dev;

use function Dev\output;
use function Dev\replace;
use function Dev\destroyContainer;
use Symfony\Component\Finder\Finder;
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

        // Removes certificates associated with applicatiob
        output('<comment>Removing certificates...</comment>');
        $this->removeCertificates();

        // Removed the applications NGINX config
        output('<comment>Removing NGINX configuration files...</comment>');
        $this->removeNginxConfig();

        // Removes the PHP config
        output('<comment>Removing PHP configuration files...</comment>');
        $this->removePhpConfig();

        // Remove .env file
        output('<comment>Removing .env file...</comment>');
        (new Filesystem)->remove('.env');

        output('<comment>Cleaning up...</comment>');

        // Change the value back to localhost in the H5BP SSL certificate file
        replace($domain, 'localhost', $this->path.'/lemp/nginx/config/h5bp/ssl/certificate_files.conf');

        // Change the default process name back to the originals
        replace($domain, 'www', $this->path.'/lemp/php/configs/docker.conf');
        replace($domain, 'www', $this->path.'/lemp/php/configs/zz-docker.conf');

        // Shut down and remove Docker containers
        destroyContainer();
    }

    /**
     * Remove PHP configuration file.
     */
    private function removePhpConfig()
    {
        return $this->remove('lemp/php/configs', ['docker.conf', 'zz-docker.conf']);
    }

    /**
     * Remove NGINX configuration.
     */
    private function removeNginxConfig()
    {
        return $this->remove('lemp/nginx/config/conf.d');
    }

    /**
     * Remove certificates from 'certs' folder.
     */
    private function removeCertificates()
    {
        return $this->remove('certs');
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

    /**
     * Remove files from selected folder.
     *
     * @param array|string $path
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
}
