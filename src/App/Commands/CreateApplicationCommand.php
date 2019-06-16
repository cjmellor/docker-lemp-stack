<?php

namespace Saber;

use function Saber\output;
use function Saber\replace;
use function Saber\restartContainers;
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
            ->addOption('tls', 't', InputOption::VALUE_NONE, 'Add TLS certificates for your app');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $domain = $input->getArgument('domain').'.test';
        $tls = $input->getOption('tls');

        // The TLD is added automatically, so no need to write it out yourself
        if (strpos($input->getArgument('domain'), '.')) {
            throw new RuntimeException('A domain TLD is not required, this will be added automatically.');
        }

        // If --tls was enabled, certificates will be generated for the domain
        if ($tls) {
            $this->generateCertificate($domain);
        }

        // Generate new app configurations.
        $configFile = ($tls)
            ? $this->path.'/lemp/nginx/config/conf.d/.default-tls.conf'
            : $this->path.'/lemp/nginx/config/conf.d/.default-no_tls.conf';

        $this->createNginxConfig($domain, $configFile);

        $this->createPhpConfig($domain);

        restartContainers();
        
        output('<info>Application built and ready to use!</info>');
    }

    private function cleanup(array $fileName, $delimiter)
    {
        foreach ($fileName as $configFileName) {
            $grep = "grep -Ev '^$delimiter|^$' $configFileName";

            $newOutput = shell_exec($grep);

            $this->filesystem->dumpFile($configFileName, $newOutput);
        }
    }

    /**
     * Copy the stub PHP config to the applications config
     * Replace the stub domain with the application domain
     * Cleanup the PHP config - removing whitespace
     *
     * @param string $domain
     *
     * @return void
     */
    private function createPhpConfig($domain)
    {
        $phpConfig = $this->path."/lemp/php/configs/{$domain}.conf";

        output('<comment>Creating PHP configuration...</comment>');

        // Copy the PHP config file to the new app config.
        $this->filesystem->copy($this->path.'/lemp/php/configs/www.conf', $phpConfig);

        // Replace the app name in the PHP configs
        replace('\[www\]', '['.$domain.']', $phpConfig);

        // Clean up config files, removing all the unwanted commented out code
        $this->cleanup([$phpConfig], ';');
    }

    /**
     * Copies the stub NGINX config to the applications config
     * Replace the stub domain with the application domain
     *
     * @param string $domain
     * @param string $config
     *
     * @return void
     */
    private function createNginxConfig($domain, $config)
    {
        $nginxConfig = $this->path."/lemp/nginx/config/conf.d/{$domain}.conf";

        output('<comment>Creating NGINX configuration...</comment>');

        // Make a copy of the stub config for the app
        $this->filesystem->copy($config, $nginxConfig);

        // Replace default hostname with custom domain.
        replace('localhost', $domain, $nginxConfig);

        $this->createCodeDirectory($domain);
    }

    /**
     * Creates the folder for the application code
     * Creates an HTML to show it's working
     *
     * @param string $domain
     *
     * @return void
     */
    private function createCodeDirectory($domain)
    {
        $codePath = $this->path . '/code/' . $domain;

        // Create a folder for the root code
        $this->filesystem->mkdir($codePath);

        // Add a HTML file in there to show it's working
        $this->filesystem->dumpFile($codePath . '/index.html', "<h1>$domain is up and running!</h1>");
    }

    /**
     * Generate a self-signed certificate for the specified domain
     *
     * @param string $domain
     *
     * @return void
     */
    private function generateCertificate(string $domain)
    {
        $certificateStub = $this->path . '/lemp/nginx/config/h5bp/ssl/certificate_files.conf';
        $appCertificate = $this->path . '/lemp/nginx/config/ssl/' . $domain . '.conf';

        // Check if MKCert is installed, if not, install it
        $this->installMkCert();

        // If the 'certs' folder doesn't exist, create it
        if (! $this->filesystem->exists($this->path.'/certs')) {
            $this->filesystem->mkdir($this->path.'/certs');
        }

        // Copy the SSL configuration into it's own app location
        $this->filesystem->copy($certificateStub, $appCertificate);

        // Run the 'mkcert' command to create certificates.
        $this->createSelfSignedCertificate($domain);

        output('<info>Cretificates successfully created!</info>');
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
    private function createSelfSignedCertificate(string $domain)
    {
        $appCertificate = $this->path . '/lemp/nginx/config/ssl/' . $domain . '.conf';

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
        replace('localhost', $domain, $appCertificate);

        // Clean up the SSL config
        $this->cleanup([$appCertificate], '#');
    }
}
