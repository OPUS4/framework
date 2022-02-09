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
 * @copyright   Copyright (c) 2008-2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * @category    Framework
 * @package     Opus
 * @author      Felix Ostrowski (ostrowski@hbz-nrw.de)
 * @author      Pascal-Nicolas Becker <becker@zib.de>
 * @author      Gunar Maiwald <maiwald@zib.de>
 * @author      Jens Schwidder <schwidder@zib.de>
 * @author      Sascha Szott <szott@zib.de>
 */

namespace Opus;

use Opus\Db\TableGateway;
use Opus\Model\Dependent\AbstractDependentModel;
use Opus\Model\Field;
use Opus\Model\ModelException;
use Zend_Validate_NotEmpty;

/**
 * Domain model for enrichments in the Opus framework
 *
 * @uses        \Opus\Model\Abstract
 *
 * @category    Framework
 * @package     Opus
 * @method self setKeyName(string $name)
 * @method string getKeyName()
 * @method self setValue(string $value)
 * @method string getValue()
 */
class Enrichment extends AbstractDependentModel
{
    /**
     * Primary key of the parent model.
     *
     * @var mixed
     */
    protected $parentColumn = 'document_id';

    /**
     * Specify then table gateway.
     *
     * @var string Classname of \Zend_DB_Table to use if not set in constructor.
     */
    protected static $tableGatewayClass = Db\DocumentEnrichments::class;

    /**
     * Initialize model with the following fields:
     * - KeyName
     * - Value
     */
    protected function init()
    {
        $key = new Field('KeyName');
        $key->setMandatory(true)
                ->setValidator(new Zend_Validate_NotEmpty())
                ->setSelection(true)
                ->setDefault(EnrichmentKey::getAll());

        $value = new Field('Value');
        $value->setMandatory(true)
            ->setValidator(new Zend_Validate_NotEmpty());

        $this->addField($key);
        $this->addField($value);
    }

    /**
     * Returns the associated enrichment key or null if it does not exist.
     *
     * @return EnrichmentKey|null
     * @throws ModelException
     */
    public function getEnrichmentKey()
    {
        $keyName = $this->getField('KeyName')->getValue();
        if ($keyName === null || $keyName === '') {
            return null;
        }

        return EnrichmentKey::fetchByName($keyName);
    }

    /**
     * Returns the names of all enrichment keys that are currently used by enrichments.
     * This function does not distinguish between enrichment keys that are
     * registered and enrichment keys that are only referenced by name.
     *
     * @return string[]
     */
    public static function getAllUsedEnrichmentKeyNames()
    {
        $table  = TableGateway::getInstance(self::$tableGatewayClass);
        $db     = $table->getAdapter();
        $select = $db->select()->from('document_enrichments');
        $select->reset('columns');
        $select->columns('key_name');
        $select->distinct(true); // we do not want to consider keys more than once
        return $db->fetchCol($select);
    }
}
