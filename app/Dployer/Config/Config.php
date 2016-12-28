<?php

namespace Dployer\Config;

use InvalidArgumentException;

/**
 * Class to get configuration values from a given file.
 */
class Config
{
    /**
     * @var array
     */
    protected $data = [];

    /**
     * Constructor.
     *
     * @param string $filePath Full path of configuration file
     *
     * @throws InvalidArgumentException
     * @throws BadFormattedFileException
     */
    public function __construct($filePath = '.dployer')
    {
        if (false === file_exists($filePath)) {
            throw new InvalidArgumentException(
                'The following config file does not exists: '.$filePath
            );
        }

        $this->parseData($filePath);
    }

    /**
     * Retrieves the value of corresponding key.
     *
     * @param string $path
     *
     * @return string|int|array|null
     */
    public function get($path)
    {
        $value = $this->data;
        $path = strtok($path, '.');

        do {
            if (false === array_key_exists($path, $value)) {
                return;
            }

            $value = $value[$path];
        } while ($path = strtok('.'));

        return $value;
    }

    /**
     * Gets file content and parse it into $data array.
     *
     * @param string $filePath
     *
     * @throws BadFormattedFileException
     */
    private function parseData($filePath)
    {
        $this->data = json_decode(file_get_contents($filePath), true);

        if (is_null($this->data)) {
            throw new BadFormattedFileException($filePath);
        }
    }
}
