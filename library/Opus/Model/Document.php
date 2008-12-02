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
    protected $_externalFields = array(
            'TitleMain',
            'TitleAbstract',
            'Licence',
            'Isbn',
            'PersonAuthor',
        );

    /**
     * Constructor.
     *
     * @param int|string $id
     * @param string $type
     * @param Zend_Db_Table $tableGatewayModel
     * @see Opus_Model_Abstract::__construct()
     * @see $_builder
     */
    public function __construct($id = null, $type = null, $tableGatewayModel = null) {
        if ($id === null and $type === null) {
            throw new Opus_Model_Exception('Either id or type must be passed.');
        }

        if ($tableGatewayModel === null) {
            parent::__construct($id, new Opus_Db_Documents);
        } else {
            parent::__construct($id, $tableGatewayModel);
        }

        if ($id === null) {
            if (is_string($type)) {
                $this->_builder = new Opus_Document_Builder(new Opus_Document_Type($type));
            } elseif ($type instanceof Opus_Document_Type) {
                $this->_builder = new Opus_Document_Builder($type);
            } else {
                throw new Opus_Model_Exception('Unkown document type.');
            }
        } else if ($type === null) {
            $this->_builder = new Opus_Document_Builder(new
                    Opus_Document_Type($this->_primaryTableRow->document_type));
        }

        $this->_builder->addFieldsTo($this);
        parent::_fetchValues();
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
     * Set the type for the document.
     *
     * @param string $type
     */
    /* FIXME: Currently destroys all field values!
    public function setDocumentType($type) {
        $this->_builder = new Opus_Document_Builder(new Opus_Document_Type($type));
        $this->_fields['DocumentType'] = $type;
        // TODO: Remove and restore old field values
        $this->_builder->addFieldsTo($this);
        parent::_fetchValues();
    }
    */

    /**
     * Store values of external field TitleMain
     *
     * @param array $value Associative array containing 'value' and 'language'.
     *
     * @see Opus_Model_Abstract::$_externalFields
     */
    protected function _storeTitleMain($value) {
        $data['title_abstract_type'] = 'main';
        $data['title_abstract_value'] = $value['value'];
        $data['title_abstract_language'] = $value['language'];
        $this->_addDependentRowsToTable(new Opus_Db_DocumentTitleAbstracts, $data);
    }

    /**
     * Fetch values of external field TitleMain
     *
     * @see Opus_Model_Abstract::$_externalFields
     */
    protected function _fetchTitleMain() {
        $rows = $this->_getDependentRowsFromTable(new Opus_Db_DocumentTitleAbstracts,
                'title_abstract_type', 'main');
        $result = array();
        if (count($rows) === 1) {
            $result['value'] = $rows[0]['title_abstract_value'];
            $result['language'] = $rows[0]['title_abstract_language'];
        } elseif (count($rows) > 1) {
            foreach ($rows as $i => $row) {
                $result[$i]['value'] = $row['title_abstract_value'];
                $result[$i]['language'] = $row['title_abstract_language'];
            }
        }
        return $result;
    }

    /**
     * Store values of external field TitleAbstract
     *
     * @param array $value Associative array containing 'value' and 'language'.
     *
     * @see Opus_Model_Abstract::$_externalFields
     */
    protected function _storeTitleAbstract($value) {
        $data['title_abstract_type'] = 'abstract';
        $data['title_abstract_value'] = $value['value'];
        $data['title_abstract_language'] = $value['language'];
        $this->_addDependentRowsToTable(new Opus_Db_DocumentTitleAbstracts, $data);
    }

    /**
     * Fetch values of external field TitleAbstract
     *
     * @see Opus_Model_Abstract::$_externalFields
     */
    protected function _fetchTitleAbstract() {
        $rows = $this->_getDependentRowsFromTable(new Opus_Db_DocumentTitleAbstracts,
                'title_abstract_type', 'abstract');
        $result = array();
        if (count($rows) === 1) {
            $result['value'] = $rows[0]['title_abstract_value'];
            $result['language'] = $rows[0]['title_abstract_language'];
        } elseif (count($rows) > 1) {
            foreach ($rows as $i => $row) {
                $result[$i]['value'] = $row['title_abstract_value'];
                $result[$i]['language'] = $row['title_abstract_language'];
            }
        }
        return $result;
    }

    /**
     * Store values of external field Authors
     *
     * @see Opus_Model_Abstract::$_externalFields
     */
    protected function _storePersonAuthor(array $personIds) {
        if ($this->getId() === null) {
            throw new Opus_Model_Exception('Document not persisted yet.');
        }
        foreach ($personIds as $personId) {
            $this->addPersonByRole(new Opus_Model_Person($personId), 'author');
        }
    }

    /**
     * Store values of external field Authors
     *
     * @see Opus_Model_Abstract::$_externalFields
     */
    protected function _fetchPersonAuthor() {
        $authorIds = array();
        foreach ($this->getPersonsByRole('author') as $author) {
            $authorIds[] = $author->getId();
        }
        return $authorIds;
    }

    /**
     * Store values of external field Advisors
     *
     * @see Opus_Model_Abstract::$_externalFields
     */
    protected function _storePersonAdvisor(array $personIds) {
        if ($this->getId() === null) {
            throw new Opus_Model_Exception('Document not persisted yet.');
        }
        foreach ($personIds as $personId) {
            $this->addPersonByRole(new Opus_Model_Person($personId), 'advisor');
        }
    }

    /**
     * Fetch values of external field Authors
     *
     * @see Opus_Model_Abstract::$_externalFields
     */
    protected function _fetchPersonAdvisor() {
        $advisorIds = array();
        foreach ($this->getPersonsByRole('advisor') as $advisor) {
            $advisorIds[] = $advisor->getId();
        }
        return $advisorIds;
    }

    /**
     * Store values of external field Licence
     *
     * @see Opus_Model_Abstract::$_externalFields
     */
    protected function _storeLicence($value) {
        $this->_primaryTableRow->licences_id = $value;
    }

    /**
     * Fetch values of external field Licence
     *
     * @see Opus_Model_Abstract::$_externalFields
     */
    protected function _fetchLicence() {
        return $this->_primaryTableRow->licences_id;
    }

    /**
     * Fetch values of external field Isbn.
     *
     * @param array $value Associative array containing 'value' and 'label'.
     *
     */
    protected function _storeIsbn($value) {
        $row['documents_id'] = $this->getId();
        $row['identifier_type'] = 'isbn';
        $row['identifier_label'] = $value['label'];
        $row['identifier_value'] = $value['value'];
        $this->_addDependentRowsToTable(new Opus_Db_DocumentIdentifiers, $row);
    }

    /**
     * Fetch values of external field Isbn.
     *
     */
    protected function _fetchIsbn() {
        $rows = $this->_getDependentRowsFromTable(new Opus_Db_DocumentIdentifiers, 'identifier_type', 'isbn');
        $result = array();
        if (count($rows) === 1) {
            $result['value'] = $rows[0]['identifier_value'];
            $result['label'] = $rows[0]['identifier_label'];
        } elseif (count($rows) > 1) {
            foreach ($rows as $i => $row) {
                $result[$i]['value'] = $row['identifier_value'];
                $result[$i]['label'] = $row['identifier_label'];
            }
        }
        return $result;
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

    /**
     * Adds a person associated to the document by a certain role.
     *
     * @param   string  $role
     * @param Opus_Model_Person  $person
     * @return  string  void
     */
    public function addPersonByRole($person, $role) {
        if ($this->getId() === null) {
            throw new Opus_Model_Exception('Document not persisted yet.');
        }
        if (in_array($person, $this->getPersonsByRole($role)) === false) {
            $table = new Opus_Db_LinkDocumentsPersons();
            $personLink = $table->createRow();
            $personLink->documents_id = $this->getId();
            $personLink->persons_id = $person->getId();
            $personLink->institutes_id = 0;
            $personLink->role = $role;
            $personLink->save();
        }
    }

    /**
     * Get dependent entries from another table.
     *
     * @param Zend_Db_Table $table
     * @param string $where A query in the form 'column=?'.
     * @param string $value The value that the ? in $where will be replaced with.
     *
     * @returns array $rows The matching table rows as an associative array.
     */
    protected function _getDependentRowsFromTable(Zend_Db_Table $table, $where = null, $value = null) {
        if ($this->getId() === null) {
            return null;
        }
        if (is_null($where === false) and is_null($value === false)) {
            $select = $table->select();
            $select->where($where, $value);
            $rows = $this->_primaryTableRow->findDependentRowset(get_class($table), null, $select)->toArray();
        } else {
            $rows = $this->_primaryTableRow->findDependentRowset(get_class($table))->toArray();
        }
        return $rows;
    }

    /** Update dependent entries in another table.
     *
     * @param Zend_Db_Table $table
     * @param array $data
     *
     */
    protected function _addDependentRowsToTable(Zend_Db_Table $table, array $data) {
        if ($this->getId() === null) {
            throw new Opus_Model_Exception('Document not persisted yet.');
        }
        $where = $table->getAdapter()->quoteInto('documents_id=?', $this->getId());
        $table->delete($where);
        $data['documents_id'] = $this->getId();
        $table->insert($data);
    }
    
    
    /**
     * Reconnect primary table row to database after unserializing.
     *
     * @return void
     */
    public function __wakeup() {
        $tableclass = $this->_primaryTableRow->getTableClass();
        $this->_primaryTableRow->setTable(new $tableclass);
    }

}
