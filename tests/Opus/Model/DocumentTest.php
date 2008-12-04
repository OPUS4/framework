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
 * @author      Felix Ostrowski (ostrowski@hbz-nrw.de)
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

    /**
     * Valid document data.
     *
     * @var array  An array of arrays of arrays. Each 'inner' array must be an 
     * associative array that represents valid document data.
     */
    protected static $_validDocumentData = array(
            array(
                array(
                    'Language' => 'de',
                    'Licence' => null,
                    'ContributingCorporation' => 'Contributing, Inc.',
                    'CreatingCorporation' => 'Creating, Inc.',
                    'DateAccepted' => '1901-01-01',
                    'PublishedYear' => '1901',
                    'Edition' => 2,
                    'Issue' => 3,
                    'Volume' => 1,
                    'NonInstituteAffiliation' => 'Wie bitte?',
                    'PageFirst' => 1,
                    'PageLast' => 297,
                    'PageNumber' => 297,
                    'PublicationStatus' => 1,
                    'PublishedDate' => '1901-01-01',
                    'CompletedYear' => 1960,
                    'CompletedDate' => '1901-01-01',
                    'PublisherName' => 'Some Publisher',
                    'PublisherPlace' => 'Somewhere',
                    'PublisherUniversity' => 1,
                    'Reviewed' => 'peer',
                    'ServerDateModified' => '2008-12-01 00:00:00',
                    'ServerDatePublished' => '2008-12-01 00:00:00',
                    'ServerDateUnlocking' => '2008-12-01',
                    'ServerDateValid' => '2008-12-01',
                    'Source' => 'BlaBla',
                    'SwbId' => '1',
                    'VgWortPixelUrl' => 'http://geht.doch.eh.nicht',
                )
            )
        );

    /**
     * Valid document data provider
     * @return array
     */
    public static function validDocumentDataProvider() {
        return self::$_validDocumentData;
    }


    /**
     * Test if a document's fields come out of the database as they went in.
     *
     * @dataProvider validDocumentDataProvider
     */
    public function testDocumentFieldsPersistDatabaseStorage($documentDataset) {
        Opus_Document_Type::setXmlDoctypePath(dirname(__FILE__));
        $document = new Opus_Model_Document(null, 'article');
        foreach ($documentDataset as $fieldname => $value) {
            $callname = 'set' . $fieldname;
            $document->$callname($value);
        }
        $document->setDocumentType('article');
        $document->getTitleMain()->setTitleAbstractValue('Title');
        $document->getTitleMain()->setTitleAbstractLanguage('de');
        $document->getTitleAbstract()->setTitleAbstractValue('Abstract');
        $document->getTitleAbstract()->setTitleAbstractLanguage('fr');
        $document->getTitleParent()->setTitleAbstractValue('Parent');
        $document->getTitleParent()->setTitleAbstractLanguage('en');
        $document->getIsbn()->setIdentifierValue('123-123-123');
        $document->getIsbn()->setIdentifierLabel('label');

        $document = new Opus_Model_Document($document->store());
        foreach ($documentDataset as $fieldname => $value) {
            $this->assertEquals($value, $document->{'get'.$fieldname}(), "Field $fieldname was changed by database.");
        }
    }

}
