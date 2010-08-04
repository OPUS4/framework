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
 * @category    Test
 * @package     Opus_Search
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @author      Sascha Szott <szott@zib.de>
 * @copyright   Copyright (c) 2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Test search indexing.
 *
 * @category   Test
 * @package    Opus_Search
 *
 * @group SearchIndexIndexerTests
 */
class Opus_Search_Index_Solr_IndexerTest extends TestCase
{
    /**
     * @var Opus_Search_Index_Solr_Indexer
     */
    protected $indexer;

    /**
     * @var int
     */
    protected $document_id;

    /**
     * Valid document data.
     *
     * @var array  An array of arrays of arrays. Each 'inner' array must be an
     * associative array that represents valid document data.
     */
    protected static $_validDocumentData = array(
        'Type' => 'article',
        'Language' => 'de',
        'ContributingCorporation' => 'Contributing, Inc.',
        'CreatingCorporation' => 'Creating, Inc.',
        'DateAccepted' => '1901-01-01',
        'Edition' => 2,
        'Issue' => 3,
        'Volume' => 1,
        'PageFirst' => 1,
        'PageLast' => 297,
        'PageNumber' => 297,
        'CompletedYear' => 1960,
        'CompletedDate' => '1901-01-01',
        'ServerDateUnlocking' => '2008-12-01',
        'ServerDateValid' => '2008-12-01',
        'Source' => 'BlaBla',
    );

    /**
     * Valid document data provider
     *
     * @return array
     */
    public static function validDocumentDataProvider() {
        return self::$_validDocumentData;
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        // parent::setUp();
        $this->indexer = new Opus_Search_Index_Solr_Indexer;

        $document = new Opus_Document();
        foreach (self::$_validDocumentData as $fieldname => $value) {
            $callname = 'set' . $fieldname;
            $document->$callname($value);
        }
        $document->store();
        $this->document_id = $document->getId();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        // parent::tearDown();
    }

    /**
     * @todo Implement testAddDocumentToEntryIndex().
     */
    public function testAddDocumentToEntryIndex()
    {
        $document = new Opus_Document($this->document_id);
        $this->indexer->addDocumentToEntryIndex($document);
        $this->indexer->commit();
    }

    /**
     * @todo Implement testRemoveDocumentFromEntryIndex().
     */
    public function testRemoveDocumentFromEntryIndex()
    {
        $document = new Opus_Document($this->document_id);
        $this->indexer->removeDocumentFromEntryIndex($document);
        $this->indexer->commit();

        $this->setExpectedException('InvalidArgumentException');
        $this->indexer->removeDocumentFromEntryIndex(null);
    }

    /**
     * @todo Implement testDeleteAllDocs().
     */
    public function testDeleteAllDocs()
    {
        $this->indexer->deleteAllDocs();
        $this->indexer->commit();
    }

    /**
     * @todo Implement testDeleteDocsByQuery().
     */
    public function testDeleteDocsByQuery()
    {
        $this->indexer->deleteDocsByQuery("*");
        $this->indexer->commit();
    }

    /**
     * @todo Implement testCommit().
     */
    public function testCommit()
    {
        $this->indexer->commit();
    }

    /**
     * @todo Implement testOptimize().
     */
    public function testOptimize()
    {
        $this->indexer->optimize();
    }

}
?>
