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


class Opus_Search_ConfigTest extends SimpleTestCase {

	public function testProvidesSearchConfiguration() {
		$this->dropDeprecatedConfiguration();

		$config = Opus_Search_Config::getServiceConfiguration( 'search', null, 'solr' );

		$this->assertInstanceOf( 'Zend_Config', $config );
		$this->assertInstanceOf( 'Zend_Config', $config->query );
		$this->assertInstanceOf( 'Zend_Config', $config->query->alldocs );

		$this->assertEquals( 'search', $config->marker );
		$this->assertNotNull( $config->endpoint->primary->host );
		$this->assertNotNull( $config->endpoint->primary->port );
		$this->assertNotNull( $config->endpoint->primary->path );

        $this->assertNotNull( $config->endpoint->primary->timeout );
        $this->assertEquals(10, $config->endpoint->primary->timeout );
	}

	public function testProvidesIndexConfiguration() {
		$this->dropDeprecatedConfiguration();

		$config = Opus_Search_Config::getServiceConfiguration( 'index', null, 'solr' );

		$this->assertInstanceOf( 'Zend_Config', $config );
		$this->assertInstanceOf( 'Zend_Config', $config->query );
		$this->assertInstanceOf( 'Zend_Config', $config->query->alldocs );

		$this->assertEquals( 'index', $config->marker );
		$this->assertNotNull( $config->endpoint->primary->host );
		$this->assertNotNull( $config->endpoint->primary->port );
		$this->assertNotNull( $config->endpoint->primary->path );
	}

	public function testProvidesExtractConfiguration() {
		$this->dropDeprecatedConfiguration();

		$config = Opus_Search_Config::getServiceConfiguration( 'extract', null, 'solr' );

		$this->assertInstanceOf( 'Zend_Config', $config );
		$this->assertInstanceOf( 'Zend_Config', $config->query );
		$this->assertInstanceOf( 'Zend_Config', $config->query->alldocs );

		$this->assertEquals( 'extract', $config->marker );
		$this->assertNotNull( $config->endpoint->primary->host );
		$this->assertNotNull( $config->endpoint->primary->port );
		$this->assertNotNull( $config->endpoint->primary->path );
	}

	public function testProvidesDefaultConfiguration() {
		$this->dropDeprecatedConfiguration();

		$config = Opus_Search_Config::getServiceConfiguration( 'default', null, 'solr' );

		$this->assertInstanceOf( 'Zend_Config', $config );
		$this->assertInstanceOf( 'Zend_Config', $config->query );
		$this->assertInstanceOf( 'Zend_Config', $config->query->alldocs );

		$this->assertEquals( 'default', $config->marker );
		$this->assertNotNull( $config->endpoint->primary->host );
		$this->assertNotNull( $config->endpoint->primary->port );
		$this->assertNotNull( $config->endpoint->primary->path );
	}

	public function testProvidesSpecialSearchConfiguration() {
		$this->dropDeprecatedConfiguration();

		$config = Opus_Search_Config::getServiceConfiguration( 'search', 'special', 'solr' );

		$this->assertInstanceOf( 'Zend_Config', $config );
		$this->assertInstanceOf( 'Zend_Config', $config->query );
		$this->assertInstanceOf( 'Zend_Config', $config->query->alldocs );

		$this->assertEquals( 'search2', $config->marker );
		$this->assertEquals( '127.0.0.2', $config->endpoint->primary->host );
		$this->assertNotNull( $config->endpoint->primary->port );
		$this->assertEquals( '/solr-special/', $config->endpoint->primary->path );
	}

	public function testProvidesSpecialExtractConfiguration() {
		$this->dropDeprecatedConfiguration();

		$config = Opus_Search_Config::getServiceConfiguration( 'extract', 'special', 'solr' );

		$this->assertInstanceOf( 'Zend_Config', $config );
		$this->assertInstanceOf( 'Zend_Config', $config->query );
		$this->assertInstanceOf( 'Zend_Config', $config->query->alldocs );

		$this->assertEquals( 'extract2', $config->marker );
		$this->assertNotNull( $config->endpoint->primary->host );
		$this->assertNotNull( $config->endpoint->primary->port );
		$this->assertEquals( '/solr-special/', $config->endpoint->primary->path );
	}

	public function testProvidesDefaultConfigurationAsFallback() {
		$this->dropDeprecatedConfiguration();

		$config = Opus_Search_Config::getServiceConfiguration( 'missing', null, 'solr' );

		$this->assertInstanceOf( 'Zend_Config', $config );
		$this->assertInstanceOf( 'Zend_Config', $config->query );
		$this->assertInstanceOf( 'Zend_Config', $config->query->alldocs );

		$this->assertEquals( 'default', $config->marker );
		$this->assertNotNull( $config->endpoint->primary->host );
		$this->assertNotNull( $config->endpoint->primary->port );
		$this->assertNotNull( $config->endpoint->primary->path );
	}

	public function testProvidesAllSolrConfiguration() {
		$this->dropDeprecatedConfiguration();

		$config = Opus_Search_Config::getDomainConfiguration( 'solr' );

		$this->assertInstanceOf( 'Zend_Config', $config );
		$this->assertInstanceOf( 'Zend_Config', $config->default );
		$this->assertInstanceOf( 'Zend_Config', $config->special );
	}

	public function testProvidesCachedConfiguration() {
		$configA = Opus_Search_Config::getServiceConfiguration( 'search' );
		$configB = Opus_Search_Config::getServiceConfiguration( 'search' );

		$this->assertTrue( $configA === $configB );

		Opus_Search_Config::dropCached();

		$configC = Opus_Search_Config::getServiceConfiguration( 'search' );

		$this->assertTrue( $configA !== $configC );
	}

	public function testAdoptsDeprecatedSearchConfiguration() {
		$this->dropDeprecatedConfiguration();

		// test new style configuration as provided in ini-file
		$config = Opus_Search_Config::getServiceConfiguration( 'search', null, 'solr' );

		$this->assertInstanceOf( 'Zend_Config', $config );
		$this->assertInstanceOf( 'Zend_Config', $config->query );
		$this->assertInstanceOf( 'Zend_Config', $config->query->alldocs );

		$this->assertEquals( 'search', $config->marker );
		$this->assertNotNull( $config->endpoint->primary->host );
		$this->assertNotNull( $config->endpoint->primary->port );
		$this->assertNotNull( $config->endpoint->primary->path );

		$this->assertNotEquals( '10.1.2.3', $config->endpoint->primary->host );
		$this->assertNotEquals( '12345', $config->endpoint->primary->port );
		$this->assertNotEquals( '/some/fallback', $config->endpoint->primary->path );

		// provide some deprecated-style configuration to overlay
		$this->adjustConfiguration( array( 'searchengine' => array( 'index' => array(
			'host' => '10.1.2.3',
			'port' => 12345,
			'app'  => 'some/fallback'
		) ) ) );

		$this->assertEquals( '10.1.2.3', Opus_Config::get()->searchengine->index->host );
		$this->assertEquals( '12345', Opus_Config::get()->searchengine->index->port );
		$this->assertEquals( 'some/fallback', Opus_Config::get()->searchengine->index->app );

		// repeat test above now expecting to get overlaid configuration
		$config = Opus_Search_Config::getServiceConfiguration( 'search', null, 'solr' );

		$this->assertInstanceOf( 'Zend_Config', $config );
		$this->assertInstanceOf( 'Zend_Config', $config->query );
		$this->assertInstanceOf( 'Zend_Config', $config->query->alldocs );

		$this->assertEquals( 'search', $config->marker );

		$this->assertEquals( '10.1.2.3', $config->endpoint->primary->host );
		$this->assertEquals( '12345', $config->endpoint->primary->port );
		$this->assertEquals( '/some/fallback', $config->endpoint->primary->path );
	}

	public function testAdoptsDeprecatedIndexConfiguration() {
		$this->dropDeprecatedConfiguration();

		// test new style configuration as provided in ini-file
		$config = Opus_Search_Config::getServiceConfiguration( 'index', null, 'solr' );

		$this->assertInstanceOf( 'Zend_Config', $config );
		$this->assertInstanceOf( 'Zend_Config', $config->query );
		$this->assertInstanceOf( 'Zend_Config', $config->query->alldocs );

		$this->assertEquals( 'index', $config->marker );
		$this->assertNotNull( $config->endpoint->primary->host );
		$this->assertNotNull( $config->endpoint->primary->port );
		$this->assertNotNull( $config->endpoint->primary->path );

		$this->assertNotEquals( '10.1.2.3', $config->endpoint->primary->host );
		$this->assertNotEquals( '12345', $config->endpoint->primary->port );
		$this->assertNotEquals( '/some/fallback', $config->endpoint->primary->path );

		// provide some deprecated-style configuration to overlay
		$this->adjustConfiguration( array( 'searchengine' => array( 'index' => array(
			'host' => '10.1.2.3',
			'port' => 12345,
			'app'  => 'some/fallback',
            'timeout' => 20
		) ) ) );

		$this->assertEquals( '10.1.2.3', Opus_Config::get()->searchengine->index->host );
		$this->assertEquals( '12345', Opus_Config::get()->searchengine->index->port );
		$this->assertEquals( 'some/fallback', Opus_Config::get()->searchengine->index->app );
        $this->assertEquals( '20', Opus_config::get()->searchengine->index->timeout );

		// repeat test above now expecting to get overlaid configuration
		$config = Opus_Search_Config::getServiceConfiguration( 'index', null, 'solr' );

		$this->assertInstanceOf( 'Zend_Config', $config );
		$this->assertInstanceOf( 'Zend_Config', $config->query );
		$this->assertInstanceOf( 'Zend_Config', $config->query->alldocs );

		$this->assertEquals( 'index', $config->marker );

		$this->assertEquals( '10.1.2.3', $config->endpoint->primary->host );
		$this->assertEquals( '12345', $config->endpoint->primary->port );
		$this->assertEquals( '/some/fallback', $config->endpoint->primary->path );
	}

	public function testAdoptsDeprecatedExtractConfiguration() {
		$this->dropDeprecatedConfiguration();

		// test new style configuration as provided in ini-file
		$config = Opus_Search_Config::getServiceConfiguration( 'extract', null, 'solr' );

		$this->assertInstanceOf( 'Zend_Config', $config );
		$this->assertInstanceOf( 'Zend_Config', $config->query );
		$this->assertInstanceOf( 'Zend_Config', $config->query->alldocs );

		$this->assertEquals( 'extract', $config->marker );
		$this->assertNotNull( $config->endpoint->primary->host );
		$this->assertNotNull( $config->endpoint->primary->port );
		$this->assertNotNull( $config->endpoint->primary->path );

		$this->assertNotEquals( '10.1.2.3', $config->endpoint->primary->host );
		$this->assertNotEquals( '12345', $config->endpoint->primary->port );
		$this->assertNotEquals( '/some/fallback', $config->endpoint->primary->path );

		// provide some deprecated-style configuration to overlay
		$this->adjustConfiguration( array( 'searchengine' => array( 'extract' => array(
			'host' => '10.1.2.3',
			'port' => 12345,
			'app'  => 'some/fallback'
		) ) ) );

		$this->assertEquals( '10.1.2.3', Opus_Config::get()->searchengine->extract->host );
		$this->assertEquals( '12345', Opus_Config::get()->searchengine->extract->port );
		$this->assertEquals( 'some/fallback', Opus_Config::get()->searchengine->extract->app );

		// repeat test above now expecting to get overlaid configuration
		$config = Opus_Search_Config::getServiceConfiguration( 'extract', null, 'solr' );

		$this->assertInstanceOf( 'Zend_Config', $config );
		$this->assertInstanceOf( 'Zend_Config', $config->query );
		$this->assertInstanceOf( 'Zend_Config', $config->query->alldocs );

		$this->assertEquals( 'extract', $config->marker );

		$this->assertEquals( '10.1.2.3', $config->endpoint->primary->host );
		$this->assertEquals( '12345', $config->endpoint->primary->port );
		$this->assertEquals( '/some/fallback', $config->endpoint->primary->path );
	}

	public function testAccessingDisfunctSearchConfiguration() {
		$this->dropDeprecatedConfiguration();

		$config = Opus_Search_Config::getServiceConfiguration( 'search', 'disfunct' );

		$this->assertInstanceOf( 'Zend_Config', $config );
		$this->assertInstanceOf( 'Zend_Config', $config->query );
		$this->assertInstanceOf( 'Zend_Config', $config->query->alldocs );

		$this->assertEquals( 'search', $config->marker );
		$this->assertEquals( '1.2.3.4', $config->endpoint->primary->host );
		$this->assertEquals( '12345', $config->endpoint->primary->port );
		$this->assertEquals( '/solr-disfunct/', $config->endpoint->primary->path );
	}

	public function testAccessingDisfunctIndexConfiguration() {
		$this->dropDeprecatedConfiguration();

		$config = Opus_Search_Config::getServiceConfiguration( 'index', 'disfunct' );

		$this->assertInstanceOf( 'Zend_Config', $config );
		$this->assertInstanceOf( 'Zend_Config', $config->query );
		$this->assertInstanceOf( 'Zend_Config', $config->query->alldocs );

		$this->assertEquals( 'index', $config->marker );
		$this->assertEquals( '1.2.3.4', $config->endpoint->primary->host );
		$this->assertEquals( '12345', $config->endpoint->primary->port );
		$this->assertEquals( '/solr-disfunct/', $config->endpoint->primary->path );
	}

	public function testAccessingDisfunctSearchConfigurationFailsDueToDeprecated() {
		$config = Opus_Search_Config::getServiceConfiguration( 'search', 'disfunct' );

		$this->assertInstanceOf( 'Zend_Config', $config );
		$this->assertInstanceOf( 'Zend_Config', $config->query );
		$this->assertInstanceOf( 'Zend_Config', $config->query->alldocs );

		// deprecated configuration is overlaying newer configuration
		$this->assertNotEquals( '1.2.3.4', $config->endpoint->primary->host );
		$this->assertNotEquals( '12345', $config->endpoint->primary->port );
		$this->assertNotEquals( '/solr-disfunct/', $config->endpoint->primary->path );
	}

	public function testAccessingDisfunctIndexConfigurationFailsDueToDeprecated() {
		$config = Opus_Search_Config::getServiceConfiguration( 'search', 'disfunct' );

		$this->assertInstanceOf( 'Zend_Config', $config );
		$this->assertInstanceOf( 'Zend_Config', $config->query );
		$this->assertInstanceOf( 'Zend_Config', $config->query->alldocs );

		// deprecated configuration is overlaying newer configuration
		$this->assertNotEquals( '1.2.3.4', $config->endpoint->primary->host );
		$this->assertNotEquals( '12345', $config->endpoint->primary->port );
		$this->assertNotEquals( '/solr-disfunct/', $config->endpoint->primary->path );
	}
}
