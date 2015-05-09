<?php
namespace Dployer\Config;

/**
* Test case for Dployer\Config class
*/
class ConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidConfigPathShouldThrowsException()
    {
        new Config($this->getFixture('non-existent'));
    }

    public function testConstructorShouldReadFileAndFillDataAttibute()
    {
        $config = new Config($this->getFixture('well-formatted.json'));
        $this->assertAttributeEquals(
            [
                'app' => 'app-value',
                'env' => 'env-value',
            ],
            'data',
            $config
        );
    }

    /**
     * @expectedException Dployer\Config\BadFormattedFileException
     */
    public function testParseDataOfBadFormattedFileShouldThrowsException()
    {
        new Config($this->getFixture('bad-formatted.json'));
    }

    public function testGetShouldReturnConfigValueFromFile()
    {
        $config = new Config($this->getFixture('well-formatted.json'));

        $this->assertEquals('app-value', $config->get('app'));
        $this->assertEquals('env-value', $config->get('env'));
    }

    public function testGetNonExistentKeyShouldReturnNull()
    {
        $config = new Config($this->getFixture('well-formatted.json'));

        $this->assertNull($config->get('non-existent-key'));
    }

    /**
     * Retrieves fill path of fixture
     *
     * @param  string $fileName
     *
     * @return string
     */
    private function getFixture($fileName)
    {
        return __DIR__.'/../../fixtures/'.$fileName;
    }
}
