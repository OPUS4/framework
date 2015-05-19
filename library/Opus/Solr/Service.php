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

class Opus_Solr_Service {

	protected static $pool = array(
		'index'   => array(),
		'search'  => array(),
		'extract' => array(),
		);

	/**
	 * @param string $serviceType one out of 'index', 'search' or 'extract'
	 * @param string $serviceInterface required interface of service adapter, e.g. 'Opus_Solr_Indexable'
	 * @param string|null $serviceName name of configured service to work with
	 * @return Opus_Solr_Indexable|Opus_Solr_Searchable|Opus_Solr_Extractable
	 * @throws Zend_Config_Exception
	 */
	protected static function selectService( $serviceType, $serviceInterface, $serviceName = null ) {
		if ( !$serviceName ) {
			$serviceName = 'default';
		}

		if ( !array_key_exists( $serviceName, self::$pool[$serviceType] ) ) {
			$config    = static::getConfiguration();

			$className = $config->adapter;
			if ( $className instanceof Zend_Config ) {
				$className = $className->get( $serviceName, $className->get( 'default' ) );
				if ( !$className ) {
					throw new Zend_Config_Exception( 'missing Solr adapter' );
				}
			}

			$class = new ReflectionClass( $className );

			if ( !$class->implementsInterface( $serviceInterface ) ) {
				throw new Zend_Config_Exception( 'invalid Solr adapter' );
			}

			self::$pool[$serviceType][$serviceName] = $class->newInstance( $serviceName );
		}

		return self::$pool[$serviceType][$serviceName];
	}

	/**
	 * @param string|null $serviceName name of configured service to work with
	 * @return Opus_Solr_Indexable
	 * @throws Zend_Config_Exception
	 */
	public static function selectIndexingService( $serviceName = null ) {
		return static::selectService( 'index', 'Opus_Solr_Indexable', $serviceName );
	}

	/**
	 * @param string|null $serviceName name of configured service to work with
	 * @return Opus_Solr_Searchable
	 * @throws Zend_Config_Exception
	 */
	public static function selectSearchingService( $serviceName = null ) {
		return static::selectService( 'search', 'Opus_Solr_Searchable', $serviceName );
	}

	/**
	 * @param string|null $serviceName name of configured service to work with
	 * @return Opus_Solr_Extractable
	 * @throws Zend_Config_Exception
	 */
	public static function selectExtractingService( $serviceName = null ) {
		return static::selectService( 'extract', 'Opus_Solr_Extractable', $serviceName );
	}

	/**
	 * Retrieves extract from configuration regarding Solr integration.
	 *
	 * @return Zend_Config
	 */
	public static function getConfiguration() {
		return Opus_Config::get()->searchengine->solr;
	}

	/**
	 * Retrieves configuration of selected Solr integration service.
	 *
	 * @note Default is retrieved if explicitly selected service is missing.
	 *
	 * @param string $serviceName name of service, omit for 'default'
	 * @return Zend_Config
	 */
	public static function getServiceConfiguration( $serviceName = null ) {
		if ( !$serviceName || !is_string( $serviceName ) ) {
			$serviceName = 'default';
		}

		$config = static::getConfiguration()->service;
		return $config->get( $serviceName, $config->default );
	}
}
