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


class Opus_Search_QueryTest extends TestCase {

	public function testInitiallyEmpty() {
		$query = new Opus_Search_Query();

		$this->assertFalse( isset( $query->start ) );
		$this->assertFalse( isset( $query->rows ) );
		$this->assertFalse( isset( $query->fields ) );
		$this->assertFalse( isset( $query->sort ) );
		$this->assertFalse( isset( $query->union ) );
	}

	public function testSupportingExplicitGetter() {
		$query = new Opus_Search_Query();

		$this->assertNull( $query->get( 'start' ) );
		$this->assertNull( $query->get( 'rows' ) );
		$this->assertNull( $query->get( 'fields' ) );
		$this->assertNull( $query->get( 'sort' ) );
		$this->assertNull( $query->get( 'union' ) );
	}

	public function testSupportingImplicitGetter() {
		$query = new Opus_Search_Query();

		$this->assertNull( $query->start );
		$this->assertNull( $query->rows );
		$this->assertNull( $query->fields );
		$this->assertNull( $query->sort );
		$this->assertNull( $query->union );
	}

	public function testSupportingGetterMethods() {
		$query = new Opus_Search_Query();

		$this->assertNull( $query->getStart() );
		$this->assertNull( $query->getRows() );
		$this->assertNull( $query->getFields() );
		$this->assertNull( $query->getSort() );
		$this->assertNull( $query->getUnion() );
	}

	/**
	 * @dataProvider provideValidScalarSettings
	 */
	public function testSupportingImplicitScalarSetterValid( $value, $property, $method, $expecting ) {
		$query = new Opus_Search_Query();
		$query->{$property} = $value;
		$this->assertEquals( $expecting, $query->{$property} );
	}

	/**
	 * @dataProvider provideValidScalarSettings
	 */
	public function testSupportingExplicitScalarSetterValid( $value, $property, $method, $expecting ) {
		$query = new Opus_Search_Query();
		$query->set( $property, $value );
		$this->assertEquals( $expecting, $query->get( $property ) );

		$query->set( $property, $value, false );
		$this->assertEquals( $expecting, $query->get( $property ) );
	}

	/**
	 * @dataProvider provideValidScalarSettings
	 */
	public function testSupportingScalarSetterMethodValid( $value, $property, $method, $expecting ) {
		$query = new Opus_Search_Query();
		$query->{$method}( $value );
		$this->assertEquals( $expecting, $query->get( $property ) );
	}

	/**
	 * @expectedException InvalidArgumentException
	 * @dataProvider provideValidScalarSettings
	 */
	public function testSupportingExplicitScalarSetterValidRejectToAdd( $value, $property, $method, $expecting ) {
		$query = new Opus_Search_Query();
		$query->set( $property, $value, true );
	}

	/**
	 * @expectedException InvalidArgumentException
	 * @dataProvider provideValidScalarSettings
	 */
	public function testSupportingScalarSetterMethodValidRejectToAdd( $value, $property, $method, $expecting ) {
		$method = preg_replace( '/^set/', 'add', $method );

		$query = new Opus_Search_Query();
		$query->{$method}( $value );
	}

	/**
	 * @expectedException InvalidArgumentException
	 * @dataProvider provideInvalidScalarSettings
	 */
	public function testSupportingImplicitScalarSetterInvalid( $value, $property, $method ) {
		$query = new Opus_Search_Query();
		$query->{$property} = $value;
	}

	/**
	 * @expectedException InvalidArgumentException
	 * @dataProvider provideInvalidScalarSettings
	 */
	public function testSupportingExplicitScalarSetterInvalid( $value, $property, $method ) {
		$query = new Opus_Search_Query();
		$query->{$property} = $value;
	}

	/**
	 * @expectedException InvalidArgumentException
	 * @dataProvider provideInvalidScalarSettings
	 */
	public function testSupportingScalarSetterMethodInvalid( $value, $property, $method ) {
		$query = new Opus_Search_Query();
		$query->{$method}( $value );
	}

	public function provideValidScalarSettings() {
		return array(
			array( 0, 'start', 'setStart', 0 ),
			array( 10, 'start', 'setStart', 10 ),
			array( 100, 'start', 'setStart', 100 ),
			array( 1000, 'start', 'setStart', 1000 ),
			array( 10000, 'start', 'setStart', 10000 ),
			array( 100000, 'start', 'setStart', 100000 ),
			array( 1000000, 'start', 'setStart', 1000000 ),
			array( 10000000, 'start', 'setStart', 10000000 ),
			array( 100000000, 'start', 'setStart', 100000000 ),
			array( 1000000000, 'start', 'setStart', 1000000000 ),

			array( 0, 'rows', 'setRows', 0 ),
			array( 10, 'rows', 'setRows', 10 ),
			array( 100, 'rows', 'setRows', 100 ),
			array( 1000, 'rows', 'setRows', 1000 ),
			array( 10000, 'rows', 'setRows', 10000 ),
			array( 100000, 'rows', 'setRows', 100000 ),
			array( 1000000, 'rows', 'setRows', 1000000 ),
			array( 10000000, 'rows', 'setRows', 10000000 ),
			array( 100000000, 'rows', 'setRows', 100000000 ),
			array( 1000000000, 'rows', 'setRows', 1000000000 ),

			array( true, 'union', 'setUnion', true ),
			array( 1, 'union', 'setUnion', true ),
			array( "yes", 'union', 'setUnion', true ),
			array( "no", 'union', 'setUnion', true ),
			array( array( 1 ), 'union', 'setUnion', true ),
			array( false, 'union', 'setUnion', false ),
			array( null, 'union', 'setUnion', false ),
			array( 0, 'union', 'setUnion', false ),
			array( "", 'union', 'setUnion', false ),
		);
	}

	public function provideInvalidScalarSettings() {
		return array(
			array( -10, 'start', 'setStart' ),
			array( 5.5, 'start', 'setStart' ),
			array( array( 10 ), 'start', 'setStart' ),
			array( array(), 'start', 'setStart' ),
			array( "test", 'start', 'setStart' ),
			array( array( 'test' => 10 ), 'start', 'setStart' ),
			array( (object) array( 'test' => 10 ), 'start', 'setStart' ),

			array( -10, 'rows', 'setRows' ),
			array( 5.5, 'rows', 'setRows' ),
			array( array( 10 ), 'rows', 'setRows' ),
			array( array(), 'rows', 'setRows' ),
			array( "test", 'rows', 'setRows' ),
			array( array( 'test' => 10 ), 'rows', 'setRows' ),
			array( (object) array( 'test' => 10 ), 'rows', 'setRows' ),
		);
	}

	/**
	 * @dataProvider provideValidFieldsSettings
	 */
	public function testSupportingImplicitFieldsSetterValid( $value, $property, $method, $expecting ) {
		$query = new Opus_Search_Query();
		$query->{$property} = $value;
		$this->assertEquals( $expecting, $query->{$property} );
	}

	/**
	 * @dataProvider provideValidFieldsSettings
	 */
	public function testSupportingExplicitFieldsSetterValid( $value, $property, $method, $expecting ) {
		$query = new Opus_Search_Query();
		$query->set( $property, $value );
		$this->assertEquals( $expecting, $query->get( $property ) );

		$query->set( $property, $value, false );
		$this->assertEquals( $expecting, $query->get( $property ) );
	}

	/**
	 * @dataProvider provideValidFieldsSettings
	 */
	public function testSupportingFieldsSetterMethodValid( $value, $property, $method, $expecting ) {
		$query = new Opus_Search_Query();
		$query->{$method}( $value );
		$this->assertEquals( $expecting, $query->get( $property ) );
	}

	/**
	 * @dataProvider provideValidFieldsSettings
	 */
	public function testSupportingExplicitFieldsSetterValidAdding( $value, $property, $method, $expecting ) {
		$query = new Opus_Search_Query();
		$query->set( $property, 'auto', false );
		$query->set( $property, $value, true );
		$this->assertEquals( array_merge( array( 'auto' ), $expecting ), $query->get( $property ) );
	}

	/**
	 * @dataProvider provideValidFieldsSettings
	 */
	public function testSupportingFieldsSetterMethodValidAdding( $value, $property, $method, $expecting ) {
		$adder = preg_replace( '/^set/', 'add', $method );

		$query = new Opus_Search_Query();
		$query->{$method}( 'auto' );
		$query->{$adder}( $value );
		$this->assertEquals( array_merge( array( 'auto' ), $expecting ), $query->get( $property ) );
	}

	/**
	 * @expectedException InvalidArgumentException
	 * @dataProvider provideInvalidFieldsSettings
	 */
	public function testSupportingImplicitFieldsSetterInvalid( $value, $property, $method ) {
		$query = new Opus_Search_Query();
		$query->{$property} = $value;
	}

	/**
	 * @expectedException InvalidArgumentException
	 * @dataProvider provideInvalidFieldsSettings
	 */
	public function testSupportingExplicitFieldsSetterInvalid( $value, $property, $method ) {
		$query = new Opus_Search_Query();
		$query->{$property} = $value;
	}

	/**
	 * @expectedException InvalidArgumentException
	 * @dataProvider provideInvalidFieldsSettings
	 */
	public function testSupportingFieldsSetterMethodInvalid( $value, $property, $method ) {
		$query = new Opus_Search_Query();
		$query->{$method}( $value );
	}

	public function provideValidFieldsSettings() {
		return array(
			array( '*', 'fields', 'setFields', array( '*' ) ),
			array( '*,*', 'fields', 'setFields', array( '*' ) ),
			array( 'a', 'fields', 'setFields', array( 'a' ) ),
			array( 'a,,', 'fields', 'setFields', array( 'a' ) ),
			array( ',,a', 'fields', 'setFields', array( 'a' ) ),
			array( 'a,b', 'fields', 'setFields', array( 'a', 'b' ) ),
			array( 'a,,b', 'fields', 'setFields', array( 'a', 'b' ) ),
			array( 'a,a', 'fields', 'setFields', array( 'a' ) ),
			array( 'ab', 'fields', 'setFields', array( 'ab' ) ),
			array( 'ab,cd', 'fields', 'setFields', array( 'ab', 'cd' ) ),
			array( 'abcdefghijklmnopqrstuvwxyzaaabacadaeafagahaiajakalamanaoapaqarasatauavawaxayaz', 'fields', 'setFields', array( 'abcdefghijklmnopqrstuvwxyzaaabacadaeafagahaiajakalamanaoapaqarasatauavawaxayaz' ) ),
			array( array( '*' ), 'fields', 'setFields', array( '*' ) ),
			array( array( '*,*' ), 'fields', 'setFields', array( '*' ) ),
			array( array( 'a' ), 'fields', 'setFields', array( 'a' ) ),
			array( array( 'a', 'b' ), 'fields', 'setFields', array( 'a', 'b' ) ),
			array( array( 'a', 'a' ), 'fields', 'setFields', array( 'a' ) ),
			array( array( 'ab,cd', 'ef' ), 'fields', 'setFields', array( 'ab', 'cd', 'ef' ) ),
			array( array( ',,ab,,cd,,', 'ef' ), 'fields', 'setFields', array( 'ab', 'cd', 'ef' ) ),
		);
	}

	public function provideInvalidFieldsSettings() {
		return array(
			array( '', 'fields', 'setFields' ),
			array( ',', 'fields', 'setFields' ),
			array( true, 'fields', 'setFields' ),
			array( false, 'fields', 'setFields' ),
			array( array(), 'fields', 'setFields' ),
			array( array( '' ), 'fields', 'setFields' ),
			array( array( array() ), 'fields', 'setFields' ),
			array( array( array( 'a' ) ), 'fields', 'setFields' ),
			array( array( 'a', array() ), 'fields', 'setFields' ),
			array( array( 'a', array( 'b' ) ), 'fields', 'setFields' ),
			array( null, 'fields', 'setFields' ),
			array( array( null ), 'fields', 'setFields' ),
			array( array( array( null ) ), 'fields', 'setFields' ),
			array( array( null, array( null ) ), 'fields', 'setFields' ),
			array( array( 'a', null ), 'fields', 'setFields' ),
		);
	}

	/**
	 * @dataProvider provideValidSortSettings
	 */
	public function testSupportingImplicitSortSetterValid( $value, $property, $method, $expecting ) {
		$query = new Opus_Search_Query();
		$query->{$property} = $value;
		$this->assertEquals( $expecting, $query->{$property} );
	}

	/**
	 * @dataProvider provideValidSortSettings
	 */
	public function testSupportingExplicitSortSetterValid( $value, $property, $method, $expecting ) {
		$query = new Opus_Search_Query();
		$query->set( $property, $value );
		$this->assertEquals( $expecting, $query->get( $property ) );

		$query->set( $property, $value, false );
		$this->assertEquals( $expecting, $query->get( $property ) );
	}

	/**
	 * @dataProvider provideValidSortSettings
	 */
	public function testSupportingSortSetterMethodValid( $value, $property, $method, $expecting ) {
		$query = new Opus_Search_Query();
		$query->{$method}( $value );
		$this->assertEquals( $expecting, $query->get( $property ) );
	}

	/**
	 * @dataProvider provideValidSortSettings
	 */
	public function testSupportingExplicitSortSetterValidAdding( $value, $property, $method, $expecting ) {
		$query = new Opus_Search_Query();
		$query->set( $property, 'auto', false );
		$query->set( $property, $value, true );
		$this->assertEquals( array_merge( array( 'auto' => 'asc' ), $expecting ), $query->get( $property ) );
	}

	/**
	 * @dataProvider provideValidSortSettings
	 */
	public function testSupportingSortSetterMethodValidAdding( $value, $property, $method, $expecting ) {
		$adder = preg_replace( '/^set/', 'add', $method );

		$query = new Opus_Search_Query();
		$query->{$method}( 'auto' );
		$query->{$adder}( $value );
		$this->assertEquals( array_merge( array( 'auto' => 'asc' ), $expecting ), $query->get( $property ) );
	}

	/**
	 * @expectedException InvalidArgumentException
	 * @dataProvider provideInvalidSortSettings
	 */
	public function testSupportingImplicitSortSetterInvalid( $value, $property, $method ) {
		$query = new Opus_Search_Query();
		$query->{$property} = $value;
	}

	/**
	 * @expectedException InvalidArgumentException
	 * @dataProvider provideInvalidSortSettings
	 */
	public function testSupportingExplicitSortSetterInvalid( $value, $property, $method ) {
		$query = new Opus_Search_Query();
		$query->{$property} = $value;
	}

	/**
	 * @expectedException InvalidArgumentException
	 * @dataProvider provideInvalidSortSettings
	 */
	public function testSupportingSortSetterMethodInvalid( $value, $property, $method ) {
		$query = new Opus_Search_Query();
		$query->{$method}( $value );
	}

	public function provideValidSortSettings() {
		return array(
			array( 'a', 'sort', 'setSort', array( 'a' => 'asc' ) ),
			array( 'a,b', 'sort', 'setSort', array( 'a' => 'asc', 'b' => 'asc' ) ),
			array( 'a,a', 'sort', 'setSort', array( 'a' => 'asc' ) ),
			array( 'a,b,a', 'sort', 'setSort', array( 'a' => 'asc', 'b' => 'asc' ) ),
			array( 'a,b,c', 'sort', 'setSort', array( 'a' => 'asc', 'b' => 'asc', 'c' => 'asc' ) ),
			array( array( 'a' ), 'sort', 'setSort', array( 'a' => 'asc' ) ),
			array( array( 'a', 'asc' ), 'sort', 'setSort', array( 'a' => 'asc' ) ),
			array( array( 'a', 'desc' ), 'sort', 'setSort', array( 'a' => 'desc' ) ),
			array( array( 'a', 'DeSc' ), 'sort', 'setSort', array( 'a' => 'desc' ) ),
			array( array( 'a', true ), 'sort', 'setSort', array( 'a' => 'asc' ) ),
			array( array( 'a', false ), 'sort', 'setSort', array( 'a' => 'desc' ) ),
			array( array( 'a,b' ), 'sort', 'setSort', array( 'a' => 'asc', 'b' => 'asc' ) ),
			array( array( 'a,b', true ), 'sort', 'setSort', array( 'a' => 'asc', 'b' => 'asc' ) ),
			array( array( 'a,b', false ), 'sort', 'setSort', array( 'a' => 'desc', 'b' => 'desc' ) ),
			array( array( array( 'a' ), false ), 'sort', 'setSort', array( 'a' => 'desc' ) ),
			array( array( array( 'a', 'b' ), false ), 'sort', 'setSort', array( 'a' => 'desc', 'b' => 'desc' ) ),
			array( array( array( 'a,b' ), false ), 'sort', 'setSort', array( 'a' => 'desc', 'b' => 'desc' ) ),
			array( array( array( 'a,b', 'c' ), false ), 'sort', 'setSort', array( 'a' => 'desc', 'b' => 'desc', 'c' => 'desc' ) ),
		);
	}

	public function provideInvalidSortSettings() {
		return array(
			array( '', 'sort', 'setSort' ),
			array( ',', 'sort', 'setSort' ),
			array( ' , ,, ', 'sort', 'setSort' ),
			array( '*', 'sort', 'setSort' ),
			array( '*,a', 'sort', 'setSort' ),
			array( 'a,*,b', 'sort', 'setSort' ),
			array( 'a,*', 'sort', 'setSort' ),
			array( null, 'sort', 'setSort' ),
			array( true, 'sort', 'setSort' ),
			array( false, 'sort', 'setSort' ),
			array( 1, 'sort', 'setSort' ),
			array( -5.5, 'sort', 'setSort' ),
			array( array(), 'sort', 'setSort' ),
			array( array( null ), 'sort', 'setSort' ),
			array( array( '*' ), 'sort', 'setSort' ),
			array( array( 'a', 'b' ), 'sort', 'setSort' ),
			array( array( array() ), 'sort', 'setSort' ),
			array( array( array( '*' ) ), 'sort', 'setSort' ),
			array( array( array( array( 'a,b' ) ) ), 'sort', 'setSort' ),
		);
	}

	/**
	 * @dataProvider provideValidAddSortSettings
	 */
	public function testSupportingAddingSortValid( $fields, $dir, $reset, $expected ) {
		$params = new Opus_Search_Query();
		$params->addSorting( 'auto' );
		$params->addSorting( $fields, $dir, $reset );

		$this->assertEquals( $expected, $params->sort );
	}

	/**
	 * @expectedException InvalidArgumentException
	 * @dataProvider provideInvalidAddSortSettings
	 */
	public function testSupportingAddingSortInvalid( $fields, $dir, $reset ) {
		$params = new Opus_Search_Query();
		$params->addSorting( 'auto' );
		$params->addSorting( $fields, $dir, $reset );
	}

	public function provideValidAddSortSettings() {
		return array(
			array( 'a', true, true, array( 'a' => 'asc' ) ),
			array( 'a', true, true, array( 'a' => 'asc' ) ),
			array( 'a', false, true, array( 'a' => 'desc' ) ),
			array( 'a,a', false, true, array( 'a' => 'desc' ) ),
			array( 'a', 'ASC', true, array( 'a' => 'asc' ) ),
			array( 'a', 'DesC', true, array( 'a' => 'desc' ) ),
			array( 'a', 'DesC', false, array( 'auto' => 'asc', 'a' => 'desc' ) ),
			array( 'a,a', 'DesC', false, array( 'auto' => 'asc', 'a' => 'desc' ) ),
			array( 'a,b', true, false, array( 'auto' => 'asc', 'a' => 'asc', 'b' => 'asc' ) ),
			array( 'a,b,a,b,b', true, false, array( 'auto' => 'asc', 'a' => 'asc', 'b' => 'asc' ) ),
			array( array( 'a,b', 'c' ), true, false, array( 'auto' => 'asc', 'a' => 'asc', 'b' => 'asc', 'c' => 'asc' ) ),
			array( array( 'a,b', 'a' ), true, false, array( 'auto' => 'asc', 'a' => 'asc', 'b' => 'asc' ) ),
		);
	}

	public function provideInvalidAddSortSettings() {
		return array(
			array( null, true, true ),
			array( null, true, false ),
			array( null, false, true ),
			array( null, false, false ),

			array( '', true, true ),
			array( '', true, false ),
			array( '', false, true ),
			array( '', false, false ),

			array( ',', true, true ),
			array( ' , ', true, false ),
			array( ' ,, , ', true, false ),
			array( ',', false, true ),
			array( ' , ', false, false ),
			array( ' ,, , ', false, false ),

			array( true, true, true ),
			array( true, true, false ),
			array( true, false, true ),
			array( true, false, false ),

			array( array(), true, true ),
			array( array(), true, false ),
			array( array(), false, true ),
			array( array(), false, false ),

			array( array( array() ), true, true ),
			array( array( array() ), true, false ),
			array( array( array() ), false, true ),
			array( array( array() ), false, false ),

			array( array( array( array( 'a' ) ) ), true, true ),
			array( array( array( array( 'a' ) ) ), true, false ),
			array( array( array( array( 'a' ) ) ), false, true ),
			array( array( array( array( 'a' ) ) ), false, false ),
		);
	}
}
