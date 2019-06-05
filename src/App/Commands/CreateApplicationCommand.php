<?php

namespace Dev;

use function Dev\output;
use function Dev\replace;
use function Dev\buildContainer;
use Symfony\Component\Process\Process;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Process\Exception\ProcessFailedException;

class CreateApplicationCommand extends Command
{
    public function __construct()
    {
        $this->filesystem = new Filesystem();
        $this->path = (new \SplFileInfo(''))->getRealPath();

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('new')
            ->setDescription('Creates a new development application environment')
            ->setHelp('Creates a new development environment that will build and run a full LEMP stack with a custom domain.')
            ->addArgument('domain', InputArgument::REQUIRED, 'Creates a new development environment')
            ->addOption('tls', 't', InputOption::VALUE_NONE, 'Add TLS certificates for the domain');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $domain = $input->getArgument('domain').'.test';
        $tls = $input->getOption('tls');

        if (strpos($input->getArgument('domain'), '.')) {
            throw new RuntimeException('A domain TLD is not required.');
        }

        output('<comment>Building your application environment...</comment>');

        // If --tls was enabled, certificates will be generated for the domain
        if ($tls) {
            // Check if MKCert is installed, if not, install it
            $this->installMkCert();

            // If the 'certs' folder doesn't exist, create it
            if (! $this->filesystem->exists($this->path.'/certs')) {
                $this->filesystem->mkdir($this->path.'/certs');
            }

            // Run the 'mkcert' command to create certificates.
            $this->createSelfSignedCertificate($domain);

            output('<info>Cretificates successfully created!</info>');
        }

        // Generate new app configurations.
        $configFile = ($tls)
            ? $this->path.'/lemp/nginx/config/conf.d/.default-tls.conf'
            : $this->path.'/lemp/nginx/config/conf.d/.default-no_tls.conf';

        $newConfigFile = $this->path."/lemp/nginx/config/conf.d/{$domain}.conf";

        $this->filesystem->copy($configFile, $newConfigFile);

        // Replace default hostname with custom domain.
        replace('localhost', $domain, $newConfigFile);

        // Copy the PHP config file to the new app config.
        $this->filesystem->copy($this->path.'/lemp/php/configs/.www.conf', $this->path."/lemp/php/configs/{$domain}.conf");

        // Replace the app name in the PHP configs
        replace('\[www\]', '['.$domain.']', $this->path."/lemp/php/configs/{$domain}.conf");
        replace('\[www\]', '['.$domain.']', $this->path.'/lemp/php/configs/docker.conf');
        replace('\[www\]', '['.$domain.']', $this->path.'/lemp/php/configs/zz-docker.conf');

        // Copy the .env.example file and replace the app name in the .env file
        $this->filesystem->copy('.env.example', '.env');
        replace('localhost', $domain, '.env');

        // Run the Docker build
        buildContainer($input);
    }

    /**
     * Install MKCert to install self-signed certificates.
     */
    private function installMkCert()
    {
        if (! $this->filesystem->exists(getenv('HOME').'/Library/Application Support/mkcert')) {
            output('<comment>➡️ Installing MKCert...</comment>');
            $mkcert = new Process(['brew', 'install', 'mkcert']);
            output('<info>MKCert installed! ✅</info>');

            return $mkcert->run();
        }
    }

    /**
     * Creates a self-signed certificate for the domain.
     *
     * @param string $domain
     */
    private function createSelfSignedCertificate($domain)
    {
        $makeCert = new Process(
            [
                'mkcert',
                '-cert-file', "{$this->path}/certs/{$domain}.crt",
                '-key-file', "{$this->path}/certs/{$domain}-key.key",
                $domain,
            ]
        );
        $makeCert->run();

        if (! $makeCert->isSuccessful()) {
            throw new ProcessFailedException($makeCert);
        }

        // Replace default certificate with custom domain.
        replace('localhost', $domain, $this->path.'/lemp/nginx/config/h5bp/ssl/certificate_files.conf');
    }
}
