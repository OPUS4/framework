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
 * Abstract class for all dependent models in the Opus framework.
 *
 * @category    Framework
 * @package     Opus_Model
 */

abstract class Opus_Model_Dependent_Abstract extends Opus_Model_Abstract
{
    /**
     * Whether db transaction should be used in store()
     *
     * @var boolean Defaults to false.
     */
    protected $_transactional = false;

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
     */
    public function setParentId($parentId) {
        $this->_parentId = $parentId;
    }

    /**
     * Set up the foreign key of the parent before storing.
     *
     */
    public function store() {
        if (is_null($this->_parentId) === true or is_null($this->_parentColumn)
                === true) {
            throw new Opus_Model_Exception('Dependent Model ' . get_class($this)
                    . ' without parent cannot be persisted.');
        }
        $this->_primaryTableRow->{$this->_parentColumn} = $this->_parentId;
        parent::store();
    }
}
