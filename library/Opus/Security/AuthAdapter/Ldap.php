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
 * @package     Opus_Security
 * @author      Oliver Marahrens <o.marahrens@tu-harburg.de>
 * @copyright   Copyright (c) 2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id $
 */

/**
 * A simple authentication adapter for LDAP using the Opus_Account mechanism.
 *
 * @category    Framework
 * @package     Opus_Security
 */
class Opus_Security_AuthAdapter_Ldap extends Opus_Security_AuthAdapter {

    /**
     * Performs an authentication attempt
     *
     * @throws Zend_Auth_Adapter_Exception If authentication cannot be performed.
     * @return Zend_Auth_Result
     */
    public function authenticate() {
        
        $config = new Zend_Config_Ini('../config/config.ini', 'production');
        
        $log_path = $config->ldap->log_path;
        
        $options = $config->ldap->toArray();
        
        unset($options['log_path']);
        
        try {
        	// first check local DB with parent class
        	$result = parent::authenticate();
        }
        catch (Exception $e) {
            try {
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
                    $account = new Opus_Account(null, null, $this->_login);
                } catch (Exception $ex) {
                    if ($result->isValid() === true) {
            	        $account= new Opus_Account();
    		            $account->setLogin($this->_login);
    		            $account->setPassword($this->_password);
    		            $account->store();
        		        $roles = Opus_Role::getAll();
            		    // look for the publisher role in OPUS DB
            		    foreach ($roles as $role) {
            			    if ($role->getDisplayName() === 'publisher') {
        	    			    $publisherId = $role->getId();
        		    	    }
        		        }
    	    	        if ($publisherId > 0) {
    	    	            $accessRole = new Opus_Role($publisherId);
    	    	        }
        		        else {
        			        // if there is no publisher role in DB, create it
        			        $accessRole = new Opus_Role();
        		            $accessRole->setName('publisher');
            		        // the publisher role needs publish access!
            		        $privilege = new Opus_Privilege();
                            $privilege->setPrivilege('publish');
                            $accessRole->addPrivilege($privilege);
    	    	            $accessRole->store();
        		        }
    		            $account->addRole($accessRole);
    		            $account->store();
                    }
    		    }
            }
            catch (Zend_Auth_Adapter_Exception $e) {
            	throw $e;
            }
        }
        
        return $result;
    }

}
