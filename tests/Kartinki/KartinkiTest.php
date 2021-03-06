<?php

namespace happyproff\Kartinki;

use happyproff\Kartinki\Interfaces\ConfigInterface;

class KartinkiTest extends \PHPUnit_Framework_TestCase
{
    private static $assetsDir;
    private static $tempDir;

    public static function setUpBeforeClass()
    {
        self::$assetsDir = dirname(dirname(__FILE__)) . '/assets';
        self::$tempDir = self::$assetsDir . '/tmp';
    }

    public function testVersionsCreating()
    {
        $this->_testImage('big-horizontal.jpg');
        $this->_testImage('big-vertical.jpg');
        $this->_testImage('small-horizontal.png');
    }

    public function testManuallyCreatedConfigs()
    {
        $versionConfig = new Config;
        $versionConfig->setWidth(400);
        $versionConfig->setHeight(400);
        $versionConfig->setFit(true);
        $this->_testManuallyCreatedConfig($versionConfig);

        $versionConfig = (new Config)->setWidth(400)->setHeight(400)->setFit(true);
        $this->_testManuallyCreatedConfig($versionConfig);

        $versionConfigArry = Config::createFromArray([
            'width' => 400,
            'height' => 400,
            'fit' => true,
        ]);
        $this->_testManuallyCreatedConfig($versionConfig);

        $versionConfig = (new ConfigParser)->parse('400x400:fit');
        $this->_testManuallyCreatedConfig($versionConfig);

        $versionConfig = new Config(400, 400);
        $versionConfig->setFit(true);
        $this->_testManuallyCreatedConfig($versionConfig);
    }

    private function _testManuallyCreatedConfig(ConfigInterface $versionConfig) {
        $this->prepareTempDir();

        $versionsConfig = [
            'big' => $versionConfig,
        ];

        $imagePath = self::$assetsDir . '/big-horizontal.jpg';

        $kartinki = new Kartinki;
        $result = $kartinki->createImageVersions($imagePath, $versionsConfig, self::$tempDir);

        $this->assertArrayHasKey('big', $result);
        $this->assertFileExists(self::$tempDir . '/' . $result['big']);
        list($width, $height) = getimagesize(self::$tempDir . '/' . $result['big']);
        $this->assertEquals(400, $width);
        $this->assertEquals(225, $height);

        $this->removeTempDir();
    }

    private function prepareTempDir()
    {
        if (is_dir(self::$tempDir)) {
            $this->removeTempDir();
        }
        mkdir(self::$tempDir);
    }

    private function removeTempDir()
    {
        foreach (scandir(self::$tempDir) as $file) {
            if (in_array($file, ['.', '..'])) {
                continue;
            }
            unlink(self::$tempDir . '/' . $file);
        }
        rmdir(self::$tempDir);
    }

    private function _testImage($imageName)
    {
        $this->prepareTempDir();

        $versions = [
            'thumb' => ['width' => 200, 'height' => 200, 'fit' => false, 'quality' => 10],
            'vertical' => ['width' => 200, 'height' => 0, 'fit' => false],
            'horizontal' => ['width' => 0, 'height' => 200, 'fit' => false],
            'big' => ['width' => 400, 'height' => 400, 'fit' => true],
            'orig' => ['width' => 0, 'height' => 0, 'fit' => false],
        ];
        $versionsConfig = array_map(function ($value) {
            return $value['width'] . 'x' . $value['height'] . ($value['fit'] ? ':fit' : '') . (isset($value['quality']) ? ',quality=' . $value['quality'] : '');
        }, $versions);


        $imagePath = self::$assetsDir . '/' . $imageName;
        list($initialWidth, $initialHeight) = getimagesize($imagePath);

        $kartinki = new Kartinki;
        $result = $kartinki->createImageVersions($imagePath, $versionsConfig, self::$tempDir);
        foreach ($versions as $versionName => $versionConfig) {
            $this->assertArrayHasKey($versionName, $result);
            $this->assertFileExists(self::$tempDir . '/' . $result[$versionName]);
            list($width, $height) = getimagesize(self::$tempDir . '/' . $result[$versionName]);

            if (!$versionConfig['fit']) {
                if ($versionConfig['width']) {
                    $this->assertEquals($versionConfig['width'], $width);
                }
                if ($versionConfig['height']) {
                    $this->assertEquals($versionConfig['height'], $height);
                }
            }
        }

        list($width, $height) = getimagesize(self::$tempDir . '/' . $result['big']);
        if ($initialWidth > $initialHeight) {
            $this->assertEquals(400, $width);
            $this->assertEquals(($initialHeight / $initialWidth * 400), $height);
        } else {
            $this->assertEquals(400, $height);
            $this->assertEquals(($initialWidth / $initialHeight * 400), $width);
        }

        $this->removeTempDir();
    }
}
