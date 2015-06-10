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
 * Describes complex term describing union or intersection of several contained
 * simple or complex terms.
 *
 * This class is part of API used to describe query terms independently of any
 * actually used search engine.
 */

abstract class Opus_Search_Filter_Complex extends Opus_Search_Filter_Base {

	protected $negated = false;

	protected $union = false;

	/**
	 * Lists conditions of current filter.
	 *
	 * @var Opus_Search_Filtering[]
	 */
	protected $conditions = array();



	/**
	 * Adds provided condition to current filter.
	 *
	 * @param Opus_Search_Filtering $filter
	 * @return $this
	 */
	public function addFilter( Opus_Search_Filtering $filter ) {
		$this->conditions[] = $filter;

		return $this;
	}

	public function createComplexFilter() {
		return new static();
	}

	/**
	 * Creates (and adds) another simple filter term.
	 *
	 * @param string $fieldName name of field simple filter applies on
	 * @param mixed $operator one out of Opus_Search_Filter_Simple::COMPARE_* constants
	 * @param bool $addImplicitly true for adding simple term to current complex term implicitly
	 * @return Opus_Search_Filter_Simple
	 */
	public function createSimpleFilter( $fieldName, $operator, $addImplicitly = true ) {
		$simple = new Opus_Search_Filter_Simple( $fieldName, $operator );

		if ( $addImplicitly ) {
			$this->addFilter( $simple );
		}

		return $simple;
	}

	/**
	 * Creates (and adds) another simple filter term testing for equality on
	 * given field.
	 *
	 * @param string $fieldName name of field simple filter applies on
	 * @param bool $addImplicitly true for adding simple term to current complex term implicitly
	 * @return Opus_Search_Filter_Simple
	 */
	public function createSimpleEqualityFilter( $fieldName, $addImplicitly = true ) {
		return $this->createSimpleFilter( $fieldName, Opus_Search_Filter_Simple::COMPARE_EQUALITY, $addImplicitly );
	}

	/**
	 * Creates (and adds) another simple filter term testing for inequality on
	 * given field.
	 *
	 * @param string $fieldName name of field simple filter applies on
	 * @param bool $addImplicitly true for adding simple term to current complex term implicitly
	 * @return Opus_Search_Filter_Simple
	 */
	public function createSimpleInequalityFilter( $fieldName, $addImplicitly = true ) {
		return $this->createSimpleFilter( $fieldName, Opus_Search_Filter_Simple::COMPARE_INEQUALITY, $addImplicitly );
	}

	/**
	 * Creates (and adds) another simple filter term testing for similarity on
	 * given field.
	 *
	 * @param string $fieldName name of field simple filter applies on
	 * @param bool $addImplicitly true for adding simple term to current complex term implicitly
	 * @return Opus_Search_Filter_Simple
	 */
	public function createSimpleSimilarityFilter( $fieldName, $addImplicitly = true ) {
		return $this->createSimpleFilter( $fieldName, Opus_Search_Filter_Simple::COMPARE_SIMILARITY, $addImplicitly );
	}

	/**
	 * Creates (and adds) another simple filter term testing for upper exclusive
	 * limit on given field.
	 *
	 * @param string $fieldName name of field simple filter applies on
	 * @param bool $addImplicitly true for adding simple term to current complex term implicitly
	 * @return Opus_Search_Filter_Simple
	 */
	public function createSimpleLessFilter( $fieldName, $addImplicitly = true ) {
		return $this->createSimpleFilter( $fieldName, Opus_Search_Filter_Simple::COMPARE_LESS, $addImplicitly );
	}

	/**
	 * Creates (and adds) another simple filter term testing for upper inclusive
	 * limit on given field.
	 *
	 * @param string $fieldName name of field simple filter applies on
	 * @param bool $addImplicitly true for adding simple term to current complex term implicitly
	 * @return Opus_Search_Filter_Simple
	 */
	public function createSimpleLessOrEqualFilter( $fieldName, $addImplicitly = true ) {
		return $this->createSimpleFilter( $fieldName, Opus_Search_Filter_Simple::COMPARE_LESS_OR_EQUAL, $addImplicitly );
	}

	/**
	 * Creates (and adds) another simple filter term testing for lower exclusive
	 * limit on given field.
	 *
	 * @param string $fieldName name of field simple filter applies on
	 * @param bool $addImplicitly true for adding simple term to current complex term implicitly
	 * @return Opus_Search_Filter_Simple
	 */
	public function createSimpleGreaterFilter( $fieldName, $addImplicitly = true ) {
		return $this->createSimpleFilter( $fieldName, Opus_Search_Filter_Simple::COMPARE_GREATER, $addImplicitly );
	}

	/**
	 * Creates (and adds) another simple filter term testing for lower inclusive
	 * limit on given field.
	 *
	 * @param string $fieldName name of field simple filter applies on
	 * @param bool $addImplicitly true for adding simple term to current complex term implicitly
	 * @return Opus_Search_Filter_Simple
	 */
	public function createSimpleGreaterOrEqualFilter( $fieldName, $addImplicitly = true ) {
		return $this->createSimpleFilter( $fieldName, Opus_Search_Filter_Simple::COMPARE_GREATER_OR_EQUAL, $addImplicitly );
	}

	/**
	 * Requests filter describing documents matching all contained conditions.
	 *
	 * @return $this
	 */
	public function setSatisfyAll() {
		$this->negated = false;
		$this->union   = false;

		return $this;
	}

	/**
	 * Requests filter describing documents matching any contained condition.
	 *
	 * @return $this
	 */
	public function setSatisfyAny() {
		$this->negated = false;
		$this->union   = true;

		return $this;
	}

	/**
	 * Requests filter describing documents not matching any of the contained
	 * conditions.
	 *
	 * @return $this
	 */
	public function setSatisfyNone() {
		$this->negated = true;
		$this->union   = true;

		return $this;
	}

	/**
	 * Indicates if filter is describing union of sets matching conditions.
	 *
	 * @note This is false if filter is describing intersection of those sets.
	 *
	 * @return bool
	 */
	public function isRequestingUnion() {
		return !!$this->union;
	}

	/**
	 * Indicates if filter is describing complementary set of intersection or
	 * union of sets matching conditions.
	 *
	 * @return bool
	 */
	public function isGloballyNegated() {
		return !!$this->negated;
	}

	/**
	 * @return Opus_Search_Filtering[]
	 */
	public function getConditions() {
		return $this->conditions;
	}
}
