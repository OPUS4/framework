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
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * @category    Tests
 * @package     Opus\Model
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 */

namespace OpusTest\Model\Mock;

use Opus\Model\Dependent\AbstractDependentModel;

/**
 * Mockup class used for tracking calls to specific methods in unit tests.
 *
 * @category Tests
 * @package Opus\Model
 */
class ModelDependentMock extends AbstractDependentModel
{
    public $deleteHasBeenCalled = false;

    public $doDeleteHasBeenCalled = false;

    public $setParentIdHasBeenCalled = false;

    public $id;

    public function __construct()
    {
    }

    public function init()
    {
    }

    public function delete()
    {
        $this->deleteHasBeenCalled = true;
    }

    /**
     * @param string $token
     */
    public function doDelete($token)
    {
        $this->doDeleteHasBeenCalled = true;
    }

    /**
     * @param int $id
     */
    public function setParentId($id)
    {
        $this->setParentIdHasBeenCalled = true;
    }

    /**
     * @return int
     */
    public function getId()
    {
        if (null === $this->id) {
            return parent::getId();
        }
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
}
