<?php

namespace OpenOrchestra\FunctionalTests\Mapping\Metadata\Driver;

use Metadata\Driver\FileLocator;
use OpenOrchestra\Mapping\Metadata\Driver\YamlDriver;

/**
 * Class YamlDriverTest
 */
class YamlDriverTest extends AbstractDriverTest
{
    /**
     * Set Up
     */
    public function setUp()
    {
        parent::setUp();
        $dirs = array('OpenOrchestra\FunctionalTests\Mapping\Metadata\Driver\FakeClass' => __DIR__ . '/yml');
        $fileLocator = new FileLocator($dirs);

        $this->driver = new YamlDriver($fileLocator,
            $this->propertySearchMetadataFactory,
            $this->mergeableClassMetadataFactory
        );
    }

    /**
     * Test LoadMetadataForClass
     */
    public function testLoadMetadataForClass()
    {
        $this->markTestSkipped('Problem with Travis builds');
    }
}
