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
 * @copyright   Copyright (c) 2008-2020, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * The fields should be fetched in the order in which they were added. That mean getBefore should return 'bar', if
 * 'Target' has not been fetched yet and getAfter should return 'baz', if 'Target' has been fetched already.
 *
 * @category    Tests
 * @package     Opus\Model
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @author      Jens Schwidder <schwidder@zib.de>
 */

namespace OpusTest\Model\Mock;

use Opus\Model\AbstractDb;
use Opus\Model\Field;

class CheckFieldOrderDummyClass extends AbstractDb
{
    protected static $tableGatewayClass = AbstractTableProvider::class;

    private $_targetFetched = false;

    protected function init()
    {
        $this->addField(new Field("Before"));
        $this->addField(new Field("Target"));
        $this->addField(new Field("After"));
    }

    protected function _fetchBefore()
    {
        if ($this->_targetFetched === true) {
            return $this->getTarget();
        }
        return "bar"; // target has not been fetched yet
    }

    protected function _fetchTarget()
    {
        $this->_targetFetched = true;
        return "foo";
    }

    protected function _fetchAfter()
    {
        if ($this->_targetFetched === false) {
            return $this->getTarget();
        }
        return "baz"; // target has been fetched
    }
}
