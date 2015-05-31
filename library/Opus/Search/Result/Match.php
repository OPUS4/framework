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
 * Describes local document as a match in context of a related search query.
 */

class Opus_Search_Result_Match {

	/**
	 * @var mixed
	 */
	protected $id = null;

	/**
	 * @var Opus_Document
	 */
	protected $doc = null;

	/**
	 * @var float
	 */
	protected $score = null;

	/**
	 * @var Opus_Date
	 */
	protected $serverDateModified = null;



	public function __construct( $matchId ) {
		$this->id = $matchId;
	}

	public static function create( $matchId ) {
		return new static( $matchId );
	}

	/**
	 * Retrieves ID of document matching related search query.
	 *
	 * @return mixed
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Retrieves instance of Opus_Document related to current match.
	 *
	 * @throws Opus_Model_NotFoundException
	 * @return Opus_Document
	 */
	public function getDocument() {
		if ( is_null( $this->doc ) ) {
			$this->doc = new Opus_Document( $this->id );
		}

		return $this->doc;
	}

	/**
	 * Assigns score of match in context of related search.
	 *
	 * @param $score
	 * @return $this
	 */
	public function setScore( $score ) {
		if ( !is_null( $this->score ) ) {
			throw new RuntimeException( 'score has been set before' );
		}

		$this->score = floatval( $score );

		return $this;
	}

	/**
	 * Retrieves score of match in context of related search.
	 *
	 * @return float|null null if score was not set
	 */
	public function getScore() {
		return $this->score;
	}

	/**
	 * Assigns timestamp of last modification to document as tracked in search
	 * index.
	 *
	 * @note This information is temporarily overloading related timestamp in
	 *       local document.
	 *
	 * @param {int} $timestamp Unix timestamp of last modification tracked in search index
	 * @return $this fluent interface
	 */
	public function setServerDateModified( $timestamp ) {
		if ( !is_null( $this->serverDateModified ) ) {
			throw new RuntimeException( 'timestamp of modification has been set before' );
		}

		$this->serverDateModified = new Opus_Date();

		if ( ctype_digit( $timestamp = trim( $timestamp ) ) ) {
			$this->serverDateModified->setUnixTimestamp( intval( $timestamp ) );
		} else {
			$this->serverDateModified->setFromString( $timestamp );
		}

		return $this;
	}

	/**
	 * Provides timestamp of last modification preferring value provided by
	 * search engine over value stored locally in document.
	 *
	 * @note This method is used by Opus to detect outdated records in search
	 *       index.
	 *
	 * @return Opus_Date
	 */
	public function getServerDateModified() {
		if ( !is_null( $this->serverDateModified ) ) {
			return $this->serverDateModified;
		}

		return $this->getDocument()->getServerDateModified();
	}

	/**
	 * Passes all unknown method invocations to related instance of
	 * Opus_Document.
	 *
	 * @param string $method name of locally missing/protected method
	 * @param mixed[] $args arguments used on invoking that method
	 * @return mixed
	 */
	public function __call( $method, $args ) {
		return call_user_func_array( array( $this->getDocument(), $method ), $args );
	}

	/**
	 * Passes access on locally missing/protected property to related instance
	 * of Opus_Document.
	 *
	 * @param string $name name of locally missing/protected property
	 * @return mixed value of property
	 */
	public function __get( $name ) {
		return $this->getDocument()->{$name};
	}
}
