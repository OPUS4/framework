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
 * @category    Framework
 * @package     Opus
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @copyright   Copyright (c) 2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

class Opus_IdentifierTest extends TestCase {

    private function createDocumentWithIdentifierUrn($urn) {
        $document = new Opus_Document();
        $document->addIdentifier()
                ->setType('urn')
                ->setValue($urn);
        return $document;
    }
    
    /**
     * Check if exactly one document with testUrn exists
     *
     * @param int    $docId
     * @param string $type
     * @param string $value
     */
    private function checkUniqueIdentifierOnDocument($docId, $type, $value) {
        $finder = new Opus_DocumentFinder();
        $finder->setIdentifierTypeValue($type, $value);
        $this->assertEquals(1, $finder->count());
        $this->assertContains($docId, $finder->ids());
    }

    function testCreateDocumentWithUrn() {
        $testUrn = 'nbn:de:kobv:test123';
        $document = $this->createDocumentWithIdentifierUrn($testUrn);
        $docId = $document->store();

        // reload and test
        $document = new Opus_Document($docId);
        $identifiers = $document->getIdentifier();

        $this->assertEquals(1, count($identifiers));
        $this->assertEquals('urn', $identifiers[0]->getType());
        $this->assertEquals($testUrn, $identifiers[0]->getValue());
    }
    
    function testFailDoubleUrnForSameDocument() {
        $testUrn = 'nbn:de:kobv:test123';
        $document = $this->createDocumentWithIdentifierUrn($testUrn);
        $document->addIdentifier()
                ->setType('urn')
                ->setValue('nbn:de:kobv:test123');

        try {
            $document->store();
            $this->fail('expected exception');
        }
        catch (Opus_Identifier_UrnAlreadyExistsException $e) {
        }
    }

    function testCreateUrnCollisionViaDocument() {
        $testUrn = 'nbn:de:kobv:test123';
        $document = $this->createDocumentWithIdentifierUrn($testUrn);
        $docId = $document->store();

        $this->checkUniqueIdentifierOnDocument($docId, 'urn', $testUrn);

        // create second document with testUrn
        $document = $this->createDocumentWithIdentifierUrn($testUrn);
        try {
            $document->store();
            $this->fail('expected exception');
        }
        catch (Opus_Identifier_UrnAlreadyExistsException $e) {
        }
    }

    function testCreateUrnCollisionViaUsingIdentifier() {
        $testUrn = 'nbn:de:kobv:test123';
        $document = $this->createDocumentWithIdentifierUrn($testUrn);
        $docId = $document->store();

        $this->checkUniqueIdentifierOnDocument($docId, 'urn', $testUrn);

        // create second document with testUrn
        $document = new Opus_Document();
        $document->store();

        $document->addIdentifier()
                ->setType('urn')
                ->setValue($testUrn);

        try {
            $document->getIdentifier(0)->store();
            $this->fail('expected exception');
        }
        catch (Opus_Identifier_UrnAlreadyExistsException $e) {
        }
    }

}

