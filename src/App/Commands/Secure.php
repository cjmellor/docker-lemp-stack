<?php

namespace Saber;

use Exception;

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
     * @return void
     */
    public function checkSecureStatus($domain)
    {
        $sitePath = SABER_HOME_CONFIG_PATH . "/lemp/nginx/config/conf.d/{$domain}.conf";

        if ($this->file->fileExists($this->certPath . "{$domain}.crt")) {
            throw new Exception('The site is already secure.');
        }

        if (!$this->file->fileExists($sitePath)) {
            throw new Exception('This site does not exist');
        }
    }

    public function secure($domain)
    {
        // Generate a certificate for the site
        $this->site->generateCertificate($domain);

        $tlsConfig = SABER_HOME_CONFIG_PATH . '/lemp/nginx/config/conf.d/.default-tls.conf';
        $nginxTlsConfig = SABER_HOME_CONFIG_PATH . '/lemp/nginx/config/conf.d/' . $domain . '.conf';

        // Copy the TLS config file
        $this->file->copyFile($tlsConfig, $nginxTlsConfig, true);

        replace('localhost', $domain, $nginxTlsConfig);
    }
}
