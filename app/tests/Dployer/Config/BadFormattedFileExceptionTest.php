<?php
namespace Dployer\Config;

use LogicException;

/**
* Test case for Dployer\Config\BadFormattedFileException
*/
class BadFormattedFileExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructorShouldCallParentWithCustomMessage()
    {
        $filePath  = 'some';
        $previous  = new LogicException();
        $exception = new BadFormattedFileException($filePath, $previous);

        $this->assertEquals(
            'Error trying to parse some file',
            $exception->getMessage()
        );
    }
}
