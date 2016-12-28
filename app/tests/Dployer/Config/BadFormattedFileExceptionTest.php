<?php
namespace Dployer\Config;

use PHPUnit_Framework_TestCase;

class BadFormattedFileExceptionTest extends PHPUnit_Framework_TestCase
{
    public function testConstructorShouldCallParentWithCustomMessage()
    {
        $filePath  = 'some';
        $exception = new BadFormattedFileException($filePath);

        $this->assertEquals(
            'Error trying to parse some file',
            $exception->getMessage()
        );
    }
}
