<?php

namespace Xi\Filelib\Tests\Plugin\Video\FFmpeg;

use Xi\Filelib\File\File;
use Xi\Filelib\File\Resource;
use Xi\Filelib\File\FileObject;
use Xi\Filelib\Plugin\Video\FFmpeg\FFmpegPlugin;

/**
 * @group plugin
 * @group ffmpeg
 */
class FFmpegPluginTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FFmpegPlugin
     */
    private $plugin;

    /**
     * @var string
     */
    private $tempDir;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $storage;

    /**
     * @var string
     */
    private $testVideo;

    public function setUp()
    {
        if (!$this->checkFFmpegFound()) {
            $this->markTestSkipped('FFmpeg could not be found');
        }

        $this->testVideo = ROOT_TESTS . '/data/hauska-joonas.mp4';

        $this->storage = $this->getMock('Xi\Filelib\Storage\Storage');

        $options = array(
            'command' => 'echo',
            'options' => array(
                'y' => true
            ),
            'inputs' => array(
                'original' => array(
                    'filename' => true,
                    'options' => array(
                        'ss' => '00:00:01.000',
                        'r' => '1',
                        'vframes' => '1'
                    )
                )
            ),
            'outputs' => array(
                'still' => array(
                    'filename' => 'still.png',
                    'options' => array()
                ),
                'video' => array(
                    'filename' => 'video.webm',
                    'options' => array()
                ),
            )
        );

        $this->tempDir = ROOT_TESTS . '/data/temp';

        $this->plugin = new FFmpegPlugin(
            'xooxer',
            $options
        );

        $filelib = $this->getMockBuilder('Xi\Filelib\FileLibrary')->disableOriginalConstructor()->getMock();
        $filelib->expects($this->any())->method('getTempDir')->will($this->returnValue(ROOT_TESTS . '/data/temp'));
        $filelib->expects($this->any())->method('getStorage')->will($this->returnValue($this->storage));

        $this->plugin->setDependencies($filelib);
    }

    public function tearDown()
    {
        unset($this->plugin);
    }

    /**
     * @test
     */
    public function classShouldExist()
    {
        $this->assertTrue(class_exists('Xi\Filelib\Plugin\Video\FFmpeg\FFmpegPlugin'));
        $this->assertArrayHasKey(
            'Xi\Filelib\Plugin\VersionProvider\AbstractVersionProvider',
            class_parents('Xi\Filelib\Plugin\Video\FFmpeg\FFmpegPlugin')
        );
    }

    /**
     * @test
     */
    public function getHelperShouldReturnFFmpegHelper()
    {
        $helper = $this->plugin->getHelper();

        $this->assertInstanceOf('Xi\Filelib\Plugin\Video\FFmpeg\FFmpegHelper', $helper);
        $this->assertSame($helper, $this->plugin->getHelper());
    }

    /**
     * @test
     */
    public function testCreateVersions()
    {
        $file = File::create(array('id' => 1, 'resource' => Resource::create()));

        $this->storage
            ->expects($this->once())
            ->method('retrieve')
            ->with($this->isInstanceOf('Xi\Filelib\File\Resource'))
            ->will($this->returnValue($this->testVideo));

        $this->assertEquals(
            array(
                'still' => "$this->tempDir/still.png",
                'video' => "$this->tempDir/video.webm"
            ),
            $this->plugin->createVersions($file)
        );
    }

     /**
     * @test
     */
    public function testExtensionFor()
    {
        $file = $this->getMockBuilder('Xi\Filelib\File\File')->disableOriginalConstructor()->getMock();
        $this->assertEquals('png', $this->plugin->getExtensionFor($file, 'still'));
        $this->assertEquals('webm', $this->plugin->getExtensionFor($file, 'video'));
    }

    /**
     * @test
     */
    public function testGetVersions()
    {
        $this->assertEquals(array('still', 'video'), $this->plugin->getVersions());
    }

    /**
     * @test
     */
    public function pluginShouldAllowSharedResource()
    {
        $this->assertTrue($this->plugin->isSharedResourceAllowed());
    }

    /**
     * @test
     */
    public function pluginShouldAllowSharedVersions()
    {
        $this->assertTrue($this->plugin->areSharedVersionsAllowed());
    }

    private function checkFFmpegFound()
    {
        return (boolean) trim(`sh -c "which ffmpeg"`);
    }
}
