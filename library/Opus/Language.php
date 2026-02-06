<?php

/**
 * This file is part of OPUS. The software OPUS has been originally developed
 * at the University of Stuttgart with funding from the German Research Net,
 * the Federal Department of Higher Education and Research and the Ministry
 * of Science, Research and the Arts of the State of Baden-Wuerttemberg.
 *
 * OPUS 4 is a complete rewrite of the original OPUS software and was developed
 * by the Stuttgart University Library, the Library Service Center
 * Baden-Wuerttemberg, the North Rhine-Westphalian Library Service Center,
 * the Cooperative Library Network Berlin-Brandenburg, the Saarland University
 * and State Library, the Saxon State Library - Dresden State and University
 * Library, the Bielefeld University Library and the University Library of
 * Hamburg University of Technology with funding from the German Research
 * Foundation and the European Regional Development Fund.
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
 * @copyright   Copyright (c) 2009, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus;

use Opus\Common\LanguageInterface;
use Opus\Common\LanguageRepositoryInterface;
use Opus\Db\TableGateway;
use Opus\Model\AbstractDb;
use Opus\Model\Field;
use Zend_Validate_NotEmpty;

use function array_merge;
use function array_unique;
use function in_array;

/**
 * Domain model for languages in the Opus framework
 *
 * TODO define allowed types (const?)
 * TODO define allowed scopes
 * TODO disable caching
 *
 * phpcs:disable
 */
class Language extends AbstractDb implements LanguageInterface, LanguageRepositoryInterface
{
    /**
     * Specify then table gateway.
     *
     * @var string Classname of \Zend_DB_Table to use if not set in constructor.
     */
    protected static $tableGatewayClass = Db\Languages::class;

    /**
     * Cache used languages to reduce database queries.
     *
     * @var null|array
     */
    private static $usedLanguages;

    /**
     * Initialize model with fields.
     */
    protected function init()
    {
        $part2B = new Field('Part2B');

        $part2T = new Field('Part2T');
        $part2T->setMandatory(true)
            ->setValidator(new Zend_Validate_NotEmpty());

        $part1 = new Field('Part1');
        $scope = new Field('Scope');
        $type  = new Field('Type');

        $refName = new Field('RefName');
        $refName->setMandatory(true)
            ->setValidator(new Zend_Validate_NotEmpty());

        $comment = new Field('Comment');
        $active  = new Field('Active');
        $active->setCheckbox(true);
        $active->setType('bool');

        $this->addField($part2B)
            ->addField($part2T)
            ->addField($part1)
            ->addField($scope)
            ->addField($type)
            ->addField($refName)
            ->addField($comment)
            ->addField($active);
    }

    /**
     * Retrieve all Opus\Language instances from the database.
     *
     * @return array Array of Opus\Language objects.
     */
    public function getAll()
    {
        return self::getAllFrom(self::class, self::$tableGatewayClass);
    }

    /**
     * Get all active languages.
     *
     * @return array Array of Opus\Language objects which are active.
     */
    public function getAllActive()
    {
        $table  = TableGateway::getInstance(self::$tableGatewayClass);
        $rows   = $table->fetchAll($table->select()->where('active = ?', 1));
        $result = [];
        foreach ($rows as $row) {
            $result[] = new Language($row);
        }
        return $result;
    }

    /**
     * Get all active languages.
     *
     * @return array Array of Opus\Language objects which are active.
     */
    public function getAllActiveTable()
    {
        $table = TableGateway::getInstance(self::$tableGatewayClass);
        return $table->fetchAll($table->select()->where('active = ?', 1))->toArray();
    }

    /**
     * Get properties of language object as array for a specific terminology code
     *
     * @param string $code ISO639-2 terminology code to retrieve properties for
     * @return array|null Array of properties or null if object not found in database
     */
    public function getPropertiesByPart2T($code)
    {
        $table = TableGateway::getInstance(self::$tableGatewayClass);
        $rows  = $table->fetchAll($table->select()->where('part2_t = ?', $code))->toArray();
        return $rows[0] ?? null;
    }

    /**
     * Returns part2_t language code for locale (part1 code).
     *
     * @param string $locale
     * @return null|string
     */
    public function getPart2tForPart1($locale)
    {
        $table  = TableGateway::getInstance(self::$tableGatewayClass);
        $select = $table->select()->from([$table->info('name')], ['part2_t'])->where('part1 = ?', $locale);
        $rows   = $table->fetchRow($select);
        if ($rows !== null && isset($rows['part2_t'])) {
            return $rows['part2_t'];
        }
        return null;
    }

    /**
     * Returns reference language name.
     *
     * @see \Opus\Model\Abstract#getDisplayName()
     * @return string
     */
    public function getDisplayName()
    {
        return $this->getRefName();
    }

    /**
     * Returns language code for internal language identifier.
     *
     * @param string $language Internal language identifier (e.g. 'deu')
     * @param null $part string Field to use for language code
     * @return string Language code
     */
    public function getLanguageCode($language, $part = null)
    {
        $result = self::getPropertiesByPart2T($language);

        if (empty($result)) {
            return $language;
        }

        $code = null;

        if ($part !== null && isset($result[$part])) {
            $code = $result[$part];
        } else {
            $code = $result['part2_b'];
        }

        return empty($code) ? $language : $code;
    }

    /**
     * Checks if a language is being used in database.
     *
     * Language values are used in multiple tables:
     * - document_licences
     * - documents
     * - document_files
     * - document_subjects
     * - document_title_abstracts
     */
    public function isUsed()
    {
        $languages = self::getUsedLanguages();
        return in_array($this->getPart2T(), $languages);
    }

    /**
     * Returns all languages used in database.
     */
    public function getUsedLanguages()
    {
        if (self::$usedLanguages !== null) {
            return self::$usedLanguages;
        }

        $table    = TableGateway::getInstance(self::$tableGatewayClass);
        $database = $table->getAdapter();

        $tables = [
            'documents',
            'document_title_abstracts',
            'document_licences',
            'document_subjects',
            'document_files',
        ];

        $languages = [];

        // get languages for documents
        foreach ($tables as $table) {
            $select = $database->select()->distinct()->from($table, ['language'])->where('language is not null');
            $rows   = $database->fetchCol($select);

            if ($rows !== false) {
                $languages = array_merge($languages, $rows);
            }
        }

        self::$usedLanguages = array_unique($languages);

        return self::$usedLanguages;
    }

    /**
     * Removes cached values.
     */
    public static function clearCache()
    {
        self::$usedLanguages = null;
    }

    public function getActive()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function setActive($active)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function getComment()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function setComment($comment)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function getPart1()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function setPart1($part1)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function getPart2B()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function setPart2B($part2b)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function getPart2T()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function setPart2T($part2t)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function getRefName()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function setRefName($refName)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function getScope()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function setScope($scope)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function getType()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function setType($type)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }
}
