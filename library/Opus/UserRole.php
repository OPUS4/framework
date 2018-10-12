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
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2008-2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

/**
 * Domain model for licences in the Opus framework
 *
 * @category    Framework
 * @package     Opus
 * @uses        Opus_Model_Abstract
 *
 * @method string getName()
 * @method void setName($name)
 */
class Opus_UserRole extends Opus_Model_AbstractDb
{

    /**
     * Specify then table gateway.
     *
     * @var string Classname of Zend_DB_Table to use if not set in constructor.
     */
    protected static $_tableGatewayClass = 'Opus_Db_UserRoles';

    /**
     * List of pending accessResource actions.
     *
     * @var array
     */
    private $_pendingAccessResources = [];

    /**
     * Retrieve all Opus_Db_UserRoles instances from the database.
     *
     * @return array Array of Opus_UserRole objects.
     */
    public static function getAll()
    {
        return self::getAllFrom('Opus_UserRole', 'Opus_Db_UserRoles');
    }

    /**
     * Initialize model with the following fields:
     * - Name
     *
     * @return void
     */
    protected function _init()
    {
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
    public static function fetchByName($name = null)
    {
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
    public function getDisplayName()
    {
        return $this->getName();

    }

    /**
     * Get a list of all account IDs for the current role instance.
     *
     * @return array
     */
    public function getAllAccountIds()
    {
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
     * Get a list of all account IDs for the current role instance.
     *
     * @return array
     */
    public function getAllAccountNames()
    {
        if ($this->isNewRecord()) {
            return;
        }

        $table = Opus_Db_TableGateway::getInstance("Opus_Db_LinkAccountsRoles");
        $adapter = $table->getAdapter();
        $select = $adapter->select()
                        ->from('link_accounts_roles AS lr', '')
                        ->join('accounts AS a', "a.id = lr.account_id", 'a.login')
                        ->where('lr.role_id = ?', $this->getId())
                        ->distinct();

        return $table->getAdapter()->fetchCol($select);
    }

    /**
     * Return an array of all document-ids, which are assigned to the current role.
     *
     * @return array
     */
    public function listAccessDocuments()
    {
        $table = Opus_Db_TableGateway::getInstance("Opus_Db_AccessDocuments");
        $adapter = $table->getAdapter();
        $select = $adapter->select()
                        ->from('access_documents', ['document_id'])
                        ->where('role_id = ?', $this->getId());

        return $adapter->fetchCol($select);
    }

    /**
     * Append (document_id) to list of allowed ressources.
     *
     * @param string $documentId
     * @return Opus_UserRole Provide fluent interface.
     */
    public function appendAccessDocument($documentId)
    {
        $this->_pendingAccessResources[] = [
            'append', 'document_id', $documentId,
        ];
        return $this;

    }

    /**
     * Remove (document_id) from list of allowed ressources.
     *
     * @param string $documentId
     * @return Opus_UserRole Provide fluent interface.
     */
    public function removeAccessDocument($documentId)
    {
        $this->_pendingAccessResources[] = [
            'remove', 'document_id', $documentId,
        ];
        return $this;
    }

    /**
     * Return an array of all file-ids, which are assigned to the current role.
     *
     * @return array
     */
    public function listAccessFiles()
    {
        $table = Opus_Db_TableGateway::getInstance("Opus_Db_AccessFiles");
        $adapter = $table->getAdapter();
        $select = $adapter->select()
                        ->from('access_files', ['file_id'])
                        ->where('role_id = ?', $this->getId());

        return $adapter->fetchCol($select);
    }

    /**
     * Append (file_id) to list of allowed ressources.
     *
     * @param string $fileId
     * @return Opus_UserRole Provide fluent interface.
     */
    public function appendAccessFile($fileId)
    {
        $this->_pendingAccessResources[] = [
            'append', 'file_id', $fileId,
        ];
        return $this;
    }

    /**
     * Remove (file_id) from list of allowed ressources.
     *
     * @param string $fileId
     * @return Opus_UserRole Provide fluent interface.
     */
    public function removeAccessFile($fileId)
    {
        $this->_pendingAccessResources[] = [
            'remove', 'file_id', $fileId,
        ];
        return $this;
    }

    /**
     * Return an array of all modules_names, which are assigned to the current
     * role.
     *
     * @return array
     */
    public function listAccessModules()
    {
        $table = Opus_Db_TableGateway::getInstance("Opus_Db_AccessModules");
        return $table->listByRoleId($this->getId());
    }

    /**
     * Append (module) to list of allowed ressources.
     *
     * @param string $moduleName
     * @return Opus_UserRole Provide fluent interface.
     */
    public function appendAccessModule($moduleName)
    {
        $this->_pendingAccessResources[] = [
            'append', 'module_name', $moduleName,
        ];
        return $this;
    }

    /**
     * Remove (module) from list of allowed ressources.
     *
     * @param string $moduleName
     * @return Opus_UserRole Provide fluent interface.
     */
    public function removeAccessModule($moduleName)
    {
        $this->_pendingAccessResources[] = [
            'remove', 'module_name', $moduleName,
        ];
        return $this;
    }

    /**
     * Flush all pending AccessModule actions.
     */
    private function _flushAccessResourceQueue()
    {
        $resourceTables = [
            'document_id' => Opus_Db_TableGateway::getInstance("Opus_Db_AccessDocuments"),
            'file_id'     => Opus_Db_TableGateway::getInstance("Opus_Db_AccessFiles"),
            'module_name' => Opus_Db_TableGateway::getInstance("Opus_Db_AccessModules"),
        ];
        $roleId = $this->getId();

        foreach ($this->_pendingAccessResources AS $entry) {
            $action        = $entry[0];
            $resourceName = $entry[1];
            $resourceId   = $entry[2];

            $table = $resourceTables[$resourceName];
            $data = [
                'role_id' => $roleId,
                $resourceName => $resourceId,
            ];

            if ($action == 'append') {
                $table->insertIgnoreDuplicate($data);
            } else if ($action == 'remove') {
                $table->deleteWhereArray($data);
            }
        }
        $this->_pendingAccessResources = [];
    }

    /**
     * Overriding storing of internal fields: This is the place where we flush
     * the queued data.
     */
    protected function _postStoreInternalFields()
    {
        parent::_postStoreInternalFields();

        $this->_flushAccessResourceQueue();
    }

    /**
     * Overriding isModified() method.  Returning TRUE if the pending queues
     * have been changed, otherwise call parent::isModified().
     *
     * @return boolean
     */
    public function isModified()
    {
        if (count($this->_pendingAccessResources) > 0) {
            return true;
        }

        return parent::isModified();
    }
}
