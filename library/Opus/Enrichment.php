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
 * @author      Jens Schwidder <schwidder@zib.de>
 * @author      Sascha Szott <szott@zib.de>
 * @copyright   Copyright (c) 2008-2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

/**
 * Domain model for enrichments in the Opus framework
 *
 * @category    Framework
 * @package     Opus
 * @uses        Opus_Model_Abstract
 *
 * @method void setKeyName(string $name)
 * @method string getKeyName()
 *
 * @method void setValue(string $value)
 * @method string getValue()
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
    protected function _init()
    {
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

    /**
     * Returns the associated enrichment key or null if it does not exist.
     *
     * @return Opus_EnrichmentKey|null
     * @throws \Opus\Model\Exception
     */
    public function getEnrichmentKey()
    {
        $keyName = $this->getField('KeyName')->getValue();
        if (is_null($keyName) || $keyName === '') {
            return null;
        }

        return Opus_EnrichmentKey::fetchByName($keyName);
    }

    /**
     * Returns the names of all enrichment keys that are currently used by enrichments.
     * This function does not distinguish between enrichment keys that are
     * registered and enrichment keys that are only referenced by name.
     */
    public static function getAllUsedEnrichmentKeyNames()
    {
        $table = Opus_Db_TableGateway::getInstance(self::$_tableGatewayClass);
        $db = $table->getAdapter();
        $select = $db->select()->from('document_enrichments');
        $select->reset('columns');
        $select->columns('key_name');
        $select->distinct(true); // we do not want to consider keys more than once
        return $db->fetchCol($select);
    }
}
