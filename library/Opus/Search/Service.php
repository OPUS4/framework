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

class Opus_Search_Service {

	protected static $pool = array();

	/**
	 * @param string $serviceType one out of 'index', 'search' or 'extract'
	 * @param string $serviceInterface required interface of service adapter, e.g. 'Opus_Search_Indexable'
	 * @param string|null $serviceName name of configured service to work with
	 * @param string $serviceDomain name of domain selected service belongs to
	 * @return Opus_Search_Indexable|Opus_Search_Searchable|Opus_Search_Extractable
	 * @throws Zend_Config_Exception
	 */
	protected static function selectService( $serviceType, $serviceInterface, $serviceName = null, $serviceDomain = null ) {
		if ( !$serviceName ) {
			$serviceName = 'default';
		}

		if ( !array_key_exists( $serviceDomain, self::$pool ) ) {
			self::$pool[$serviceDomain] = array(
				'index'   => array(),
				'search'  => array(),
				'extract' => array(),
			);
		}

		$domainPool =& self::$pool[$serviceDomain];

		if ( !array_key_exists( $serviceName, $domainPool[$serviceType] ) ) {
			$config    = static::getDomainConfiguration( $serviceDomain );

			$className = $config->adapter;
			if ( $className instanceof Zend_Config ) {
				$className = $className->get( $serviceName, $className->get( 'default' ) );
				if ( !$className ) {
					throw new Zend_Config_Exception( 'missing search engine adapter' );
				}
			}

			$class = new ReflectionClass( $className );

			if ( !$class->implementsInterface( $serviceInterface ) ) {
				throw new Zend_Config_Exception( 'invalid search engine adapter' );
			}

			$domainPool[$serviceType][$serviceName] = $class->newInstance( $serviceName );
		}

		return $domainPool[$serviceType][$serviceName];
	}

	/**
	 * @param string|null $serviceName name of configured service to work with
	 * @param string $serviceDomain name of domain selected service belongs to
	 * @return Opus_Search_Indexable
	 * @throws Zend_Config_Exception
	 */
	public static function selectIndexingService( $serviceName = null, $serviceDomain = null ) {
		return static::selectService( 'index', 'Opus_Search_Indexable', $serviceName, $serviceDomain );
	}

	/**
	 * @param string|null $serviceName name of configured service to work with
	 * @param string $serviceDomain name of domain selected service belongs to
	 * @return Opus_Search_Searchable
	 * @throws Zend_Config_Exception
	 */
	public static function selectSearchingService( $serviceName = null, $serviceDomain = null ) {
		return static::selectService( 'search', 'Opus_Search_Searchable', $serviceName, $serviceDomain );
	}

	/**
	 * @param string|null $serviceName name of configured service to work with
	 * @param string $serviceDomain name of domain selected service belongs to
	 * @return Opus_Search_Extractable
	 * @throws Zend_Config_Exception
	 */
	public static function selectExtractingService( $serviceName = null, $serviceDomain = null ) {
		return static::selectService( 'extract', 'Opus_Search_Extractable', $serviceName, $serviceDomain );
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
		$serviceDomain = static::getQualifiedDomain( $serviceDomain );

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
	 * @param string $serviceName name of service, omit for 'default'
	 * @param string $serviceDomain name of domain selected service belongs to
	 * @return Zend_Config
	 */
	public static function getServiceConfiguration( $serviceName = null, $serviceDomain = null ) {
		$config = static::getDomainConfiguration( $serviceDomain )->service;

		if ( !$serviceName || !is_string( $serviceName ) ) {
			$serviceName = 'default';
		}

		return $config->get( $serviceName, $config->default );
	}

	/**
	 * Retrieves new instance for managing parameters in selected search engine
	 * domain.
	 *
	 * @param string $serviceDomain desired name of search engine's domain to manage parameters for
	 * @return Opus_Search_Parameters
	 */
	public static function createDomainParameters( $serviceDomain = null ) {
		switch ( static::getQualifiedDomain( $serviceDomain ) ) {
			case 'solr' :
				return new Opus_Search_Solr_Parameters();

			default :
				throw new InvalidArgumentException( 'invalid search engine domain' );
		}
	}

	/**
	 * Validates provided explicit selection of search domain using any
	 * configured domain by default.
	 *
	 * @note If configuration is missing explicit definition of default search
	 *       domain, "solr" is returned by default.
	 *
	 * @param string $searchDomain explicitly selected search domain
	 * @return string
	 */
	protected static function getQualifiedDomain( $searchDomain = null ) {
		if ( is_null( $searchDomain ) ) {
			$config = static::getConfiguration();
			$searchDomain = $config->get( 'domain', 'solr' );
		}

		if ( !is_string( $searchDomain ) || !trim( $searchDomain ) ) {
			throw new InvalidArgumentException( 'invalid default search domain' );
		}

		return trim( $searchDomain );
	}
}
