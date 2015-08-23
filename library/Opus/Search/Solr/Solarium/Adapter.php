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

class Opus_Search_Solr_Solarium_Adapter extends Opus_Search_Adapter implements Opus_Search_Indexing,
    Opus_Search_Searching, Opus_Search_Extracting {

	/**
	 * @var Zend_Config
	 */
	protected $options;

	/**
	 * @var \Solarium\Core\Client\Client
	 */
	protected $client;


	public function __construct( $serviceName, $options ) {
		$this->options = $options;
		$this->client  = new Solarium\Client( $options );

		// ensure service is basically available
		$ping = $this->client->createPing();
		$this->execute( $ping, 'failed pinging service ' . $serviceName );
	}

	/**
	 * @param \Solarium\Core\Query\Query $query
	 * @param string $actionText
	 * @return \Solarium\Core\Query\Result\ResultInterface
	 * @throws Opus_Search_Exception
	 * @throws Opus_Search_InvalidQueryException
	 * @throws Opus_Search_InvalidServiceException
	 */
	protected function execute( $query, $actionText ) {
		try {
			$result = $this->client->execute( $query );
		} catch ( \Solarium\Exception\HttpException $e ) {
			$msg = sprintf( '%s: %d %s', $actionText, $e->getCode(), $e->getStatusMessage() );

			if ( $e->getCode() == 404 || $e->getCode() >= 500 ) {
				throw new Opus_Search_InvalidServiceException( $msg, $e->getCode(), $e );
			}

			if ( $e->getCode() == 400 ) {
				throw new Opus_Search_InvalidQueryException( $msg, $e->getCode(), $e );
			}

			throw new Opus_Search_Exception( $msg, $e->getCode(), $e );
		}

		if ( $result->getStatus() ) {
			throw new Opus_Search_Exception( $actionText, $result->getStatus() );
		}

		return $result;
	}

	/**
	 * Maps name of field returned by search engine into name of asset to use
	 * on storing field's value in context of related match.
	 *
	 * This mapping relies on runtime configuration. Mapping is defined per
	 * service in
	 *
	 * @param string $fieldName
	 * @return string
	 */
	protected function mapResultFieldToAsset( $fieldName ) {
		if ( $this->options->fieldToAsset instanceof Zend_Config ) {
			return $this->options->fieldToAsset->get( $fieldName, $fieldName );
		}

		return $fieldName;
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
	 * -- part of Opus_Search_Indexing --
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

				$this->execute( $update, 'failed updating slice of documents' );
			}

			// finally commit all updates
			$update = $this->client->createUpdate();
			$update->addCommit();

			$this->execute( $update, 'failed committing update of documents' );

			return $this;
		} catch ( Opus_Search_Exception $e ) {
			Opus_log::get()->err( $e->getMessage() );

			if ( $this->options->get( 'rollback', 1 ) ) {
				// roll back updates due to failure
				$update = $this->client->createUpdate();
				$update->addRollback();

				try {
					$this->execute( $update, 'failed rolling back update of documents' );
				} catch ( Exception $inner ) {
					// SEVERE case: rolling back failed, too
					Opus_Log::get()->alert( $inner->getMessage() );
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

				$this->execute( $delete, 'failed deleting slice of documents' );
			}

			// finally commit all deletes
			$update = $this->client->createUpdate();
			$update->addCommit();

			$this->execute( $update, 'failed committing deletion of documents' );

			return $this;
		} catch ( Opus_Search_Exception $e ) {
			Opus_log::get()->err( $e->getMessage() );

			if ( $this->options->get( 'rollback', 1 ) ) {
				// roll back deletes due to failure
				$update = $this->client->createUpdate();
				$update->addRollback();

				try {
					$this->execute( $update, 'failed rolling back update of documents' );
				} catch ( Exception $inner ) {
					// SEVERE case: rolling back failed, too
					Opus_Log::get()->alert( $inner->getMessage() );
				}
			}

			throw $e;
		}
	}

	public function removeAllDocumentsFromIndex() {
		$update = $this->client->createUpdate();

		$update->addDeleteQuery( '*:*' );
		$update->addCommit();

		$this->execute( $update, 'failed removing all documents from index' );

		return $this;
	}


	/*
	 *
	 * -- part of Opus_Search_Searching --
	 *
	 */

	public function customSearch( Opus_Search_Query $query ) {
		$search = $this->client->createSelect();

		return $this->processQuery( $this->applyParametersOnQuery( $search, $query, false ) );
	}

	public function namedSearch( $name, Opus_Search_Query $customization = null ) {
		if ( !preg_match( '/^[a-z_]+$/i', $name ) ) {
			throw new Opus_Search_Exception( 'invalid name of pre-defined query: ' . $name );
		}

		// lookup named query in configuration of current service
		if ( isset( $this->options->query->{$name} ) ) {
			$definition = $this->options->query->{$name};
		} else {
			$definition = null;
		}

		if ( !$definition || !( $definition instanceof Zend_Config ) ) {
			throw new Opus_Search_InvalidQueryException( 'selected query is not pre-defined: ' . $name );
		}

		$search = $this->client->createSelect( $definition );

		return $this->processQuery( $this->applyParametersOnQuery( $search, $customization, true ) );
	}

	public function createQuery() {
		return new Opus_Search_Query();
	}

	public function createFilter() {
		return new Opus_Search_Solr_Solarium_Filter_Complex( $this->client );
	}

	/**
	 * Executs prepared query fetching all listed instances of Opus_Document on
	 * success.
	 *
	 * @param \Solarium\QueryType\Select\Query\Query $query
	 * @return Opus_Search_Result_Base
	 * @throws Opus_Search_Exception
	 */
	protected function processQuery( $query ) {
		// send search query to service
		$request = $this->execute( $query, 'failed querying search engine' );

		/** @var \Solarium\QueryType\Select\Result\Result $request */

		// create result descriptor
		$result = Opus_Search_Result_Base::create()
			->setAllMatchesCount( $request->getNumFound() )
			->setQueryTime( $request->getQueryTime() );

		// add description on every returned match
		$excluded = 0;
		foreach ( $request->getDocuments() as $document ) {
			/** @var Solarium\QueryType\Select\Result\Document $document */
			$fields = $document->getFields();

			if ( array_key_exists( 'id', $fields ) ) {
				$match = $result->addMatch( $fields['id'] );

				foreach ( $fields as $fieldName => $fieldValue ) {
					switch ( $fieldName ) {
						case 'id' :
							break;

						case 'score' :
							$match->setScore( $fieldValue );
							break;

						case 'server_date_modified' :
							$match->setServerDateModified( $fieldValue );
							break;

						default :
							$match->setAsset( $this->mapResultFieldToAsset( $fieldName ), $fieldValue );
							break;
					}
				}

			} else {
				$excluded++;
			}
		}

		if ( $excluded > 0 ) {
			Opus_Log::get()->warn( sprintf( 'search yielded %d matches not available in result set for missing ID of related document', $excluded ) );
		}

		// add returned results of faceted search
		$facetResult = $request->getFacetSet();
		if ( $facetResult ) {
			foreach ( $facetResult->getFacets() as $fieldName => $facets ) {
				foreach ( $facets as $value => $occurrences ) {
					$result->addFacet( $fieldName, $value, $occurrences );
				}
			}
		}

		return $result;
	}

	/**
	 * Adjusts provided query depending on explicitly defined parameters.
	 *
	 * @param \Solarium\QueryType\Select\Query\Query $query
	 * @param Opus_Search_Query $parameters
	 * @param bool $preferOriginalQuery true for keeping existing query in $query
	 * @return mixed
	 */
	protected function applyParametersOnQuery( \Solarium\QueryType\Select\Query\Query $query, Opus_Search_Query $parameters = null, $preferOriginalQuery = false ) {
		if ( $parameters ) {

			$subfilters = $parameters->getSubFilters();
			if ( $subfilters !== null ) {
				foreach ( $subfilters as $name => $subfilter ) {
					if ( $subfilter instanceof Opus_Search_Solr_Filter_Raw || $subfilter instanceof Opus_Search_Solr_Solarium_Filter_Complex ) {
						$query->createFilterQuery( $name )
						      ->setQuery( $subfilter->compile( $query ) );
					}
				}
			}

			$filter = $parameters->getFilter();
			if ( $filter instanceof Opus_Search_Solr_Filter_Raw || $filter instanceof Opus_Search_Solr_Solarium_Filter_Complex ) {
				if ( !$query->getQuery() || !$preferOriginalQuery ) {
					$compiled = $filter->compile( $query );
					if ( $compiled !== null ) {
						// compile() hasn't implicitly assigned query before
						$query->setQuery( $compiled );
					}
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

			$facet = $parameters->getFacet();
			if ( $facet !== null ) {
				$facetSet = $query->getFacetSet();
				foreach ( $facet->getFields() as $field ) {
					$facetSet->createFacetField( $field->getName() )
					         ->setField( $field->getName() )
					         ->setMinCount( $field->getMinCount() )
					         ->setLimit( $field->getLimit() );
				}

				if ( $facet->isFacetOnly() ) {
					$query->setFields( array() );
				}
			}

		}

		return $query;
	}


	/*
	 *
	 * -- part of Opus_Search_Extracting --
	 *
	 */

	public function extractDocumentFile( Opus_File $file, Opus_Document $document = null ) {
		Opus_Log::get()->debug( 'extracting fulltext from ' . $file->getPath() );

		try {
			// ensure file is basically available and extracting is supported
			if ( !$file->exists() ) {
				throw new Opus_Storage_FileNotFoundException( $file->getPath() . ' does not exist.' );
			}

			if ( !$file->isReadable() ) {
				throw new Opus_Storage_FileAccessException( $file->getPath() . ' is not readable.' );
			}

			if ( !$this->isMimeTypeSupported( $file ) ) {
				throw new Opus_Search_Exception( $file->getPath() . ' has MIME type ' . $file->getMimeType() . ' which is not supported' );
			}


			// use cached result of previous extraction if available
			$fulltext = Opus_Search_FulltextFileCache::readOnFile( $file );
			if ( $fulltext !== false ) {
				Opus_Log::get()->info( 'Found cached fulltext for file ' . $file->getPath() );
				return $fulltext;
			}


			if ( filesize( $file->getPath() ) ) {

				// query Solr service for extracting fulltext data
				$extract = $this->client->createExtract()
				                        ->setExtractOnly( true )
				                        ->setFile( $file->getPath() )
				                        ->setCommit( true );

				$result = $this->execute( $extract, 'failed extracting fulltext data' );
				/** @var Solarium\QueryType\Extract\Result $response */

				// got response -> extract
				$response = $result->getData();
				$fulltext = null;

				if ( is_array( $response ) ) {
					$keys = array_keys( $response );
					foreach ( $keys as $k => $key ) {
						if ( substr( $key, -9 ) === '_metadata' && array_key_exists( substr( $key, 0, -9 ), $response ) ) {
							unset( $response[$key] );
						}
					}

					$fulltextData = array_shift( $response );
					if ( is_string( $fulltextData ) ) {
						if ( substr( $fulltextData, 0, 6 ) === '<?xml ' ) {
							$dom = new DOMDocument();
							$dom->loadHTML( $fulltextData );
							$body = $dom->getElementsByTagName( "body" )->item( 0 );
							if ( $body ) {
								$fulltext = $body->textContent;
							} else {
								$fulltext = $dom->textContent;
							}
						} else {
							$fulltext = $fulltextData;
						}
					}
				}

				if ( is_null( $fulltext ) ) {
					Opus_Log::get()->err( 'failed extracting fulltext data from solr response' );
					$fulltext = '';
				} else {
					$fulltext = trim( $fulltext );
				}

			} else {
				// empty file -> empty fulltext index
				$fulltext = '';
			}


			// always write returned fulltext data to cache to keep client from
			// re-extracting same file as query has been processed properly this
			// time
			Opus_Search_FulltextFileCache::writeOnFile( $file, $fulltext );

			return $fulltext;

		} catch ( Exception $e ) {
			if ( !( $e instanceof Opus_Search_Exception ) && !( $e instanceof Opus_Storage_Exception ) ) {
				$e = new Opus_Search_Exception( 'error while extracting fulltext from file ' . $file->getPath(), null, $e );
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

		$mimeType = preg_split( '/[;\s]+/', trim( $mimeType ), null, PREG_SPLIT_NO_EMPTY )[0];

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
