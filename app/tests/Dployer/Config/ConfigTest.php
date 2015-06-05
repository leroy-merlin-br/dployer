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
                "second" => [
                    "level" => "second-value",
                ],
                "third" => [
                    "level" => [
                        "key" => "third-value",
                        "array" => [
                            "third-value-array-1",
                            "third-value-array-2",
                            "third-value-array-3",
                        ]
                    ]
                ]
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

    public function testGetSecondLevelValueUsingDotNotationShouldReturnValue()
    {
        $config = new Config($this->getFixture('well-formatted.json'));

        $this->assertEquals('second-value', $config->get('second.level'));
    }

    public function testGetThirdLevelValueUsingDotNotationShouldReturnValue()
    {
        $config = new Config($this->getFixture('well-formatted.json'));

        $this->assertEquals(
            'third-value',
            $config->get('third.level.key')
        );
        $this->assertEquals(
            [
                'third-value-array-1',
                'third-value-array-2',
                'third-value-array-3',
            ],
            $config->get('third.level.array')
        );
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
