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
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus;

use Opus\Common\Model\ModelException;
use Opus\Common\UserRoleInterface;
use Opus\Common\UserRoleRepositoryInterface;
use Opus\Db\TableGateway;
use Opus\Model\AbstractDb;
use Opus\Model\Field;

use function count;
use function func_get_args;

/**
 * Domain model for licences in the Opus framework
 */
class UserRole extends AbstractDb implements UserRoleInterface, UserRoleRepositoryInterface
{
    /**
     * Specify then table gateway.
     *
     * @var string Classname of \Zend_DB_Table to use if not set in constructor.
     */
    protected static $tableGatewayClass = Db\UserRoles::class;

    /**
     * List of pending accessResource actions.
     *
     * @var array
     */
    private $pendingAccessResources = [];

    /**
     * Retrieve all Opus\Db\UserRoles instances from the database.
     *
     * @return array Array of Opus\UserRole objects.
     */
    public function getAll()
    {
        return self::getAllFrom(self::class, Db\UserRoles::class);
    }

    /**
     * Initialize model with the following fields:
     * - Name
     */
    protected function init()
    {
        $name = new Field('Name');
        $name->setMandatory(true);
        $this->addField($name);
    }

    /**
     * ALTERNATE CONSTRUCTOR: Retrieve Opus\UserRole instance by name.  Returns
     * null if name is null *or* nothing found.
     *
     * @param  null|string $name
     * @return self|null
     */
    public function fetchByName($name = null)
    {
        if (false === isset($name)) {
            return null;
        }

        $table  = TableGateway::getInstance(self::$tableGatewayClass);
        $select = $table->select()->where('name = ?', $name);
        $row    = $table->fetchRow($select);

        if (isset($row)) {
            return new UserRole($row);
        }

        return null;
    }

    /**
     * Returns name.
     *
     * @return string|null
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
            return [];
        }

        $table  = TableGateway::getInstance(Db\LinkAccountsRoles::class);
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
            return [];
        }

        $table   = TableGateway::getInstance(Db\LinkAccountsRoles::class);
        $adapter = $table->getAdapter();
        $select  = $adapter->select()
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
        $table   = TableGateway::getInstance(Db\AccessDocuments::class);
        $adapter = $table->getAdapter();
        $select  = $adapter->select()
                        ->from('access_documents', ['document_id'])
                        ->where('role_id = ?', $this->getId());

        return $adapter->fetchCol($select);
    }

    /**
     * Append (document_id) to list of allowed ressources.
     *
     * @param int $documentId
     * @return $this Provide fluent interface.
     */
    public function appendAccessDocument($documentId)
    {
        $this->pendingAccessResources[] = [
            'append',
            'document_id',
            $documentId,
        ];
        return $this;
    }

    /**
     * Remove (document_id) from list of allowed ressources.
     *
     * @param int $documentId
     * @return $this Provide fluent interface.
     */
    public function removeAccessDocument($documentId)
    {
        $this->pendingAccessResources[] = [
            'remove',
            'document_id',
            $documentId,
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
        $table   = TableGateway::getInstance(Db\AccessFiles::class);
        $adapter = $table->getAdapter();
        $select  = $adapter->select()
                        ->from('access_files', ['file_id'])
                        ->where('role_id = ?', $this->getId());

        return $adapter->fetchCol($select);
    }

    /**
     * Append (file_id) to list of allowed ressources.
     *
     * @param string $fileId
     * @return $this Provide fluent interface.
     */
    public function appendAccessFile($fileId)
    {
        $this->pendingAccessResources[] = [
            'append',
            'file_id',
            $fileId,
        ];
        return $this;
    }

    /**
     * Remove (file_id) from list of allowed ressources.
     *
     * @param string $fileId
     * @return $this Provide fluent interface.
     */
    public function removeAccessFile($fileId)
    {
        $this->pendingAccessResources[] = [
            'remove',
            'file_id',
            $fileId,
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
        $table = TableGateway::getInstance(Db\AccessModules::class);
        return $table->listByRoleId($this->getId());
    }

    /**
     * Append (module) to list of allowed ressources.
     *
     * @param string $moduleName
     * @return $this Provide fluent interface.
     */
    public function appendAccessModule($moduleName)
    {
        $this->pendingAccessResources[] = [
            'append',
            'module_name',
            $moduleName,
        ];
        return $this;
    }

    /**
     * Remove (module) from list of allowed ressources.
     *
     * @param string $moduleName
     * @return $this Provide fluent interface.
     */
    public function removeAccessModule($moduleName)
    {
        $this->pendingAccessResources[] = [
            'remove',
            'module_name',
            $moduleName,
        ];
        return $this;
    }

    /**
     * Flush all pending AccessModule actions.
     */
    private function flushAccessResourceQueue()
    {
        $resourceTables = [
            'document_id' => TableGateway::getInstance(Db\AccessDocuments::class),
            'file_id'     => TableGateway::getInstance(Db\AccessFiles::class),
            'module_name' => TableGateway::getInstance(Db\AccessModules::class),
        ];
        $roleId         = $this->getId();

        foreach ($this->pendingAccessResources as $entry) {
            $action       = $entry[0];
            $resourceName = $entry[1];
            $resourceId   = $entry[2];

            $table = $resourceTables[$resourceName];
            $data  = [
                'role_id'     => $roleId,
                $resourceName => $resourceId,
            ];

            if ($action === 'append') {
                $table->insertIgnoreDuplicate($data);
            } elseif ($action === 'remove') {
                $table->deleteWhereArray($data);
            }
        }
        $this->pendingAccessResources = [];
    }

    /**
     * Overriding storing of internal fields: This is the place where we flush
     * the queued data.
     *
     * phpcs:disabled
     */
    protected function _postStoreInternalFields()
    {
        parent::_postStoreInternalFields();

        $this->flushAccessResourceQueue();
    }

    /**
     * Overriding isModified() method.  Returning TRUE if the pending queues
     * have been changed, otherwise call parent::isModified().
     *
     * phpcs:enabled
     *
     * @return bool
     */
    public function isModified()
    {
        if (count($this->pendingAccessResources) > 0) {
            return true;
        }

        return parent::isModified();
    }

    /**
     * @return string|null
     * @throws ModelException
     */
    public function getName()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @param string $name
     * @return $this
     * @throws ModelException
     */
    public function setName($name)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }
}
