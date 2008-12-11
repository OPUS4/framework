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
 * Abstract class for link licence model in the Opus framework.
 *
 * @category    Framework
 * @package     Opus_Model
 */
class Opus_Model_Dependent_Link_Licence extends Opus_Model_Dependent_Link_Abstract
{
    /**
     * Primary key of the parent model.
     *
     * @var mixed $_parentId.
     */
    protected $_parentColumn = 'documents_id';

    /**
     * Create a new parent title model instance.
     *
     * @see Opus_Model_Abstract::__construct()
     * @param mixed $id (Optional) Primary key of a persisted title model instance.
     * @param mixed $parent_id (Optional) Primary key of the parent document.
     * @param Zend_Db_Table $tableGatewayModel
     * @throws Opus_Model_Exception Thrown if an instance with the given primary key could not be found.
     */
    public function __construct($id = null, $tableGatewayModel = null) {
        if ($tableGatewayModel === null) {
            parent::__construct($id, new Opus_Db_LinkDocumentsLicences);
        } else {
            parent::__construct($id, $tableGatewayModel);
        }
    }

    /**
     * Initialize model with the following values:
     * - Role
     *
     * @return void
     */
    protected function _init() {
        if (is_null($this->getId()) === false) {
            $this->_model = new Opus_Model_Licence($this->_primaryTableRow->licences_id);
        } else {
            $this->_model = new Opus_Model_Licence();
        }
    }

    /**
     * Persist foreign model & link.
     *
     * @return void
     */
    public function store() {
        $this->_model->_setTransactional(false);
        $this->_primaryTableRow->licences_id = $this->_model->store();
        parent::store();
    }

}


