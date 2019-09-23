<?php
/**
 * This file is part of OPUS. The software OPUS has been originally developed
 * at the University of Stuttgart with funding from the German Research Net,
 * the Federal Department of Higher Education and Research and the Ministry
 * of Science, Research and the Arts of the State of Baden-Wuerttemberg.
 *
 * OPUS 4 is a complete rewrite of the original OPUS software and was developed
 * by the Stuttgart University Library, the Library Service Center
 * Baden-Wuerttemberg, the North Rhine-Westphalian Library Service Center,
 * the Cooperative Library Network Berlin-Brandenburg, the Saarland University
 * and State Library, the Saxon State Library - Dresden State and University
 * Library, the Bielefeld University Library and the University Library of
 * Hamburg University of Technology with funding from the German Research
 * Foundation and the European Regional Development Fund.
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
 * @category    Application
 * @author      Thomas Urban <thomas.urban@cepharum.de>
 * @copyright   Copyright (c) 2009-2015, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */


class Opus_Search_QueryTest extends TestCase
{

    public function testInitiallyEmpty()
    {
        $query = new Opus_Search_Query();

        $this->assertFalse(isset($query->start));
        $this->assertFalse(isset($query->rows));
        $this->assertFalse(isset($query->fields));
        $this->assertFalse(isset($query->sort));
        $this->assertFalse(isset($query->union));
    }

    public function testSupportingExplicitGetter()
    {
        $query = new Opus_Search_Query();

        $this->assertNull($query->get('start'));
        $this->assertNull($query->get('rows'));
        $this->assertNull($query->get('fields'));
        $this->assertNull($query->get('sort'));
        $this->assertNull($query->get('union'));
    }

    public function testSupportingImplicitGetter()
    {
        $query = new Opus_Search_Query();

        $this->assertNull($query->start);
        $this->assertNull($query->rows);
        $this->assertNull($query->fields);
        $this->assertNull($query->sort);
        $this->assertNull($query->union);
    }

    public function testSupportingGetterMethods()
    {
        $query = new Opus_Search_Query();

        $this->assertNull($query->getStart());
        $this->assertNull($query->getRows());
        $this->assertNull($query->getFields());
        $this->assertNull($query->getSort());
        $this->assertNull($query->getUnion());
    }

    /**
     * @dataProvider provideValidScalarSettings
     */
    public function testSupportingImplicitScalarSetterValid($value, $property, $method, $expecting)
    {
        $query = new Opus_Search_Query();
        $query->{$property} = $value;
        $this->assertEquals($expecting, $query->{$property});
    }

    /**
     * @dataProvider provideValidScalarSettings
     */
    public function testSupportingExplicitScalarSetterValid($value, $property, $method, $expecting)
    {
        $query = new Opus_Search_Query();
        $query->set($property, $value);
        $this->assertEquals($expecting, $query->get($property));

        $query->set($property, $value, false);
        $this->assertEquals($expecting, $query->get($property));
    }

    /**
     * @dataProvider provideValidScalarSettings
     */
    public function testSupportingScalarSetterMethodValid($value, $property, $method, $expecting)
    {
        $query = new Opus_Search_Query();
        $query->{$method}( $value );
        $this->assertEquals($expecting, $query->get($property));
    }

    /**
     * @expectedException InvalidArgumentException
     * @dataProvider provideValidScalarSettings
     */
    public function testSupportingExplicitScalarSetterValidRejectToAdd($value, $property, $method, $expecting)
    {
        $query = new Opus_Search_Query();
        $query->set($property, $value, true);
    }

    /**
     * @expectedException InvalidArgumentException
     * @dataProvider provideValidScalarSettings
     */
    public function testSupportingScalarSetterMethodValidRejectToAdd($value, $property, $method, $expecting)
    {
        $method = preg_replace('/^set/', 'add', $method);

        $query = new Opus_Search_Query();
        $query->{$method}( $value );
    }

    /**
     * @expectedException InvalidArgumentException
     * @dataProvider provideInvalidScalarSettings
     */
    public function testSupportingImplicitScalarSetterInvalid($value, $property, $method)
    {
        $query = new Opus_Search_Query();
        $query->{$property} = $value;
    }

    /**
     * @expectedException InvalidArgumentException
     * @dataProvider provideInvalidScalarSettings
     */
    public function testSupportingExplicitScalarSetterInvalid($value, $property, $method)
    {
        $query = new Opus_Search_Query();
        $query->{$property} = $value;
    }

    /**
     * @expectedException InvalidArgumentException
     * @dataProvider provideInvalidScalarSettings
     */
    public function testSupportingScalarSetterMethodInvalid($value, $property, $method)
    {
        $query = new Opus_Search_Query();
        $query->{$method}( $value );
    }

    public function provideValidScalarSettings()
    {
        return [
            [ 0, 'start', 'setStart', 0 ],
            [ 10, 'start', 'setStart', 10 ],
            [ 100, 'start', 'setStart', 100 ],
            [ 1000, 'start', 'setStart', 1000 ],
            [ 10000, 'start', 'setStart', 10000 ],
            [ 100000, 'start', 'setStart', 100000 ],
            [ 1000000, 'start', 'setStart', 1000000 ],
            [ 10000000, 'start', 'setStart', 10000000 ],
            [ 100000000, 'start', 'setStart', 100000000 ],
            [ 1000000000, 'start', 'setStart', 1000000000 ],

            [ 0, 'rows', 'setRows', 0 ],
            [ 10, 'rows', 'setRows', 10 ],
            [ 100, 'rows', 'setRows', 100 ],
            [ 1000, 'rows', 'setRows', 1000 ],
            [ 10000, 'rows', 'setRows', 10000 ],
            [ 100000, 'rows', 'setRows', 100000 ],
            [ 1000000, 'rows', 'setRows', 1000000 ],
            [ 10000000, 'rows', 'setRows', 10000000 ],
            [ 100000000, 'rows', 'setRows', 100000000 ],
            [ 1000000000, 'rows', 'setRows', 1000000000 ],

            [ true, 'union', 'setUnion', true ],
            [ 1, 'union', 'setUnion', true ],
            [ "yes", 'union', 'setUnion', true ],
            [ "no", 'union', 'setUnion', true ],
            [ [ 1 ], 'union', 'setUnion', true ],
            [ false, 'union', 'setUnion', false ],
            [ null, 'union', 'setUnion', false ],
            [ 0, 'union', 'setUnion', false ],
            [ "", 'union', 'setUnion', false ],
        ];
    }

    public function provideInvalidScalarSettings()
    {
        return [
            [ -10, 'start', 'setStart' ],
            [ 5.5, 'start', 'setStart' ],
            [ [ 10 ], 'start', 'setStart' ],
            [ [], 'start', 'setStart' ],
            [ "test", 'start', 'setStart' ],
            [ [ 'test' => 10 ], 'start', 'setStart' ],
            [ (object) [ 'test' => 10 ], 'start', 'setStart' ],

            [ -10, 'rows', 'setRows' ],
            [ 5.5, 'rows', 'setRows' ],
            [ [ 10 ], 'rows', 'setRows' ],
            [ [], 'rows', 'setRows' ],
            [ "test", 'rows', 'setRows' ],
            [ [ 'test' => 10 ], 'rows', 'setRows' ],
            [ (object) [ 'test' => 10 ], 'rows', 'setRows' ],
        ];
    }

    /**
     * @dataProvider provideValidFieldsSettings
     */
    public function testSupportingImplicitFieldsSetterValid($value, $property, $method, $expecting)
    {
        $query = new Opus_Search_Query();
        $query->{$property} = $value;
        $this->assertEquals($expecting, $query->{$property});
    }

    /**
     * @dataProvider provideValidFieldsSettings
     */
    public function testSupportingExplicitFieldsSetterValid($value, $property, $method, $expecting)
    {
        $query = new Opus_Search_Query();
        $query->set($property, $value);
        $this->assertEquals($expecting, $query->get($property));

        $query->set($property, $value, false);
        $this->assertEquals($expecting, $query->get($property));
    }

    /**
     * @dataProvider provideValidFieldsSettings
     */
    public function testSupportingFieldsSetterMethodValid($value, $property, $method, $expecting)
    {
        $query = new Opus_Search_Query();
        $query->{$method}( $value );
        $this->assertEquals($expecting, $query->get($property));
    }

    /**
     * @dataProvider provideValidFieldsSettings
     */
    public function testSupportingExplicitFieldsSetterValidAdding($value, $property, $method, $expecting)
    {
        $query = new Opus_Search_Query();
        $query->set($property, 'auto', false);
        $query->set($property, $value, true);
        $this->assertEquals(array_merge([ 'auto' ], $expecting), $query->get($property));
    }

    /**
     * @dataProvider provideValidFieldsSettings
     */
    public function testSupportingFieldsSetterMethodValidAdding($value, $property, $method, $expecting)
    {
        $adder = preg_replace('/^set/', 'add', $method);

        $query = new Opus_Search_Query();
        $query->{$method}( 'auto' );
        $query->{$adder}( $value );
        $this->assertEquals(array_merge([ 'auto' ], $expecting), $query->get($property));
    }

    /**
     * @expectedException InvalidArgumentException
     * @dataProvider provideInvalidFieldsSettings
     */
    public function testSupportingImplicitFieldsSetterInvalid($value, $property, $method)
    {
        $query = new Opus_Search_Query();
        $query->{$property} = $value;
    }

    /**
     * @expectedException InvalidArgumentException
     * @dataProvider provideInvalidFieldsSettings
     */
    public function testSupportingExplicitFieldsSetterInvalid($value, $property, $method)
    {
        $query = new Opus_Search_Query();
        $query->{$property} = $value;
    }

    /**
     * @expectedException InvalidArgumentException
     * @dataProvider provideInvalidFieldsSettings
     */
    public function testSupportingFieldsSetterMethodInvalid($value, $property, $method)
    {
        $query = new Opus_Search_Query();
        $query->{$method}( $value );
    }

    public function provideValidFieldsSettings()
    {
        return [
            [ '*', 'fields', 'setFields', [ '*' ] ],
            [ '*,*', 'fields', 'setFields', [ '*' ] ],
            [ 'a', 'fields', 'setFields', [ 'a' ] ],
            [ 'a,,', 'fields', 'setFields', [ 'a' ] ],
            [ ',,a', 'fields', 'setFields', [ 'a' ] ],
            [ 'a,b', 'fields', 'setFields', [ 'a', 'b' ] ],
            [ 'a,,b', 'fields', 'setFields', [ 'a', 'b' ] ],
            [ 'a,a', 'fields', 'setFields', [ 'a' ] ],
            [ 'ab', 'fields', 'setFields', [ 'ab' ] ],
            [ 'ab,cd', 'fields', 'setFields', [ 'ab', 'cd' ] ],
            [ 'abcdefghijklmnopqrstuvwxyzaaabacadaeafagahaiajakalamanaoapaqarasatauavawaxayaz', 'fields', 'setFields', [ 'abcdefghijklmnopqrstuvwxyzaaabacadaeafagahaiajakalamanaoapaqarasatauavawaxayaz' ] ],
            [ [ '*' ], 'fields', 'setFields', [ '*' ] ],
            [ [ '*,*' ], 'fields', 'setFields', [ '*' ] ],
            [ [ 'a' ], 'fields', 'setFields', [ 'a' ] ],
            [ [ 'a', 'b' ], 'fields', 'setFields', [ 'a', 'b' ] ],
            [ [ 'a', 'a' ], 'fields', 'setFields', [ 'a' ] ],
            [ [ 'ab,cd', 'ef' ], 'fields', 'setFields', [ 'ab', 'cd', 'ef' ] ],
            [ [ ',,ab,,cd,,', 'ef' ], 'fields', 'setFields', [ 'ab', 'cd', 'ef' ] ],
        ];
    }

    public function provideInvalidFieldsSettings()
    {
        return [
            [ '', 'fields', 'setFields' ],
            [ ',', 'fields', 'setFields' ],
            [ true, 'fields', 'setFields' ],
            [ false, 'fields', 'setFields' ],
            [ [], 'fields', 'setFields' ],
            [ [ '' ], 'fields', 'setFields' ],
            [ [ [] ], 'fields', 'setFields' ],
            [ [ [ 'a' ] ], 'fields', 'setFields' ],
            [ [ 'a', [] ], 'fields', 'setFields' ],
            [ [ 'a', [ 'b' ] ], 'fields', 'setFields' ],
            [ null, 'fields', 'setFields' ],
            [ [ null ], 'fields', 'setFields' ],
            [ [ [ null ] ], 'fields', 'setFields' ],
            [ [ null, [ null ] ], 'fields', 'setFields' ],
            [ [ 'a', null ], 'fields', 'setFields' ],
        ];
    }

    /**
     * @dataProvider provideValidSortSettings
     */
    public function testSupportingImplicitSortSetterValid($value, $property, $method, $expecting)
    {
        $query = new Opus_Search_Query();
        $query->{$property} = $value;
        $this->assertEquals($expecting, $query->{$property});
    }

    /**
     * @dataProvider provideValidSortSettings
     */
    public function testSupportingExplicitSortSetterValid($value, $property, $method, $expecting)
    {
        $query = new Opus_Search_Query();
        $query->set($property, $value);
        $this->assertEquals($expecting, $query->get($property));

        $query->set($property, $value, false);
        $this->assertEquals($expecting, $query->get($property));
    }

    /**
     * @dataProvider provideValidSortSettings
     */
    public function testSupportingSortSetterMethodValid($value, $property, $method, $expecting)
    {
        $query = new Opus_Search_Query();
        $query->{$method}( $value );
        $this->assertEquals($expecting, $query->get($property));
    }

    /**
     * @dataProvider provideValidSortSettings
     */
    public function testSupportingExplicitSortSetterValidAdding($value, $property, $method, $expecting)
    {
        $query = new Opus_Search_Query();
        $query->set($property, 'auto', false);
        $query->set($property, $value, true);
        $this->assertEquals(array_merge([ 'auto' => 'asc' ], $expecting), $query->get($property));
    }

    /**
     * @dataProvider provideValidSortSettings
     */
    public function testSupportingSortSetterMethodValidAdding($value, $property, $method, $expecting)
    {
        $adder = preg_replace('/^set/', 'add', $method);

        $query = new Opus_Search_Query();
        $query->{$method}( 'auto' );
        $query->{$adder}( $value );
        $this->assertEquals(array_merge([ 'auto' => 'asc' ], $expecting), $query->get($property));
    }

    /**
     * @expectedException InvalidArgumentException
     * @dataProvider provideInvalidSortSettings
     */
    public function testSupportingImplicitSortSetterInvalid($value, $property, $method)
    {
        $query = new Opus_Search_Query();
        $query->{$property} = $value;
    }

    /**
     * @expectedException InvalidArgumentException
     * @dataProvider provideInvalidSortSettings
     */
    public function testSupportingExplicitSortSetterInvalid($value, $property, $method)
    {
        $query = new Opus_Search_Query();
        $query->{$property} = $value;
    }

    /**
     * @expectedException InvalidArgumentException
     * @dataProvider provideInvalidSortSettings
     */
    public function testSupportingSortSetterMethodInvalid($value, $property, $method)
    {
        $query = new Opus_Search_Query();
        $query->{$method}( $value );
    }

    public function provideValidSortSettings()
    {
        return [
            [ 'a', 'sort', 'setSort', [ 'a' => 'asc' ] ],
            [ 'a,b', 'sort', 'setSort', [ 'a' => 'asc', 'b' => 'asc' ] ],
            [ 'a,a', 'sort', 'setSort', [ 'a' => 'asc' ] ],
            [ 'a,b,a', 'sort', 'setSort', [ 'a' => 'asc', 'b' => 'asc' ] ],
            [ 'a,b,c', 'sort', 'setSort', [ 'a' => 'asc', 'b' => 'asc', 'c' => 'asc' ] ],
            [ [ 'a' ], 'sort', 'setSort', [ 'a' => 'asc' ] ],
            [ [ 'a', 'asc' ], 'sort', 'setSort', [ 'a' => 'asc' ] ],
            [ [ 'a', 'desc' ], 'sort', 'setSort', [ 'a' => 'desc' ] ],
            [ [ 'a', 'DeSc' ], 'sort', 'setSort', [ 'a' => 'desc' ] ],
            [ [ 'a', true ], 'sort', 'setSort', [ 'a' => 'asc' ] ],
            [ [ 'a', false ], 'sort', 'setSort', [ 'a' => 'desc' ] ],
            [ [ 'a,b' ], 'sort', 'setSort', [ 'a' => 'asc', 'b' => 'asc' ] ],
            [ [ 'a,b', true ], 'sort', 'setSort', [ 'a' => 'asc', 'b' => 'asc' ] ],
            [ [ 'a,b', false ], 'sort', 'setSort', [ 'a' => 'desc', 'b' => 'desc' ] ],
            [ [ [ 'a' ], false ], 'sort', 'setSort', [ 'a' => 'desc' ] ],
            [ [ [ 'a', 'b' ], false ], 'sort', 'setSort', [ 'a' => 'desc', 'b' => 'desc' ] ],
            [ [ [ 'a,b' ], false ], 'sort', 'setSort', [ 'a' => 'desc', 'b' => 'desc' ] ],
            [ [ [ 'a,b', 'c' ], false ], 'sort', 'setSort', [ 'a' => 'desc', 'b' => 'desc', 'c' => 'desc' ] ],
        ];
    }

    public function provideInvalidSortSettings()
    {
        return [
            [ '', 'sort', 'setSort' ],
            [ ',', 'sort', 'setSort' ],
            [ ' , ,, ', 'sort', 'setSort' ],
            [ '*', 'sort', 'setSort' ],
            [ '*,a', 'sort', 'setSort' ],
            [ 'a,*,b', 'sort', 'setSort' ],
            [ 'a,*', 'sort', 'setSort' ],
            [ null, 'sort', 'setSort' ],
            [ true, 'sort', 'setSort' ],
            [ false, 'sort', 'setSort' ],
            [ 1, 'sort', 'setSort' ],
            [ -5.5, 'sort', 'setSort' ],
            [ [], 'sort', 'setSort' ],
            [ [ null ], 'sort', 'setSort' ],
            [ [ '*' ], 'sort', 'setSort' ],
            [ [ 'a', 'b' ], 'sort', 'setSort' ],
            [ [ [] ], 'sort', 'setSort' ],
            [ [ [ '*' ] ], 'sort', 'setSort' ],
            [ [ [ [ 'a,b' ] ] ], 'sort', 'setSort' ],
        ];
    }

    /**
     * @dataProvider provideValidAddSortSettings
     */
    public function testSupportingAddingSortValid($fields, $dir, $reset, $expected)
    {
        $params = new Opus_Search_Query();
        $params->addSorting('auto');
        $params->addSorting($fields, $dir, $reset);

        $this->assertEquals($expected, $params->sort);
    }

    /**
     * @expectedException InvalidArgumentException
     * @dataProvider provideInvalidAddSortSettings
     */
    public function testSupportingAddingSortInvalid($fields, $dir, $reset)
    {
        $params = new Opus_Search_Query();
        $params->addSorting('auto');
        $params->addSorting($fields, $dir, $reset);
    }

    public function provideValidAddSortSettings()
    {
        return [
            [ 'a', true, true, [ 'a' => 'asc' ] ],
            [ 'a', true, true, [ 'a' => 'asc' ] ],
            [ 'a', false, true, [ 'a' => 'desc' ] ],
            [ 'a,a', false, true, [ 'a' => 'desc' ] ],
            [ 'a', 'ASC', true, [ 'a' => 'asc' ] ],
            [ 'a', 'DesC', true, [ 'a' => 'desc' ] ],
            [ 'a', 'DesC', false, [ 'auto' => 'asc', 'a' => 'desc' ] ],
            [ 'a,a', 'DesC', false, [ 'auto' => 'asc', 'a' => 'desc' ] ],
            [ 'a,b', true, false, [ 'auto' => 'asc', 'a' => 'asc', 'b' => 'asc' ] ],
            [ 'a,b,a,b,b', true, false, [ 'auto' => 'asc', 'a' => 'asc', 'b' => 'asc' ] ],
            [ [ 'a,b', 'c' ], true, false, [ 'auto' => 'asc', 'a' => 'asc', 'b' => 'asc', 'c' => 'asc' ] ],
            [ [ 'a,b', 'a' ], true, false, [ 'auto' => 'asc', 'a' => 'asc', 'b' => 'asc' ] ],
        ];
    }

    public function provideInvalidAddSortSettings()
    {
        return [
            [ null, true, true ],
            [ null, true, false ],
            [ null, false, true ],
            [ null, false, false ],

            [ '', true, true ],
            [ '', true, false ],
            [ '', false, true ],
            [ '', false, false ],

            [ ',', true, true ],
            [ ' , ', true, false ],
            [ ' ,, , ', true, false ],
            [ ',', false, true ],
            [ ' , ', false, false ],
            [ ' ,, , ', false, false ],

            [ true, true, true ],
            [ true, true, false ],
            [ true, false, true ],
            [ true, false, false ],

            [ [], true, true ],
            [ [], true, false ],
            [ [], false, true ],
            [ [], false, false ],

            [ [ [] ], true, true ],
            [ [ [] ], true, false ],
            [ [ [] ], false, true ],
            [ [ [] ], false, false ],

            [ [ [ [ 'a' ] ] ], true, true ],
            [ [ [ [ 'a' ] ] ], true, false ],
            [ [ [ [ 'a' ] ] ], false, true ],
            [ [ [ [ 'a' ] ] ], false, false ],
        ];
    }
}
