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
 * @author      Ralf ClauÃnitzer (ralf.claussnitzer@slub-dresden.de)
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Test creation XML (version1) from models and creation of models by valid XML respectivly.
 *
 * @category    Tests
 * @package     Opus_Model
 *
 * @group XmlVersion1Test
 */
class Opus_Model_Xml_Version1Test extends PHPUnit_Framework_TestCase {

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

        // Root element is Opus
        $this->assertEquals('Opus', $dom->documentElement->localName, 'Root element should be named "Opus".');

        // Assert that first child represents serialized model
        $this->assertEquals(get_class($model), $dom->documentElement->firstChild->localName, 'Node name does not equal Model class name');

        // There is an attribute "Value" with the value "FooBar"
        $value = $dom->documentElement->firstChild->attributes->getNamedItem('Value');
        $this->assertNotNull($value, 'Value attribute missing.');
        $this->assertEquals('FooBar', $value->nodeValue, 'Attribute value is wrong.');
    }


    /**
     * Test if a submodel serializes to an XML element that has the name
     * of the supermodels containing field.
     *
     * @return void
     */
    public function testXmlSubElementsHaveFieldNamesAsDefinedInTheModel() {
        $model = new Opus_Model_ModelAbstract;
        $model->getField('Value')->setValueModelClass('Opus_Model_ModelAbstract');
        $model->setValue(new Opus_Model_ModelAbstract);
        $xml = new Opus_Model_Xml;
        $xml->setModel($model);
        $dom = $xml->getDomDocument();

        // assert that there is a sub element of name Value
        $root = $dom->documentElement->firstChild;
        $child = $root->firstChild;
        $this->assertEquals('Value', $child->localName, 'Wrong field name.');
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

        // assert that there is a sub element of name Value
        $root = $dom->documentElement->firstChild;
        $child = $root->firstChild;
        $this->assertEquals('Value', $child->localName, 'Missing XML element for field.');
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
        $element = $dom->getElementsByTagName('Opus_Model_ModelAbstract')->item(0);
        $this->assertFalse($element->hasAttribute('Value'), 'Empty field has not been excluded.');
    }

    /**
     * Test if empty fields can be serialized.
     *
     * @return void
     */
    public function testEmptyFieldsAreSerializedIfWanted() {
        $model = new Opus_Model_ModelAbstract;
        $model->setValue(null);

        $xml = new Opus_Model_Xml;
        $xml->setModel($model);
        $dom = $xml->getDomDocument();

        // assert that testField is not there
        $element = $dom->getElementsByTagName('Opus_Model_ModelAbstract')->item(0);
        $this->assertTrue($element->hasAttribute('Value'), 'Empty field has not been included.');
    }

    /**
     * Data provider for models and corresponding xml representations.
     *
     * @return array
     */
    public function xmlModelDataProvider() {
        // $this->markTestIncomplete( 'Skipped: Unknown field: Id for Opus_Model_ModelAbstract' );

        // one-field model
        $model1 = new Opus_Model_ModelAbstract();
        $model1->setValue('Foo');

        return array(
            array('<Opus><Opus_Model_ModelAbstract Value="Foo" /></Opus>', $model1, 'Model array representations differ.'),
            array('<Opus>
                    <Opus_Model_ModelAbstract Value="Foo" />
                   </Opus>', $model1, 'Incorrect handling of XML containing spaces and line breaks.'),
            array($model1->toXml(), $model1, 'Build invalid model from before generated XML representation.')
        );
    }

    /**
     * Create a model using its XML representation.
     *
     * @param DomDocument|string  $xml   XML representation of a model.
     * @param Opus_Model_Abstract $model A model corresponding to the given XML representation.
     * @param string              $msg   Error message given on test failure.
     * @return void
     *
     * @dataProvider xmlModelDataProvider
     */
    public function testCreateFromXml($xml, $model, $msg) {
        $xmlHelper = new Opus_Model_Xml;
        if ($xml instanceof DomDocument) {
            $xmlHelper->setDomDocument($xml);
        } else {
            $xmlHelper->setXml($xml);
        }
        $fromXml = $xmlHelper->getModel();
        $this->assertEquals($model->toArray(), $fromXml->toArray(), $msg);
    }

    /**
     * Test if a persisted sub model can be referenced by an xlink:href element
     * instead of a whole XML tree.
     *
     * @return void
     */
    public function testReferencePersistedSubModelsWithXlink() {
        // set up mock models and xml helper
        $model = new Opus_Model_ModelAbstract;
        $model->getField('Value')->setValueModelClass('Opus_Model_ModelAbstractDbMock');
        $model->setValue(new Opus_Model_ModelAbstractDbMock);
        $xml = new Opus_Model_Xml;
        $xml->setModel($model);

        // set up model URI mapping
        $baseUri = 'http://www.localhost.de';
        $resourceMap = array('Opus_Model_ModelAbstractDbMock' => 'dbmock');

        $xml->setXlinkBaseUri($baseUri)
            ->setResourceNameMap($resourceMap);
        $dom = $xml->getDomDocument();
        $root = $dom->documentElement->firstChild;
        $element = $root->firstChild;

        $this->assertEquals('Value', $element->nodeName);
        $this->assertTrue($element->hasAttribute('xlink:type'), 'Missing xlink:type attribute.');
        $this->assertEquals('simple', $element->getAttribute('xlink:type'), 'Wrong xlink:type value.');
        $this->assertTrue($element->hasAttribute('xlink:href'), 'Missing xlink:href attribute.');
        $this->assertEquals('http://www.localhost.de/dbmock/4711',
            $element->getAttribute('xlink:href'), 'Wrong xlink:href reference URI.');
    }

    /**
     * Test if the XML encoding is set to UTF 8.
     *
     * @return void
     */
    public function testXmlEncodingIsUtf8() {
        $xml = new Opus_Model_Xml;
        $xml->setModel(new Opus_Model_ModelAbstract);
        $dom = $xml->getDomDocument();

        $this->assertEquals('UTF-8', $dom->xmlEncoding, 'XML encoding expected to be UTF-8.');
    }

    /**
     * Test if the xmlns:xlink namespace attribute is set.
     *
     * @return void
     */
    public function testXlinkNamespaceIsSpecified() {
        $xml = new Opus_Model_Xml;
        $xml->setModel(new Opus_Model_ModelAbstract);
        $dom = $xml->getDomDocument();
        $root = $dom->documentElement;
        // workaround for hasAttribute bug
        // $root->hasAttribute('xmlns:xlink') delivers false thouhg the element is there
        $this->assertNotNull($root->attributes->getNamedItem('xmlns:xlink'), 'Xlink namespace declaration required.');
    }

    /**
     * Test if using a Opus_Model_Dependent_Link_* as field value get
     * properly resolved by serialization to the associated model of the link.
     *
     * Therefore Opus_Model_Xml needs to call getLinkedModelId() instead of getId() on this model.
     *
     * @return void
     */
    public function testLinkModelsTunnelGetIdCallsToAssociatedModel() {
        $model = new Opus_Model_ModelAbstractDbMock;
        $field = new Opus_Model_Field('LinkField');
        $field->setValueModelClass('Opus_Model_ModelAbstract');
        $model->addField($field);

        // create mock to track calls
        $link = $this->getMock('Opus_Model_ModelDependentLinkMock', array('getId', 'getLinkedModelId', 'describeAll'));
        $link->setModelClass('Opus_Model_ModelAbstract');
        $model->setLinkField($link);

        // expect getLinkedModelId() has been called in instead of getId()
        $link->expects($this->any())->method('describeAll')->will($this->returnValue(array()));
        $link->expects($this->once())->method('getLinkedModelId');
        $link->expects($this->never())->method('getId');

        // trigger behavior
        $xml = new Opus_Model_Xml;
        $xml->setModel($model)->setResourceNameMap(
            array('Opus_Model_ModelAbstract' => 'dbmockresource'));
        $xml->getDomDocument();
    }

    /**
     * Test if the mapping of model classes to named resources is based on the
     * classname of an associated class even when it is connected via a linked model.
     *
     * @return void
     */
    public function testResourceNameMappingUsesAssociatedModelClassWithLinkedModels() {
        // set up a model with a linked Opus_Model_ModelAbstractDbMock
        // use linking via Opus_Model_ModelDependentLinkMock
        $model = new Opus_Model_ModelAbstract;
        $field = new Opus_Model_Field('LinkField');
        $field->setValueModelClass('Opus_Model_ModelAbstractDbMock');
        $model->addField($field);
        $link = new Opus_Model_ModelDependentLinkMock;
        $link->setModelClass('Opus_Model_ModelAbstractDbMock');
        $link->setModel(new Opus_Model_ModelAbstractDbMock);
        $model->setLinkField($link);

        // generate XML
        $xml = new Opus_Model_Xml;
        $xml->setModel($model)->setResourceNameMap(
            array('Opus_Model_ModelAbstractDbMock' => 'dbmockresource'));
        $dom = $xml->getDomDocument();

        // assert that there is a LinkField element with an xlink:href attribute
        $this->assertEquals(1, $dom->getElementsByTagName('LinkField')->length, 'Element for LinkField field is missing.');

        $linkField = $dom->getElementsByTagName('LinkField')->item(0);
        $this->assertNotNull($linkField->attributes->getNamedItem('xlink:href'), 'Xlink declaration missing.');
    }

    /**
     * Test if link model fields are represented by attributes for linked models.
     *
     * @return void
     */
    public function testLinkModelFieldAreMappedToAttributesForLinkedModels() {
        // set up a model with a linked Opus_Model_ModelAbstractDbMock
        // use linking via Opus_Model_ModelDependentLinkMock
        $model = new Opus_Model_ModelAbstract;
        $field = new Opus_Model_Field('LinkField');
        $field->setValueModelClass('Opus_Model_ModelAbstractDbMock');
        $model->addField($field);
        $link = new Opus_Model_ModelDependentLinkMock;
        $link->setModelClass('Opus_Model_ModelAbstractDbMock');
        $link->setModel(new Opus_Model_ModelAbstractDbMock);
        $link->addField(new Opus_Model_Field('LinkModelField'));
        $link->setLinkModelField('SomeValue');
        $model->setLinkField($link);

        // generate XML
        $xml = new Opus_Model_Xml;
        $xml->setModel($model);
        $dom = $xml->getDomDocument();

        // assert existence of attributes for link model fields
        $linkField = $dom->getElementsByTagName('LinkField')->item(0);
        $this->assertTrue($linkField->hasAttribute('LinkModelField'), 'Missing link model attribute.');
    }

    /**
     * Test if link model fields are represented by attributes for linked models
     * even if these models get represented via xlink.
     *
     * @return void
     */
    public function testLinkModelFieldAreMappedToAttributesForLinkedModelsWhenXlinked() {
        // set up a model with a linked Opus_Model_ModelAbstractDbMock
        // use linking via Opus_Model_ModelDependentLinkMock
        $model = new Opus_Model_ModelAbstract;
        $field = new Opus_Model_Field('LinkField');
        $field->setValueModelClass('Opus_Model_ModelAbstractDbMock');
        $model->addField($field);
        $link = new Opus_Model_ModelDependentLinkMock;
        $link->setModelClass('Opus_Model_ModelAbstractDbMock');
        $link->setModel(new Opus_Model_ModelAbstractDbMock);
        $link->addField(new Opus_Model_Field('LinkModelField'));
        $link->setLinkModelField('SomeValue');
        $model->setLinkField($link);

        // generate XML
        $xml = new Opus_Model_Xml;
        $xml->setModel($model)->setResourceNameMap(
            array('Opus_Model_ModelAbstractDbMock' => 'dbmockresource'));
        $dom = $xml->getDomDocument();

        // assert existence of attributes for link model fields
        $linkField = $dom->getElementsByTagName('LinkField')->item(0);
        $this->assertTrue($linkField->hasAttribute('LinkModelField'), 'Missing link model attribute.');
    }

    /**
     * Test if a given attribute get used for construction of
     * the new Model object.
     *
     * @return void
     */
    public function testAttributeCanBeUsedForClassConstruction() {
        $xml = '<Opus><Opus_Model_ModelAbstract Cons="ByConstructorCall"/></Opus>';
        $omx = new Opus_Model_Xml;
        $omx->setConstructionAttributesMap(array('Opus_Model_ModelAbstract' => array('Cons')));
        $omx->setXml($xml);
        $model = $omx->getModel();
        $this->assertEquals('ByConstructorCall', $model->cons, 'Value has not been set by constructor call.');
    }

    /**
     * Test if null can be passed as default value to a constructor instead of
     * querying an XML attribute.
     *
     * @return void
     */
    public function testNullCanBeUsedAsConstructionAttributeDefault() {
        $xml = '<Opus><Opus_Model_ModelAbstract Cons="ByConstructorCall"/></Opus>';
        $omx = new Opus_Model_Xml;
        $omx->setConstructionAttributesMap(array('Opus_Model_ModelAbstract' => array(null)));
        $omx->setXml($xml);
        $model = $omx->getModel();
        $this->assertNull($model->cons, 'Null has not been set by constructor call.');
    }

    /**
     * Test if an exception is thrown when one tries to deserialize invalid XML.
     *
     * @return void
     */
    public function testLoadInvalidXmlThrowsException() {
        $omx = new Opus_Model_Xml;
        $this->setExpectedException('Opus_Model_Exception');
        $omx->setXml('<Opus attr/>');
    }

    /**
     * Test if models with an empty value do not show in the XML.
     *
     * @return void
     */
    public function testEmptyModelsAreNotSerialized() {
        $model = new Opus_Model_ModelAbstract;
        $model->getField('Value')->setValueModelClass('something');
        $model->setValue(null);

        $xml = new Opus_Model_Xml;
        $xml->setModel($model)
            ->excludeEmptyFields();
        $dom = $xml->getDomDocument();

        // assert that testField is not there
        $value = $dom->getElementsByTagName('Value');
        $this->assertEquals(0, $value->length, 'Models with empty values should not be shown.');
    }

    /**
     * Test if referenced submodel is serialized to an XML child element having
     * an attribute for each field of the submodel.
     *
     * @return void
     */
    public function testReferencedSubmodelIsRepresentedByXmlElement() {
        $model = new Opus_Model_ModelAbstract;
        $submodel = new Opus_Model_ModelAbstract;
        $submodel->addField(new Opus_Model_Field('CommodityField'));
        $submodel->setCommodityField('Value! There is a Value!');
        $model->getField('Value')->setValueModelClass(get_class($submodel));
        $model->setValue($submodel);

        $xml = new Opus_Model_Xml;
        $xml->setModel($model);
        $dom = $xml->getDomDocument();

        // assert that there is a sub element of name Value
        $valueElement = $dom->getElementsByTagName('Value')->item(0);
        $this->assertNotNull($valueElement, 'Mapping of "Value" field failed.');

        // assert that the Value element has an attribute called "CommodityField"
        $this->assertTrue($valueElement->hasAttribute('CommodityField'), 'Submodel field mapping failed (no attribute found).');

        // assert that this attribute has a value
        $this->assertEquals($submodel->getCommodityField(),
            $valueElement->getAttribute('CommodityField'), 'Field value has not been mapped correctly.');
    }

    /**
     * Test if a linked Model is correctly mapped to an XML element.
     *
     * @return void
     */
    public function testLinkedModelIsRepresentedByXmlElement() {
        // set up a model with a linked Opus_Model_ModelAbstractDbMock
        // use linking via Opus_Model_ModelDependentLinkMock
        $model = new Opus_Model_ModelAbstract;
        $field = new Opus_Model_Field('LinkField');
        $field->setValueModelClass('Opus_Model_ModelAbstract');
        $model->addField($field);

        $linkedModel = new Opus_Model_ModelAbstract;
        $linkedModel->setValue('LinkedModelsValue');

        $link = new Opus_Model_ModelDependentLinkMock;
        $link->setModelClass(get_class($linkedModel));
        $link->setModel($linkedModel);

        $model->setLinkField($link);

        // generate XML
        $xml = new Opus_Model_Xml;
        $xml->setModel($model);
        $dom = $xml->getDomDocument();

        // assert that there is an element representing the LinkField
        $linkFieldElement = $dom->getElementsByTagName('LinkField')->item(0);
        $this->assertNotNull($linkFieldElement, 'Mapping of "LinkField" field failed.');

        // assert that the LinkField element has an attribute representing
        // the LinkModels field
        $this->assertTrue($linkFieldElement->hasAttribute('Value'),
            'Attribute for field of linked Model is missing.');

        // assert that the value of the attribute equals to the value
        // of the linked Models field "Value"
        $this->assertEquals($linkedModel->getValue(),
            $linkFieldElement->getAttribute('Value'), 'Field value has not been mapped correctly.');
    }

    /**
     * Test that only link fields are shown if a resource mapping is setted.
     *
     * @return void
     */
    public function testLinkModelFieldNotShown() {
        $model = new Opus_Model_ModelAbstract;
        $field = new Opus_Model_Field('LinkField');
        $field->setValueModelClass('Opus_Model_ModelAbstractDbMock');
        $model->addField($field);
        $link = new Opus_Model_ModelDependentLinkMock;
        $link->setModelClass('Opus_Model_ModelAbstractDbMock');
        $linkedModel = new Opus_Model_ModelAbstractDbMock;
        $linkedModel->addField(new Opus_Model_Field('Value'));
        $linkedModel->setValue('Foo');
        $link->setModel($linkedModel);
        $linkedField = new Opus_Model_Field('LinkFieldAttr');

        $link->addField($linkedField);
        $link->setLinkFieldAttr('Blubb');
        $model->setLinkField($link);

        // generate XML
        $xml = new Opus_Model_Xml;
        $xml->setModel($model);
        $xml->setResourceNameMap(array('Opus_Model_ModelAbstractDbMock' => 'dbmockresource'));
        $dom = $xml->getDomDocument();

        // assert that there is a LinkField element with an xlink:href attribute
        $elements = $dom->getElementsByTagName('LinkField');
        $this->assertEquals(1, $elements->length, 'Element for LinkField field is missing.');

        $linkField = $elements->item(0);
        $this->assertFalse($linkField->hasAttribute('Value'), 'Link Model field should not be available.');
    }

    /**
     * Test if updating with an attribute value works.
     *
     * @return void
     */
    public function testUpdateFromXmlAttributeValues() {

        $xmlData = '<Opus><Opus_Model_ModelAbstract Value="1123"/></Opus>';
        $omx = new Opus_Model_Xml;
        $model = new Opus_Model_ModelAbstract();
        $omx->setModel($model);
        $omx->updateFromXml($xmlData);
        $this->assertEquals(1123, $model->getValue());
    }

    /**
     * Test if updating dependent models works.
     *
     * @return void
     */
    public function testUpdateFromXmlWithDependentModel() {

        $xmlData = '<Opus><Opus_Model_ModelAbstract Value="1">';
        $xmlData .= '<ModelDependentMock FirstName="Chuck" LastName="Norris" />';
        $xmlData .= '</Opus_Model_ModelAbstract></Opus>';


        $dependentModel = new Opus_Model_ModelDependentMock();
        $dependentModel->addField(new Opus_Model_Field('FirstName'));
        $dependentModel->setFirstName('Elvis');
        $dependentModel->addField(new Opus_Model_Field('LastName'));
        $dependentModel->setLastName('Presley');

        $field = new Opus_Model_Field('ModelDependentMock');
        $field->setValueModelClass(get_class($dependentModel));
        $field->setValue($dependentModel);

        $model = new Opus_Model_ModelAbstract();
        $model->addField($field);

        $omx = new Opus_Model_Xml;
        $omx->setModel($model);
        $omx->updateFromXml($xmlData);

        $this->assertEquals(1, $model->getValue());
        $this->assertEquals('Chuck', $model->getModelDependentMock()->getFirstName());
        $this->assertEquals('Norris', $model->getModelDependentMock()->getLastName());
    }

    /**
     * Test if updating of a multiple dependent model works.
     *
     * @return void
     */
    public function testUpdateFromXmlWithDependentModels() {

        $xmlData = '<Opus><Opus_Model_ModelAbstract Value="1">';
        $xmlData .= '<ModelDependentMock FirstName="Chuck" LastName="Norris" />';
        $xmlData .= '<ModelDependentMock FirstName="Kleiner" LastName="Muck" />';
        $xmlData .= '</Opus_Model_ModelAbstract></Opus>';

        $dependentModel1 = new Opus_Model_ModelDependentMock();
        $dependentModel1->addField(new Opus_Model_Field('FirstName'));
        $dependentModel1->setFirstName('Elvis');
        $dependentModel1->addField(new Opus_Model_Field('LastName'));
        $dependentModel1->setLastName('Presley');

        $dependentModel2 = new Opus_Model_ModelDependentMock();
        $dependentModel2->addField(new Opus_Model_Field('FirstName'));
        $dependentModel2->setFirstName('Hans');
        $dependentModel2->addField(new Opus_Model_Field('LastName'));
        $dependentModel2->setLastName('Glueck');

        $field = new Opus_Model_Field('ModelDependentMock');
        $field->setValueModelClass(get_class($dependentModel1));
        $field->setMultiplicity(2);
        $field->setValue(array($dependentModel1, $dependentModel2));

        $model = new Opus_Model_ModelAbstract();
        $model->addField($field);

        $omx = new Opus_Model_Xml;
        $omx->setModel($model);
        $omx->updateFromXml($xmlData);

        $this->assertEquals(1, $model->getValue());
        $this->assertEquals('Chuck', $model->getModelDependentMock(0)->getFirstName());
        $this->assertEquals('Norris', $model->getModelDependentMock(0)->getLastName());
        $this->assertEquals('Kleiner', $model->getModelDependentMock(1)->getFirstName());
        $this->assertEquals('Muck', $model->getModelDependentMock(1)->getLastName());
    }

    /**
     * Test if creating a Model from XML document leads to correct Model values
     * for single value fields.
     *
     * @return void
     */
    public function testCreateModelFromXmlFillsCorrectAttributeValues() {
        $xmlData = '<Opus><Opus_Model_ModelAbstract Value="1123"/></Opus>';
        $omx = new Opus_Model_Xml;
        $omx->setXml($xmlData);
        $model = $omx->getModel();
        $this->assertEquals(1123, $model->getValue(), 'Created model has wrong attribute value.');
    }


    /**
     * Test if creating a Model from XML document leads to correct Model values
     * for submodel value fields.
     *
     * @return void
     */
    public function testCreateModelFromXmlAssignsCorrectSubmodels() {
        eval(
            'class testCreateModelFromXmlAssignsCorrectSubmodels extends Opus_Model_Abstract {
                protected function _init() {
                    $link = new Opus_Model_Field(\'Link\');
                    $link->setValueModelClass(\'Opus_Model_ModelAbstract\');
                    $this->addField($link);
                }
            }');

        $xmlData =
            '<Opus><testCreateModelFromXmlAssignsCorrectSubmodels>' .
            '<Link Value="4711" />' .
            '</testCreateModelFromXmlAssignsCorrectSubmodels></Opus>';

        $omx = new Opus_Model_Xml;
        $omx->setXml($xmlData);
        $model = $omx->getModel();

        $this->assertNotNull($model->getLink(), 'No linked model assigned.');
        $this->assertType('Opus_Model_ModelAbstract', $model->getLink(), 'Wrong model type.');
        $this->assertEquals(4711, $model->getLink()->getValue(), 'Sub model initialised incorrectly.');
    }

    /**
     * Test if a given XlinkResolver instance is called to resolve xlink attribute content.
     *
     * @return void
     */
    public function testCallToResolverWhenXlinkIsEncounteredForDeserializingModels() {
        $mockResolver = $this->getMock('Opus_Uri_Resolver', array('get'));
        $xmlData = '<Opus><Opus_Model_ModelAbstract xlink:href="www.example.org/item/12" /></Opus>';

        $mockResolver->expects($this->once())
            ->method('get')
            ->with($this->equalTo('www.example.org/item/12'))
            ->will($this->returnValue(new Opus_Model_ModelAbstract));

        $omx = new Opus_Model_Xml;
        $omx->setXlinkResolver($mockResolver)
            ->setXml($xmlData)
            ->getModel();
    }

    /**
     * Test if a given XlinkResolver instance is called to resolve xlink attribute content
     * in updateFromXml().
     *
     * @return void
     */
    public function testCallToResolverWhenXlinkIsEncounteredForUpdatingSubModels() {
        // Mock model class with external field
        eval(
            'class testCallToResolverWhenXlinkIsEncounteredForUpdatingModels extends Opus_Model_Abstract {
                protected function _init() {
                    $link = new Opus_Model_Field(\'Link\');
                    $link->setValueModelClass(\'Opus_Model_ModelAbstract\');
                    $this->addField($link);
                }
            }');
        $preModel = new testCallToResolverWhenXlinkIsEncounteredForUpdatingModels;
        $preModel->setLink(new Opus_Model_ModelAbstract);
        $preModel->getLink()->setValue('before');

        // XML for update
        $xmlData =
            '<Opus><testCallToResolverWhenXlinkIsEncounteredForUpdatingModels>' .
            '<Link xlink:href="www.example.org/mockitem" />' .
            '</testCallToResolverWhenXlinkIsEncounteredForUpdatingModels></Opus>';

        // Mock resolver and model
        $mockModel = new Opus_Model_ModelAbstract;
        $mockModel->setValue('after');

        $mockResolver = $this->getMock('Opus_Uri_Resolver', array('get'));
        $mockResolver->expects($this->once())
            ->method('get')
            ->with($this->equalTo('www.example.org/mockitem'))
            ->will($this->returnValue($mockModel));

        // perform update
        $omx = new Opus_Model_Xml;
        $omx->setXlinkResolver($mockResolver)
            ->setModel($preModel)
            ->updateFromXml($xmlData);

        // check updated model
        $this->assertEquals('after', $preModel->getLink()->getValue(), 'Sub model has not been updated correctly.');
    }

}
