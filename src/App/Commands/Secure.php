<?php

namespace Saber;

class Secure
{
    public $file;
    public $site;

    /**
     * Secure configuration class instance
     *
     * @param File $file
     */
    public function __construct(File $file, Site $site)
    {
        $this->file = $file;
        $this->site = $site;
        $this->certPath = SABER_HOME_CONFIG_PATH . '/certs/';
    }

    /**
     * Check if the certificate already exists
     *
     * @param string $domain
     * @param bool $unsecure
     * @return void
     */
    public function checkSecureStatus($domain, $unsecure = false)
    {
        $sitePath = SABER_HOME_CONFIG_PATH . "/lemp/nginx/config/conf.d/{$domain}.conf";

        if ($this->file->fileExists($this->certPath . "{$domain}.crt") && (!$unsecure == true)) {
            throw new \Exception('The site is already secure.');
        }

        if (!$this->file->fileExists($sitePath)) {
            throw new \Exception('This site does not exist');
        }
    }

    /**
     * Secure an app with an SSL certificate
     *
     * @param string $domain
     * @return void
     */
    public function secure($domain)
    {
        // Generate a certificate for the site
        $this->site->generateCertificate($domain);

        $tlsConfig = SABER_HOME_CONFIG_PATH . '/lemp/nginx/config/conf.d/.default-tls.conf';
        $nginxConfig = SABER_HOME_CONFIG_PATH . '/lemp/nginx/config/conf.d/' . $domain . '.conf';

        // Copy the TLS config file
        $this->file->copyFile($tlsConfig, $nginxConfig, true);

        replace('localhost', $domain, $nginxConfig);
    }

    /**
     * Unsecure an app - removes the certificate
     *
     * @param string $domain
     * @return void
     */
    public function unsecure($domain)
    {
        // Remove the certificate and key
        $this->file->deleteFiles([
            SABER_HOME_CONFIG_PATH . "/certs/{$domain}.crt",
            SABER_HOME_CONFIG_PATH . "/certs/{$domain}-key.key"
        ]);

        $nonTlsConfig = SABER_HOME_CONFIG_PATH . '/lemp/nginx/config/conf.d/.default-no_tls.conf';
        $nginxConfig = SABER_HOME_CONFIG_PATH . '/lemp/nginx/config/conf.d/' . $domain . '.conf';

        // Copy the non-TLS config file
        $this->file->copyFile($nonTlsConfig, $nginxConfig, true);

        replace('localhost', $domain, $nginxConfig);
    }
}
