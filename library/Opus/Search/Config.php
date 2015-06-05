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
 * Provides access on sections of configuration regarding selected domains of
 * searching and/or particular services or queries defined in either domain.
 *
 * All configuration is available through static methods to be globally
 * accessible in code. This API implements some merging of existing
 * configuration to support fallback
 *
 * @author Thomas Urban <thomas.urban@cepharum.de>
 */

class Opus_Search_Config {

	protected static $configurationsPool = array();



	/**
	 * Drops any cached configuration.
	 *
	 */
	public static function dropCached() {
		self::$configurationsPool = array();
	}

	/**
	 * Retrieves extract from configuration regarding integration with some
	 * search engine.
	 *
	 * @return Zend_Config
	 */
	public static function getConfiguration() {
		return Opus_Config::get()->searchengine;
	}

	/**
	 * Retrieves extract from configuration regarding integration with search
	 * engine of selected domain.
	 *
	 * @param string $serviceDomain name of a search engine's domain
	 * @return Zend_Config
	 */
	public static function getDomainConfiguration( $serviceDomain = null ) {
		$serviceDomain = Opus_Search_Service::getQualifiedDomain( $serviceDomain );

		$config = static::getConfiguration()->get( $serviceDomain );
		if ( !( $config instanceof Zend_Config ) ) {
			throw new InvalidArgumentException( 'invalid search engine domain: ' . $serviceDomain );
		}

		return $config;
	}

	/**
	 * Retrieves configuration of selected Solr integration service.
	 *
	 * @note Default is retrieved if explicitly selected service is missing.
	 *
	 * @param string $serviceType one out of 'index', 'search' or 'extract'
	 * @param string $serviceName name of service, omit for 'default'
	 * @param string $serviceDomain name of domain selected service belongs to
	 * @return Zend_Config
	 */
	public static function getServiceConfiguration( $serviceType, $serviceName = null, $serviceDomain = null ) {
		if ( !$serviceName || !is_string( $serviceName ) ) {
			$serviceName = 'default';
		}

		// try runtime cache first to keep configurations from being re-merged
		$hash = sha1( "$serviceDomain::$serviceName::$serviceType" );
		if ( array_key_exists( $hash, self::$configurationsPool ) ) {
			return self::$configurationsPool[$hash];
		}


		$config = static::getDomainConfiguration( $serviceDomain );

		$base = array();

		if ( isset( $config->adapterClass ) ) {
			$base['adapterClass'] = $config->adapterClass;
		}

		$result = new Zend_Config( $base, true );

		if ( isset( $config->default->service ) ) {
			$result->merge( $config->default->service );
		}

		if ( isset( $config->default->service->{$serviceType} ) ) {
			$result->merge( $config->default->service->{$serviceType} );
		}

		if ( $serviceName && $serviceName != 'default' ) {
			if ( isset( $config->{$serviceName}->service ) ) {
				$result->merge( $config->{$serviceName}->service );
			}

			if ( isset( $config->{$serviceName}->service->{$serviceType} ) ) {
				$result->merge( $config->{$serviceName}->service->{$serviceType} );
			}
		}

		$result->setReadOnly();

		self::$configurationsPool[$hash] = $result;

		return $result;
	}

	/**
	 * Retrieves set of field names to use in faceted search.
	 *
	 * @note Provided name enables use of different sets. But extracted set is
	 *       downward compatible with previous sort of unnamed configurations.
	 *
	 * @param string $facetSetName name of configured facets set
	 * @param string $serviceDomain name of domain to read configuration of
	 * @return string[] probably empty set of found field names to use in faceted search
	 * @throws Zend_Config_Exception
	 */
	public static function getFacetFields( $facetSetName = null, $serviceDomain = null ) {
		$facetSetName = is_null( $facetSetName ) ? 'default' : trim( $facetSetName );
		if ( !$facetSetName ) {
			throw new InvalidArgumentException( 'invalid facet set name' );
		}


		$config = static::getDomainConfiguration( $serviceDomain )->get( 'facets' );

		if ( $config instanceof Zend_Config ) {
			// BEST: use configuration in searchengine.solr.facets.$facetSetName
			$sub = $facetSetName ? $config->get( $facetSetName ) : null;
			if ( !( $sub instanceof Zend_Config ) ) {
				// BETTER: use fallback configuration in searchengine.solr.facets.default
				$sub = $config->get( 'default' );
			}

			if ( $sub instanceof Zend_Config ) {
				$config = $sub;
			}
			// ELSE: GOOD: use downward-compatible searchengine.solr.facets
		}

		if ( $config && is_scalar( $config ) ) {
			$set = preg_split( '/[\s,]+/', trim( $config ), null, PREG_SPLIT_NO_EMPTY );
		} else {
			$set = array();
		}


		return $set;
	}

	public static function getFacetLimits( $facetSetName = null, $serviceDomain = null ) {
		$facetSetName = is_null( $facetSetName ) ? 'default' : trim( $facetSetName );
		if ( !$facetSetName ) {
			throw new InvalidArgumentException( 'invalid facet set name' );
		}


		$config = static::getDomainConfiguration( $serviceDomain );

		// get configured limits from configuration
		$fieldLimits = $config->get( 'facetlimit', array() );
		$globalLimit = (int) $config->get( 'globalfacetlimit', 10 );

		$set = array(
			'__global__' => $globalLimit
		);


		$fields = static::getFacetFields( $facetSetName, $serviceDomain );

		foreach ( $fields as $field ) {
			if ( array_key_exists( $field, $fieldLimits ) ) {
				$set[$field] = (int) $fieldLimits[$field];
			} else {
				$set[$field] = $globalLimit;
			}
		}


		// if facet-name is 'year_inverted', the facet values have to be sorted vice versa
		// however, the facet-name should be 'year' (reset in framework (ResponseRenderer::getFacets())
		if ( array_key_exists( 'year_inverted', $set ) ) {
			$set['year'] = $set['year_inverted'];
			unset( $set['year_inverted'] );
		}


		return $set;
	}

	public static function getFacetSorting( $facetSetName = null, $serviceDomain = null ) {
		$facetSetName = is_null( $facetSetName ) ? 'default' : trim( $facetSetName );
		if ( !$facetSetName ) {
			throw new InvalidArgumentException( 'invalid facet set name' );
		}


		$fields = static::getFacetFields( $facetSetName, $serviceDomain );
		$config = static::getDomainConfiguration( $serviceDomain )->get( 'sortcrit', null );

		if ( $config instanceof Zend_Config ) {
			// BEST: try configuration in searchengine.solr.sortcrit.$facetSetName
			$sub = $config->get( $facetSetName );
			if ( !( $sub instanceof Zend_Config ) ) {
				// BETTER: use fallback configuration in searchengine.solr.sortcrit.default
				$sub = $config->get( 'default' );
			}

			if ( $sub instanceof Zend_Config ) {
				$config = $sub;
			}
			// ELSE: GOOD: use downward-compatible configuration in searchengine.solr.sortcrit
		}

		if ( $config && !( $config instanceof Zend_Config ) ) {
			throw new Zend_Config_Exception( 'invalid facet sorting configuration' );
		}

		$set = array();

		if ( count( $fields ) && $config ) {
			foreach ( $fields as $field ) {
				if ( $config->get( $field ) == 'lexi' ) {
					$set[$field] = 'index';
				}
			}
		}


		return $set;
	}
}
