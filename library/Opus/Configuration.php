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
 * @package     Opus
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Domain model for configurations in the Opus framework
 *
 * @category    Framework
 * @package     Opus
 * @uses        Opus_Model_AbstractDb
 */
class Opus_Configuration extends Opus_Model_AbstractDb
{

    /**
     * Specify then table gateway.
     *
     * @var string Classname of Zend_DB_Table to use if not set in constructor.
     */
    protected static $_tableGatewayClass = 'Opus_Db_Configurations';

    /**
     * Initialize model with the required fields.
     *
     * @return void
     */
    protected function _init() {
        $name = new Opus_Model_Field('Name');
        $name->setMandatory(true);
        $theme = new Opus_Model_Field('Theme');
        $theme->setDefault('default');
        $siteName = new Opus_Model_Field('SiteName');
        $adminEmail = new Opus_Model_Field('AdminEmail');
        $smtpServerHost = new Opus_Model_Field('SmtpServerHost');
        $smtpServerLogin = new Opus_Model_Field('SmtpServerLogin');
        $smtpServerPassword = new Opus_Model_Field('SmtpServerPassword');
        
        $this->addField($name)
            ->addField($theme)
            ->addField($siteName)
            ->addField($adminEmail)
            ->addField($smtpServerHost)
            ->addField($smtpServerLogin)
            ->addField($smtpServerPassword);
    }
    
    /**
     * Return a Zend_Config instance with values from the
     * configuration fields.
     *
     * @return Zend_Config Configuration object.
     */
    public function getZendConfig() {
        // Map field values array
        $fieldmap = $this->toArray();
        // Remove Name field
        unset($fieldmap['Name']);
        // Assemble configuration array with the value
        // of getName() as array key
        $cfg = array($this->getName() => $fieldmap);
        // Return a new configuration
        return new Zend_Config($cfg);
    }

}
