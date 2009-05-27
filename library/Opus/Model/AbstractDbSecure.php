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
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @author      Pascal-Nicolas Becker <becker@zib.de>
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id: AbstractDb.php 2214 2009-03-18 14:43:32Z claussnitzer $
 */

/**
 * Abstract class for all domain models in the Opus framework that are connected
 * to a database table and enforce security permissions.
 *
 * @category    Framework
 * @package     Opus_Model
 */

abstract class Opus_Model_AbstractDbSecure extends Opus_Model_AbstractDb implements Zend_Acl_Resource_Interface
{
    /**
     * Define name for 'create' permission.
     *
     * @var string
     */
    const PERM_CREATE = 'create';

    /**
     * Define name for 'read' permission.
     *
     * @var string
     */
    const PERM_READ = 'read';

    /**
     * Define name for 'update' permission.
     *
     * @var string
     */
    const PERM_UPDATE = 'update';

    /**
     * Define name for 'delete' permission.
     *
     * @var string
     */
    const PERM_DELETE = 'delete';

    /**
     * Return an array describing the available permissons.
     *
     * @return array
     */
    public function describePermissions() {
        return array(
        self::PERM_CREATE   =>  'Permission to create a new persistent representation of the model.',
        self::PERM_READ     =>  'Permisson to read (deserialize) a persisted model from.',
        self::PERM_UPDATE   =>  'Permisson to update a persisted model.',
        self::PERM_DELETE   =>  'Permisson to delete a persisted model.',
        );
    }

    /**
     * Holds the resource that is assigned as parent resource when
     * persisted.
     *
     * @var Zend_Acl_Resource_Interface
     */
    private $_masterAclResource = null;

    /**
     * Returns class name and the id of the model instance if an id is
     * set already.
     *
     * @return string The ResourceId for Zend_Acl.
     */
    public function getResourceId() {
        $id = $this->getId();
        $result = str_replace('_', '/', get_class($this));

        if (is_null($id)) {
            // Return, no id to append
            return $result;
        }

        // Prepare for id appending
        if (false === is_array($id)) {
            $id = array($id);
        }

        // Append ids in URL style
        foreach ($id as $index => $value) {
            if ($index === 0) {
                $result .= "/" . $value;
            } else {
                $result .= "-" . $value;
            }
        }
        return $result;
    }

    /**
     * Check security permissions and delegate to parent constructor.
     *
     * @see Opus_Model_AbstractDb::__construct()
     * @param integer|Zend_Db_Table_Row $id                (Optional) (Id of) Existing database row.
     * @param Zend_Db_Table             $tableGatewayModel (Optional) Opus_Db model to fetch table row from.
     * @throws Opus_Security_Exception  Thrown if read permisson is needed but not granted.
     */
    public function __construct($id = null, Opus_Db_TableGateway $tableGatewayModel = null) {
        parent::__construct($id);
        // Check for permission to read the model
        // if an id is given
        if (null !== $id) {
            $this->_ensure(self::PERM_READ);
        }
    }

    /**
     * Set up the Acl Resource that gets used as parent resource when
     * the model is stored
     *
     * @param Zend_Acl_Resource_Interface $resource Acl resource to be registered as parent resource.
     * @return void
     */
    public function setMasterResource(Zend_Acl_Resource_Interface $resource) {
        $this->_masterAclResource = $resource;
    }

    /**
     * Persist all the models information to its database locations
     * after checking if all required permissions are granted.
     *
     * If a new model is created, it gets registered as a resource in the current acl.
     *
     * @see    Opus_Model_Interface::store()
     * @throws Opus_Security_Exception  If the current role has no permission for the 'update' or the 'create' operation respectivly.
     * @return mixed $id    Primary key of the models primary table row.
     */
    public function store() {
        // refuse to store if data is not valid
        if (false === $this->isValid()) {
            $msg = 'Attempt to store model with invalid data.';
            foreach ($this->getValidationErrors() as $fieldname=>$err) {
                if (false === empty($err)) {
                    $msg = $msg . "\n" . "$fieldname\t" . implode("\n", $err);
                }
            }
            throw new Opus_Model_Exception($msg);
        }

        // Check permissions
        $registerResource = false;
        if (null === $this->getId()) {
            // probably creation of new record, needs PERM_CREATE
            $this->_ensure(self::PERM_CREATE);
            $registerResource = true;
        } else {
            // probably update, needs PERM_UPDATE
            $this->_ensure(self::PERM_UPDATE);
        }

        // Start transaction
        $dbadapter = $this->_primaryTableRow->getTable()->getAdapter();
        $dbadapter->beginTransaction();

        // store internal fields, get id
        try {
            $id = $this->_storeInternalFields();
        } catch (Exception $e) {
            throw $e;
        }

        // Register model as resource
        if (true === $registerResource) {
            $acl = Opus_Security_Realm::getInstance()->getAcl();
            if (null !== $acl) {
                if (false === $acl->has($this)) {
                    if (null === $this->_masterAclResource) {
                        $this->_masterAclResource = Opus_Security_Realm::getInstance()->getResourceMaster();
                    }
                    $acl->add($this, $this->_masterAclResource);
                }
            }
        }
        // set up this object as master resource for all child elements
        foreach ($this->_fields as $field) {
            $value = $field->getValue();
            if (false === is_array($value)) {
                $value = array($value);
            }
            foreach ($value as $item) {
                if ($item instanceof Opus_Model_Dependent_Abstract) {
                    $item->setMasterResource($this);
                }
            }

        }

        // store external fields
        try {
            $this->_storeExternalFields();
        } catch (Exception $e) {
            throw $e;
        }

        // commit transaction
        $dbadapter->commit();

        $this->_isNewRecord = false;
        return $id;
    }


    /**
     * Remove the model instance from the database. If sucessfull, also remove resource from Acl.
     *
     * @see    Opus_Model_Interface::delete()
     * @throws Opus_Security_Exception If the current role has no permission for the 'delete' operation.
     * @return void
     */
    public function delete() {
        $this->_ensure(self::PERM_DELETE);

        parent::delete();

        // remove resource registration
        $acl = Opus_Security_Realm::getInstance()->getAcl();
        if (null !== $acl) {
            if (false === $acl->has($this)) {
                $acl->remove($this);
            }
        }
    }

    /**
     * Test if a given privilege is granted for the current Role
     * within the current Security Realm. If not, throw exception.
     *
     * If no Acl is defined nothing happens.
     *
     * @throws Opus_Security_Exception Thrown if specified permission is not granted.
     * @return void
     */
    protected function _ensure($privilege) {
        $acl = Opus_Security_Realm::getInstance()->getAcl();
        if (null === $acl) {
            return;
        }
        $role = Opus_Security_Realm::getInstance()->getRole();

        if (false === $acl->isAllowed($role, $this, $privilege)) {
            if (null !== $role) {
                if ($role instanceOf Zend_Acl_Role_Interface) {
                    $roleId = $role->getRoleId();
                } else {
                    $roleId = $role;
                }
            } else {
                $roleId = 'everyone';
            }
            $resourceId = $this->getResourceId();
            throw new Opus_Security_Exception("Operation $privilege is not allowed for $roleId on $resourceId.");
        }
    }

}
