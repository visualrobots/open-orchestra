<?php

/*
 * Business & Decision - Commercial License
 *
 * Copyright 2014 Business & Decision.
 *
 * All rights reserved. You CANNOT use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell this Software or any parts of this
 * Software, without the written authorization of Business & Decision.
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * See LICENSE.txt file for the full LICENSE text.
 */

namespace PHPOrchestra\CMSBundle\Test\Form\Type;

use PHPOrchestra\CMSBundle\Form\Type\CustomFieldType;

/**
 * Description of ContentTypeTest
 *
 * @author Noël GILAIN <noel.gilain@businessdecision.com>
 */
class CustomFieldTypeTypeTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $availableFields = array(
            'orchestra_missconf' => 'fakeData',
            'orchestra_notdescribed' => array(
                'type' => 'notdescribed'
            ),
            'orchestra_text' => array(
                'type' => 'text',
                'options' => array('required' => true)
            ),
        );
        
        $container = $this->getMock('\\Symfony\\Component\\DependencyInjection\\ContainerInterface');
        
        $container
            ->expects($this->any())
            ->method('getParameter')
            ->will($this->returnValue($availableFields));
        
        $this->customField = new CustomFieldType($container);
        
        $this->formBuilderMock =
            $this->getMock('\\Symfony\\Component\\Form\\FormBuilderInterface');
    }

    /**
     * @dataProvider getOptions
     * 
     * @param array  $options
     * @param int  $expectedAddCount
     */
    public function testBuildForm($options, $expectedAddCount)
    {
        $this->formBuilderMock
            ->expects($this->exactly($expectedAddCount))
            ->method('add')
            ->will($this->returnSelf());
        
        $this->customField->buildForm($this->formBuilderMock, $options);
    }

    /**
     * @dataProvider getExceptionsData
     * 
     * @param array  $options
     */
    public function testException($options)
    {
        $this->setExpectedException('\\PHPOrchestra\\CMSBundle\\Exception\\UnknownFieldTypeException');
        
        $this->customField->buildForm($this->formBuilderMock, $options);
    }

    public function testGetName()
    {
        $this->assertEquals('orchestra_customField', $this->customField->getName());
    }

    public function getOptions()
    {
        $dataWithNoOptions = (object) array('type' => 'orchestra_text');
        $dataWithOptions = (object) array(
            'type' => 'orchestra_text',
            'options' => (object) array('required' => false)
        );
        
        return array(
            array(array('data' =>  $dataWithNoOptions), 7),
            array(array('data' =>  $dataWithOptions), 7)
        );
    }

    public function getExceptionsData()
    {
        $unknownFieldType = (object) array('type' => 'orchestra_hidden');
        $missConfiguration = (object) array('type' => 'orchestra_missconf');
        $fieldtypeNoDesc = (object) array('type' => 'orchestra_notdescribed');
        
        return array(
            array(array()), // No data
            array(array('data' =>  $unknownFieldType)), // Unknown field type
            array(array('data' =>  $missConfiguration)), // Missconfiguration
            array(array('data' =>  $fieldtypeNoDesc)), // Field type not described
        );
    }
}
