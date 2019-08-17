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
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @copyright   Copyright (c) 2008-2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Test cases for class Opus_Model_Filter.
 *
 * @package Opus_Model
 * @category Tests
 *
 * @group FilterTest
 */
class Opus_Model_FilterTest extends TestCase
{

    /**
     * Holds model that gets filtered.
     *
     * @var Opus_Model_Abstract
     */
    protected $model = null;

    /**
     * Holds filter instance wrapping the model in $model.
     *
     * @var Opus_Model_Filter
     */
    protected $filter = null;

    public function setUp()
    {
        if (false === class_exists('Opus_Model_FilterTest_Mock', false)) {
            $clazz =
            'class Opus_Model_FilterTest_Mock extends Opus_Model_Abstract {
                protected $_internalFields = array(\'InternalField\');

                protected function _init() {
                    $this->addField(new Opus_Model_Field(\'InternalField\'));
                    $this->addField(new Opus_Model_Field(\'Field1\'));
                    $this->addField(new Opus_Model_Field(\'Field2\'));
                    $field = new Opus_Model_Field(\'Field3\');
                    $field->setMultiplicity(3);
                    $this->addField($field);
                }
            }';
            eval($clazz);
        }
        $this->model = new Opus_Model_FilterTest_Mock;
        $this->filter = new Opus_Model_Filter();
        $this->filter->setModel($this->model);
    }

    /**
     * Overwrite parent methods.
     */
    public function tearDown()
    {
    }

    /**
     * Test if filter without blacklist returnes all fields.
     *
     * @return void
     */
    public function testFilterWithoutBlacklistReturnsAllFields()
    {
        $this->assertEquals(
            array_values($this->filter->describe()),
            array_values($this->model->describe()),
            'Filter fieldlist result differs from model fieldlist.'
        );
    }

    /**
     * Test if filter with blacklist returnes all visible fields.
     *
     * @return void
     */
    public function testFilterWithBlacklistReturnsAllAllowedFields()
    {
        $blacklist = ['Field2'];
        $this->filter->setBlacklist($blacklist);
        $this->assertNotContains(
            'Field2',
            $this->filter->describe(),
            'Filter fieldlist contains fields from blacklist.'
        );
    }

    /**
     * Test if a not-blacklistet field can be retrieved via getField().
     *
     * @return void
     */
    public function testRetrieveNotBlacklistedField()
    {
        $field = $this->filter->getField('Field1');
        $this->assertNotNull($field, 'Field should be retrievable.');
    }


    /**
     * Test if a defined sorting order is ensured when retrieving field names.
     *
     * @return void
     */
    public function testRetrieveAllFieldsInDefinedSortOrder()
    {
        $fieldlist = ['Field2', 'Field3', 'Field1'];
        $this->filter->setSortOrder($fieldlist);
        $this->assertEquals(
            $this->filter->describe(),
            $fieldlist,
            'Filter fieldlist result differs from sort order.'
        );
    }

    /**
     * Test if defining a partial sort order may contain
     * an fieldname that is not actual declared for the model.
     *
     * @return void
     */
    public function testSortOrderDefinitionCanContainUnknownField()
    {
        $this->filter->setSortOrder(['Field2', 'Field1', 'FooField']);
        $this->assertNotContains(
            'FooField',
            $this->filter->describe(),
            'Undefined field is listed.'
        );
        $this->assertEquals(
            $this->filter->describe(),
            ['Field2', 'Field1', 'Field3'],
            'Filter fieldlist result differs from sort order.'
        );
    }

    /**
     * Test if a not-blacklistet field cannot be retrieved via getField().
     *
     * @return void
     */
    public function testRetrieveBlacklistedFieldThrowsException()
    {
        $blacklist = ['Field2'];
        $this->filter->setBlacklist($blacklist);

        $this->setExpectedException('Opus_Model_Exception');
        $field = $this->filter->getField('Field2');
    }

    /**
     * Test if calling add<Fieldname>() on a non-blacklisted field is allowed.
     *
     * @return void
     */
    public function testAddCallToNotBlacklistedFieldNotThrowsAnException()
    {
        try {
            $this->filter->addField3('fsdfd');
        } catch (Exception $ex) {
            $this->fail('Add call on visible field should be permitted.');
        }
    }

    /**
     * Test if a not-blacklistet field cannot be modified via add<Fieldname>().
     *
     * @return void
     */
    public function testAddToBlacklistedFieldThrowsException()
    {
        $blacklist = ['Field2'];
        $this->filter->setBlacklist($blacklist);

        $this->setExpectedException('Opus_Model_Exception');
        $field = $this->filter->addField2();
    }


    /**
     * Test if calling get<Fieldname>() on a non-blacklisted field is allowed.
     *
     * @return void
     */
    public function testGetCallToNotBlacklistedFieldNotThrowsAnException()
    {
        try {
            $this->filter->getField1();
        } catch (Exception $ex) {
            $this->fail('Get call on visible field should be permitted.');
        }
    }

    /**
     * Test if a not-blacklistet field cannot be modified via get<Fieldname>().
     *
     * @return void
     */
    public function testGetToBlacklistedFieldThrowsException()
    {
        $blacklist = ['Field2'];
        $this->filter->setBlacklist($blacklist);

        $this->setExpectedException('Opus_Model_Exception');
        $field = $this->filter->getField2();
    }

    /**
     * Test if calling set<Fieldname>() on a non-blacklisted field is allowed.
     *
     * @return void
     */
    public function testSetCallToNotBlacklistedFieldNotThrowsAnException()
    {
        try {
            $this->filter->setField1('value');
        } catch (Exception $ex) {
            $this->fail('Set call on visible field should be permitted.');
        }
    }

    /**
     * Test if a not-blacklistet field cannot be modified via set<Fieldname>().
     *
     * @return void
     */
    public function testSetToBlacklistedFieldThrowsException()
    {
        $blacklist = ['Field2'];
        $this->filter->setBlacklist($blacklist);

        $this->setExpectedException('Opus_Model_Exception');
        $field = $this->filter->setField2('value');
    }

    /**
     * Test if toArray() call returnes properly filtered result.
     *
     * @return void
     */
    public function testToArrayReturnesFilteredResult()
    {
        $blacklist = ['Field2'];
        $this->filter
            ->setBlacklist($blacklist)
            ->setSortOrder(['Field3', 'Field2', 'Field1']);

        $this->assertEquals(
            ['Field3' => [], 'Field1' => null],
            $this->filter->toArray(),
            'Filter result is wrong for toArray().'
        );
    }
}
