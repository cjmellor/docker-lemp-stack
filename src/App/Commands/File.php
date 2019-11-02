<?php

namespace Saber;

use Symfony\Component\Filesystem\Filesystem;

class File
{
    /**
     * Filesystem configuration class instance
     *
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem, Shell $cli)
    {
        $this->filesystem = $filesystem;
        $this->cli = $cli;
    }

    /**
     * Moves the files need to run Saber to it's config home
     *
     * @return void
     */
    public function createSaberStructure()
    {
        info('Installing Saber...');

        $this->moveDirectories('.', SABER_HOME_CONFIG_PATH);

        $this->cli->run('composer update');
    }

    /**
     * Create a directory
     *
     * @param string $folderName
     * @param boolean $recursive
     * @param integer $mode
     * @return void
     */
    public function createDirectory($folderName, $recursive = true, $mode = 0755)
    {
        mkdir($folderName, $mode, $recursive);
    }

    /**
     * Removes a directory
     *
     * @param array|string $folderName
     * @return void
     */
    public function removeDirectory($folderName)
    {
        $this->filesystem->remove($folderName);
    }

    /**
     * Check if a file exists
     *
     * @param string $file
     * @return bool
     */
    public function fileExists($file)
    {
        return $this->filesystem->exists($file);
    }

    /**
     * Make sure a folder exists.
     *
     * @param string $folder
     * @return bool
     */
    public function folderExists($folder)
    {
        return is_dir($folder);
    }

    /**
     * Copy directories to a location
     *
     * @param string $source
     * @param string $dest
     * @return void
     */
    public function moveDirectories($source, $dest)
    {
        if (is_array($source)) {
            foreach ($source as $dir) {
                return $this->filesystem->mirror($dir, $dest);
            }
        }

        $this->filesystem->mirror($source, $dest);
    }

    /**
     * Copy a file to a destination
     *
     * @param string $source
     * @param string $dest
     * @param boolean $overwrite
     * @return void
     */
    public function copyFile($source, $dest, $overwrite = false)
    {
        $this->filesystem->copy($source, $dest, $overwrite);
    }

    /**
     * Removes a single or multiple files
     *
     * @param array|string $files
     * @return void
     */
    public function deleteFiles($files)
    {
        $this->filesystem->remove($files);
    }

    /**
     * Put content into a file.
     *
     * @param string $filename
     * @param string $content
     * @return void
     */
    public function putContent($filename, $content)
    {
        $this->filesystem->dumpFile($filename, $content);
    }
}
