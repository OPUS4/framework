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
 * @package     Opus_Model_Dependent
 * @author      Felix Ostrowski (ostrowski@hbz-nrw.de)
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Abstract class for all dependent models in the Opus framework.
 *
 * @category    Framework
 * @package     Opus_Model_Dependent
 */

abstract class Opus_Model_Dependent_Abstract
    extends Opus_Model_AbstractDbSecure
{

    /**
     * Primary key of the parent model.
     *
     * @var mixed $_parentId Defaults to null.
     */
    protected $_parentId = null;

    /**
     * Name of the column in the dependent model's primary table row that
     * contains the parent model's primary key.
     *
     * @var mixed $_parentColumn Defaults to null.
     */
    protected $_parentColumn = null;

    /**
     * Setter for $_parentId.
     *
     * @param integer $parentId The id of the parent Opus_Model
     * @return void
     */
    public function setParentId($parentId) {
        $this->_parentId = $parentId;
    }
    
    /**
     * Return the identifier of this models parent model.
     *
     * @return mixed Identifier of the parent model or Null if not set.
     */
    public function getParentId() {
        return $this->_parentId;
    }
    
    /**
     * Set the name of the column holding the parent id
     * of the linked model.
     *
     * @param string $column Name of the parent id column.
     * @return void
     */
    public function setParentIdColumn($column) {
        $this->_parentColumn = $column;
    }

    /**
     * Set up the foreign key of the parent before storing.
     *
     * @throws Opus_Model_Exception Thrown if trying to store without parent.
     * @return void
     */
    public function store() {
        if (null === $this->_parentId) {
            throw new Opus_Model_Exception('Dependent Model ' . get_class($this)
                    . ' without parent cannot be persisted.');
        } 
        if (null === $this->_parentColumn) {
            throw new Opus_Model_Exception('Dependent Model ' . get_class($this)
                    . ' needs to know name of the parent-id column.');
        } 
        $this->_primaryTableRow->{$this->_parentColumn} = $this->_parentId;
        parent::store();
    }
}
