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

namespace Opus;

use Opus\Common\Log;
use Opus\Common\Security\Realm;
use Opus\Common\Security\RealmStorageInterface;
use Opus\Common\Security\SecurityException;
use Opus\Db\Accounts;
use Opus\Db\TableGateway;
use Opus\Db\UserRoles;

use function count;
use function ip2long;
use function sprintf;

/**
 * This singleton class encapsulates all security specific information
 * like the current User, IP address, and method to check rights.
 */
class SecurityStorage implements RealmStorageInterface
{
    /**
     * Get the roles that are assigned to the specified username.
     *
     * @param string $username username to be set.
     * @throws SecurityException Thrown if the supplied identity could not be found.
     * @return array Array of assigned roles or an empty array.
     */
    public function getUsernameRoles($username)
    {
        if ($username === null || true === empty($username)) {
            return [];
        }

        $accounts = TableGateway::getInstance(Accounts::class);
        $account  = $accounts->fetchRow($accounts->select()->where('login = ?', $username));
        if (null === $account) {
            $logger  = Log::get();
            $message = "An user with the given name: $username could not be found.";
            if ($logger !== null) {
                $logger->err($message);
            }
            throw new SecurityException($message);
        }

        $db = TableGateway::getInstance(UserRoles::class)->getAdapter();
        return $db->fetchCol(
            $db->select()
                ->from(['r' => 'user_roles'], ['r.name'])
                ->join(['l' => 'link_accounts_roles'], 'l.role_id = r.id', '')
                ->join(['a' => 'accounts'], 'l.account_id = a.id', '')
                ->where('login = ?', $username)
                ->distinct()
        );
    }

    /**
     * Map an IP address to Roles.
     *
     * @param string $ipaddress ip address to be set.
     * @throws SecurityException Thrown if the supplied ip is not valid.
     * @return array Array of assigned roles or an empty array.
     */
    public function getIpaddressRoles($ipaddress)
    {
        if ($ipaddress === null || true === empty($ipaddress)) {
            return [];
        }

        if (! Realm::validateIpAddress($ipaddress)) {
            throw new SecurityException('Your IP address could not be validated.');
        }

        $db = TableGateway::getInstance(UserRoles::class)->getAdapter();
        return $db->fetchCol(
            $db->select()
                ->from(['r' => 'user_roles'], ['r.name'])
                ->join(['l' => 'link_ipranges_roles'], 'l.role_id = r.id', '')
                ->join(['i' => 'ipranges'], 'l.iprange_id = i.id', '')
                ->where('i.startingip <= ?', sprintf("%u", ip2long($ipaddress)))
                ->where('i.endingip >= ?', sprintf("%u", ip2long($ipaddress)))
                ->distinct()
        );
    }

    /**
     * Returns all module resources to which the current user and ip address
     * has access.
     *
     * @param string|null $username  name of the account to get resources for.
     *                               Defaults to currently logged in user
     * @param string|null $ipaddress IP address to get resources for.
     *                               Defaults to current remote address if available.
     * @return array Module resource names
     * @throws SecurityException Thrown if the supplied ip is not valid or user can not be determined.
     */
    public function getAllowedModuleResources($username = null, $ipaddress = null)
    {
        // TODO verify parameters? The code for that is now in Opus\Common\Security\Realm

        $db     = TableGateway::getInstance(UserRoles::class)->getAdapter();
        $select = $db->select();
        $select->from(['am' => 'access_modules'], ['am.module_name'])
                ->joinLeft(['r' => 'user_roles'], 'r.id = am.role_id')
                ->distinct();

        if ($username !== null) {
            $select->joinLeft(['la' => 'link_accounts_roles'], 'la.role_id = r.id', '')
                    ->joinLeft(['a' => 'accounts'], 'la.account_id = a.id', '')
                    ->where('login = ?', $username);
        }

        if ($ipaddress !== null) {
            $select->joinLeft(['li' => 'link_ipranges_roles'], 'li.role_id = r.id', '')
                    ->joinLeft(['i' => 'ipranges'], 'li.iprange_id = i.id', '');
            $select->orWhere(
                'i.startingip <= ? AND i.endingip >= ?',
                sprintf("%u", ip2long($ipaddress)),
                sprintf("%u", ip2long($ipaddress))
            );
        }

        return $db->fetchCol($select);
    }

    /**
     * Checks, if the logged user is allowed to access (document_id).
     *
     * @param string   $documentId ID of the document to check
     * @param string[] $roles
     * @return bool Returns true only if access is granted.
     */
    public function checkDocument($documentId, $roles)
    {
        $db      = TableGateway::getInstance(UserRoles::class)->getAdapter();
        $results = $db->fetchAll(
            $db->select()
                ->from(['ad' => 'access_documents'], ['document_id'])
                ->join(['r' => 'user_roles'], 'ad.role_id = r.id', '')
                ->where('r.name IN (?)', $roles)
                ->where('ad.document_id = ?', $documentId)
        );
        return 1 <= count($results) ? true : false;
    }

    /**
     * Checks, if the logged user is allowed to access (file_id).
     *
     * @param string   $fileId ID of the file to check
     * @param string[] $roles
     * @return bool Returns true only if access is granted.
     */
    public function checkFile($fileId, $roles)
    {
        $db      = TableGateway::getInstance(UserRoles::class)->getAdapter();
        $results = $db->fetchAll(
            $db->select()
                                ->from(['af' => 'access_files'], ['file_id'])
                                ->join(['r' => 'user_roles'], 'af.role_id = r.id', '')
                                ->where('r.name IN (?)', $roles)
                                ->where('af.file_id = ?', $fileId)
        );
        return 1 <= count($results) ? true : false;
    }

    /**
     * Checks, if the logged user is allowed to access (module_name).
     *
     * @param string   $moduleName Name of the module to check
     * @param string[] $roles
     * @return bool Returns true only if access is granted.
     */
    public function checkModule($moduleName, $roles)
    {
        $db      = TableGateway::getInstance(UserRoles::class)->getAdapter();
        $results = $db->fetchAll(
            $db->select()
                                ->from(['am' => 'access_modules'], ['module_name'])
                                ->join(['r' => 'user_roles'], 'am.role_id = r.id', '')
                                ->where('r.name IN (?)', $roles)
                                ->where('am.module_name = ?', $moduleName)
        );
        return 1 <= count($results) ? true : false;
    }

    /**
     * Checks if a user has access to a module.
     *
     * @param string $moduleName Name of module
     * @param string $user Name of user
     * @return bool
     */
    public function checkModuleForUser($moduleName, $user)
    {
        $roles = $this->getUsernameRoles($user);

        $db      = TableGateway::getInstance(UserRoles::class)->getAdapter();
        $results = $db->fetchAll(
            $db->select()
                ->from(['am' => 'access_modules'], ['module_name'])
                ->join(['r' => 'user_roles'], 'am.role_id = r.id', '')
                ->where('r.name IN (?)', $roles)
                ->where('am.module_name = ?', $moduleName)
        );
        return 1 <= count($results) ? true : false;
    }
}
