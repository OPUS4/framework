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
 * Implements API for accessing and controlling facet information on a single
 * field.
 *
 * @method string getName()
 * @method string getSort()
 * @method int getLimit()
 * @method int getMinCount()
 */
class Opus_Search_Facet_Field {

	protected $data = array(
		'name' => null,
		'sort' => null,
		'limit' => null,
		'mincount' => null
	);

	public function __construct( $fieldName ) {
		if ( !is_string( $fieldName ) || !( $fieldName = trim( $fieldName ) ) ) {
			throw new InvalidArgumentException( 'invalid facet field name' );
		}

		$this->data['name'] = $fieldName;
	}

	public static function create( $fieldName ) {
		return new static( $fieldName );
	}

	/**
	 * Sets limit on facet counter (-1 for disabling any limit).
	 *
	 * @param int $limit
	 * @return $this fluent interface
	 */
	public function setLimit( $limit ) {
		if ( !preg_match( '/^[+-]?\d+$/', trim( $limit ) ) ) {
			throw new InvalidArgumentException( 'invalid limit value' );
		}

		$this->data['limit'] = intval( $limit );

		return $this;
	}

	/**
	 * Sets minimum count required for obeying values in faceted search on
	 * field.
	 *
	 * @param int $minCount
	 * @return $this fluent interface
	 */
	public function setMinCount( $minCount ) {
		if ( !preg_match( '/^[+-]?\d+$/', trim( $minCount ) ) ) {
			throw new InvalidArgumentException( 'invalid minCount value' );
		}

		$this->data['mincount'] = intval( $minCount );

		return $this;
	}

	/**
	 * Selects sorting facet results by index or by count.
	 *
	 * @param bool $useIndex sort facet results by index (service specific) or by count values per result
	 * @return $this fluent interface
	 */
	public function setSort( $useIndex = true ) {
		if ( !is_bool( $useIndex ) && !preg_match( '/^(count|index)$/', $useIndex = strtolower( trim( $useIndex ) ) ) ) {
			throw new InvalidArgumentException( 'invalid sort direction value' );
		}

		if ( is_bool( $useIndex ) ) {
			$this->data['sort'] = $useIndex;
		} else {
			$this->data['sort'] = ( $useIndex === 'index' );
		}

		return $this;
	}

	public function get( $name, $default = null ) {
		if ( array_key_exists( $name, $this->data ) ) {
			return is_null( $this->data[$name] ) ? $default : $this->data[$name];
		}

		throw new RuntimeException( 'invalid request for unknown facet property' );
	}

	public function __get( $name ) {
		return $this->get( $name );
	}

	public function __isset( $name ) {
		return !is_null( $this->data[$name] );
	}

	public function __set( $name, $value ) {
		switch ( $name ) {
			case 'sort' : return $this->setSort( $value );
			case 'limit' : return $this->setLimit( $value );
			case 'mincount' : return $this->setMinCount( $value );
			default :
				throw new RuntimeException( 'invalid request for setting facet field property' );
		}
	}

	public function __call( $name, $args ) {
		switch ( substr( $name, 0, 3 ) ) {
			case 'get' :
				$propertyName = strtolower( substr( $name, 3 ) );
				return $this->{$propertyName};

			default :
				throw new RuntimeException( 'invalid call for method ' . $name );
		}
	}
}
