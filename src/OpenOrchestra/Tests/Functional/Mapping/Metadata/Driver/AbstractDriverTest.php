<?php

namespace OpenOrchestra\FunctionalTests\Mapping\Metadata\Driver;

use Metadata\PropertyMetadata;
use Metadata\ClassMetadata;
use Metadata\Driver\DriverInterface;
use OpenOrchestra\Mapping\Metadata\MergeableClassMetadataFactory;
use OpenOrchestra\Mapping\Metadata\PropertySearchMetadataFactory;
use OpenOrchestra\FunctionalTests\Mapping\Metadata\Driver\FakeClass\FakeClassMetadata;
use OpenOrchestra\FunctionalTests\Mapping\Metadata\Driver\FakeClass\FakeClassWithOutMetadata;
use ReflectionObject;

/**
 * Class AbstractDriverTest
 */
abstract class AbstractDriverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DriverInterface
     */
    protected $driver;
    protected $propertySearchMetadataFactory;
    protected $mergeableClassMetadataFactory;

    public function setUp()
    {
        $this->propertySearchMetadataFactory = new PropertySearchMetadataFactory();
        $this->mergeableClassMetadataFactory = new MergeableClassMetadataFactory();
    }

    /**
     * Test LoadMetadataForClass with out search metadata
     */
    public function testLoadMetadaForClassWithOutMetadata()
    {
        $class = new \ReflectionClass(new FakeClassWithOutMetadata());
        $metadata = $this->driver->loadMetadataForClass($class);
        $this->assertNull($metadata);
    }

    /**
     * Test LoadMetadataForClass
     */
    public function testLoadMetadataForClass()
    {
        $class = new \ReflectionClass(new FakeClassMetadata());
        $metadata = $this->driver->loadMetadataForClass($class);
        $this->assertMetadata($metadata);
    }

    /**
     * @param ClassMetadata $metadata
     */
    protected function assertMetadata(ClassMetadata $metadata)
    {
        $this->assertInstanceOf('Metadata\ClassMetadata', $metadata);
        $propertiesMetadata = $metadata->propertyMetadata;
        $this->assertArrayHasKey('fakeProperty1', $propertiesMetadata);
        $this->assertArrayHasKey('fakeProperty2', $propertiesMetadata);
        $propertyMetadataFakeProperty1 = $propertiesMetadata['fakeProperty1']->propertySearchMetadata[0];
        $this->assertPropertyMetadata($propertyMetadataFakeProperty1, 'fake_property1', 'fakeType', 'fakeProperty1');

        $this->assertCount(2, $propertiesMetadata['fakeProperty2']->propertySearchMetadata);
        $propertyMetadataFakeProperty2 = $propertiesMetadata['fakeProperty2']->propertySearchMetadata[0];
        $this->assertPropertyMetadata($propertyMetadataFakeProperty2, array("fake_property2", "fake_property_multi"), 'string', 'fakeProperty2');
        $propertyMetadataFakeProperty2Other = $propertiesMetadata['fakeProperty2']->propertySearchMetadata[1];
        $this->assertPropertyMetadata($propertyMetadataFakeProperty2Other, "fake_property3", 'string', 'fakeProperty2');

    }

    /**
     * @param PropertyMetadata $property
     * @param array|string     $key
     * @param string           $type
     * @param string           $field
     */
    protected function assertPropertyMetadata($property, $key, $type, $field)
    {
        $this->assertEquals($property->key, $key);
        $this->assertEquals($property->type, $type);
        $this->assertEquals($property->field, $field);
    }

    /**
     * Clean up
     */
    protected function tearDown()
    {
        $refl = new ReflectionObject($this);
        foreach ($refl->getProperties() as $prop) {
            if (!$prop->isStatic() && 0 !== strpos($prop->getDeclaringClass()->getName(), 'PHPUnit_')) {
                $prop->setAccessible(true);
                $prop->setValue($this, null);
            }
        }
    }
}
