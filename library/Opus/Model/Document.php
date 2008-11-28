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
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
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
    /**
     * The table row that holds the documents title information
     *
     * @var Zend_Db_TableRow
     */
    protected $_documentTitleAbstractTableRow;

    /**
     * The document is the most complex Opus_Model. An Opus_Document_Builder is
     * used in the _init() function to construct an Opus_Model_Document of a
     * certain type.
     *
     * @var Opus_Document_Builder
     */
    protected $_builder;

    /**
     * The documents external fields, i.e. those not mapped directly to the
     * Opus_Db_Documents table gateway.
     *
     * @var array
     * @see Opus_Model_Abstract::$_externalFields
     */
    protected $_externalFields = array('TitleMain', 'Authors', 'Licence', 'Isbn', 'Institute',
            'PersonAuthor');

    /**
     * Constructor.
     *
     * @param Opus_Document_Builder $builder
     * @param int|string $id
     * @param Zend_Db_Table $tableGatewayModel
     * @see Opus_Model_Abstract::__construct()
     * @see $_builder
     */
    public function __construct($id = null, Opus_Document_Type $type = null, $tableGatewayModel = null) {
        if ($id === null and $type === null) {
            throw new Opus_Model_Exception('Either id or type must be passed.');
        }
        if ($tableGatewayModel === null) {
            parent::__construct($id, new Opus_Db_Documents);
        } else {
            parent::__construct($id, $tableGatewayModel);
        }
        if ($id === null) {
            $this->_builder = new Opus_Document_Builder(new Opus_Document_Type($type));
        } else if ($type === null) {
            $this->_builder = new Opus_Document_Builder(new
                    Opus_Document_Type($this->_primaryTableRow->document_type));
        }
        if ($this->getId() !== null) {
            // TODO: Make this work as a 1:m relationship (i.e. handle rowset, not row)
            $this->_documentTitleAbstractTableRow =
                $this->_primaryTableRow->findDependentRowset('Opus_Db_DocumentTitleAbstracts')->getRow(0);
        } else {
            // TODO: Make this work as a 1:m relationship (i.e. handle rowset, not row)
            $titleAbstract = new Opus_Db_DocumentTitleAbstracts;
            $this->_documentTitleAbstractTableRow = $titleAbstract->createRow();
        }
        $this->_builder->addFieldsTo($this);
        parent::_fetchValues();
    }


    /**
     * TODO: short description.
     *
     * @return TODO
     */
    public function setBuilder() {
    }

    /**
     * TODO: short description.
     * 
     * @return TODO
     */
    public function getBuilder() {
    }

    /**
     * TODO: short description.
     *
     * @return TODO
     */
    public function setType() {

    }

    /**
     * TODO: short description.
     *
     * @return TODO
     */
    public function getType() {

    }

    /**
     * Store values of external field TitleMain
     *
     * @see Opus_Model_Abstract::$_externalFields
     */
    protected function _storeTitleMain($value) {
        $this->_documentTitleAbstractTableRow->documents_id = $this->getId();
        $this->_documentTitleAbstractTableRow->title_abstract_type = 'parent';
        $this->_documentTitleAbstractTableRow->title_abstract_value = $value['value'];
        $this->_documentTitleAbstractTableRow->title_abstract_language = $value['language'];
        $this->_documentTitleAbstractTableRow->save();
    }

    /**
     * Fetch values of external field TitleMain
     *
     * @see Opus_Model_Abstract::$_externalFields
     */
    protected function _fetchTitleMain() {
        $result['value'] = $this->_documentTitleAbstractTableRow->title_abstract_value;
        $result['language'] = $this->_documentTitleAbstractTableRow->title_abstract_language;
        return $result;
    }

    /**
     * TODO: Implement mock up!
     *
     */
    protected function _fetchLicence() {
        $result['value'] = 'Licence';
        $result['language'] = 'de';
        return $result;
    }


    /**
     * TODO: Implement mock up!
     *
     */
    protected function _fetchIsbn() {
        $result['value'] = '978-3-12-514906-9';
        $result['language'] = 'de';
        return $result;
    }

    /**
     * TODO: Implement mock up!
     *
     */
    protected function _fetchInstitute() {
        $result['value'] = 'Institut für Dequalifizierung';
        $result['language'] = 'de';
        return $result;
    }

    /**
     * Store values of external field Authors
     *
     * @see Opus_Model_Abstract::$_externalFields
     */
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

    /**
     * Store values of external field Authors
     *
     * @see Opus_Model_Abstract::$_externalFields
     */
    protected function _fetchAuthors() {
        $authorIds = array();
        foreach ($this->getPersonsByRole('author') as $author) {
            $authorIds[] = $author->getId();
        }
        return $authorIds;
    }

    /**
     * Opus_Model_Document has extensive database initialization to do. Thus,
     * _fetchValues() ist overwritten and parent::_fetchValue() is called at the
     * right time.
     *
     * @see __construct()
     */
    protected function _fetchValues() {
    }

    /**
     * Fetches all persons associated to the document by a certain role.
     *
     * @param string $role
     * @return array An array of Opus_Model_Person
     */
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
