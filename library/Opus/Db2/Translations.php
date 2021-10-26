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
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2021, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\Db2;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Opus\Translate\StorageInterface;
use Opus\Db2\TableGateway;
use Opus\Translate\TranslateException;

/**
 * Class for managing custom translations in database.
 *
 * Diffentiating between modules is important, for instance to clean up translations if a module
 * is removed.
 *
 * TODO import TMX files (application?)
 * TODO use custom Row and RowSet classes? Use ->save() function?
 * TODO is it necessary to support same key for multiple modules?
 * TODO how to handle same key in default and module translations is not clear yet. What if a key needs to be edited in
 *      the administration? How to decide which module is meant?
 *
 * TODO merge getTranslations and getTranslationsByModule
 *      This is functionality for the management user interface. The translations are always needed with the module
 *      information.
 *
 * TODO rename StorageInterface into TranslationStorageInterface
 * TODO remove old Zend_Db Dao class
 * TODO remove dependency on TableGateway class or?
 */
class Translations extends TableGateway implements StorageInterface
{

    const TABLE_TRANSLATION_KEYS = 'translationkeys';

    const TABLE_TRANSLATIONS = 'translations';


    /**
     * @param $key
     * @param null $module
     * @return mixed|void
     *
     * TODO SQL injection?
     */
    public function remove($key, $module = null)
    {
        $database = $this->getDatabaseAdapter();

        $database->delete(
            self::TABLE_TRANSLATION_KEYS,
            ['`key`' => $key]
        );
    }

    /**
     * Deletes all translations.
     *
     * TODO better way to delete all rows?
     */
    public function removeAll()
    {
        $database = $this->getDatabaseAdapter();

        $database->delete(self::TABLE_TRANSLATION_KEYS, ['1' => '1']);
    }

    /**
     * Deletes all translations for a module.
     * @param $module
     *
     * TODO SQL injection?
     */
    public function removeModule($module)
    {
        $database = $this->getDatabaseAdapter();

        $database->delete(self::TABLE_TRANSLATION_KEYS, ['`module`' => $module]);
    }

    /**
     * Sets translations for a key.
     *
     * Always adding key even if already present.
     *
     * @param $key
     * @param $translation
     */
    public function setTranslation($key, $translation, $module = 'default')
    {
        if (is_null($translation)) {
            return $this->remove($key, $module);
        }

        $database = $this->getDatabaseAdapter();

        $database->beginTransaction();

        $this->insertIgnoreDuplicate(self::TABLE_TRANSLATION_KEYS, 'id', [
            'key' => $key,
            'module' => $module
        ]);

        $keyId = $database->lastInsertId();

        foreach ($translation as $language => $value) {
            $this->insertIgnoreDuplicate(self::TABLE_TRANSLATIONS, null, [
                'key_id' => $keyId,
                'locale' => $language,
                'value' => $value
            ]);
        }

        $database->commit();
    }

    /**
     * Returns translation for a key, optionally a locale.
     *
     * This function gets 0 or more rows from database depending on how many locales are
     * stored.
     *
     * @param      $key
     * @param null $locale
     */
    public function getTranslation($key, $locale = null, $module = null)
    {
        $conn = $this->getDatabaseAdapter();

        $queryBuilder = $conn->createQueryBuilder();

        $select = $queryBuilder->select('locale', 'value')
            ->from('translations', 't')
            ->join('t', 'translationkeys', 'k', 't.key_id = k.id')
            ->where("k.key = ?"); // TODO SQL injection

        if (! is_null($locale)) {
            $select->andWhere("t.locale = '$locale'"); // TODO SQL injection
        }

        if (! is_null($module)) {
            $select->andWhere("k.module = '$module'"); // TODO SQL injection
        }

        $rows = $conn->fetchAllAssociative($select, [$key]);

        if (count($rows) > 0) {
            $result = [];

            if (is_null($locale)) {
                foreach ($rows as $row) {
                    $result[$row['locale']] = $row['value'];
                }
            } else {
                foreach ($rows as $row) {
                    $result[] = $row['value'];
                }

                if (count($result) === 1) {
                    $result = $result[0];
                }
            }

            return $result;
        } else {
            return null;
        }
    }

    /**
     * Finds a translation containing the search string.
     * @param $needle
     */
    public function findTranslation($needle)
    {
    }

    /**
     * Returns all translations, optionally just for a module.
     * @param null $module
     */
    public function getTranslations($module = null)
    {
        $conn = $this->getDatabaseAdapter();

        $queryBuilder = $conn->createQueryBuilder();

        $select = $queryBuilder->select('k.key', 'locale', 'value')
            ->from('translations', 't')
            ->join('t','translationkeys', 'k', 't.key_id = k.id');

        if (! is_null($module)) {
            $select->where('k.module = ?');
        }

        $rows = $conn->fetchAllAssociative($select, [$module]);

        $result = [];

        foreach ($rows as $row) {
            $key = $row['key'];
            $locale = $row['locale'];
            $value = $row['value'];

            $result[$key][$locale] = $value;
        }

        return $result;
    }

    public function getTranslationsByLocale($module = null)
    {
        $conn = $this->getDatabaseAdapter();

        $queryBuilder = $conn->createQueryBuilder();

        $select = $queryBuilder->select('k.key', 'locale', 'value')
            ->from('translations', 't')
            ->join('t', 'translationkeys', 'k', 't.key_id = k.id');

        if (! is_null($module)) {
            $select->where('k.module = ?');
        }

        $rows = $conn->fetchAllAssociative($select, [$module]);

        $result = [];

        foreach ($rows as $row) {
            $key = $row['key'];
            $locale = $row['locale'];
            $value = $row['value'];

            $result[$locale][$key] = $value;
        }

        return $result;
    }

    /**
     * Adds translations to the database.
     * @param $translations
     *
     * TODO TableGateway dependency
     */
    public function addTranslations($translations, $module = 'default')
    {
        $conn = $this->getDatabaseAdapter();

        $conn->beginTransaction();

        foreach ($translations as $key => $locales) {
            $this->insertIgnoreDuplicate(self::TABLE_TRANSLATION_KEYS, 'id', [
                'key' => $key,
                'module' => $module
            ]);

            $keyId = $conn->lastInsertId();

            foreach ($locales as $language => $value) {
                $this->insertIgnoreDuplicate(self::TABLE_TRANSLATIONS, null, [
                    'key_id' => $keyId,
                    'locale' => $language,
                    'value'  => $value
                ]);
            }
        }

        $conn->commit();
    }

    /**
     * Returns all translations.
     */
    public function getAll()
    {
        return $this->getTranslations();
    }

    /**
     * Renames translation key.
     *
     * @throws TranslateException
     *
     * TODO remove dependency on TableGateway
     */
    public function renameKey($key, $newKey, $module = 'default')
    {
        $conn = $this->getDatabaseAdapter();

        $where = [];
        $where['`key`'] = $key;
        $where['`module`'] = $module;

        $data = [
            '`key`' => $newKey
        ];

        $conn->beginTransaction();

        try {
            $conn->update(self::TABLE_TRANSLATION_KEYS, $data, $where);
        } catch (UniqueConstraintViolationException $ex) {
            throw new TranslateException($ex);
        }

        $conn->commit();
    }

    public function getTranslationsWithModules($modules = null)
    {
        $conn = $this->getDatabaseAdapter();

        $queryBuilder = $conn->createQueryBuilder();

        $select = $queryBuilder->select('k.key', 'locale', 'value', 'k.module')
            ->from('translations', 't' )
            ->join('t', 'translationkeys', 'k', 't.key_id = k.id');

        if (! is_null($modules)) {
            if (is_array($modules)) {
                $select->where('k.module IN (?)');
                $rows = $conn->fetchAllAssociative($select, [$modules], [$conn::PARAM_STR_ARRAY]);
            } else {
                $select->where('k.module = ?');
                $rows = $conn->fetchAllAssociative($select, [$modules]);
            }
        } else {
            $rows = $conn->fetchAllAssociative($select);
        }


        $result = [];

        foreach ($rows as $row) {
            $key = $row['key'];
            $locale = $row['locale'];
            $value = $row['value'];
            $module = $row['module'];

            $result[$key]['module'] = $module;
            $result[$key]['values'][$locale] = $value;
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getModules()
    {
        $conn = $this->getDatabaseAdapter();

        $queryBuilder = $conn->createQueryBuilder();

        $select = $queryBuilder
            ->select('module')
            ->from(self::TABLE_TRANSLATION_KEYS)
            ->distinct();

        $rows = $conn->fetchFirstColumn($select);

        return $rows;
    }
}
