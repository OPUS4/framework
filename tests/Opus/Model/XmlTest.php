<?php
/**
 * This file is part of OPUS. The software OPUS has been originally developed
 * at the University of Stuttgart with funding from the German Research Net,
 * the Federal Department of Higher Education and Research and the Ministry
 * of Science, Research and the Arts of the State of Baden-Wuerttemberg.
 *
 * OPUS 4 is a complete rewrite of the original OPUS software and was developed
 * by the Stuttgart University Library, the Library Service Center
 * Baden-Wuerttemberg, the Cooperative Library Network Berlin-Brandenburg,
 * the Saarland University and State Library, the Saxon State Library -
 * Dresden State and University Library, the Bielefeld University Library and
 * the University Library of Hamburg University of Technology with funding from
 * the German Research Foundation and the European Regional Development Fund.
 *
 * LICENCE
 * OPUS is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the Licence, or any later version.
 * OPUS is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details. You should have received a copy of the GNU General Public License
 * along with OPUS; if not, write to the Free Software Foundation, Inc., 51
 * Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 *
 * @category    Tests
 * @package     Opus_Model
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Test creation XML from models and creation of models by valid XML respectivly.
 *
 * @category    Tests
 * @package     Opus_Model
 *
 * @group XmlTest
 */
class Opus_Model_XmlTest extends PHPUnit_Framework_TestCase {

    /**
     * Test if getModel() returns model previously defined with setModel().
     *
     * @return void
     */
    public function testGetModelRetrievesModelSetBeforeBySetModel() {
        $xml = new Opus_Model_Xml();
        $model = new Opus_Model_ModelAbstract();
        $xml->setModel($model);
        $this->assertEquals($model, $xml->getModel(), 'Returned Model does not equal given Model.');
    }

    /**
     * Test if attempt to generate XML from null throws Exception.
     *
     * @return void
     */
    public function testXmlFromEmptyModelThrowsException() {
        $xml = new Opus_Model_Xml();
        $xml->setModel(null);
        $this->setExpectedException('Opus_Model_Exception');
        $xml->getDomDocument();
    }
    
    /**
     * Test if getDomDocument() returns a DomDocument object.
     *
     * @return void
     */
    public function testGetDomDocumentReturnsDomDocument() {
        $xml = new Opus_Model_Xml();
        $model = new Opus_Model_ModelAbstract();
        $xml->setModel($model);
        $dom = $xml->getDomDocument();
        $this->assertType('DomDocument', $dom, 'Returned object is of wrong type.');        
    }
    
    /**
     * Test if a valid XML representation of a Model gets returned.
     *
     * @return void
     */
    public function testCreateXmlFromModel() {
        $xml = new Opus_Model_Xml();
        $model = new Opus_Model_ModelAbstract();
        $model->setValue('FooBar');
        $dom = $xml->setModel($model)->getDomDocument();
        
        // Root element has name of Model class
        $this->assertEquals(get_class($model), $dom->documentElement->localName, 'Node name does not equal Model class name');
        
        // There is an attribute "Value" with the value "FooBar"
        $value = $dom->documentElement->attributes->getNamedItem('Value');
        $this->assertNotNull($value, 'Value attribute missing.');
        $this->assertEquals('FooBar', $value->nodeValue, 'Attribute value is wrong.');
    }
    
    /**
     * Test if a XML child element os generated for each sub model.
     *
     * @return void
     */
    public function testOneChildElementPerSubModel() {
        $model = new Opus_Model_ModelAbstract;
        $model->getField('Value')->setValueModelClass('Opus_Model_ModelAbstract');
        $model->setValue(new Opus_Model_ModelAbstract);
        $xml = new Opus_Model_Xml;
        $xml->setModel($model);
        $dom = $xml->getDomDocument();
        
        // assert that there is a sub element of name Opus_Model_ModelAbstract
        $child = $dom->documentElement->firstChild;
        $this->assertEquals('Opus_Model_ModelAbstract', $child->localName);
    }
   
    /**
     * Test if fields that are statet in the exclude list do not show in the XML.
     *
     * @return void
     */
    public function testFieldsFromExcludeListAreNotSerialized() {
        $model = new Opus_Model_ModelAbstract;
        $model->addField(new Opus_Model_Field('TestField'));
        $model->setTestField(4711)
            ->setValue('Foo');
        
        $xml = new Opus_Model_Xml;
        $xml->setModel($model)
            ->exclude(array('TestField'));
        $dom = $xml->getDomDocument();

        // assert that testField is not there
        $attr = $dom->documentElement->hasAttribute('TestField');
        $this->assertFalse($attr, 'Field has not been excluded.');
    }

    /**
     * Test if fields that are empty do not show in the XML.
     *
     * @return void
     */
    public function testEmptyFieldsAreNotSerialized() {
        $model = new Opus_Model_ModelAbstract;
        $model->setValue(null);
        
        $xml = new Opus_Model_Xml;
        $xml->setModel($model)
            ->excludeEmptyFields();
        $dom = $xml->getDomDocument();

        // assert that testField is not there
        $attr = $dom->documentElement->hasAttribute('Value');
        $this->assertFalse($attr, 'Empty field has not been excluded.');
    }



    /**
     * Data provider for models and corresponding xml representations.
     *
     * @return array
     */
    public function xmlModelDataProvider() {
        $model1 = new Opus_Model_ModelAbstract();
        $model1->setValue('Foo');
    
        return array(
            array('<Opus_Model_ModelAbstract Value="Foo" />', $model1),
            array($model1->toXml(), $model1)
        );
    }
    
    /**
     * Create a model using its XML representation.
     *
     * @param DomDocument|string $xml    XML representation of a model.
     * @param Opus_Model_Abstract $model A model corresponding to the given XML representation.
     * @return void
     *
     * @dataProvider xmlModelDataProvider
     */
    public function testCreateFromXml($xml, $model) {
        $fromXml = Opus_Model_Abstract::fromXml($xml);
        $this->assertEquals($model->toArray(), $fromXml->toArray(), 'Models array representations differ.');  
    }      


}

