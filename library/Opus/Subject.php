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

use Opus\Common\Config;
use Opus\Common\SubjectInterface;
use Opus\Common\SubjectRepositoryInterface;
use Opus\Db\TableGateway;
use Opus\Model\Dependent\AbstractDependentModel;
use Opus\Model\Field;
use Zend_Validate_NotEmpty;

use function func_get_args;

/**
 * Domain model for document subjects in the Opus framework
 */
class Subject extends AbstractDependentModel implements SubjectInterface, SubjectRepositoryInterface
{
    const SWD          = 'swd';
    const PSYNDEX      = 'psyndex';
    const UNCONTROLLED = 'uncontrolled';

    /**
     * Primary key of the parent model.
     *
     * @var mixed
     */
    protected $parentColumn = 'document_id';

    /**
     * Specify then table gateway.
     *
     * @var string
     */
    protected static $tableGatewayClass = Db\DocumentSubjects::class;

    /**
     * Initialize model with the following fields:
     * - Language
     * - Type
     * - Value
     * - External key
     */
    protected function init()
    {
        $language           = new Field('Language');
        $availableLanguages = Config::getInstance()->getTempPath();
        if ($availableLanguages !== null) {
            $language->setDefault($availableLanguages);
        }
        $language->setSelection(true);
        $language->setMandatory(true);

        $type = new Field('Type');
        $type->setMandatory(true);
        $type->setSelection(true);
        $type->setDefault([
            'swd'          => 'swd',
            'psyndex'      => 'psyndex',
            'uncontrolled' => 'uncontrolled',
        ]);

        $value = new Field('Value');
        $value->setMandatory(true)
            ->setValidator(new Zend_Validate_NotEmpty());

        $externalKey = new Field('ExternalKey');

        $this->addField($language)
            ->addField($type)
            ->addField($value)
            ->addField($externalKey);
    }

    /**
     * Return matching keywords for use in autocomplete function.
     *
     * @param string $term String that must be included in keyword
     * @param string $type Type of keywords
     * @param int    $limit Maximum number of returned results
     * @return array
     */
    public function getMatchingSubjects($term, $type = 'swd', $limit = 20)
    {
        $table = TableGateway::getInstance(self::$tableGatewayClass);

        $select = $table->select()
            ->from($table, ['value', 'external_key'])
            ->where('value like ?', "%$term%")
            ->order('value ASC')
            ->group(['value', 'external_key']);

        if ($type !== null) {
            $select->where('type = ?', $type);
        }

        if ($limit !== null) {
            $select->limit($limit, 0);
        }

        $rows = $table->fetchAll($select);

        $values = [];

        foreach ($rows as $row) {
            $columns = $row->toArray();

            $subject           = [];
            $subject['value']  = $columns['value'];
            $subject['extkey'] = $columns['external_key'];

            $values[] = $subject;
        }

        return $values;
    }

    /**
     * @return string|null
     */
    public function getExternalKey()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @param string|null $externalKey
     * @return $this
     */
    public function setExternalKey($externalKey)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @return string|null
     * @throws Common\Model\ModelException
     */
    public function getLanguage()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @param string $lang
     * @return $this
     */
    public function setLanguage($lang)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @return string|null
     */
    public function getType()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @return string|null
     */
    public function getValue()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setValue($value)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }
}
