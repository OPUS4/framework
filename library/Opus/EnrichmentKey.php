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
 * @author      Gunar Maiwald <maiwald@zib.de>
 * @copyright   Copyright (c) 2011, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Domain model for enrichments in the Opus framework
 *
 * @category    Framework
 * @package     Opus
 * @uses        Opus_Model_Abstract
 */
class Opus_EnrichmentKey extends Opus_Model_AbstractDb {
    /**
     * Specify the table gateway.
     *
     * @var string Classname of Zend_DB_Table to use if not set in constructor.
     */
    protected static $_tableGatewayClass = 'Opus_Db_EnrichmentKeys';

    /**
     * Retrieve all Opus_EnrichmentKeys instances from the database.
     *
     * @return array Array of Opus_EnrichmentKeys objects.
     */
    public static function getAll() {
        return self::getAllFrom('Opus_EnrichmentKey', 'Opus_Db_EnrichmentKeys');
    }

    /**
     * Initialize model with the following fields:
     * - Name
     *
     * @return void
     */
    protected function _init() {
        $name = new Opus_Model_Field('Name');
        $name->setMandatory(true)
                ->setValidator(new Zend_Validate_NotEmpty());

        $this->addField($name);
    }

    /**
     * ALTERNATE CONSTRUCTOR: Retrieve Opus_EnrichmentKey instance by name.  Returns
     * null if name is null *or* nothing found.
     *
     * @param  string $name
     * @return Opus_EnrichmentKey
     */
    public static function fetchByName($name = null) {
        if (false === isset($name)) {
            return;
        }

        $table = Opus_Db_TableGateway::getInstance(self::$_tableGatewayClass);
        $select = $table->select()->where('name = ?', $name);
        $row = $table->fetchRow($select);

        if (isset($row)) {
            return new Opus_EnrichmentKey($row);
        }

        return;
    }

    /**
     * Returns name of an enrichmentkey.
     *
     * @see library/Opus/Model/Opus_Model_Abstract#getDisplayName()
     */
    public function getDisplayName() {
       return $this->getName();
    }

    /**
     * Retrieve all Opus_EnrichmentKeys referenced by document from the database.
     *
     * @return array Array of Opus_EnrichmentKeys objects.
     */
    public static function getAllReferenced() {
        $table = Opus_Db_TableGateway::getInstance('Opus_Db_DocumentEnrichments');
        $db = $table->getAdapter();
        $select = $db->select()->from(array('document_enrichments'));
        $select->reset('columns');
        $select->columns("key_name")->distinct(true);
        return $db->fetchCol($select);
    }

}
