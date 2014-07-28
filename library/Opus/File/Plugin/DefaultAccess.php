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
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @copyright   Copyright (c) 2011, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Plugin for adding "default" privileges to a file.
 *
 * @category    Framework
 * @package     Opus
 * @uses        Opus_Model_Abstract
 */
class Opus_File_Plugin_DefaultAccess extends Opus_Model_Plugin_Abstract {

    private $_logger;

    /**
     * Post-store hook will be called right after the document has been stored
     * to the database.
     * 
     * @see {Opus_Model_Plugin_Interface::postStore}
     */
    public function postStore(Opus_Model_AbstractDb $model) {
        // only index Opus_File instances
        if (false === ($model instanceof Opus_File)) {
            $this->getLogger()->err(__METHOD__ . '#1 argument must be instance of Opus_File');
            return;
        }

        // only new Opus_File instances
        if (true !== $model->isNewRecord()) {
            return;
        }

        $config = Zend_Registry::get('Zend_Config');

        if (!is_null($config) && isset($config->securityPolicy->files->defaultAccessRole)) {
            $roleName = $config->securityPolicy->files->defaultAccessRole;

            // Empty name -> don't set any role for access
            if (strlen(trim($roleName)) > 0) {
                $accessRole = Opus_UserRole::fetchByName($roleName);

                if (is_null($accessRole)) {
                    $this->getLogger()->err(__METHOD__ . ": Failed to add role '$roleName' to file " .
                        $model->getId() . "; '$roleName' role does not exist!");
                    return;
                }

                $accessRole->appendAccessFile($model->getId());
                $accessRole->store();
            }
        }
    }

    public function setLogger($logger) {
        $this->_logger = $logger;
    }

    public function getLogger() {
        if (is_null($this->_logger)) {
            $this->_logger = Zend_Registry::get('Zend_Log');
        }

        return $this->_logger;
    }

}

