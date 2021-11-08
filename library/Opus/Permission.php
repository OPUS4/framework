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
 * @copyright   Copyright (c) 2018-2020, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * @category    Framework
 * @package     Opus
 * @author      Jens Schwidder <schwidder@zib.de>
 */

namespace Opus;

use Opus\Db\TableGateway;

/**
 * Class represents a permission in the OPUS 4 access control system.
 *
 * TODO this is just a quick start that needs review/refactoring
 */
class Permission
{
    /**
     * Returns user accounts that have a permission.
     *
     * @param string $permission string Name of permission
     * @return Account[]
     */
    public static function getAccounts($permission)
    {
        if ($permission === null) {
            return [];
        }

        $table = TableGateway::getInstance(Db\UserRoles::class);

        $adapter = $table->getAdapter();

        $roleSelect = $adapter->select()->from(
            'access_modules',
            ['role_id']
        )->where(
            'module_name = ?',
            $permission
        );

        $select = $adapter->select()->from(
            ['a' => 'accounts'],
            ['a.id']
        )->join(
            ['link' => 'link_accounts_roles'],
            'link.account_id = a.id',
            []
        )->where(
            "link.role_id IN ($roleSelect)"
        );

        $accountIds = $adapter->fetchAll($select);

        $accounts = [];

        foreach ($accountIds as $id) {
            $accounts[] = new Account($id);
        }

        return $accounts;
    }
}
