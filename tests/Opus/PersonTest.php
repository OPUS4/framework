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
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @copyright   Copyright (c) 2008-2010, OPUS 4 development team
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
class Opus_PersonTest extends TestCase {

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
        parent::setUp();

        // create documents
        for ($i = 0; $i<10; $i++) {
            $doc = new Opus_Document;
            $doc->store();
            $this->_documents[] = $doc;
        }

        for ($i = 0; $i<10; $i++) {
            $p = new Opus_Person;
            $p->setFirstName('Dummy-'.$p)
                ->setLastName('Empty-'.$p)
                ->store();
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
     * Get all documents for a given role.
     *
     * @return void
     */
    public function testGetDocumentsByRole() {
        // TODO: $doc->getPersonAuthor()->getId() gibt nicht die Id eines
        // TODO: Autors zurueck, sondern das Paar (document_id, person_id) aus
        // TODO: der Tabelle link_persons_documents.
        //
        // TODO: Die ID der Person erhält man mit getLinkedModelId()

        foreach ($this->_authors as $author) {
            $docs = $author->getDocumentsByRole('author');
            foreach ($docs as $doc) {
                $this->assertEquals(
                    $doc->getPersonAuthor(0)->getLinkedModelId(),
                    $author->getId(),
                    'Retrieved author is not the author of the document as defined in test data.'
                    );
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