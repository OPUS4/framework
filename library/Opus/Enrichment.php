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
 * @author      Felix Ostrowski (ostrowski@hbz-nrw.de)
 * @author      Pascal-Nicolas Becker <becker@zib.de>
 * @author      Gunar Maiwald <maiwald@zib.de>
 * @copyright   Copyright (c) 2008-2011, OPUS 4 development team
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
class Opus_Enrichment extends Opus_Model_Dependent_Abstract
{
    /**
     * Primary key of the parent model.
     *
     * @var mixed $_parentId.
     */
    protected $_parentColumn = 'document_id';

    /**
     * Specify then table gateway.
     *
     * @var string Classname of Zend_DB_Table to use if not set in constructor.
     */
    protected static $_tableGatewayClass = 'Opus_Db_DocumentEnrichments';

    /**
     * Initialize model with the following fields:
     * - KeyName
     * - Value
     *
     * @return void
     */
    protected function _init() {
        $key = new Opus_Model_Field('KeyName');
        $key->setMandatory(true)
                ->setValidator(new Zend_Validate_NotEmpty())
                ->setSelection(true)
                ->setDefault(Opus_EnrichmentKey::getAll());

        $value = new Opus_Model_Field('Value');
        $value->setMandatory(true)
              ->setValidator(new Zend_Validate_NotEmpty());

        $this->addField($key);
        $this->addField($value);
    }


    public function store() {

        // only 'new' DocumentEnrichments without id will be checked !!
        if (!is_null($this->getParentId()) && !is_null($this->getKeyName()) && !is_null($this->getValue())) {

            $table = Opus_Db_TableGateway::getInstance(self::$_tableGatewayClass);

            if (is_null($this->getId())) {
                $select = $table->select()
                                ->where('document_id = ?', $this->getParentId())
                                ->where('key_name = ?', $this->getKeyName())
                                ->where('value = ?', $this->getValue());
            } else {
                $select = $table->select()
                            ->where('id != ?', $this->getId())
                            ->where('document_id = ?', $this->getParentId())
                            ->where('key_name = ?', $this->getKeyName())
                            ->where('value = ?', $this->getValue());
            }

            $row = $table->fetchRow($select);

            if (!is_null($row)) {
                throw new Opus_Model_Exception('DocumentEnrichment with same document_id, key_name and value already exists.');
            }
        }



        // Now really store.
        try {
            return parent::store();
        } catch (Exception $ex) {
            $logger = Zend_Registry::get('Zend_Log');
            if (null !== $logger) {
                $message = "Unknown exception while storing account: ";
                $message .= $ex->getMessage();
                $logger->err(__METHOD__ . ': ' . $message);
            }

            $message = "Caught exception.  Please consult the server logfile.";
            throw new Opus_Security_Exception($message);
        }
    }


}
