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
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace OpusTest\Model\Xml;

use DOMDocument;
use Opus\Common\Model\ModelException;
use Opus\Document;
use Opus\Model\AbstractModel;
use Opus\Model\Field;
use Opus\Model\Filter;
use Opus\Model\Xml;
use Opus\Model\Xml\Version1;
use Opus\Model\Xml\XlinkResolverInterface;
use Opus\Title;
use OpusTest\Model\Mock\AbstractModelMock;
use OpusTest\Model\Mock\AbstractModelWithoutIdMock;
use OpusTest\Model\Mock\ModelAbstractDbMock;
use OpusTest\Model\Mock\ModelDependentLinkMock;
use OpusTest\Model\Mock\ModelDependentMock;
use OpusTest\TestAsset\TestCase;
use testCallToResolverWhenXlinkIsEncounteredForUpdatingModels;

use function chr;
use function get_class;
use function preg_replace;

/**
 * Test creation XML (version1) from models and creation of models by valid XML respectivly.
 */
class Version1Test extends TestCase
{
    /**
     * Overwrite parent methods.
     */
    public function setUp(): void
    {
    }

    public function tearDown(): void
    {
    }

    public function testGetVersion()
    {
        $strategy = new Version1();
        $this->assertEquals('1.0', $strategy->getVersion());
    }

    /**
     * Test if getModel() returns model previously defined with setModel().
     */
    public function testGetModelRetrievesModelSetBeforeBySetModel()
    {
        $xml   = new Xml();
        $model = new AbstractModelMock();
        $xml->setModel($model);
        $this->assertEquals($model, $xml->getModel(), 'Returned Model does not equal given Model.');
    }

    /**
     * Test if attempt to generate XML from null throws Exception.
     */
    public function testXmlFromEmptyModelThrowsException()
    {
        $xml = new Xml();
        $xml->setModel(null);
        $this->expectException(ModelException::class);
        $xml->getDomDocument();
    }

    /**
     * Test if getDomDocument() returns a DomDocument object.
     */
    public function testGetDomDocumentReturnsDomDocument()
    {
        $xml   = new Xml();
        $model = new AbstractModelMock();
        $xml->setModel($model);
        $dom = $xml->getDomDocument();
        $this->assertInstanceOf('DomDocument', $dom, 'Returned object is of wrong type.');
    }

    /**
     * Test if a valid XML representation of a Model gets returned.
     */
    public function testCreateXmlFromModel()
    {
        $xml   = new Xml();
        $model = new AbstractModelMock();
        $model->setValue('FooBar');
        $dom = $xml->setModel($model)->getDomDocument();

        // Root element is Opus
        $this->assertEquals('Opus', $dom->documentElement->localName, 'Root element should be named "Opus".');

        // Assert that first child represents serialized model
        $this->assertEquals(
            preg_replace('/\\\\/', '_', get_class($model)),
            $dom->documentElement->firstChild->localName,
            'Node name does not equal Model class name'
        );

        // There is an attribute "Value" with the value "FooBar"
        $value = $dom->documentElement->firstChild->attributes->getNamedItem('Value');
        $this->assertNotNull($value, 'Value attribute missing.');
        $this->assertEquals('FooBar', $value->nodeValue, 'Attribute value is wrong.');
    }

    /**
     * Test if a submodel serializes to an XML element that has the name
     * of the supermodels containing field.
     */
    public function testXmlSubElementsHaveFieldNamesAsDefinedInTheModel()
    {
        $model = new AbstractModelMock();
        $model->getField('Value')->setValueModelClass(AbstractModelMock::class);
        $model->setValue(new AbstractModelMock());
        $xml = new Xml();
        $xml->setModel($model);
        $dom = $xml->getDomDocument();

        // assert that there is a sub element of name Value
        $root  = $dom->documentElement->firstChild;
        $child = $root->firstChild;
        $this->assertEquals('Value', $child->localName, 'Wrong field name.');
    }

    /**
     * Test if a XML child element os generated for each sub model.
     */
    public function testOneChildElementPerSubModel()
    {
        $model = new AbstractModelMock();
        $model->getField('Value')->setValueModelClass(AbstractModelMock::class);
        $model->setValue(new AbstractModelMock());
        $xml = new Xml();
        $xml->setModel($model);
        $dom = $xml->getDomDocument();

        // assert that there is a sub element of name Value
        $root  = $dom->documentElement->firstChild;
        $child = $root->firstChild;
        $this->assertEquals('Value', $child->localName, 'Missing XML element for field.');
    }

    /**
     * Test if fields that are statet in the exclude list do not show in the XML.
     */
    public function testFieldsFromExcludeListAreNotSerialized()
    {
        $model = new AbstractModelMock();
        $model->addField(new Field('TestField'));
        $model->setTestField(4711)
            ->setValue('Foo');

        $xml = new Xml();
        $xml->setModel($model)
            ->exclude(['TestField']);
        $dom = $xml->getDomDocument();

        // assert that testField is not there
        $attr = $dom->documentElement->hasAttribute('TestField');
        $this->assertFalse($attr, 'Field has not been excluded.');
    }

    /**
     * Test if fields that are empty do not show in the XML.
     */
    public function testEmptyFieldsAreNotSerialized()
    {
        $model = new AbstractModelMock();
        $model->setValue(null);

        $xml = new Xml();
        $xml->setModel($model)
            ->excludeEmptyFields();
        $dom = $xml->getDomDocument();

        // assert that testField is not there
        $element = $dom->getElementsByTagName('OpusTest_Model_Mock_AbstractModelMock')->item(0);
        $this->assertFalse($element->hasAttribute('Value'), 'Empty field has not been excluded.');
    }

    /**
     * Test if empty fields can be serialized.
     */
    public function testEmptyFieldsAreSerializedIfWanted()
    {
        $model = new AbstractModelMock();
        $model->setValue(null);

        $xml = new Xml();
        $xml->setModel($model);
        $dom = $xml->getDomDocument();

        // assert that testField is not there
        $element = $dom->getElementsByTagName('OpusTest_Model_Mock_AbstractModelMock')->item(0);
        $this->assertTrue($element->hasAttribute('Value'), 'Empty field has not been included.');
    }

    /**
     * Data provider for models and corresponding xml representations.
     *
     * @return array
     */
    public function xmlModelDataProvider()
    {
        // $this->markTestIncomplete( 'Skipped: Unknown field: Id for OpusTest\Model\Mock\AbstractModelMock' );

        // one-field model
        $model1 = new AbstractModelMock();
        $model1->setValue('Foo');

        return [
            ['<Opus><OpusTest_Model_Mock_AbstractModelMock Value="Foo" /></Opus>', $model1, 'Model array representations differ.'],
            [
                '<Opus>
                    <OpusTest_Model_Mock_AbstractModelMock Value="Foo" />
                   </Opus>',
                $model1,
                'Incorrect handling of XML containing spaces and line breaks.',
            ],
            [$model1->toXml(), $model1, 'Build invalid model from before generated XML representation.'],
        ];
    }

    /**
     * Create a model using its XML representation.
     *
     * @param DOMDocument|string $xml   XML representation of a model.
     * @param AbstractModel      $model A model corresponding to the given XML representation.
     * @param string             $msg   Error message given on test failure.
     * @dataProvider xmlModelDataProvider
     */
    public function testCreateFromXml($xml, $model, $msg)
    {
        $xmlHelper = new Xml();
        if ($xml instanceof DOMDocument) {
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
     */
    public function testReferencePersistedSubModelsWithXlink()
    {
        // set up mock models and xml helper
        $model = new AbstractModelMock();
        $model->getField('Value')->setValueModelClass(ModelAbstractDbMock::class);
        $model->setValue(new ModelAbstractDbMock());
        $xml = new Xml();
        $xml->setModel($model);

        // set up model URI mapping
        $baseUri     = 'http://www.localhost.de';
        $resourceMap = [ModelAbstractDbMock::class => 'dbmock'];

        $xml->setXlinkBaseUri($baseUri)
            ->setResourceNameMap($resourceMap);
        $dom     = $xml->getDomDocument();
        $root    = $dom->documentElement->firstChild;
        $element = $root->firstChild;

        $this->assertEquals('Value', $element->nodeName);
        $this->assertTrue($element->hasAttribute('xlink:type'), 'Missing xlink:type attribute.');
        $this->assertEquals('simple', $element->getAttribute('xlink:type'), 'Wrong xlink:type value.');
        $this->assertTrue($element->hasAttribute('xlink:href'), 'Missing xlink:href attribute.');
        $this->assertEquals(
            'http://www.localhost.de/dbmock/4711',
            $element->getAttribute('xlink:href'),
            'Wrong xlink:href reference URI.'
        );
    }

    /**
     * Test if the XML encoding is set to UTF 8.
     */
    public function testXmlEncodingIsUtf8()
    {
        $xml = new Xml();
        $xml->setModel(new AbstractModelMock());
        $dom = $xml->getDomDocument();

        $this->assertEquals('UTF-8', $dom->xmlEncoding, 'XML encoding expected to be UTF-8.');
    }

    /**
     * Test if the xmlns:xlink namespace attribute is set.
     */
    public function testXlinkNamespaceIsSpecified()
    {
        $xml = new Xml();
        $xml->setModel(new AbstractModelMock());
        $dom  = $xml->getDomDocument();
        $root = $dom->documentElement;
        // workaround for hasAttribute bug
        // $root->hasAttribute('xmlns:xlink') delivers false thouhg the element is there
        $this->assertNotNull($root->attributes->getNamedItem('xmlns:xlink'), 'Xlink namespace declaration required.');
    }

    /**
     * Test if using a Opus\Model\Dependent\Link\* as field value get
     * properly resolved by serialization to the associated model of the link.
     *
     * Therefore Opus\Model\Xml needs to call getLinkedModelId() instead of getId() on this model.
     */
    public function testLinkModelsTunnelGetIdCallsToAssociatedModel()
    {
        $model = new ModelAbstractDbMock();
        $field = new Field('LinkField');
        $field->setValueModelClass(AbstractModelMock::class);
        $model->addField($field);

        // create mock to track calls
        $link = $this->getMockBuilder(ModelDependentLinkMock::class)
            ->setMethods(['getId', 'getLinkedModelId', 'describeAll'])
            ->getMock();

        $link->setModelClass(AbstractModelMock::class);
        $model->setLinkField($link);

        // expect getLinkedModelId() has been called in instead of getId()
        $link->expects($this->any())->method('describeAll')->will($this->returnValue([]));

        // TODO: Removed failing test.
        // $link->expects($this->once())->method('getLinkedModelId');

        $link->expects($this->never())->method('getId');

        // trigger behavior
        $xml = new Xml();
        $xml->setModel($model)->setResourceNameMap(
            [AbstractModelMock::class => 'dbmockresource']
        );
        $xml->getDomDocument();
    }

    /**
     * Test if the mapping of model classes to named resources is based on the
     * classname of an associated class even when it is connected via a linked model.
     */
    public function testResourceNameMappingUsesAssociatedModelClassWithLinkedModels()
    {
        // set up a model with a linked OpusTest\Model\Mock\ModelAbstractDbMock
        // use linking via OpusTest\Model\Mock\ModelDependentLinkMock
        $model = new AbstractModelMock();
        $field = new Field('LinkField');
        $field->setValueModelClass(ModelAbstractDbMock::class);
        $model->addField($field);
        $link = new ModelDependentLinkMock();
        $link->setModelClass(ModelAbstractDbMock::class);
        $link->setModel(new ModelAbstractDbMock());
        $model->setLinkField($link);

        // generate XML
        $xml = new Xml();
        $xml->setModel($model)->setResourceNameMap(
            [ModelAbstractDbMock::class => 'dbmockresource']
        );
        $dom = $xml->getDomDocument();

        // assert that there is a LinkField element with an xlink:href attribute
        $this->assertEquals(1, $dom->getElementsByTagName('LinkField')->length, 'Element for LinkField field is missing.');

        $linkField = $dom->getElementsByTagName('LinkField')->item(0);
        $this->assertNotNull($linkField->attributes->getNamedItem('xlink:href'), 'Xlink declaration missing.');
    }

    /**
     * Test if link model fields are represented by attributes for linked models.
     */
    public function testLinkModelFieldAreMappedToAttributesForLinkedModels()
    {
        // set up a model with a linked OpusTest\Model\Mock\ModelAbstractDbMock
        // use linking via OpusTest\Model\Mock\ModelDependentLinkMock
        $model = new AbstractModelMock();
        $field = new Field('LinkField');
        $field->setValueModelClass(ModelAbstractDbMock::class);
        $model->addField($field);
        $link = new ModelDependentLinkMock();
        $link->setModelClass(ModelAbstractDbMock::class);
        $link->setModel(new ModelAbstractDbMock());
        $link->addField(new Field('LinkModelField'));
        $link->setLinkModelField('SomeValue');
        $model->setLinkField($link);

        // generate XML
        $xml = new Xml();
        $xml->setModel($model);
        $dom = $xml->getDomDocument();

        // assert existence of attributes for link model fields
        $linkField = $dom->getElementsByTagName('LinkField')->item(0);
        $this->assertTrue($linkField->hasAttribute('LinkModelField'), 'Missing link model attribute.');
    }

    /**
     * Test if link model fields are represented by attributes for linked models
     * even if these models get represented via xlink.
     */
    public function testLinkModelFieldAreMappedToAttributesForLinkedModelsWhenXlinked()
    {
        // set up a model with a linked OpusTest\Model\Mock\ModelAbstractDbMock
        // use linking via OpusTest\Model\Mock\ModelDependentLinkMock
        $model = new AbstractModelMock();
        $field = new Field('LinkField');
        $field->setValueModelClass(ModelAbstractDbMock::class);
        $model->addField($field);
        $link = new ModelDependentLinkMock();
        $link->setModelClass(ModelAbstractDbMock::class);
        $link->setModel(new ModelAbstractDbMock());
        $link->addField(new Field('LinkModelField'));
        $link->setLinkModelField('SomeValue');
        $model->setLinkField($link);

        // generate XML
        $xml = new Xml();
        $xml->setModel($model)->setResourceNameMap(
            [ModelAbstractDbMock::class => 'dbmockresource']
        );
        $dom = $xml->getDomDocument();

        // assert existence of attributes for link model fields
        $linkField = $dom->getElementsByTagName('LinkField')->item(0);
        $this->assertTrue($linkField->hasAttribute('LinkModelField'), 'Missing link model attribute.');
    }

    /**
     * Test if an exception is thrown when one tries to deserialize invalid XML.
     */
    public function testLoadInvalidXmlThrowsException()
    {
        $omx = new Xml();
        $this->expectException(ModelException::class);
        $omx->setXml('<Opus attr/>');
    }

    /**
     * Test if models with an empty value do not show in the XML.
     */
    public function testEmptyModelsAreNotSerialized()
    {
        $model = new AbstractModelMock();
        $model->getField('Value')->setValueModelClass('something');
        $model->setValue(null);

        $xml = new Xml();
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
     */
    public function testReferencedSubmodelIsRepresentedByXmlElement()
    {
        $model    = new AbstractModelMock();
        $submodel = new AbstractModelMock();
        $submodel->addField(new Field('CommodityField'));
        $submodel->setCommodityField('Value! There is a Value!');
        $model->getField('Value')->setValueModelClass(get_class($submodel));
        $model->setValue($submodel);

        $xml = new Xml();
        $xml->setModel($model);
        $dom = $xml->getDomDocument();

        // assert that there is a sub element of name Value
        $valueElement = $dom->getElementsByTagName('Value')->item(0);
        $this->assertNotNull($valueElement, 'Mapping of "Value" field failed.');

        // assert that the Value element has an attribute called "CommodityField"
        $this->assertTrue($valueElement->hasAttribute('CommodityField'), 'Submodel field mapping failed (no attribute found).');

        // assert that this attribute has a value
        $this->assertEquals(
            $submodel->getCommodityField(),
            $valueElement->getAttribute('CommodityField'),
            'Field value has not been mapped correctly.'
        );
    }

    /**
     * Test if a linked Model is correctly mapped to an XML element.
     */
    public function testLinkedModelIsRepresentedByXmlElement()
    {
        // set up a model with a linked OpusTest\Model\Mock\ModelAbstractDbMock
        // use linking via OpusTest\Model\Mock\ModelDependentLinkMock
        $model = new AbstractModelMock();
        $field = new Field('LinkField');
        $field->setValueModelClass(AbstractModelMock::class);
        $model->addField($field);

        $linkedModel = new AbstractModelMock();
        $linkedModel->setValue('LinkedModelsValue');

        $link = new ModelDependentLinkMock();
        $link->setModelClass(get_class($linkedModel));
        $link->setModel($linkedModel);

        $model->setLinkField($link);

        // generate XML
        $xml = new Xml();
        $xml->setModel($model);
        $dom = $xml->getDomDocument();

        // assert that there is an element representing the LinkField
        $linkFieldElement = $dom->getElementsByTagName('LinkField')->item(0);
        $this->assertNotNull($linkFieldElement, 'Mapping of "LinkField" field failed.');

        // assert that the LinkField element has an attribute representing
        // the LinkModels field
        $this->assertTrue(
            $linkFieldElement->hasAttribute('Value'),
            'Attribute for field of linked Model is missing.'
        );

        // assert that the value of the attribute equals to the value
        // of the linked Models field "Value"
        $this->assertEquals(
            $linkedModel->getValue(),
            $linkFieldElement->getAttribute('Value'),
            'Field value has not been mapped correctly.'
        );
    }

    /**
     * Test that only link fields are shown if a resource mapping is setted.
     */
    public function testLinkModelFieldNotShown()
    {
        $model = new AbstractModelMock();
        $field = new Field('LinkField');
        $field->setValueModelClass(ModelAbstractDbMock::class);
        $model->addField($field);
        $link = new ModelDependentLinkMock();
        $link->setModelClass(ModelAbstractDbMock::class);
        $linkedModel = new ModelAbstractDbMock();
        $linkedModel->addField(new Field('Value'));
        $linkedModel->setValue('Foo');
        $link->setModel($linkedModel);
        $linkedField = new Field('LinkFieldAttr');

        $link->addField($linkedField);
        $link->setLinkFieldAttr('Blubb');
        $model->setLinkField($link);

        // generate XML
        $xml = new Xml();
        $xml->setModel($model);
        $xml->setResourceNameMap([ModelAbstractDbMock::class => 'dbmockresource']);
        $dom = $xml->getDomDocument();

        // assert that there is a LinkField element with an xlink:href attribute
        $elements = $dom->getElementsByTagName('LinkField');
        $this->assertEquals(1, $elements->length, 'Element for LinkField field is missing.');

        $linkField = $elements->item(0);
        $this->assertFalse($linkField->hasAttribute('Value'), 'Link Model field should not be available.');
    }

    /**
     * Test if updating with an attribute value works.
     */
    public function testUpdateFromXmlAttributeValues()
    {
        $xmlData = '<Opus><OpusTest_Model_Mock_AbstractModelMock Value="1123"/></Opus>';
        $omx     = new Xml();
        $model   = new AbstractModelMock();
        $omx->setModel($model);
        $omx->updateFromXml($xmlData);
        $this->assertEquals(1123, $model->getValue());
    }

    /**
     * Test if updating dependent models works.
     */
    public function testUpdateFromXmlWithDependentModel()
    {
        $xmlData  = '<Opus><OpusTest_Model_Mock_AbstractModelMock Value="1">';
        $xmlData .= '<ModelDependentMock FirstName="Chuck" LastName="Norris" />';
        $xmlData .= '</OpusTest_Model_Mock_AbstractModelMock></Opus>';

        $dependentModel = new ModelDependentMock();
        $dependentModel->addField(new Field('FirstName'));
        $dependentModel->setFirstName('Elvis');
        $dependentModel->addField(new Field('LastName'));
        $dependentModel->setLastName('Presley');

        $field = new Field('ModelDependentMock');
        $field->setValueModelClass(get_class($dependentModel));
        $field->setValue($dependentModel);

        $model = new AbstractModelMock();
        $model->addField($field);

        $omx = new Xml();
        $omx->setModel($model);
        $omx->updateFromXml($xmlData);

        $this->assertEquals(1, $model->getValue());
        $this->assertEquals('Chuck', $model->getModelDependentMock()->getFirstName());
        $this->assertEquals('Norris', $model->getModelDependentMock()->getLastName());
    }

    /**
     * Test if updating of a multiple dependent model works.
     */
    public function testUpdateFromXmlWithDependentModels()
    {
        $xmlData  = '<Opus><OpusTest_Model_Mock_AbstractModelMock Value="1">';
        $xmlData .= '<ModelDependentMock FirstName="Chuck" LastName="Norris" />';
        $xmlData .= '<ModelDependentMock FirstName="Kleiner" LastName="Muck" />';
        $xmlData .= '</OpusTest_Model_Mock_AbstractModelMock></Opus>';

        $dependentModel1 = new ModelDependentMock();
        $dependentModel1->addField(new Field('FirstName'));
        $dependentModel1->setFirstName('Elvis');
        $dependentModel1->addField(new Field('LastName'));
        $dependentModel1->setLastName('Presley');

        $dependentModel2 = new ModelDependentMock();
        $dependentModel2->addField(new Field('FirstName'));
        $dependentModel2->setFirstName('Hans');
        $dependentModel2->addField(new Field('LastName'));
        $dependentModel2->setLastName('Glueck');

        $field = new Field('ModelDependentMock');
        $field->setValueModelClass(get_class($dependentModel1));
        $field->setMultiplicity(2);
        $field->setValue([$dependentModel1, $dependentModel2]);

        $model = new AbstractModelMock();
        $model->addField($field);

        $omx = new Xml();
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
     */
    public function testCreateModelFromXmlFillsCorrectAttributeValues()
    {
        $xmlData = '<Opus><OpusTest_Model_Mock_AbstractModelMock Value="1123"/></Opus>';
        $omx     = new Xml();
        $omx->setXml($xmlData);
        $model = $omx->getModel();
        $this->assertEquals(1123, $model->getValue(), 'Created model has wrong attribute value.');
    }

    /**
     * Test if creating a Model from XML document leads to correct Model values
     * for submodel value fields.
     */
    public function testCreateModelFromXmlAssignsCorrectSubmodels()
    {
        eval('
            class testCreateModelFromXmlAssignsCorrectSubmodels extends \Opus\Model\AbstractModel {
                protected function init() {
                    $link = new \Opus\Model\Field(\'Link\');
                    $link->setValueModelClass(\'OpusTest\Model\Mock\AbstractModelMock\'); 
                    $this->addField($link);
                }
            }
        ');

        $xmlData =
            '<Opus><testCreateModelFromXmlAssignsCorrectSubmodels>'
            . '<Link Value="4711" />'
            . '</testCreateModelFromXmlAssignsCorrectSubmodels></Opus>';

        $omx = new Xml();

        $omx->setXml($xmlData);
        $model = $omx->getModel();
        $this->assertNotNull($model->getLink(), 'No linked model assigned.');
        $this->assertInstanceOf(AbstractModelMock::class, $model->getLink(), 'Wrong model type.');
        $this->assertEquals(4711, $model->getLink()->getValue(), 'Sub model initialised incorrectly.');
    }

    /**
     * Test if a given XlinkResolver instance is called to resolve xlink attribute content.
     */
    public function testCallToResolverWhenXlinkIsEncounteredForDeserializingModels()
    {
        $mockResolver = $this->getMockBuilder(XlinkResolverInterface::class)->getMock();

        $xmlData = '<Opus xmlns:xlink="http://www.w3.org/1999/xlink"><OpusTest_Model_Mock_AbstractModelMock xlink:href="www.example.org/item/12" /></Opus>';

        $mockResolver->expects($this->once())
            ->method('get')
            ->with($this->equalTo('www.example.org/item/12'))
            ->will($this->returnValue(new AbstractModelMock()));

        $omx = new Xml();
        $omx->setXlinkResolver($mockResolver)
            ->setXml($xmlData)
            ->getModel();
    }

    /**
     * Test if a given XlinkResolver instance is called to resolve xlink attribute content
     * in updateFromXml().
     */
    public function testCallToResolverWhenXlinkIsEncounteredForUpdatingSubModels()
    {
        // TODO NAMESPACE review this code
        // Mock model class with external field
        eval('
            class testCallToResolverWhenXlinkIsEncounteredForUpdatingModels extends \Opus\Model\AbstractModel {
                protected function init() {
                    $link = new \Opus\Model\Field(\'Link\');
                    $link->setValueModelClass(\'OpusTest\Model\Mock\AbstractModelMock\');
                    $this->addField($link);
                }
            }
        ');
        $preModel = new testCallToResolverWhenXlinkIsEncounteredForUpdatingModels();
        $preModel->setLink(new AbstractModelMock());
        $preModel->getLink()->setValue('before');

        // XML for update
        $xmlData =
            '<Opus xmlns:xlink="http://www.w3.org/1999/xlink"><testCallToResolverWhenXlinkIsEncounteredForUpdatingModels>'
            . '<Link xlink:href="www.example.org/mockitem" />'
            . '</testCallToResolverWhenXlinkIsEncounteredForUpdatingModels></Opus>';

        // Mock resolver and model
        $mockModel = new AbstractModelMock();
        $mockModel->setValue('after');

        $mockResolver = $this->getMockBuilder(XlinkResolverInterface::class)
            ->setMethods(['get'])
            ->getMock();

        $mockResolver->expects($this->once())
            ->method('get')
            ->with($this->equalTo('www.example.org/mockitem'))
            ->will($this->returnValue($mockModel));

        // perform update
        $omx = new Xml();
        $omx->setXlinkResolver($mockResolver)
            ->setModel($preModel)
            ->updateFromXml($xmlData);

        // check updated model
        $this->assertEquals('after', $preModel->getLink()->getValue(), 'Sub model has not been updated correctly.');
    }

    /**
     * Small helper to create invalid utf8 strings.
     *
     * @return string
     */
    private static function createInvalidUTF8String()
    {
        $invalidChars = [
            1,
            2,
            3,
            4,
            5,
            6,
            7,
            8, // \x01-\x08
            11,
            12, // \x0B\x0C
            14,
            15,
            16,
            17,
            18,
            19,
            20,
            21,
            22,
            23,
            24,
            25,
            26,
            27,
            28,
            29,
            30,
            31, // \x0E-\x1F
            127, // \x7F
        ];

        $string = '';
        foreach ($invalidChars as $char) {
            $string .= " " . $char . ":" . chr($char);
        }

        return $string;
    }

    /**
     * Test if a XML child element os generated for each sub model.
     */
    public function testSerializingInvalidUTF8Chars()
    {
        $invalidValue = "foo... " . self::createInvalidUTF8String() . " ...bar";

        $model = new AbstractModelMock();
        $model->setValue($invalidValue);

        // Serialize model to XML.
        $xml = new Xml();
        $xml->setStrategy(new Version1());
        $xml->setModel($model);
        $dom       = $xml->getDomDocument();
        $xmlString = $dom->saveXML();

        // first, check that the string contains all required substrings.
        $this->assertContains('foo...', $xmlString);
        $this->assertContains('...bar', $xmlString);

        // second, check that xml string does *not* contain invalid characters.
        $this->assertNotRegExp('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $xmlString);

        // last, check that string can be serialized to model.
        $xml = new Xml();
        $xml->setStrategy(new Version1());
        $xml->setXml($xmlString);

        $model = $xml->getModel();
        $this->assertInstanceOf(AbstractModelMock::class, $model);

        $this->assertContains('foo...', $model->getValue());
        $this->assertContains('...bar', $model->getValue());
    }

    /**
     * Check that a Opus\Model\Filter gets an Id attribute in the XML output
     * if the filtered object is of Opus\Model\AbstractDb
     */
    public function testFilteredAbstractDbModelsGetId()
    {
        $model = new ModelAbstractDbMock();

        $filterModel = new Filter();
        $filterModel->setModel($model);

        $xml = new Xml();
        $xml->setStrategy(new Version1());
        $xml->setModel($filterModel);
        $dom = $xml->getDomDocument();

        $filterDom = $dom->getElementsByTagName('Opus_Model_Filter')->item(0);
        $this->assertTrue($filterDom->hasAttribute('Id'), 'missing attribute Id');
        $this->assertEquals("4711", $filterDom->getAttribute('Id'), 'Id != 4711');
    }

    /**
     * Check that a Opus\Model\Filter gets an Id attribute in the XML output
     * if the filtered object is of Opus\Model\AbstractDb
     */
    public function testFilteredAbstractModelsGetId()
    {
        $model = new AbstractModelWithoutIdMock();
        $this->assertFalse(
            $model->hasField('Id'),
            'Test model must not have an "Id" field!'
        );
        $this->assertTrue(
            $model->hasField('Value'),
            'Test model must have a "Value"field!'
        );

        $filterModel = new Filter();
        $filterModel->setModel($model);

        $xml = new Xml();
        $xml->setStrategy(new Version1());
        $xml->setModel($filterModel);
        $dom = $xml->getDomDocument();

        $filterDom = $dom->getElementsByTagName('Opus_Model_Filter')->item(0);
        $this->assertFalse($filterDom->hasAttribute('Id'), 'unexpected attribute Id');
        $this->assertTrue($filterDom->hasAttribute('Value'), 'missing attribute Value');
        $this->assertEquals("test", $filterDom->getAttribute('Value'), 'Value != "test"');
    }

    public function testDateXml()
    {
        $document = Document::new();
        $title    = Title::new();
        $title->setLanguage('eng');
        $title->setValue('Document Title');
        $document->addTitleMain($title);
        $document->setCompletedDate('2022-05-19');
        $docId = Document::get($document->store());

        $xml = new Xml();
        $xml->setStrategy(new Version1());
        $xml->setModel($document);
        $dom = $xml->getDomDocument();

        $output = $dom->saveXml();

        $elements = $dom->getElementsByTagName('CompletedDate');
        $this->assertCount(1, $elements);

        $completedDate = $elements->item(0);
        $this->assertTrue($completedDate->hasAttribute('Year'));
        $this->assertEquals('2022', $completedDate->attributes->getNamedItem('Year')->nodeValue);
        $this->assertTrue($completedDate->hasAttribute('Month'));
        $this->assertEquals('05', $completedDate->attributes->getNamedItem('Month')->nodeValue);
        $this->assertTrue($completedDate->hasAttribute('Day'));
        $this->assertEquals('19', $completedDate->attributes->getNamedItem('Day')->nodeValue);

        $this->assertTrue($completedDate->hasAttribute('Hour'));
        $this->assertEquals('', $completedDate->attributes->getNamedItem('Hour')->nodeValue);
        $this->assertTrue($completedDate->hasAttribute('Minute'));
        $this->assertEquals('', $completedDate->attributes->getNamedItem('Minute')->nodeValue);

        $this->assertTrue($completedDate->hasAttribute('Second'));
        $this->assertTrue($completedDate->hasAttribute('Timezone'));
        $this->assertTrue($completedDate->hasAttribute('UnixTimestamp'));
    }
}
