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
 * @package     Opus
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */


/**
 * Test cases for class Opus_Person.
 *
 * @package Opus
 * @category Tests
 *
 * @group PersonTest
 *
 */
class Opus_PersonTest extends PHPUnit_Framework_TestCase {


    /**
     * Test document type.
     *
     * @var string
     */
    private $_xmlDoctype =
        '<?xml version="1.0" encoding="UTF-8" ?>
        <documenttype name="person_test"
            xmlns="http://schemas.opus.org/documenttype"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
            <field name="PersonAdvisor"/>
            <field name="PersonAuthor"/>
            <field name="PersonOther"/>
            <field name="PersonContributor"/>
            <field name="PersonEditor"/>
            <field name="PersonReferee"/>
            <field name="PersonTranslator"/>
        </documenttype>';

    /**
     * List of Opus_Person identifiers having the role Author.
     *
     * @var array
     */
    private $_authors = array();

    /**
     * List of test documents.
     *
     * @var array
     */
    private $_documents = array();

    /**
     * Set up test data documents and persons.
     *
     * @return void
     */
    public function setUp() {
        TestHelper::clearTable('link_persons_documents');
        TestHelper::clearTable('link_documents_collections');
        TestHelper::clearTable('documents');
        TestHelper::clearTable('persons');

        $type = new Opus_Document_Type($this->_xmlDoctype);

        // create documents
        for ($i = 0; $i<10; $i++) {
            $doc = new Opus_Document(null, $type);
            $doc->store();
            $this->_documents[] = $doc;
        }

        // add a person as author to every document
        // and add the person to the list of authors
        foreach ($this->_documents as $document) {
            $p = new Opus_Person;
            $p->setFirstName('Rainer')
                ->setLastName('Zufall')
                ->store();
            $this->_authors[] = $p;
            $document->addPersonAuthor($p);
            $document->store();
        }
    }

    /**
     * Clear out all test data.
     *
     * @return void
     */
    public function tearDown() {
        TestHelper::clearTable('documents');
        TestHelper::clearTable('persons');
        TestHelper::clearTable('link_persons_documents');
    }

    /**
     * Get all documents for a given role.
     *
     * @return void
     */
    public function testGetDocumentsByRole() {
        $this->markTestIncomplete( 'This test must be checked.' );

        // TODO: $doc->getPersonAuthor()->getId() gibt nicht die Id eines
        // TODO: Autors zurueck, sondern das Paar (document_id, person_id) aus
        // TODO: der Tabelle link_persons_documents.

        foreach ($this->_authors as $author) {
            $docs = $author->getDocumentsByRole('author');
            foreach ($docs as $doc) {
                echo "---\n";
                var_dump($doc->getPersonAuthor()->getId());
                var_dump($author->getId());

                $this->assertEquals(
                    $doc->getPersonAuthor()->getId(),
                    $author->getId(),
                    'Retrieved author is not the author of the document as defined in test data.'
                    );

                echo "---\n";
            }
        }
    }

    /**
     * Test if all Person identifer for persons of a given role
     * can be obtained.
     *
     * @return void
     */
    public function testGetAllPersonIdsByRole() {
        $ids = Opus_Person::getAllIdsByRole('author');

        $this->assertTrue(is_array($ids), 'No array returned.');

        foreach ($this->_authors as $author) {
            $this->assertTrue(
                in_array($author->getId(), $ids),
                'Author id not found.');
        }

    }

}