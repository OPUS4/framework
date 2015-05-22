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
 * Implements normalized query parameter support.
 *
 * @method int getStart()
 * @method int getRows()
 * @method string[] getFields()
 * @method array getSort()
 * @method bool getUnion()
 * @method array getFilter()
 * @method void setStart( int $offset )
 * @method void setRows( int $count )
 * @method void setFields( $fields )
 * @method void setSort( $sorting )
 * @method void setUnion( bool $isUnion )
 * @method void addFields( string $fields )
 * @method void addSort( $sorting )
 */
class Opus_Search_Parameters {

	protected $_data;

	public function reset() {
		$this->_data = array(
			'start'  => null,
			'rows'   => null,
			'fields' => null,
			'sort'   => null,
			'union'  => null,
			'filter' => null,
		);
	}

	public function __construct() {
		$this->reset();
	}

	/**
	 * Tests if provided name is actually name of known parameter normalizing it
	 * on return.
	 *
	 * @throws InvalidArgumentException unless providing name of existing parameter
	 * @param string $name name of parameter to access
	 * @return string normalized name of existing parameter
	 */
	protected function isValidParameter( $name )  {
		if ( !array_key_exists( strtolower( trim( $name ) ), $this->_data ) ) {
			throw new InvalidArgumentException( 'invalid query parameter: ' . $name );
		}

		return strtolower( trim( $name ) );
	}

	/**
	 * Normalizes one or more field names or set of comma-separated field names
	 * into set of field names.
	 *
	 * @param string|string[] $input one or more field names or comma-separated lists of fields' names
	 * @return string[] list of field names
	 */
	protected function normalizeFields( $input )  {
		if ( !is_array( $input ) ) {
			$input = array( $input );
		}

		$output = array();

		foreach ( $input as $field ) {
			if ( !is_string( $field ) ) {
				throw new InvalidArgumentException( 'invalid type of field selector' );
			}

			$fieldNames = preg_split( '/(\s*,)+\s*/', trim( $field, " \r\n\t," ) );
			foreach ( $fieldNames as $name ) {
				if ( !preg_match( '/^(?:\*|[a-z0-9_]+)$/i', $name ) ) {
					throw new InvalidArgumentException( 'malformed field selector: ' . $name );
				}

				$output[] = $name;
			}
		}

		if ( !count( $input ) ) {
			throw new InvalidArgumentException( 'missing field selector' );
		}

		return $output;
	}

	protected function normalizeDirection( $ascending ) {
		if ( !strcasecmp( $ascending, 'asc' ) ) {
			$ascending = true;
		} else if ( !strcasecmp( $ascending, 'desc' ) ) {
			$ascending = false;
		} else if ( $ascending !== false && $ascending !== true ) {
			throw new InvalidArgumentException( 'invalid sorting direction selector' );
		}

		return $ascending;
	}

	/**
	 * Retrieves value of selected parameter.
	 *
	 * @param $property
	 * @return mixed
	 */
	public function get( $property ) {
		$property = $this->isValidParameter( $property );

		return $this->_data[$property];
	}

	public function set( $property, $value, $adding = false ) {
		$property = $this->isValidParameter( $property );

		switch ( $property ) {
			case 'start' :
			case 'rows' :
				if ( $adding ) {
					throw new InvalidArgumentException( 'invalid parameter access on ' . $property );
				}

				if ( !is_scalar( $value ) || !ctype_digit( trim( $value ) ) ) {
					throw new InvalidArgumentException( 'invalid parameter value on ' . $property );
				}

				$this->_data[$property] = intval( $value );
				break;

			case 'fields' :
				$fields = $this->normalizeFields( $value );

				if ( $adding && is_null( $this->_data['fields'] ) ) {
					$adding = false;
				}

				if ( $adding ) {
					$this->_data['fields'] = array_merge( $this->_data['fields'], $fields );
				} else {
					if ( !count( $fields ) ) {
						throw new InvalidArgumentException( 'setting empty set of fields rejected' );
					}

					$this->_data['fields'] = $fields;
				}

				$this->_data['fields'] = array_unique( $this->_data['fields'] );
				break;

			case 'sort' :
				if ( !is_array( $value ) ) {
					$value = array( $value, true );
				}

				switch ( count( $value ) ) {
					case 2 :
						$fields    = array_shift( $value );
						$ascending = array_shift( $value );
						break;
					case 1 :
						$fields    = array_shift( $value );
						$ascending = true;
						break;
					default :
						throw new InvalidArgumentException( 'invalid sorting selector' );
				}

				$fields    = $this->normalizeFields( $fields );
				$ascending = $this->normalizeDirection( $ascending );

				if ( $adding && is_null( $this->_data['sort'] ) ) {
					$adding = false;
				}

				if ( !$adding ) {
					$this->_data['sort'] = array();
				}

				foreach ( $fields as $field ) {
					if ( $field === '*' ) {
						throw new InvalidArgumentException( 'invalid request for sorting by all fields (*)' );
					}

					$this->_data['sort'][] = array( $field, $ascending ? 'asc' : 'desc' );
				}
				break;

			case 'union' :
				if ( $adding ) {
					throw new InvalidArgumentException( 'invalid parameter access on ' . $property );
				}

				$this->_data[$property] = !!$value;
				break;

			case 'filter' :
				throw new RuntimeException( 'implicitly setting filter rejected, use addFilter() instead' );
		}

		return $this;
	}

	public function __get( $name ) {
		return $this->get( $name );
	}

	public function __isset( $name ) {
		return !is_null( $this->get( $name ) );
	}

	public function __set( $name, $value ) {
		$this->set( $name, $value, false );
	}

	public function __call( $method, $arguments ) {
		if ( preg_match( '/^(get|set|add)([a-z]+)$/i', $method, $matches ) ) {
			$property = $this->isValidParameter( $matches[2] );
			switch ( strtolower( $matches[1] ) ) {
				case 'get' :
					return $this->get( $property );

				case 'set' :
					$this->set( $property, array_shift( $arguments ), false );
					return $this;

				case 'add' :
					$this->set( $property, array_shift( $arguments ), true );
					return $this;
			}
		}

		throw new RuntimeException( 'invalid method: ' . $method );
	}

	public function addSorting( $field, $ascending = true, $reset = false ) {
		$fields    = $this->normalizeFields( $field );
		$ascending = $this->normalizeDirection( $ascending );

		if ( $reset || !is_array( $this->_data['sort'] ) ) {
			$this->_data['sort'] = array();
		}

		foreach ( $fields as $field ) {
			$this->_data['sort'][] = array( $field, $ascending ? 'asc' : 'desc' );
		}

		return $this;
	}

	public function addFilter( $field, $value, $reset = false ) {
		$fields = $this->normalizeFields( $field );

		if ( $reset || !is_array( $this->_data['filter'] ) ) {
			$this->_data['filter'] = array();
		}

		foreach ( $fields as $field ) {
			if ( array_key_exists( $field, $this->_data['filter'] ) ) {
				$this->_data['filter'][$field] = array( $value );
			} else {
				$this->_data['filter'][$field][] = $value;
			}
		}

		return $this;
	}
}
