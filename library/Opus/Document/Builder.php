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
 * @package     Opus_Model
 * @author      Henning Gerhardt (henning.gerhardt@slub-dresden.de)
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Builds a document
 *
 */
class Opus_Document_Builder {

    /**
     * Holds type information
     *
     * @var Opus_Document_Type
     */
    private $_type = null;

    /**
     * Contructor of class.
     *
     * @param Opus_Document_Type $type
     * @return void
     */
    public function __construct(Opus_Document_Type $type = null) {
        if (is_null($type) === false) {
            $this->_type = $type;
        }
    }

    /**
     * Create a document from a proper document type
     *
     * @param Opus_Document_Type $type
     * @return Opus_Model_Document
     */
    public function create(Opus_Document_Type $type = null) {
        if ((is_null($type) === true) and (is_null($this->_type) === true)) {
            throw new Opus_Document_Exception('No document type specified.');
        }
        if (is_null($type) === true) {
            $type = $this->_type;
        }
        $document = new Opus_Model_Document();
        return $this->addFieldsTo($document);
    }


    /**
     * Add fields to a document
     *
     * @param Opus_Model_Document $document
     * @return Opus_Model_Document
     */
    public function addFieldsTo(Opus_Model_Document $document) {
        $fieldlist = $this->_type->getFields();
        foreach($fieldlist as $fieldname => $fieldinfo) {
            $field = new Opus_Model_Field($fieldname);
            $field->setMandatory($fieldinfo['mandatory']);
            $field->setMultiplicity($fieldinfo['multiplicity']);
            $field->setLanguageOption($fieldinfo['languageoption']);
            $document->addField($field);
        }
        return $document;
    }
}
