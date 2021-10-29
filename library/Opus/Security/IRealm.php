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
 * @copyright   Copyright (c) 2008-2011, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * @category    Framework
 * @package     Opus\Security
 * @author      Julian Heise <heise@zib.de>
 */

namespace Opus\Security;

/**
 * Interface for classes providing security implementation.
 *
 * phpcs:disable
 */
interface IRealm
{
    /**
     * Set the current username.
     *
     * @param string username username to be set.
     * @throws SecurityException Thrown if the supplied identity could not be found.
     * @return Realm Fluent interface.
     */
    public function setUser($username);

    /**
     * Set the current ip address.
     *
     * @param string ipaddress ip address to be set.
     * @throws SecurityException Thrown if the supplied ip address is not a valid ip address.
     * @return Realm Fluent interface.
     */
    public function setIp($ipaddress);

    /**
     * Checks, if the logged user is allowed to access (document_id).
     *
     * @param null|string $document_id ID of the document to check
     * @return bool Returns true only if access is granted.
     */
    public function checkDocument($document_id = null);

    /**
     * Checks, if the logged user is allowed to access (file_id).
     *
     * @param null|string $file_id ID of the file to check
     * @return bool Returns true only if access is granted.
     */
    public function checkFile($file_id = null);

    /**
     * Checks, if the logged user is allowed to access (module_name).
     *
     * @param null|string $module_name Name of the module to check
     * @return bool Returns true only if access is granted.
     */
    public function checkModule($module_name = null);

    /**
     * Returns the roles of the current user.
     *
     * @return array of strings - Names of roles for current user
     */
    public function getRoles();

    /**
     * Checks if a privilege is granted for actual context (usersession,
     * ip address).
     *
     * If administrator is one of the current roles true will be returned
     * ingoring everything else.
     *
     * @deprecated
     */
    public function check(
        $privilege,
        $documentServerState = null,
        $fileId = null
    );
}
