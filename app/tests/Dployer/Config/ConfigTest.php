<?php
namespace Dployer\Config;

use InvalidArgumentException;
use PHPUnit_Framework_TestCase;

class ConfigTest extends PHPUnit_Framework_TestCase
{
    public function testInvalidConfigPathShouldThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        
        new Config($this->getFixture('non-existent'));
    }

    public function testConstructorShouldReadFileAndFillDataAttribute()
    {
        $config = new Config($this->getFixture('well-formatted.json'));
        $this->assertAttributeEquals(
            [
                'app' => 'app-value',
                'env' => 'env-value',
                'second' => [
                    'level' => 'second-value',
                ],
                'third' => [
                    'level' => [
                        'key' => 'third-value',
                        'array' => [
                            'third-value-array-1',
                            'third-value-array-2',
                            'third-value-array-3',
                        ]
                    ]
                ]
            ],
            'data',
            $config
        );
    }

    public function testParseDataOfBadFormattedFileShouldThrowsException()
    {
        $this->expectException(BadFormattedFileException::class);

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
