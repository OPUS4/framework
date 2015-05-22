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

class Opus_Search_Solr_Solarium_Adapter extends Opus_Search_Adapter implements Opus_Search_Indexable, Opus_Search_Searchable, Opus_Search_Extractable {

	/**
	 * @var Zend_Config
	 */
	protected $options;

	/**
	 * @var \Solarium\Core\Client\Client
	 */
	protected $client;


	public function __construct( $serviceName ) {
		$this->options = Opus_Search_Service::getServiceConfiguration( $serviceName, 'solr' );
		$this->client  = new Solarium\Client( $this->options );

		// ensure service is basically available
		$ping   = $this->client->createPing();

		try {
			$result = $this->client->execute( $ping );
		} catch ( \Exception $e ) {
			throw new Opus_Search_Exception( 'failed pinging service', $e->getCode(), $e );
		}

		if ( $result->getStatus() ) {
			throw new Opus_Search_Exception( 'failed pinging service: ' . $serviceName );
		}
	}


	/*
	 *
	 * -- part of Opus_Search_Adapter --
	 *
	 */

	public function getDomain() {
		return 'solr';
	}


	/*
	 *
	 * -- part of Opus_Search_Indexable --
	 *
	 */

	protected function normalizeDocuments( $documents ) {
		if ( !is_array( $documents ) ) {
			$documents = array( $documents );
		}

		foreach ( $documents as $document ) {
			if ( !( $document instanceof Opus_Document ) ) {
				throw new InvalidArgumentException( "invalid document in provided set" );
			}
		}

		return $documents;
	}

	protected function normalizeDocumentIds( $documentIds ) {
		if ( !is_array( $documentIds ) ) {
			$documentIds = array( $documentIds );
		}

		foreach ( $documentIds as $id ) {
			if ( !$id ) {
				throw new InvalidArgumentException( "invalid document ID in provided set" );
			}
		}

		return $documentIds;
	}

	public function addDocumentsToIndex( $documents ) {
		$documents = $this->normalizeDocuments( $documents );

		$builder = new Opus_Search_Solr_Solarium_Document( $this->options );

		try {
			// split provided set of documents into chunks of 16 documents
			$slices = array_chunk( $documents, $this->options->get( 'updateChunkSize', 16 ) );

			// update documents of every chunk in a separate request
			foreach ( $slices as $slice ) {
				$update = $this->client->createUpdate();

				$updateDocs = array_map( function( $opusDoc ) use ( $builder, $update ) {
					return $builder->toSolrDocument( $opusDoc, $update->createDocument() );
				}, $slice );

				$update->addDocuments( $updateDocs );

				try {
					$result = $this->client->execute( $update );
				} catch ( \Exception $e ) {
					throw new Opus_Search_Exception( 'failed executing query against solr service', $e->getCode(), $e );
				}

				if ( $result->getStatus() ) {
					throw new Opus_Search_Exception( 'failed updating slice of documents: ' . $result->getResponse()->getStatusMessage(), $result->getResponse()->getStatusCode() );
				}
			}

			// finally commit all updates
			$update = $this->client->createUpdate();
			$update->addCommit();

			try {
				$result = $this->client->execute( $update );
			} catch ( \Exception $e ) {
				throw new Opus_Search_Exception( 'failed executing query against solr service', $e->getCode(), $e );
			}

			if ( $result->getStatus() ) {
				throw new Opus_Search_Exception( 'failed commiting update of documents: ' . $result->getResponse()->getStatusMessage(), $result->getResponse()->getStatusCode() );
			}

			return $this;
		} catch ( Opus_Search_Exception $e ) {
			Opus_log::get()->err( $e->getMessage() );

			if ( $this->options->get( 'rollback', 1 ) ) {
				// roll back updates due to failure
				$update = $this->client->createUpdate();
				$update->addRollback();

				try {
					$result = $this->client->execute( $update );
				} catch ( \Exception $inner ) {
					// SEVERE case: rolling back failed, too
					Opus_Log::get()->alert( 'failed rolling back update of documents: ' . $inner->getMessage() );
					throw $e;
				}

				if ( $result->getStatus() ) {
					// SEVERE case: rolling back failed, too
					Opus_Log::get()->alert( 'failed rolling back update of documents: ' . $result->getResponse()->getStatusMessage() );
				}
			}

			throw $e;
		}
	}

	public function removeDocumentsFromIndex( $documents ) {
		$documents = $this->normalizeDocuments( $documents );

		$documentIds = array_map( function( $doc ) {
			/** @var Opus_Document $doc */
			return $doc->getId();
		}, $documents );

		return $this->removeDocumentsFromIndexById( $documentIds );
	}

	public function removeDocumentsFromIndexById( $documentIds ) {
		$documentIds = $this->normalizeDocumentIds( $documentIds );

		try {
			// split provided set of documents into chunks of 128 documents
			$slices = array_chunk( $documentIds, $this->options->get( 'deleteChunkSize', 128 ) );

			// delete documents of every chunk in a separate request
			foreach ( $slices as $deleteIds ) {
				$delete = $this->client->createUpdate();
				$delete->addDeleteByIds( $deleteIds );

				try {
					$result = $this->client->execute( $delete );
				} catch ( \Exception $e ) {
					throw new Opus_Search_Exception( 'failed executing query against solr service', $e->getCode(), $e );
				}

				if ( $result->getStatus() ) {
					throw new Opus_Search_Exception( 'failed deleting slice of documents: ' . $result->getResponse()->getStatusMessage(), $result->getResponse()->getStatusCode() );
				}
			}

			// finally commit all deletes
			$update = $this->client->createUpdate();
			$update->addCommit();

			try {
				$result = $this->client->execute( $update );
			} catch ( \Exception $e ) {
				throw new Opus_Search_Exception( 'failed executing query against solr service', $e->getCode(), $e );
			}

			if ( $result->getStatus() ) {
				throw new Opus_Search_Exception( 'failed commiting update of documents: ' . $result->getResponse()->getStatusMessage(), $result->getResponse()->getStatusCode() );
			}

			return $this;
		} catch ( Opus_Search_Exception $e ) {
			Opus_log::get()->err( $e->getMessage() );

			if ( $this->options->get( 'rollback', 1 ) ) {
				// roll back deletes due to failure
				$update = $this->client->createUpdate();
				$update->addRollback();

				try {
					$result = $this->client->execute( $update );
				} catch ( \Exception $inner ) {
					// SEVERE case: rolling back failed, too
					Opus_Log::get()->alert( 'failed rolling back update of documents: ' . $inner->getMessage() );
					throw $e;
				}

				if ( $result->getStatus() ) {
					// SEVERE case: rolling back failed, too
					Opus_Log::get()->alert( 'failed rolling back update of documents: ' . $result->getResponse()->getStatusMessage() );
				}
			}

			throw $e;
		}
	}

	public function removeAllDocumentsFromIndex() {
		$update = $this->client->createUpdate();

		$update->addDeleteQuery( '*:*' );
		$update->addCommit();

		$result = $this->client->execute( $update );

		if ( $result->getStatus() ) {
			throw new Opus_Search_Exception( 'failed removing all documents from index: ' . $result->getResponse()->getStatusMessage(), $result->getResponse()->getStatusCode() );
		}

		return $this;
	}


	/*
	 *
	 * -- part of Opus_Search_Searchable --
	 *
	 */

	public function customSearch( Opus_Search_Parameters $query ) {
		$search = $this->client->createSelect();

		return $this->processQuery( $this->applyParametersOnQuery( $search, $query ) );
	}

	public function namedSearch( $name, Opus_Search_Parameters $parameters = null ) {
		if ( !preg_match( '/^[a-z_]+$/i', $name ) ) {
			throw new Opus_Search_Exception( 'invalid name of pre-defined query: ' . $name );
		}

		// lookup named query in Solr configuration
		$definitions = Opus_Search_Service::getDomainConfiguration( 'solr' )->query;
		$definition = $definitions->get( $name );
		if ( !$definition ) {
			throw new Opus_Search_Exception( 'selected query is not pre-defined: ' . $name );
		}

		$search = $this->client->createSelect( $definition );

		return $this->processQuery( $this->applyParametersOnQuery( $search, $parameters ) );
	}

	/**
	 * Executs prepared query fetching all listed instances of Opus_Document on
	 * success.
	 *
	 * @param \Solarium\QueryType\Select\Query\Query $query
	 * @return Opus_Search_ResultSet
	 * @throws Opus_Search_Exception
	 */
	protected function processQuery( $query ) {
		$result = $this->client->execute( $query );
		if ( $result->getStatus() ) {
			throw new Opus_Search_Exception( 'failed querying index: ' . $result->getResponse()->getStatusMessage(), $result->getResponse()->getStatusCode() );
		}

		$documents = array();

		foreach ( $result as $match ) {
			try {
				$document    = new Opus_Document( $match->id );
				$documents[] = $document;
			} catch ( Opus_DocumentFinder_Exception $e ) {
				Opus_Log::get()->info( 'skipping matching, but locally missing document #' . $match->id );
			}
		}

		return new Opus_Search_ResultSet( $documents, $result->getNumFound() );
	}

	/**
	 * Adjusts provided query depending on explicitly defined parameters.
	 *
	 * @param \Solarium\QueryType\Select\Query\Query $query
	 * @param Opus_Search_Parameters $parameters
	 * @return mixed
	 */
	protected function applyParametersOnQuery( \Solarium\QueryType\Select\Query\Query $query, Opus_Search_Parameters $parameters = null ) {
		if ( $parameters ) {

			$filters = $parameters->getFilter();
			if ( $filters !== null ) {
				$query->clearFilterQueries();

				foreach ( $filters as $field => $values ) {
					$query->addFilterQuery(
						$query->createFilterQuery()
							->setKey( $field )
							->setQuery( array_shift( $values ) )
					);
				}
			}

			$start = $parameters->getStart();
			if ( $start !== null ) {
				$query->setStart( intval( $start ) );
			}

			$rows = $parameters->getRows();
			if ( $rows !== null ) {
				$query->setRows( intval( $rows ) );
			}

			$union = $parameters->getUnion();
			if ( $union !== null ) {
				$query->setQueryDefaultOperator( $union ? 'OR' : 'AND' );
			}

			$fields = $parameters->getFields();
			if ( $fields !== null ) {
				$query->setFields( $fields );
			}

			$sortings = $parameters->getSort();
			if ( $sortings !== null ) {
				$query->setSorts( $sortings );
			}

		}

		return $query;
	}


	/*
	 *
	 * -- part of Opus_Search_Extractable --
	 *
	 */

	public function extractDocumentFile( Opus_File $file, Opus_Document $document = null ) {
		Opus_Log::get()->debug( 'extracting fulltext from ' . $file->getPath() );

		try {
			// ensure file is basically available and extracting is supported
			if ( !$file->exists() ) {
				throw new Opus_Search_Exception( $file->getPath() . ' does not exist.' );
			}

			if ( !$file->isReadable() ) {
				throw new Opus_Search_Exception( $file->getPath() . ' is not readable.' );
			}

			if ( !$this->isMimeTypeSupported( $file ) ) {
				throw new Opus_Search_Exception( $file->getPath() . ' has MIME type ' . $file->getMimeType() . ' which is not supported' );
			}


			// use cached result of previous extraction if available
			$fulltext = Opus_Search_Solr_FulltextFileCache::readOnFile( $file );
			if ( $fulltext !== false ) {
				Opus_Log::get()->info( 'Found cached fulltext for file ' . $file->getPath() );
				return $fulltext;
			}


			// query Solr service for extracting fulltext data
			$extract = $this->client->createExtract();
			$extract->setExtractOnly( true );
			$extract->setFile( $file->getPath() );

			$result = $this->client->execute( $extract );
			if ( $result->getStatus() ) {
				throw new Opus_Search_Exception( 'Extracting fulltext data failed: ' . $result->getResponse()->getStatusMessage(), $result->getResponse()->getStatusCode() );
			}

			// got response -> extract
			$response = $result->getData();
			if ( array_key_exists( '', $response ) ) {
				$fulltext = trim( $response[''] );
			} else {
				$fulltext = '';
			}

			// always write returned fulltext data to cache to keep client from
			// re-extracting same file as query has been processed properly this
			// time
			Opus_Search_Solr_FulltextFileCache::writeOnFile( $file, $fulltext );

			return $fulltext;

		} catch ( Exception $e ) {
			if ( !( $e instanceof Opus_Search_Exception ) ) {
				$e = new Opus_Search_Exception( 'error while extracting fulltext from file ' . $file->getPath(), 1, $e );
			}

			Opus_Log::get()->err( $e->getMessage() );
			throw $e;
		}
	}

	/**
	 * Detects if provided file has MIME type supported for extracting fulltext
	 * data.
	 *
	 * @param Opus_File $file
	 * @return bool
	 */
	protected function isMimeTypeSupported( Opus_File $file ) {
		$mimeType = $file->getMimeType();

		if ( $mimeType ) {
			$supported = $this->options->get( "supportedMimeType", array(
				'text/html',
				'text/plain',
				'application/pdf',
				'application/postscript',
				'application/xhtml+xml',
				'application/xml',
			) );

			return in_array( $mimeType, (array) $supported );
		}

		return false;
	}
}
