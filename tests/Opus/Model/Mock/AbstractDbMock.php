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
 */

namespace OpusTest\Model\Mock;

use Opus\Common\Model\ModelException;
use Opus\Model\AbstractDb;
use Opus\Model\Field;
use Zend_Db_Table_Abstract;

/**
 * This class extends AbstractDb to be able to test its code.
 * Opus\Model\AbstractDb is an abstract class.
 * This mock is needed to be able to instantiate Opus\Model\AbstractModel.
 *
 * @category Tests
 * @package Opus\Model
 */
class AbstractDbMock extends AbstractDb
{
    /** @var bool */
    public $postStoreHasBeenCalled = false;

    /**
     * Specify then table gateway.
     *
     * @var string Classname of\Zend_DB_Table to use if not set in constructor.
     */
    protected static $tableGatewayClass = AbstractTableProvider::class;

    /**
     * @param null|int $id
     * @throws ModelException
     */
    public function __construct($id = null, ?Zend_Db_Table_Abstract $tableGatewayModel = null, array $plugins = [])
    {
        foreach ($plugins as $plugin) {
            $this->registerPlugin($plugin);
        }
        parent::__construct($id, $tableGatewayModel);
    }

    /**
     * Initialize model with the a single field "value".
     */
    protected function init()
    {
        $this->_validatorPrefix[] = 'Opus_Model_ValidateTest';
        $this->_filterPrefix[]    = 'Opus_Model_FilterTest';

        $value = new Field('Value');
        $this->addField($value);
    }

    /**
     * @throws ModelException
     * phpcs:disable
     */
    public function _postStore()
    {
        parent::_postStore();
        $this->postStoreHasBeenCalled = true;
    }
}
