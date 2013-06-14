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
 * @category    Tests
 * @package     Opus_SolrSearch
 * @author      Sascha Szott <szott@zib.de>
 * @copyright   Copyright (c) 2008-2013, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

class Opus_SolrSearch_SearcherTest extends TestCase {

    public function testLatestDocumentsQuery() {
        $rows = 5;
        $ids = array();
        for ($i = 0; $i < $rows; $i++) {
            $document = new Opus_Document();
            $document->setServerState('published');
            $document->store();
            sleep(1);
            array_push($ids, $document->getId());
        }
        
        $query = new Opus_SolrSearch_Query(Opus_SolrSearch_Query::LATEST_DOCS);
        $query->setRows($rows);
        $searcher = new Opus_SolrSearch_Searcher();
        $results = $searcher->search($query);

        $i = $rows - 1;
        foreach ($results->getResults() as $result) {
            $this->assertEquals($ids[$i], $result->getId());
            $i--;
        }
        $this->assertEquals(-1, $i);
    }

    public function testIndexFieldServerDateModifiedIsPresent() {
        $doc = new Opus_Document();
        $doc->setServerState('published');
        $doc->store();

        $id = $doc->getId();
        $doc = new Opus_Document($id);
        $serverDateModified = $doc->getServerDateModified()->getUnixTimestamp();

        $query = new Opus_SolrSearch_Query(Opus_SolrSearch_Query::LATEST_DOCS);
        $query->setRows(1);
        $searcher = new Opus_SolrSearch_Searcher();
        $results = $searcher->search($query);
        
        $this->assertEquals(1, count($results));
        $result = $results->getResults();        
        $this->assertEquals($serverDateModified, $result[0]->getServerDateModified());
    }

    public function testIndexFieldServerDateModifiedIsCorrectAfterModification() {
        $doc = new Opus_Document();
        $doc->setServerState('published');
        $doc->store();
        $id = $doc->getId();

        $query = new Opus_SolrSearch_Query(Opus_SolrSearch_Query::LATEST_DOCS);
        $query->setRows(1);
        $searcher = new Opus_SolrSearch_Searcher();
        $results = $searcher->search($query);

        $doc = new Opus_Document($id);
        $doc->setLanguage('eng');
        $doc->store();

        $doc = new Opus_Document($id);
        $serverDateModified = $doc->getServerDateModified()->getUnixTimestamp();
        
        $this->assertEquals(1, count($results));
        $result = $results->getResults();
        $this->assertLessThan($serverDateModified, $result[0]->getServerDateModified());
    }


}

