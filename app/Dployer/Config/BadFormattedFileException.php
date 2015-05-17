<?php
namespace Dployer\Config;

use Exception;

/**
* This exception is used to catch bad formatted config files
*/
class BadFormattedFileException extends Exception
{
    /**
     * Constructor
     *
     * @param string     $filePath
     * @param \Exception $previous
     */
    public function __construct($filePath)
    {
        parent::__construct(
            sprintf('Error trying to parse %s file', $filePath)
        );
    }
}
