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
 * @author      Felix Ostrowski (ostrowski@hbz-nrw.de)
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @copyright   Copyright (c) 2008-2011, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Domain model for licences in the Opus framework
 *
 * @category    Framework
 * @package     Opus
 * @uses        Opus_Model_Abstract
 */
class Opus_UserRole extends Opus_Model_AbstractDb {

    /**
     * Specify then table gateway.
     *
     * @var string Classname of Zend_DB_Table to use if not set in constructor.
     */
    protected static $_tableGatewayClass = 'Opus_Db_UserRoles';

    /**
     * List of pending AccessModule actions.
     *
     * @var array
     */
    private $_pendingAccessModules = array();

    /**
     * Retrieve all Opus_Db_UserRoles instances from the database.
     *
     * @return array Array of Opus_UserRole objects.
     */
    public static function getAll() {
        return self::getAllFrom('Opus_UserRole', 'Opus_Db_UserRoles');
    }

    /**
     * Initialize model with the following fields:
     * - Name
     *
     * @return void
     */
    protected function _init() {
        $name = new Opus_Model_Field('Name');
        $name->setMandatory(true);
        $this->addField($name);
    }

    /**
     * ALTERNATE CONSTRUCTOR: Retrieve Opus_UserRole instance by name.  Returns
     * null if name is null *or* nothing found.
     *
     * @param  string $name
     * @return Opus_UserRole
     */
    public static function fetchByName($name = null) {
        if (false === isset($name)) {
            return;
        }

        $table = Opus_Db_TableGateway::getInstance(self::$_tableGatewayClass);
        $select = $table->select()->where('name = ?', $name);
        $row = $table->fetchRow($select);

        if (isset($row)) {
            return new Opus_UserRole($row);
        }

        return;
    }

    /**
     * Returns name.
     *
     * @see library/Opus/Model/Opus_Model_Abstract#getDisplayName()
     */
    public function getDisplayName() {
        return $this->getName();

    }

    /**
     * Get a list of all account IDs for the current role instance.
     *
     * @return array
     */
    public function getAllAccountIds() {
        if ($this->isNewRecord()) {
            return;
        }

        $table = Opus_Db_TableGateway::getInstance("Opus_Db_LinkAccountsRoles");
        $select = $table->select(true)->columns('account_id AS id')
                        ->where('role_id = ?', $this->getId())
                        ->distinct();

        return $table->getAdapter()->fetchCol($select);
    }

    /**
     * Return an array of all document-ids, which are assigned to the current role.
     *
     * @return array
     */
    public function listAccessDocuments() {
        $table = Opus_Db_TableGateway::getInstance("Opus_Db_AccessDocuments");
        $adapter = $table->getAdapter();
        $select = $adapter->select()
                        ->from('access_documents', array('document_id'))
                        ->where('role_id = ?', $this->getId());

        return $adapter->fetchCol($select);
    }

    /**
     * Return an array of all file-ids, which are assigned to the current role.
     *
     * @return array
     */
    public function listAccessFiles() {
        $table = Opus_Db_TableGateway::getInstance("Opus_Db_AccessFiles");
        $adapter = $table->getAdapter();
        $select = $adapter->select()
                        ->from('access_files', array('file_id'))
                        ->where('role_id = ?', $this->getId());

        return $adapter->fetchCol($select);
    }

    /**
     * Returns an array of all known module-access-permissions for the current
     * role.  The associative array looks (for example) as follows:
     *
     * array(
     *    "default" => array( "auth" ),
     *    "solrsearch" => array( "*" ),
     *    "admin" => array( "index", "statistics" )
     * );
     *
     * @return array
     */
    public function listAccessModules() {
        $table = Opus_Db_TableGateway::getInstance("Opus_Db_AccessModules");
        return $table->listByRoleId($this->getId());

    }

    /**
     * Append (module, controller) to list of allowed ressources.
     *
     * @param string $module
     * @param string $controller
     * @return Opus_UserRole Provide fluent interface.
     */
    public function appendAccessModule($module, $controller) {
        $this->_pendingAccessModules[] = array(
            'append', $module, $controller,
        );
        return $this;

    }

    /**
     * Remove (module, controller) from list of allowed ressources.
     *
     * @param string $module
     * @param string $controller
     * @return Opus_UserRole Provide fluent interface.
     */
    public function removeAccessModule($module, $controller) {
        $this->_pendingAccessModules[] = array(
            'remove', $module, $controller,
        );
        return $this;

    }

    /**
     * Flush all pending AccessModule actions.
     */
    private function _flushAccessModuleQueue() {
        $table = Opus_Db_TableGateway::getInstance("Opus_Db_AccessModules");
        $role_id = $this->getId();

        foreach ($this->_pendingAccessModules AS $entry) {
            $action = $entry[0];

            $data = array(
                'role_id' => $role_id,
                'module_name' => $entry[1],
                'controller_name' => $entry[2],
            );

            if ($action == 'append') {
                // echo "append:  {$data['role_id']},{$data['module_name']},{$data['controller_name']}\n";
                $table->insertIgnoreDuplicate($data);
            }
            else if ($action == 'remove') {
                // echo "remove:  {$data['role_id']},{$data['module_name']},{$data['controller_name']}\n";
                $table->deleteWhereArray($data);
            }
        }
        $this->_pendingAccessModules = array();
    }

    /**
     * Overriding storing of internal fields: This is the place where we flush
     * the queued data.
     */
    protected function _postStoreInternalFields() {
        parent::_postStoreInternalFields();

        $this->_flushAccessModuleQueue();
    }

    /**
     * Overriding isModified() method.  Returning TRUE if one of the HASHES
     * changed, otherwise call parent::isModified().
     *
     * @return boolean
     */
    public function isModified() {
        if (count($this->_pendingAccessModules) > 0) {
            return true;
        }

        return parent::isModified();
    }

}
