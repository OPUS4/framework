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
 * @copyright   Copyright (c) 2008-2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * @category    Tests
 * @package     Opus\Model
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 */

namespace OpusTest\Model;

use Exception;
use Opus\Model\AbstractModel;
use Opus\Model\Filter;
use Opus\Model\ModelException;
use opusFilterTestMock;
use OpusTest\TestAsset\TestCase;

use function array_values;
use function class_exists;

/**
 * Test cases for class Opus\Model\Filter.
 *
 * @package Opus\Model
 * @category Tests
 * @group FilterTest
 */
class FilterTest extends TestCase
{
    /**
     * Holds model that gets filtered.
     *
     * @var AbstractModel
     */
    protected $model;

    /**
     * Holds filter instance wrapping the model in $model.
     *
     * @var Filter
     */
    protected $filter;

    public function setUp()
    {
        // TODO NAMESPACE is this good code? does it work?

        if (false === class_exists('opusFilterTestMock', false)) {
            eval('
                class opusFilterTestMock extends \Opus\Model\AbstractModel 
                {
                    protected $internalFields = array(\'InternalField\');
    
                    protected function init() {
                        $this->addField(new \Opus\Model\Field(\'InternalField\'));
                        $this->addField(new \Opus\Model\Field(\'Field1\'));
                        $this->addField(new \Opus\Model\Field(\'Field2\'));
                        $field = new \Opus\Model\Field(\'Field3\');
                        $field->setMultiplicity(3);
                        $this->addField($field);
                    }
                }
            ');
        }
        $this->model  = new opusFilterTestMock();
        $this->filter = new Filter();
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
     */
    public function testRetrieveNotBlacklistedField()
    {
        $field = $this->filter->getField('Field1');
        $this->assertNotNull($field, 'Field should be retrievable.');
    }

    /**
     * Test if a defined sorting order is ensured when retrieving field names.
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
     */
    public function testRetrieveBlacklistedFieldThrowsException()
    {
        $blacklist = ['Field2'];
        $this->filter->setBlacklist($blacklist);

        $this->setExpectedException(ModelException::class);
        $field = $this->filter->getField('Field2');
    }

    /**
     * Test if calling add<Fieldname>() on a non-blacklisted field is allowed.
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
     */
    public function testAddToBlacklistedFieldThrowsException()
    {
        $blacklist = ['Field2'];
        $this->filter->setBlacklist($blacklist);

        $this->setExpectedException(ModelException::class);
        $field = $this->filter->addField2();
    }

    /**
     * Test if calling get<Fieldname>() on a non-blacklisted field is allowed.
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
     */
    public function testGetToBlacklistedFieldThrowsException()
    {
        $blacklist = ['Field2'];
        $this->filter->setBlacklist($blacklist);

        $this->setExpectedException(ModelException::class);
        $field = $this->filter->getField2();
    }

    /**
     * Test if calling set<Fieldname>() on a non-blacklisted field is allowed.
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
     */
    public function testSetToBlacklistedFieldThrowsException()
    {
        $blacklist = ['Field2'];
        $this->filter->setBlacklist($blacklist);

        $this->setExpectedException(ModelException::class);
        $field = $this->filter->setField2('value');
    }

    /**
     * Test if toArray() call returnes properly filtered result.
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
