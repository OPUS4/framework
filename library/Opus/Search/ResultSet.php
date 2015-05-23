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
 * Implements description of particular search query's result in case of
 * success.
 */

class Opus_Search_ResultSet {

	protected $data;


	/**
	 * @param Opus_Document[]|int[] $matches set of matching documents or set of matching documents' IDs
	 * @param int $allMatchesCount overall number of matches
	 * @param bool $listingIds true if IDs listed in $matches mustn't be resolved
	 */
	public function __construct( $matches, $allMatchesCount, $listingIds ) {
		if ( !is_array( $matches ) ) {
			throw new InvalidArgumentException( 'invalid set of matches' );
		}

		// normalize provided set of matches according to selected mode of delivery
		foreach ( $matches as $key => $match ) {
			if ( $match instanceof Opus_Document ) {
				if ( $listingIds ) {
					$matches[$key] = $match->getId();
				}
			} else {
				if ( !ctype_digit( trim( $match ) ) ) {
					throw new InvalidArgumentException( 'invalid element in set of matches' );
				}

				if ( !$listingIds ) {
					$matches[$key] = new Opus_Document( $match );
				}
			}
		}

		if ( !ctype_digit( trim( $allMatchesCount ) ) ) {
			throw new InvalidArgumentException( 'invalid number of overall matches' );
		}


		$this->data = array(
			'matches'   => $matches,
			'count'     => intval( $allMatchesCount ),
			'qualified' => !$listingIds,
		);
	}

	/**
	 * Retrieves set of matching and locally existing documents.
	 *
	 * @return Opus_Document[]
	 */
	public function getMatches() {
		if ( $this->data['qualified'] ) {
			return $this->data['matches'];
		}

		$matches = array();

		foreach ( $this->data['matches'] as $match ) {
			if ( $match instanceof Opus_Document ) {
				$matches[] = $match;
			} else {
				try {
					$matches[] = new Opus_Document( $match );
				} catch ( Opus_Document_Exception $e ) {
					Opus_Log::get()->warn( 'skipping matching but locally missing document #' . $match );
				}
			}
		}

		return $matches;
	}

	/**
	 * Retrieves set of matching documents' IDs.
	 *
	 * @note If query was requesting to retrieve non-qualified matches this set
	 *       might include IDs of documents that doesn't exist locally anymore.
	 *
	 * @return int[]
	 */
	public function getMatchingIds() {
		return array_map( function( $match ) {
			return $match instanceof Opus_Document ? $match->getId() : $match;
		}, $this->data['matches'] );
	}

	/**
	 * Retrieves overall number of matches.
	 *
	 * @note This number includes matches not included in fetched subset of
	 *       matches.
	 *
	 * @return int
	 */
	public function getAllMatchesCount() {
		return $this->data['count'];
	}

	public function __get( $name ) {
		switch ( strtolower( trim( $name ) ) ) {
			case 'matches' :
				return $this->getMatches();

			case 'allmatchescount' :
				return $this->getAllMatchesCount();

			default :
				throw new RuntimeException( 'invalid request for property ' . $name );
		}
	}

}
