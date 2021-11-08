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
 * @copyright   Copyright (c) 2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * @category    Framework
 * @package     Opus\Security
 * @author      Oliver Marahrens <o.marahrens@tu-harburg.de>
 */

namespace Opus\Security\AuthAdapter;

use Exception;
use Opus\Account;
use Opus\Security\AuthAdapter;
use Zend_Auth;
use Zend_Auth_Adapter_Exception;
use Zend_Auth_Adapter_Ldap;
use Zend_Auth_Result;
use Zend_Config_Ini;
use Zend_Ldap;
use Zend_Ldap_Exception;
use Zend_Log;
use Zend_Log_Filter_Priority;
use Zend_Log_Writer_Stream;
use Zend_Session_Namespace;

use function explode;
use function in_array;
use function is_array;
use function str_replace;

/**
 * A simple authentication adapter for LDAP using the Opus\Account mechanism.
 *
 * phpcs:disable
 */
class Ldap extends AuthAdapter
{
    /**
     * Performs an authentication attempt
     *
     * @throws Zend_Auth_Adapter_Exception If authentication cannot be performed.
     * @return Zend_Auth_Result
     */
    public function authenticate()
    {
        $config = new Zend_Config_Ini('../application/configs/config.ini', 'production');

        $log_path = $config->ldap->log_path;
        $admins   = explode(',', $config->ldap->admin_accounts);

        $options = $config->ldap->toArray();

        unset($options['log_path']);
        unset($options['admin_accounts']);

        try {
            // first check local DB with parent class
            $result           = parent::authenticate();
            $user             = new Zend_Session_Namespace('loggedin');
            $user->usernumber = $this->_login;
        } catch (Exception $e) {
            throw $e;
        }
        if ($result->isValid() !== true) {
            try {
                $auth = Zend_Auth::getInstance();

                $adapter = new Zend_Auth_Adapter_Ldap($options, $this->_login, $this->_password);

                $result = $auth->authenticate($adapter);

                // log the result if a log path has been defined in config.ini
                if ($log_path) {
                    $messages = $result->getMessages();

                    $logger = new Zend_Log();
                    $logger->addWriter(new Zend_Log_Writer_Stream($log_path));
                    $filter = new Zend_Log_Filter_Priority(Zend_Log::DEBUG);
                    $logger->addFilter($filter);

                    foreach ($messages as $i => $message) {
                        if ($i-- > 1) { // $messages[2] and up are log messages
                            $message = str_replace("\n", "\n  ", $message);
                            $logger->log("Ldap: $i: $message", Zend_Log::DEBUG);
                        }
                    }
                }

                // if authentication was successfull and user is not already in OPUS DB
                // register user as publisher to OPUS database
                try {
                    $account = new Account(null, null, $this->_login);
                } catch (Exception $ex) {
                    if ($result->isValid() === true) {
                        $user             = new Zend_Session_Namespace('loggedin');
                        $user->usernumber = $this->_login;
                        $account          = new Account();
                        $account->setLogin($this->_login);
                        $account->setPassword($this->_password);
                        $account->store();
                        $roles = Opus_Role::getAll();
                        // look for the publisher role in OPUS DB
                        foreach ($roles as $role) {
                            if ($role->getDisplayName() === 'publisher') {
                                $publisherId = $role->getId();
                            }
                            if ($role->getDisplayName() === 'administrator') {
                                $adminId = $role->getId();
                            }
                        }
                        if ($publisherId > 0) {
                            $accessRole = new Opus_Role($publisherId);
                        } else {
                            // if there is no publisher role in DB, create it
                            $accessRole = new Opus_Role();
                            $accessRole->setName('publisher');
                            // the publisher role needs publish access!
                            $privilege = new Opus_Privilege();
                            $privilege->setPrivilege('publish');
                            $accessRole->addPrivilege($privilege);
                            $accessRole->store();
                        }
                        if ($adminId > 0) {
                            $adminRole = new Opus_Role($adminId);
                        } else {
                            // if there is no publisher role in DB, create it
                            $adminRole = new Opus_Role();
                            $adminRole->setName('administrator');
                            // the publisher role needs publish access!
                            $adminprivilege = new Opus_Privilege();
                            $adminprivilege->setPrivilege('administrate');
                            $adminRole->addPrivilege($adminprivilege);
                            $adminRole->store();
                        }
                        if (in_array($this->_login, $admins) === true) {
                            $account->addRole($adminRole);
                        } else {
                            $account->addRole($accessRole);
                        }
                        $account->store();
                    }
                }
            } catch (Zend_Auth_Adapter_Exception $e) {
                throw $e;
            }
        }

        return $result;
    }

    /**
     * gets userdata from LDAP
     *
     * @return array data of currently logged in user
     */
    public static function getUserdata()
    {
        // get usernumber from session
        // if session has not been defined return false
        $user = new Zend_Session_Namespace('loggedin');
        if (isset($user->usernumber) === false) {
            return false;
        }

        $return = [];

        $config = new Zend_Config_Ini('../application/configs/config.ini', 'production');

        $log_path        = $config->ldap->log_path;
        $multiOptions    = $config->ldap->toArray();
        $mappingSettings = $config->ldapmappings->toArray();

        unset($multiOptions['log_path']);
        unset($multiOptions['admin_accounts']);

        $ldap = new Zend_Ldap();

        foreach ($multiOptions as $name => $options) {
            $mappingFirstName = $mappingSettings[$name]['firstName'];
            $mappingLastName  = $mappingSettings[$name]['lastName'];
            $mappingEMail     = $mappingSettings[$name]['EMail'];
            $permanentId      = $mappingSettings[$name]['personId'];

            $ldap->setOptions($options);
            try {
                $ldap->bind();

                $ldapsearch = $ldap->search('(uid=' . $user->usernumber . ')', 'dc=tub,dc=tu-harburg,dc=de', Zend_Ldap::SEARCH_SCOPE_ONE);

                if ($ldapsearch->count() > 0) {
                    $searchresult = $ldapsearch->getFirst();

                    if (is_array($searchresult[$mappingFirstName]) === true) {
                        $return['firstName'] = $searchresult[$mappingFirstName][0];
                    } else {
                        $return['firstName'] = $searchresult[$mappingFirstName];
                    }
                    if (is_array($searchresult[$mappingLastName]) === true) {
                        $return['lastName'] = $searchresult[$mappingLastName][0];
                    } else {
                        $return['lastName'] = $searchresult[$mappingLastName];
                    }
                    if (is_array($searchresult[$mappingEMail]) === true) {
                        $return['email'] = $searchresult[$mappingEMail][0];
                    } else {
                        $return['email'] = $searchresult[$mappingEMail];
                    }
                    if (is_array($searchresult[$permanentId]) === true) {
                        $return['personId'] = $searchresult[$permanentId][0];
                    } else {
                        $return['personId'] = $searchresult[$permanentId];
                    }
                    return $return;
                }
            } catch (Zend_Ldap_Exception $zle) {
                echo '  ' . $zle->getMessage() . "\n";
                if ($zle->getCode() === Zend_Ldap_Exception::LDAP_X_DOMAIN_MISMATCH) {
                    continue;
                }
            }
        }

        return $return;
    }
}
