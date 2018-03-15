<?php
/**
 * This file is part of OPUS. The software OPUS has been originally developed
 * at the University of Stuttgart with funding from the German Research Net,
 * the Federal Department of Higher Education and Research and the Ministry
 * of Science, Research and the Arts of the State of Baden-Wuerttemberg.
 *
 * OPUS 4 is a complete rewrite of the original OPUS software and was developed
 * by the Stuttgart University Library, the Library Service Center
 * Baden-Wuerttemberg, the Cooperative Library Network Berlin-Brandenburg,
 * the Saarland University and State Library, the Saxon State Library -
 * Dresden State and University Library, the Bielefeld University Library and
 * the University Library of Hamburg University of Technology with funding from
 * the German Research Foundation and the European Regional Development Fund.
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
 * @category    Framework
 * @package     Opus_Search_Util
 * @author      Sascha Szott <szott@zib.de>
 * @copyright   Copyright (c) 2008-2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

class Opus_Search_Util_Searcher
{

    /*
     * Holds numbers of facets
     */
    private $facetArray;



    public function  __construct() {}

    /**
     *
     * @param Opus_Search_Util_Query $query
     * @param bool $validateDocIds check document IDs coming from Solr index against database
     * @return Opus_Search_Result_Base
     * @throws Opus_Search_Exception If Solr server responds with an error or the response is empty.
     */
    public function search($query, $validateDocIds = true) {

        try {
            Opus_Log::get()->debug("query: " . $query->getQ());

	        // get service adapter for searching
	        $service = Opus_Search_Service::selectSearchingService( null, 'solr' );

	        // basically create query
	        $request = $service->createQuery()
		        ->setFilter( new Opus_Search_Solr_Filter_Raw( $query->getQ() ) )
		        ->setStart( $query->getStart() )
		        ->setRows( $query->getRows() );


	        switch ( $query->getSearchType() ) {
		        case Opus_Search_Util_Query::LATEST_DOCS :
			        $request
				        ->addSorting( $query->getSortField(), $query->getSortOrder() );

		        case Opus_Search_Util_Query::DOC_ID :
					if ( $query->isReturnIdsOnly() ) {
						$request
							->setFields( 'id' );
					} else {
						$request
							->setFields( array( '*', 'score' ) );
					}
			        break;

		        case Opus_Search_Util_Query::FACET_ONLY :
					$facet = Opus_Search_Facet_Set::create()
						->setFacetOnly();

					$facet->addField( $query->getFacetField() )
						->setMinCount( 1 )
						->setLimit( -1 );

					$request->setFacet( $facet );
		            break;

		        default :
					$request->addSorting( $query->getSortField(), $query->getSortOrder() );

			        if ( $query->isReturnIdsOnly() ) {
				        $request
					        ->setFields( 'id' );
			        } else {
				        $request
					        ->setFields( array( '*', 'score' ) );

				        $facet = Opus_Search_Facet_Set::create();

				        if ( isset( $this->facetArray ) ) {
					        $facet->overrideLimits( $this->facetArray );
				        }

		                $fields = Opus_Search_Config::getFacetFields( $facet->getSetName(), 'solr' );
				        if ( empty( $fields ) ) {
					        // no facets are being configured
					        Opus_Log::get()->warn("Key searchengine.solr.facets is not present in config. No facets will be displayed.");
				        } else {
					        $request->setFacet( $facet->setFields( $fields ) );
				        }
	                }


			        $fq = $query->getFilterQueries();

			        if ( !empty( $fq ) ) {
				        foreach ( $fq as $index => $sub ) {
					        $request->setSubFilter( "fq$index", new Opus_Search_Solr_Filter_Raw( $sub ) );
				        }
			        }
	        }

	        $response = $service->customSearch( $request );

	        if ( $validateDocIds ) {
		        $response->dropLocallyMissingMatches();
	        }

	        return $response;
        }
        catch ( Opus_Search_InvalidServiceException $e ) {
	        return $this->mapException( Opus_Search_Exception::SERVER_UNREACHABLE, $e );
        }
        catch( Opus_Search_InvalidQueryException $e ) {
	        return $this->mapException( Opus_Search_Exception::INVALID_QUERY, $e );
        }
	    catch ( Exception $e ) {
		    return $this->mapException( null, $e );
	    }
    }

	/**
	 * @param mixed $type
	 * @param Exception $previousException
	 * @throws Opus_Search_Exception
	 * @return no-return
	 */
	private function mapException( $type, Exception $previousException ) {
		$msg = 'Solr server responds with an error ' . $previousException->getMessage();
		Opus_Log::get()->err($msg);

		throw new Opus_Search_Exception($msg, $type, $previousException);
	}

    public function setFacetArray($array) {
        $this->facetArray = $array;
    }
}

