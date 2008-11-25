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
 * @author      Felix Ostrowski (ostrowski@hbz-nrw.de)
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Domain model for documents in the Opus framework
 *
 * @category    Framework
 * @package     Opus_Model
 * @uses        Opus_Model_Abstract
 */
class Opus_Model_Document extends Opus_Model_Abstract
{
    protected $_documentTitleAbstractTableRow;
    protected $_builder;

    public function __construct(Opus_Document_Builder $builder, $id = null) {
        $this->_builder = $builder;
        parent::__construct(new Opus_Db_Documents, $id);
        if ($this->getId() !== null) {
            // Bestehende Zeile einlesen
            $this->_documentTitleAbstractTableRow =
                $this->_primaryTableRow->findDependentRowset('Opus_Db_DocumentTitleAbstracts')->getRow(0);
        } else {
            // Neue Zeile anlegen.
            $titleAbstract = new Opus_Db_DocumentTitleAbstracts;
            $this->_documentTitleAbstractTableRow = $titleAbstract->createRow();
        }
        parent::_fetchValues();
    }
    
    protected function _init() {
        $this->_builder->addFieldsTo($this);
    }
    
    protected $_externalFields = array('TitleMain', 'Authors');

    protected function _storeTitleMain($value) {
        $this->_documentTitleAbstractTableRow->documents_id = $this->getId();
        $this->_documentTitleAbstractTableRow->title_abstract_type = 'parent';
        $this->_documentTitleAbstractTableRow->title_abstract_value = $value['value'];
        $this->_documentTitleAbstractTableRow->title_abstract_language = $value['language'];
        $this->_documentTitleAbstractTableRow->save();
    }
    
    protected function _fetchTitleMain() {
        $result['value'] = $this->_documentTitleAbstractTableRow->title_abstract_value;
        $result['language'] = $this->_documentTitleAbstractTableRow->title_abstract_language;
        return $result;
    }

    protected function _storeAuthors(array $personIds) {
        if ($this->getId() === null) {
            throw new Opus_Model_Exception('Document not persisted yet.');
        }
        foreach ($personIds as $personId) {
            $linkRow = new Opus_Db_LinkDocumentsPersons();
            $personLink = $linkRow->createRow();
            $personLink->documents_id = $this->getId();
            $personLink->persons_id = $personId;
            $personLink->institutes_id = 0;
            $personLink->role = 'author';
            $personLink->save();
        }
    }

    protected function _fetchAuthors() {
        $authorIds = array();
        foreach ($this->getPersonsByRole('author') as $author) {
            $authorIds[] = $author->getId();
        }
        return $authorIds;
    }

    protected function _fetchValues() {
    }


    public function getPersonsByRole($role) {
        $personsDb = new Opus_Db_Persons();
        $persons = array();
        $select = $personsDb->select();
        $select->where('role=?', $role);
        foreach ($this->_primaryTableRow->findManyToManyRowset('Opus_Db_Persons',
                'Opus_Db_LinkDocumentsPersons', null, null, $select) as $person) {
            $persons[] = new Opus_Model_Person($person->persons_id);
        }
        return $persons;
    }
}
