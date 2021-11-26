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
 * @copyright   Copyright (c) 2021, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * @category    Framework
 * @package     Opus\Db2
 * @author      Jens Schwidder <schwidder@zib.de>
 */

namespace Opus\Db2;

use Doctrine\ORM\EntityRepository;
use Opus\Model2\Language;

use function array_merge;
use function array_unique;
use function in_array;

/**
 * Database specific class for Language functions.
 *
 * This class keeps the database (Doctrine) specific code out of the model class.
 *
 * TODO create LanguageRepositoryTest class - the tests for Language will be moved to opus4-common, so we need the tests
 *      here
 * TODO we probably need an interface - How would we replace this implementation with another (using MongoDB)?
 */
class LanguageRepository extends EntityRepository
{
    /**
     * Retrieve all Opus\Language instances from the database.
     *
     * @return array|object[]
     */
    public function getAll()
    {
        return $this->findAll();
    }

    /**
     * Get all active languages.
     *
     * @return object[]
     */
    public function getAllActive()
    {
        return $this->findBy(['active' => 1]);
    }

    /**
     * Get all active languages.
     *
     * @return object[]
     */
    public function getAllActiveTable()
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $query        = $queryBuilder->select('*')
            ->from('languages', 'Language')
            ->where('active = 1')
            ->getQuery();
        return $query->getArrayResult();
    }

    /**
     * Get properties of language object as array for a specific terminology code
     *
     * @param string $code ISO639-2 terminology code to retrieve properties for
     * @return Language|null Language model or null if object not found in database
     */
    public function getLanguageByPart2T($code)
    {
        $language = $this->findOneBy(['part2T' => $code]);
        return $language instanceof Language ? $language : null;
    }

    /**
     * Returns part2_t language code for locale (part1 code).
     *
     * @param string $locale
     * @return null|string
     */
    public function getPart2tForPart1($locale)
    {
        $language = $this->findOneBy(['part1' => $locale]);

        if ($language instanceof Language) {
            return $language->getPart2T();
        }

        return null;
    }

    /**
     * Returns language code for internal language identifier.
     *
     * @param string      $languageIdentifier Internal language identifier (e.g. 'deu')
     * @param null|string $part string Field to use for language code
     * @return string Language code
     */
    public function getLanguageCode($languageIdentifier, $part = null)
    {
        $language = $this->getLanguageByPart2T($languageIdentifier);

        if ($language instanceof Language) {
            if (
                $part !== null
                && in_array($part, [Language::PROPERTY_PART2B, Language::PROPERTY_PART2T, Language::PROPERTY_PART1])
            ) {
                $code = $language->{"get" . $part}();
            }

            if (empty($code)) {
                $code = $language->getPart2B();
            }
        }
        return empty($code) ? $languageIdentifier : $code;
    }

    /**
     * Returns all languages used in database.
     *
     * In order to check used language we need to look at multiple tables that contain a language column.
     *
     * @return array|null
     */
    public function getUsedLanguages()
    {
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
            // Using the ORM queryBuilder does not work here because there are currently
            // no entity classes for the tables.
            //$queryBuilder = $this->getEntityManager()->createQueryBuilder();
            //$query = $queryBuilder->select('language')
            //    ->distinct()
            //    ->from($table, $table)
            //    ->where('language IS NOT NULL')
            //    ->getQuery();
            // $rows = $query->getArrayResult();

            $conn         = Database::getConnection();
            $queryBuilder = $conn->createQueryBuilder();
            $query        = $queryBuilder->select('language')
                ->distinct()
                ->from($table)
                ->where('language IS NOT NULL');

            $rows = $conn->fetchFirstColumn($query);

            if ($rows !== false) {
                $languages = array_merge($languages, $rows);
            }
        }

        return array_unique($languages);
    }
}
