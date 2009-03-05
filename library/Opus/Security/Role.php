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
 * @package     Opus_Security
 * @author      Ralf Claussnitzer <ralf.claussnitzer@slub-dresden.de>
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Represents a system account and provides static methods to find and/or
 * remove accounts. Thus, every account has to have a password, those password
 * can be changed only by providing the current valid password. 
 *
 * @category    Framework
 * @package     Opus_Security
 */
class Opus_Security_Role extends Opus_Model_AbstractDb implements Zend_Acl_Role_Interface {

    /**
     * Specify table gateway.
     *
     * @var string
     */
    protected static $_tableGatewayClass = 'Opus_Db_Roles';

    /**
     * Augment 'Parent' field with model information.
     *
     * @var array
     */
    protected $_externalFields = array(
        'Parent' => array(
            'model' => 'Opus_Security_Role')
    );

    /**
     * Add role name and parent fields.
     *
     * @return void
     */
    protected function _init() {
        $name = new Opus_Model_Field('Name');
        $nameValidator = new Zend_Validate;
        $nameValidator->addValidator(new Zend_Validate_NotEmpty);
        $nameValidator->addValidator(new Zend_Validate_Alnum);        
        $name->setValidator($nameValidator)
            ->setMandatory(true);
        
        $parent = new Opus_Model_Field('Parent');
        
        $this->addField($name)
            ->addField($parent);
    }
    
    /**
     * Return an identifier for this role containing class name, role name and
     * id (if persistent). E.g. Opus/Security/MyRole/4711.
     *
     * @return string Role identifier.
     */
    public function getRoleId() {
        $result = str_replace('_', '/', get_class($this));
        $name = $this->getName();
        if (empty($name) === false) {
            $result = $result . '/' . $name;
        }
        $id = $this->getId();
        if (null !== $id) {
            $result = $result . '/' . $id;
        }
        return $result;
    }
    
    /**
     * Retrieve an Role model by role id as returned by getRoleId().
     *
     * @param string $roleId Role identifying string.
     * @return void
     */
    public static function getByRoleId($roleId) {
        $arr = explode('/', $roleId);
        $id = array_pop($arr);
        $table = Opus_Db_TableGateway::getInstance('Opus_Db_Roles');
        $row = $table->fetchRow($table->select()->where('id=?', $id));
        if (null !== $row) {
            return new Opus_Security_Role($row);
        }
        return null;
    }
    
    /**
     * Check if a given roleId can be mapped to an
     * persisted instance.
     *
     * @param string $roleId Role identifying string.
     * @return bool
     */
    public static function isRoleIdExistent($roleId) {
        $arr = explode('/', $roleId);
        $id = array_pop($arr);
        $table = Opus_Db_TableGateway::getInstance('Opus_Db_Roles');
        $row = $table->fetchRow($table->select()->where('id=?', $id));
        if (null !== $row) {
            return true;
        }
        return false;
    }

}
