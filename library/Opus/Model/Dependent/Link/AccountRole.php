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

namespace Opus\Model\Dependent\Link;

use Opus\Db\LinkAccountsRoles;
use Opus\UserRole;

/**
 * Abstract class to linkt model account with model role in the Opus framework.
 *
 * @category    Framework
 * @package     Opus\Model
 */
class AccountRole extends AbstractLinkModel
{
    /**
     * Specify then table gateway.
     *
     * @var string Classname of \Zend_DB_Table to use if not set in constructor.
     */
    protected static $tableGatewayClass = LinkAccountsRoles::class;

    /**
     * Primary key of the parent model.
     *
     * @var mixed
     */
    protected $parentColumn = 'account_id';

    /**
     * The class of the model that is linked to.
     *
     * @var string
     */
    protected $modelClass = UserRole::class;

    /**
     * The name of the field containing an identifying string.
     *
     * @var string
     */
    protected $displayAttributeName = 'Name';

    /**
     * Initialize model with the following values:
     * - Role
     */
    protected function init()
    {
        if ($this->getId() !== null) {
            $this->setModel(new UserRole($this->primaryTableRow->role_id));
        } else {
            $this->setModel(new UserRole());
        }
    }

    /**
     * Persist foreign model & link.
     */
    public function store()
    {
        $this->primaryTableRow->role_id = $this->model->store();
        // only store if something has changed
        // this avoids duplicate entries
        if ($this->getId() !== $this->primaryTableRow->role_id) {
            parent::store();
        }
    }
}
