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
 * @package     Opus
 * @author      Henning Gerhardt (henning.gerhardt@slub-dresden.de)
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Domain model for hashvalues in the Opus framework
 *
 * @category    Framework
 * @package     Opus
 * @uses        Opus_Model_Abstract
 */
class Opus_HashValues extends Opus_Model_Dependent_Abstract
{

    /**
     * Primary key of the parent model.
     *
     * @var mixed $_parentId.
     */
    protected $_parentColumn = 'file_id';

    /**
     * Specify then table gateway.
     *
     * @var string Classname of Zend_DB_Table to use if not set in constructor.
     */
    protected static $_tableGatewayClass  = 'Opus_Db_FileHashvalues';

     /** Plugins to load
     *
     * @var array
     */
    protected $_plugins = [
// Plugin InvalidateDocumentCache should stay disabled here since this model is
// not directly related to Opus_Document
//        'Opus_Model_Plugin_InvalidateDocumentCache' => null,
    ];


    /**
     * Initialize model with the following fields:
     * - HashType
     * - HashValue
     *
     * @return void
     */
    protected function _init()
    {
        $hashtype = new Opus_Model_Field('Type');
        $hashtype->setMandatory(true)
            ->setValidator(new Zend_Validate_NotEmpty());

        $hashvalue = new Opus_Model_Field('Value');
        $hashvalue->setMandatory(true)
            ->setValidator(new Zend_Validate_NotEmpty());

        $this->addField($hashtype)
            ->addField($hashvalue);
    }

    /**
     * Perform security resoure registration.
     *
     * @return void
     */
    protected function _postStoreInternalFields()
    {
        $isNewFlagBackup = $this->_isNewRecord;
        $this->_isNewRecord = false;

        parent::_postStoreInternalFields();

        $this->_isNewRecord = $isNewFlagBackup;
    }

    /**
     * Return the primary key of the Link Model if it has been persisted.
     *
     * @return array|null Primary key or Null if the Linked Model has not been persisted.
     */
    public function getId()
    {
        // The given id consists of the ids of the referenced linked models,
        // but there is no evidence that the LinkModel itself has been persisted yet.
        // We so have to validate, if the LinkModel is persistent or still transient.
        if (true === $this->isNewRecord()) {
            // its a new record, so return null
            return null;
        }

        // its not a new record, so we can hand over to the parent method
        return parent::getId();
    }
}
