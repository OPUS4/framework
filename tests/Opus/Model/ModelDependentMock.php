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
 * @category    Tests
 * @package     Opus_Model
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Mockup class used for tracking calls to specific methods in unit tests.
 *
 * @category Tests
 * @package Opus_Model
 */
class Opus_Model_ModelDependentMock extends Opus_Model_Dependent_Abstract
{

    public $deleteHasBeenCalled = false;

    public $doDeleteHasBeenCalled = false;

    public $setParentIdHasBeenCalled = false;

    public $id = null;

    private $_discriminatorObject = null;

    public function __construct()
    {
    }

    public function _init()
    {
    }

    public function delete()
    {
        $this->deleteHasBeenCalled = true;
    }

    public function doDelete($token)
    {
        $this->doDeleteHasBeenCalled = true;
    }

    public function setParentId($id)
    {
        $this->setParentIdHasBeenCalled = true;
    }

    public function getId()
    {
        if (null === $this->id) {
            return parent::getId();
        }
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Sets an object reference that helps to mime object
     * references when comparing instances of this class.
     *
     * @param <type> $object An arbitrary object
     */
    public function setDiscriminatorObject($object)
    {
        $this->_discriminatorObject = $object;
    }
}
