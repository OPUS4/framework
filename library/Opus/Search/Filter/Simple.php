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


/**
 * Describes simple binary term.
 *
 * This class is part of API used to describe query terms independently of any
 * actually used search engine.
 */

class Opus_Search_Filter_Simple implements Opus_Search_Filtering {

	const COMPARE_EQUALITY = '=';
	const COMPARE_INEQUALITY = '<>';
	const COMPARE_SIMILARITY = '~';
	const COMPARE_LESS = '<';
	const COMPARE_LESS_OR_EQUAL = '<=';
	const COMPARE_GREATER = '>';
	const COMPARE_GREATER_OR_EQUAL = '>=';

	protected $fieldName;

	protected $comparator;

	protected $fieldValues = array();


	/**
	 * @param string $fieldName name of field simple condition applies on
	 * @param mixed $comparator one out of Opus_Search_Filter_Simple::COMPARE_* constants
	 */
	public function __construct( $fieldName, $comparator ) {
		if ( !is_string( $fieldName ) || !$fieldName ) {
			throw new InvalidArgumentException( 'invalid field name' );
		}

		switch ( $comparator ) {
			case self::COMPARE_EQUALITY :
			case self::COMPARE_SIMILARITY :
			case self::COMPARE_LESS :
			case self::COMPARE_LESS_OR_EQUAL :
			case self::COMPARE_GREATER :
			case self::COMPARE_GREATER_OR_EQUAL :
				break;
			default :
				throw new InvalidArgumentException( 'invalid comparator' );
		}

		$this->fieldName  = $fieldName;
		$this->comparator = $comparator;
	}

	/**
	 * Creates new simple filter on selected field.
	 *
	 * @param string $field name of field filter is testing
	 * @param string $comparator comparison operator to use on testing field
	 * @return Opus_Search_Filter_Simple
	 */
	public static function createOnField( $field, $comparator ) {
		return new static( $field, $comparator );
	}

	/**
	 * Creates new simple filter for equality-matching any field.
	 *
	 * @note This filter is special and might not be supported by all adapters.
	 *
	 * @param string $value value to look up in any field of search engine
	 * @return Opus_Search_Filter_Simple
	 */
	public static function createCatchAll( $value ) {
		return static::createOnField( '*', self::COMPARE_EQUALITY )->addValue( $value );
	}

	/**
	 * Retrieves name of field simple comparison is performed on.
	 *
	 * @return string
	 */
	public function getName() {
		return $this->fieldName;
	}

	/**
	 * Retrieves operator of simple comparing operation.
	 *
	 * @return mixed one out of Opus_Search_Filter_Simple::COMPARE_* constants
	 */
	public function getComparator() {
		return $this->comparator;
	}

	/**
	 * Indicates if operand is a numeric range or not.
	 *
	 * @return bool
	 */
	public function isRangeValue() {
		return count( $this->fieldValues ) === 1 && is_array( @$this->fieldValues[0] );
	}

	/**
	 * Adds another value to operand.
	 *
	 * @note Adding multiple values is supported on simple conditions for
	 *       equality or inequality resulting in operations for field values
	 *       (not) contained in list of added values.
	 *
	 * @note Any recently set range is replaced by adding values.
	 *
	 * @param string $value
	 * @return $this fluent interface
	 */
	public function addValue( $value ) {
		if ( $this->isRangeValue() ) {
			$this->fieldValues = array();
		}

		if ( $this->comparator !== self::COMPARE_EQUALITY && $this->comparator !== self::COMPARE_INEQUALITY ) {
			if ( count( $this->fieldValues ) > 0 ) {
				throw new InvalidArgumentException( "invalid multi-value comparison" );
			}
		}

		$this->fieldValues[] = strval( $value );

		return $this;
	}

	/**
	 * Sets one or more values to compare actual values of field with.
	 *
	 * @param string|string[] $value
	 * @return $this fluent interface
	 */
	public function setValue( $value ) {
		if ( is_array( $value ) ) {
			$this->fieldValues = array_map( function( $i ) { return strval( $i ); }, $value );
		} else {
			$this->fieldValues = array( strval( $value ) );
		}

		return $this;
	}

	/**
	 * Retrieves value(s) to compare field values with.
	 *
	 * @return string[]
	 */
	public function getValues() {
		if ( !$this->isRangeValue() ) {
			return $this->fieldValues;
		}

		throw new InvalidArgumentException( 'range values must be requested differently' );
	}

	/**
	 * Declares range value (replacing any previously set value) to be used in
	 * comparing.
	 *
	 * @note Range values are supported on tests for equality or inequality,
	 *       only.
	 *
	 * @param int|null $lower optional lower (inclusive) end of range
	 * @param int|null $upper optional upper (inclusive) end of range
	 * @return $this fluent interface
	 */
	public function setRange( $lower, $upper ) {
		if ( $this->comparator !== self::COMPARE_EQUALITY && $this->comparator !== self::COMPARE_INEQUALITY ) {
			throw new InvalidArgumentException( "invalid range-value comparison" );
		}

		$this->fieldValues = array( array( $lower, $upper ) );

		return $this;
	}

	/**
	 * Retrieves some defined range value.
	 *
	 * @param int[] $default default value to use if current term doesn't contain range value
	 * @return int[] two-element array with lower/upper (inclusive) end of range (or null for open ranges at either end)
	 */
	public function getRangeValue( $default = null ) {
		if ( $this->isRangeValue() ) {
			return $this->fieldValues[0];
		}

		if ( !is_null( $default ) ) {
			return $default;
		}

		throw new RuntimeException( 'not a range value' );
	}
}
