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
 * @author      Pascal-Nicolas Becker <becker@zib.de>
 * @author      Felix Ostrowski (ostrowski@hbz-nrw.de)
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Domain model for titles in the Opus framework
 *
 * @category    Framework
 * @package     Opus
 * @uses        Opus_Model_Abstract
 */
class Opus_Privilege extends Opus_Model_Dependent_Abstract
{
    /**
     * Primary key of the parent model.
     *
     * @var mixed $_parentId.
     */
    protected $_parentColumn = 'role_id';

    /**
     * Specify then table gateway.
     *
     * @var string Classname of Zend_DB_Table to use if not set in constructor.
     */
    protected static $_tableGatewayClass = 'Opus_Db_Privileges';

    /**
     * The privileges external fields.
     *
     * @var array
     * @see Opus_Model_Abstract::$_externalFields
     */
    protected $_externalFields = array(
        'Role' => array(
            'model' => 'Opus_Role',
            'fetch' => 'lazy',
        ),
        'File' => array(
            'model' => 'Opus_File',
            'fetch' => 'lazy',
        )
    );

    /**
     * Initialize model with the following fields:
     * - Privilege
     * - DocumentServerState
     * - File
     *
     * @return void
     */
    protected function _init() {
        $privilege = new Opus_Model_Field('Privilege');
        $privilege->setDefault(array(
                    'administrate' => 'administrate',
                    'clearance' => 'clearance',
                    'remotecontrol' => 'remotecontrol',
                    'publish' => 'publish',
                    'publishUnvalidated' => 'publishUnvalidated',
                    'readMetadata' => 'readMetadata',
                    'readFile' => 'readFile'))
            ->setSelection(true);
        $this->addField($privilege);

        $documentServerState = new Opus_Model_Field('DocumentServerState');
        $documentServerState->setDefault(array(
                    'published' => 'published',
                    'unpublished' => 'unpublished',
                    'deleted' => 'deleted'))
            ->setSelection(true);
        $this->addField($documentServerState);

        $this->addField(new Opus_Model_Field('FileId'));

        $this->addField(new Opus_Model_Field('Role'));
        $this->addField(new Opus_Model_Field('File'));

    }

    /**
     * Internal method to populate external field.
     */
    protected function _fetchRole() {
        // if role_id is empty, it returns a new Opus_Role.
        return new Opus_Role($this->_primaryTableRow->role_id);
    }

    /**
     * Internal method to store external field to model.
     */
    protected function _storeRole($role) {
    }

    /**
     * Do not use this method. Privileges contains roles only for convenience!
     * Opus_Privilege is a dependent model. Do not add a role to a privilege,
     * add a privilege to a role!
     *
     * @param mixed $role
     */
    public function addRole($role) {
        $message = 'Opus_Privilege is a dependent Model.'
                   .' Add a privilege to a role, not a role to a privilege!';
        throw new Opus_Model_Exception($message);
    }

    /**
     * Do not use this method. Privileges contains roles only for convenience!
     * Opus_Privilege is a dependent model. Do not set roles to privileges,
     * set privileges to roles!
     *
     * @param mixed $role
     */
    public function setRole($role) {
        $message = 'Opus_Privilege is a dependent Model.'
                   .' Set privileges to roles, not roles to privileges!';
        throw new Opus_Model_Exception($message);
    }

    /**
     * Internal method to populate external field.
     */
    protected function _fetchFile() {
        // if file_id is empty, it returns a new Opus_File.
        return new Opus_File($this->_primaryTableRow->file_id);
    }

    /**
     * Internal method to store external field to model.
     */
    protected function _storeFile($file) {
        $file = is_array($file) ? $file : array($file);
        if (count($file) > 1) {
            throw new Opus_Model_Exception
                ('Opus_Privilege can store a link to one file only. Got more then one File');
        } else if (count($file) === 1 && false === is_null($file[0])) {
            $this->_primaryTableRow->file_id = $file[0]->getId();
        } else {
            $this->_primaryTableRow->file_id = null;
        }
    }

    public function store() {
        if ($this->getPrivilege() === 'readFile') {
            $file = $this->getFile();
            if ($file instanceof Opus_File) {
                $this->_primaryTableRow->file_id = $file->getId();
            } else if (true === is_null($file)) {
                throw new Opus_Model_Exception
                    ('You are trying to store a readFile privilege without an file_id! Which File should be readable?');
            } else {
                throw new IllegalArgumentException('Expected an Opus_File, got anything else.');
            }
        }
        parent::store();
    }

    public static function fetchPrivilegeIdsByFile($file) {
        if (false === isset($file)) {
            return;
        }
        $file_id = $file instanceof Opus_File ? $file->getId() : $file;

        $table = Opus_Db_TableGateway::getInstance(self::$_tableGatewayClass);
        $select = $table->select()->from($table, array('id' => 'id'))->where('file_id = ?', $file_id);
        $rows = $table->fetchAll($select);

        $result = array();
        foreach ($rows as $row) {
            $result[] = $row['id'];
        }
        return $result;
    }
}
