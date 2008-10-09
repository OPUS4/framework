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
 * @package     Opus_Form
 * @author      Ralf Claußnitzer <ralf.claussnitzer@slub-dresden.de>
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Test cases for class Opus_Form_Layout.
 *
 * @category Tests
 * @package  Opus_Form
 *
 * @group    LayoutTest
 */
class Opus_Form_LayoutTest extends PHPUnit_Framework_TestCase {

    /**
     * Test if there are for sure no pages defined in the layout after
     * creation.
     *
     * @return void
     */
    public function testNoPagesAfterCreation() {
        $layout = new Opus_Form_Layout();
        $result = $layout->getPages();
        $this->assertEquals(array(), $result, 'Expected an empty array.');
    }

    /**
     * Test if pages that where added can be retrieved back in the pages listing
     * in the same order they have been added.
     *
     * @return void
     */
    public function testAddingPagesInOrder() {
        $layout = new Opus_Form_Layout();
        $layout->addPage('MyPage_1');
        $layout->addPage('MyPage_2');
        $layout->addPage('MyPage_3');

        $pages = $layout->getPages();
        $this->assertEquals(3, count($pages), 'Too few pages returned.');

        $this->assertEquals($pages[0], 'MyPage_1', 'Wrong page name for first entry.');
        $this->assertEquals($pages[1], 'MyPage_2', 'Wrong page name for second entry.');
        $this->assertEquals($pages[2], 'MyPage_3', 'Wrong page name for third entry.');
    }

    /**
     * Test if adding the same page caption twice raises an exception.
     *
     * @return void
     */
    public function testAddingPageTwiceThrowsException() {
        $this->setExpectedException('Opus_Form_Exception');
        $layout = new Opus_Form_Layout();
        $layout->addPage('MyPage');
        $layout->addPage('MyPage');
    }

    /**
     * Test if an InvalidArgumentException is thrown when attempt to
     * add a page without caption.
     *
     * @return void
     */
    public function testAddingPageWithoutCaption() {
        $this->setExpectedException('InvalidArgumentException');
        $layout = new Opus_Form_Layout();
        $layout->addPage('');
    }

    /**
     * Test if an InvalidArgumentException is thrown when attempt to
     * add a group without caption.
     *
     * @return void
     */
    public function testAddingGroupWithoutCaption() {
        $this->setExpectedException('InvalidArgumentException');
        $layout = new Opus_Form_Layout();
        $layout->addPage('MyPage');
        $layout->addGroup('', 'MyPage');
    }


    /**
     * Test if adding methods provide a fluent interface.
     *
     * @return void
     */
    public function testCallFluentInterface() {
        try {
            $layout = new Opus_Form_Layout();
            $layout->addPage('MyPage')
                ->addGroup('MyGroup', 'MyPage')
                ->addField('MyField', 'MyGroup');
        } catch (Exception $ex) {
            $this->fail($ex->getMessage());
        }
    }


    /**
     * Test adding a group to a before added page.
     *
     * @return void
     */
    public function testAddGroupToPage() {
        $layout = new Opus_Form_Layout();
        $layout->addPage('MyPage');
        $layout->addGroup('MyGroup', 'MyPage');

        $elements = $layout->getPageElements('MyPage');
        $this->assertNotNull($elements, 'Null returned; expected array of page elements.');
        $this->assertArrayHasKey('MyGroup', $elements, 'Added group is not element of page.');
    }

    /**
     * Test if attempt to add a group to a non-existent page throws an
     * exception.
     *
     * @return void
     */
    public function testAddGroupToNonExistingPageThrowsException() {
        $this->setExpectedException('Opus_Form_Exception');
        $layout = new Opus_Form_Layout();
        $layout->addPage('MyPage')->addGroup('MyGroup', 'my_page');
    }

    /**
     * Test if calling getPageElements() with non-existent page raises an
     * exception.
     *
     * @return void
     *
     */
    public function testGetElementsFromNonExistingPageThrowsException() {
        $this->setExpectedException('Opus_Form_Exception');
        $layout = new Opus_Form_Layout();
        $layout->addPage('MyPage')
            ->addGroup('MyGroup', 'MyPage');

        $layout->getPageElements('my_page');
    }

    /**
     * Test if calling getPageElements() with no page raises an
     * exception.
     *
     * @return void
     *
     */
    public function testGetElementsWithNoPageThrowsException() {
        $this->setExpectedException('InvalidArgumentException');
        $layout = new Opus_Form_Layout();
        $layout->getPageElements('');
    }

    /**
     * Test adding a field.
     *
     * @return void
     */
    public function testAddFieldToPage() {
        $layout = new Opus_Form_Layout();
        $layout->addPage('MyPage')
            ->addField('MyField', 'MyPage');
        $elements = $layout->getPageElements('MyPage');
        $this->assertNotNull($elements, 'Result should not be null.');
        $this->assertContains('MyField',$elements, 'Field has not been added.');
    }

    /**
     * Test adding a field.
     *
     * @return void
     */
    public function testAddFieldToGroup() {
        $layout = new Opus_Form_Layout();
        $layout->addPage('MyPage')
            ->addGroup('MyGroup', 'MyPage')
            ->addField('MyField', 'MyGroup');
        $elements = $layout->getPageElements('MyPage');
        $this->assertNotNull($elements, 'Result should not be null.');
        $this->assertContains('MyField',$elements['MyGroup'], 'Field has not been added.');
    }

    /**
     * Test adding a field by using a path expression for the target parameter.
     *
     * @return void
     */
    public function testAddFieldToGroupByPathExpression() {
        $layout = new Opus_Form_Layout();
        $layout->addPage('MyPage')
            ->addGroup('MyGroup', 'MyPage')
            ->addField('MyField', 'MyPage.MyGroup');
        $elements = $layout->getPageElements('MyPage');
        $this->assertNotNull($elements, 'Result should not be null.');
        $this->assertContains('MyField',$elements['MyGroup'], 'Field has not been added.');
    }
    
    
    /**
     * Test adding a field to a group or page that not exist.
     *
     * @return void
     */
    public function testAddFieldToUnknownGroupThrowsException() {
        $this->setExpectedException('Opus_Form_Exception');
        $layout = new Opus_Form_Layout();
        $layout->addPage('MyPage')
            ->addGroup('MyGroup', 'MyPage')
            ->addField('MyField', 'my_unknown_group');
    }

    /**
     * Test if adding a field without giving a caption raises an
     * InvalidArgumentException.
     *
     * @return void
     */
    public function testAddFieldWithoutCaptionThrowsException() {
        $this->setExpectedException('InvalidArgumentException');
        $layout = new Opus_Form_Layout();
        $layout->addField('', '');
    }

    /**
     * Test initializing the layout by XML document.
     *
     * @return void
     */
    public function testLoadXML() {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <formlayout name="general" xmlns="http://schemas.opus.org/formlayout"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                <page name="publish">
                    <field name="document_type" />
                    <field name="licences_id" />
                    <field name="language" />
                </page>
            </formlayout>';
        $layout = Opus_Form_Layout::fromXml($xml);

        // Check layout instance
        $this->assertNotNull($layout, 'No instance returned.');
        $this->assertTrue($layout instanceof Opus_Form_Layout, 'Returned object is not of type Opus_Form_Layout.');

        // Check parsed page specificatio
        $pages = $layout->getPages();
        $this->assertContains('publish', $pages, 'Page specification is not present.');

        // Check page elements
        $elements = $layout->getPageElements('publish');
        $this->assertFalse(empty($elements), 'No page elements returned.');
        $this->assertEquals(3, count($elements), 'Too few elements returned.');
        $this->assertContains('document_type', $elements, 'Missing first field.');
        $this->assertContains('licences_id', $elements, 'Missing second field.');
        $this->assertContains('language', $elements, 'Missing third field.');
    }

    /**
     * Test loading invalid XML document throws exception.
     *
     * @return void
     */
    public function testLoadInvalidXmlThrowsException() {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <formlayout name="general" xmlns="http://schemas.opus.org/formlayout"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                <page name="publish">
                    <invalid-tag/>
                </page>
            </formlayout>';
        $this->setExpectedException('Opus_Form_Exception');
        $layout = Opus_Form_Layout::fromXml($xml);
    }

    /**
     * Test loading and validating an XML file source.
     *
     * @return void
     */
    public function testLoadXmlFromFile() {
        $path = dirname(__FILE__) . '/LayoutTest.xml';
        $layout = Opus_Form_Layout::fromXml($path);

        $this->__assertLayoutXmlPages($layout);
    }

    /**
     * Test if attempt to read from an non-existing file throws an exception.
     *
     * @return void
     */
    public function testLoadXmlFromInvalidFilenameThrowsException() {
        $this->setExpectedException('InvalidArgumentException');
        $path = dirname(__FILE__) . '/WRONG.xml';
        $layout = Opus_Form_Layout::fromXml($path);
    }

    /**
     * Test if passing a DOMDocument instance works.
     *
     * @return void
     */
    public function testLoadXmlDomDocument() {
        $path = dirname(__FILE__) . '/LayoutTest.xml';
        $dom = new DOMDocument();
        $dom->load($path);
        $layout = Opus_Form_Layout::fromXml($dom);
        $this->__assertLayoutXmlPages($layout);
    }

    /**
     * Check some assertions related to test XML file.
     *
     * @param Opus_Form_Layout $layout Layout instance to check.
     * @return void
     */
    private function __assertLayoutXmlPages(Opus_Form_Layout $layout) {
        // Check if some pages where loaded.
        $pages = $layout->getPages();
        $this->assertEquals(4, count($pages), 'Too few pages returned.');

        // Check that every page contains some elements.
        foreach ($pages as $page) {
            $elements = $layout->getPageElements($page);
            $this->assertFalse(empty($elements), 'No page elements returned.');
        }
    }

    /**
     * Test if successfully creating a layout registers it in the Zend Registry.
     *
     * @return void
     */
    public function testLayoutGetsRegisteredInZendRegistry() {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <formlayout name="general" xmlns="http://schemas.opus.org/formlayout"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                <page name="publish">
                    <field name="document_type" />
                    <field name="licences_id" />
                    <field name="language" />
                </page>
            </formlayout>';

        // Clear out the registry.
        Zend_Registry::_unsetInstance();

        $layout = Opus_Form_Layout::fromXml($xml);

        // Check if the layout is registered.
        $registry = Zend_Registry::getInstance();
        $registered = $registry->get(Opus_Form_Layout::ZEND_REGISTRY_KEY);
        $this->assertArrayHasKey('general', $registered, 'Layout has not been registered.');
    }

    /**
     * Test if a layout specification gets overwritten when another one gets registered
     * under the same name.
     *
     * @return void
     */
    public function testLayoutOverrideInRegistry() {
        // Clear out the registry.
        Zend_Registry::_unsetInstance();

        // Register layouts
        $xml1 = '<?xml version="1.0" encoding="UTF-8"?>
            <formlayout name="general" xmlns="http://schemas.opus.org/formlayout"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                <page name="publish">
                    <field name="document_type" />
                    <field name="licences_id" />
                    <field name="language" />
                </page>
            </formlayout>';
        $layout1 = Opus_Form_Layout::fromXml($xml1);
        $xml2 = '<?xml version="1.0" encoding="UTF-8"?>
            <formlayout name="general" xmlns="http://schemas.opus.org/formlayout"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                <page name="publish">
                    <field name="document_type" />
                </page>
            </formlayout>';
        $layout2 = Opus_Form_Layout::fromXml($xml2);

        // Check if the layout2 is registered.
        $registry = Zend_Registry::getInstance();
        $registered = $registry->get(Opus_Form_Layout::ZEND_REGISTRY_KEY);
        $result = $registered['general'];
        $this->assertNotSame($layout1, $result, 'Second attempt to register layout did not override the old type.');
        $this->assertSame($layout2, $result, 'Second attempt to register layout did not override the old type.');
    }

    /**
     * Test if field elements belong to correct group.
     *
     * @return void
     */
    public function testFieldsBelongToCorrectGroup() {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <formlayout name="general" xmlns="http://schemas.opus.org/formlayout"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                <page name="author">
                    <group name="g1">
                        <field name="person_author" />
                        <field name="person_advisor" />
                    </group>
                </page>
                <page name="publisher">
                    <group name="g1">
                        <field name="publisher_name" />
                        <field name="publisher_university" />
                    </group>
                </page>
            </formlayout>';
        $layout = Opus_Form_Layout::fromXml($xml);
        $author = $layout->getPageElements('author');
        $publisher = $layout->getPageElements('publisher');
        $this->assertArrayHasKey('g1', $author, 'Group g1 is not element of author.');
        $this->assertArrayHasKey('g1', $publisher, 'Group g1 is not element of page author.');
        $g1_author = $author['g1'];
        $this->assertContains('person_author', $g1_author, 'Field person_author is not element of group g1 (page author).');
        $this->assertContains('person_advisor', $g1_author, 'Field person_advisor is not element of group g1 (page author).');
        $g1_publisher = $publisher['g1'];
        $this->assertContains('publisher_name', $g1_publisher, 'Field publisher_name is not element of group g1 (page publisher).');
        $this->assertContains('publisher_university', $g1_publisher, 'Field publisher_university is not element of group g1 (page publisher).');
    }
}