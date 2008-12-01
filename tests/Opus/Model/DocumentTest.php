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
 * @package     Opus_Model
 * @author      Pascal-Nicolas Becker <becker@zib.de>
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */


/**
 * Test cases for class Opus_Model_Document.
 *
 * @package Opus_Model
 * @category Tests
 *
 * @group Document
 *
 */
class Opus_Model_DocumentTest extends PHPUnit_Framework_TestCase {


    /**
     * Test document type.
     *
     * @var String
     */
    protected $_xmlDoctype = 
        '<?xml version="1.0" encoding="UTF-8" ?>
        <documenttype name="doctoral_thesis"
            xmlns="http://schemas.opus.org/documenttype"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
            
            <field name="Language" multiplicity="*" languageoption="off" mandatory="yes" />
            <field name="Licence"/>
            <field name="ContributingCorporation"/>
            <field name="CreatingCorporation"/>
            <field name="ContributingCorporation"/>
            
            <field name="DateAccepted"/>
            <field name="PublishedYear"/>
            <field name="DocumentType"/>
            <field name="Edition"/>
            <field name="Issue"/>
            <field name="NonInstituteAffiliation"/>
            <field name="PageFirst"/>
            <field name="PageLast"/>
            <field name="PageNumber"/>
            <field name="PublicationStatus"/>
            
            <mandatory type="one-at-least">
                <field name="PublishedDate"/>
                <field name="PublishedYear"/>
            </mandatory>
            <mandatory type="one-at-least">
                <field name="CompletedYear"/>
                <field name="CompletedDate"/>
            </mandatory>
            
            <field name="PublisherName"/>
            <field name="PublisherPlace"/>
            <field name="PublisherUniversity"/>
            <field name="Reviewed"/>
            <field name="ServerDateModified"/>
            <field name="ServerDatePublished"/>
            <field name="ServerDateUnlocking"/>
            <field name="ServerDateValid"/>
            <field name="Source"/>
            <field name="SwbId"/>
            <field name="VgWortPixelUrl"/>
            <field name="Volume"/>
            
        </documenttype>';

    
    /**
     * Test fixture document type.
     *
     * @var Opus_Document_Type
     */
    protected $_type = null;
    
    
    /**
     * Set up test fixture.
     *
     * @return void
     */
    public function setUp() {
        $this->_type = new Opus_Document_Type($this->_xmlDoctype);    
    }
    
    /**
     * Test if a Document instance can be serialized.
     *
     * @return void
     */
    public function testSerializing() {
        $doc = new Opus_Model_Document(null, $this->_type);
        $ser = serialize($doc);
    }

    /**
     * Test if a serialized Document instance can be deserialized.
     *
     * @return void
     */
    public function testDeserializing() {
        $doc1 = new Opus_Model_Document(null, $this->_type);
        $ser = serialize($doc1);
        $doc2 = unserialize($ser);
        $this->assertEquals($doc1, $doc2, 'Deserializing unsuccesful.');
    }
    
    
}
