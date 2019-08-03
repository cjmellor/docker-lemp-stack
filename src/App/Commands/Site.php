<?php

namespace Saber;

use Exception;

class Site
{
    public $file;

    public function __construct(File $file, Shell $cli)
    {
        $this->file = $file;
        $this->cli = $cli;
    }

    /**
     * Generate a self-signed certificate for the specified domain
     *
     * @param string $domain
     * @return void
     */
    public function generateCertificate($domain)
    {
        // Check if MKCert is installed, if not, install it
        $this->installMkCert();

        // If the 'certs' folder doesn't exist, create it
        if (! $this->file->folderExists(SABER_HOME_CONFIG_PATH . '/certs')) {
            $this->file->createDirectory(SABER_HOME_CONFIG_PATH . '/certs');
        }

        $certificateStub = SABER_HOME_CONFIG_PATH . '/lemp/nginx/config/h5bp/ssl/certificate_files.conf';
        $appCertificate = SABER_HOME_CONFIG_PATH . '/lemp/nginx/config/ssl/' . $domain . '.conf';

        // Copy the SSL configuration into it's own app location
        $this->file->copyFile($certificateStub, $appCertificate);

        // Run the 'mkcert' command to create certificates.
        $this->createSelfSignedCertificate($domain);
    }

    /**
     * Removes certificate files for a domain
     *
     * @param string $domain
     * @return void
     */
    public function removeCertificate($domain)
    {
        info('Removing certificates...');

        $this->file->deleteFiles([
            SABER_HOME_CONFIG_PATH . '/certs/' . $domain . '.crt',
            SABER_HOME_CONFIG_PATH . '/certs/' . $domain . '-key.key',
        ]);
    }

    /**
     * Install MKCert to install self-signed certificates.
     *
     * @return void
     */
    public function installMkCert()
    {
        if (! $this->file->folderExists(getenv('HOME') . '/Library/Application Support/mkcert')) {
            info('Installing MKCert...');
            $this->cli->run('brew install mkcert');
            success('MKCert installed!');
        }
    }

    /**
     * Creates a self-signed certificate for the domain.
     *
     * @param string $domain
     * @return void
     */
    public function createSelfSignedCertificate($domain)
    {
        $this->cli->run("mkcert -cert-file " . SABER_HOME_CONFIG_PATH . "/certs/{$domain}.crt -key-file " . SABER_HOME_CONFIG_PATH . "/certs/{$domain}-key.key {$domain}");

        $appCertificate = SABER_HOME_CONFIG_PATH . '/lemp/nginx/config/ssl/' . $domain . '.conf';

        // Replace default certificate with custom domain.
        replace('localhost', $domain, $appCertificate);

        // Clean up the SSL config
        $this->cleanup([$appCertificate], '#');
    }

    /**
     * Creates the NGINX config files
     *
     * @param string $domain
     * @param string $config
     * @return void
     */
    public function createNginxConfig($domain, $config)
    {
        $nginxConfig = SABER_HOME_CONFIG_PATH . '/lemp/nginx/config/conf.d/' . $domain . '.conf';

        $this->checkAppExists($domain);

        info('Creating NGINX configuration...');

        // Make a copy of the stub config for the app
        $this->file->copyFile($config, $nginxConfig);

        // Replace default hostname with custom domain.
        replace('localhost', $domain, $nginxConfig);

        $this->createCodeDirectory($domain);
    }

    /**
     * Check and see if an app is aleady been used
     *
     * @param string $domain
     * @return void
     */
    public function checkAppExists($domain)
    {
        if ($this->file->folderExists(SABER_HOME_CONFIG_PATH . '/code/' . $domain)) {
            throw new Exception('Application already exists');
        }
    }

    /**
     * Creates a folder for the app's public code
     *
     * @param string $domain
     * @return void
     */

    public function createCodeDirectory($domain)
    {
        $codePath = SABER_HOME_CONFIG_PATH . '/code/' . $domain;

        // Create a public folder for app code
        $this->file->createDirectory($codePath);

        // Add a HTML file in there to show it's working
        $this->file->putContent($codePath . '/index.html', "<h1>$domain is up and running!</h1>");
    }

    /**
     * Removes the NGINX config
     *
     * @param string $domain
     * @return void
     */
    public function removeNginxConfig($domain)
    {
        info('Removing NGINX configuration file...');

        $this->file->deleteFiles(SABER_HOME_CONFIG_PATH . '/lemp/nginx/config/conf.d/' . $domain . '.conf');
    }

    /**
     * Remove the PHP config
     *
     * @param string $domain
     * @return void
     */
    public function removePhpConfig($domain)
    {
        info('Removing PHP configuration file...');

        $this->file->deleteFiles(SABER_HOME_CONFIG_PATH . '/lemp/php/config/' . $domain . '.conf');
    }

    /**
     * Removes the code directory for the app
     *
     * @param string $domain
     * @return void
     */
    public function removeSiteCodeDirectory($domain)
    {
        $codeFolderPath = SABER_HOME_CONFIG_PATH . '/code/' . $domain;

        if ($this->file->folderExists($codeFolderPath)) {
            $this->file->removeDirectory($codeFolderPath);
        }
    }

    /**
     * Creates the PHP configuration files
     *
     * @param string $domain
     * @return void
     */
    public function createPhpConfig($domain)
    {
        $phpConfig = SABER_HOME_CONFIG_PATH . '/lemp/php/config/' . $domain . '.conf';

        info('Creating PHP configuration...');

        // Copy the PHP config file to the new app config.
        $this->file->copyFile(SABER_HOME_CONFIG_PATH . '/lemp/php/config/www.conf', $phpConfig);

        // Replace the app name in the PHP configs
        replace('\[www\]', '[' . $domain . ']', $phpConfig);

        // Clean up config files, removing all the unwanted commented out code
        $this->cleanup([$phpConfig], ';');
    }

    /**
     * Cleans up a file, removing comments
     *
     * @param array $filename
     * @param string $delimiter
     * @return void
     */
    public function cleanUp(array $filename, $delimiter)
    {
        foreach ($filename as $configFilename) {
            $newOutput = $this->cli->run("grep -Ev '^{$delimiter}|^$' $configFilename");

            $this->file->putContent($configFilename, $newOutput);
        }
    }
}
