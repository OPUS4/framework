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
 * @copyright   Copyright (c) 2018-2022, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\Translate;

use Opus\Common\Translate\StorageInterface;
use Opus\Common\Translate\TranslateException;
use Opus\Db\TableGateway;
use Opus\Db\TranslationKeys;
use Opus\Db\Translations;
use Zend_Db_Statement_Exception;

use function count;
use function is_array;

/**
 * Class for managing custom translations in database.
 *
 * Diffentiating between modules is important, for instance to clean up translations if a module
 * is removed.
 *
 * TODO import TMX files (application?)
 * TODO should implement an interface
 * TODO use custom Row and RowSet classes? Use ->save() function?
 * TODO is it necessary to support same key for multiple modules?
 * TODO how to handle same key in default and module translations is not clear yet. What if a key needs to be edited in
 *      the administration? How to decide which module is meant?
 *
 * TODO merge getTranslations and getTranslationsByModule
 *      This is functionality for the management user interface. The translations are always needed with the module
 *      information.
 */
class Dao implements StorageInterface
{
    const TABLE_TRANSLATION_KEYS = TranslationKeys::class;

    const TABLE_TRANSLATIONS = Translations::class;

    /**
     * @param string      $key
     * @param null|string $module
     */
    public function remove($key, $module = null)
    {
        $keysTable = TableGateway::getInstance(self::TABLE_TRANSLATION_KEYS);

        $where = $keysTable->getAdapter()->quoteInto('`key` = ?', $key);

        $keysTable->delete($where);
    }

    /**
     * Deletes all translations.
     */
    public function removeAll()
    {
        $keysTable = TableGateway::getInstance(self::TABLE_TRANSLATION_KEYS);

        $keysTable->delete('1 = 1');
    }

    /**
     * Deletes all translations for a module.
     *
     * @param string $module
     */
    public function removeModule($module)
    {
        $keysTable = TableGateway::getInstance(self::TABLE_TRANSLATION_KEYS);
        $where     = $keysTable->getAdapter()->quoteInto('`module` = ?', $module);
        $keysTable->delete($where);
    }

    /**
     * Sets translations for a key.
     *
     * Always adding key even if already present.
     *
     * @param string $key
     * @param array  $translation
     * @param string $module
     */
    public function setTranslation($key, $translation, $module = 'default')
    {
        if ($translation === null) {
            $this->remove($key, $module);
            return;
        }

        $keysTable = TableGateway::getInstance(self::TABLE_TRANSLATION_KEYS);

        $database = $keysTable->getAdapter();

        $database->beginTransaction();

        $keysTable->insertIgnoreDuplicate([
            'key'    => $key,
            'module' => $module,
        ], 'id');

        $keyId = $database->lastInsertId();

        $translationsTable = TableGateway::getInstance(self::TABLE_TRANSLATIONS);

        foreach ($translation as $language => $value) {
            $translationsTable->insertIgnoreDuplicate([
                'key_id' => $keyId,
                'locale' => $language,
                'value'  => $value,
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
     * @param string      $key
     * @param null|string $locale
     * @param null|string $module
     * @return null|array
     */
    public function getTranslation($key, $locale = null, $module = null)
    {
        $table = TableGateway::getInstance(self::TABLE_TRANSLATIONS);

        $select = $table->getAdapter()->select()
            ->from(['t' => 'translations'], ['locale', 'value'])
            ->join(['keys' => 'translationkeys'], 't.key_id = keys.id')
            ->where('keys.key = ?', $key);

        if ($locale !== null) {
            $select->where('t.locale = ?', $locale);
        }

        if ($module !== null) {
            $select->where('keys.module = ?', $module);
        }

        $rows = $table->getAdapter()->fetchAll($select);

        if (count($rows) > 0) {
            $result = [];

            if ($locale === null) {
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
     *
     * @param string $needle
     */
    public function findTranslation($needle)
    {
    }

    /**
     * Returns all translations, optionally just for a module.
     *
     * @param null|string $module
     * @return array
     */
    public function getTranslations($module = null)
    {
        $table = TableGateway::getInstance(self::TABLE_TRANSLATIONS);

        $select = $table->getAdapter()->select()
            ->from(['t' => 'translations'], ['keys.key', 'locale', 'value'])
            ->join(['keys' => 'translationkeys'], 't.key_id = keys.id');

        if ($module !== null) {
            $select->where('keys.module = ?', $module);
        }

        $rows = $table->getAdapter()->fetchAll($select);

        $result = [];

        foreach ($rows as $row) {
            $key    = $row['key'];
            $locale = $row['locale'];
            $value  = $row['value'];

            $result[$key][$locale] = $value;
        }

        return $result;
    }

    /**
     * @param null|string $module
     * @return array
     */
    public function getTranslationsByLocale($module = null)
    {
        $table = TableGateway::getInstance(self::TABLE_TRANSLATIONS);

        $select = $table->getAdapter()->select()
            ->from(['t' => 'translations'], ['keys.key', 'locale', 'value'])
            ->join(['keys' => 'translationkeys'], 't.key_id = keys.id');

        if ($module !== null) {
            $select->where('keys.module = ?', $module);
        }

        $rows = $table->getAdapter()->fetchAll($select);

        $result = [];

        foreach ($rows as $row) {
            $key    = $row['key'];
            $locale = $row['locale'];
            $value  = $row['value'];

            $result[$locale][$key] = $value;
        }

        return $result;
    }

    /**
     * Adds translations to the database.
     *
     * @param array  $translations
     * @param string $module
     */
    public function addTranslations($translations, $module = 'default')
    {
        $keysTable = TableGateway::getInstance(self::TABLE_TRANSLATION_KEYS);

        $database = $keysTable->getAdapter();

        $database->beginTransaction();

        foreach ($translations as $key => $locales) {
            $keysTable->insertIgnoreDuplicate([
                'key'    => $key,
                'module' => $module,
            ]);

            $keyId = $keysTable->getAdapter()->lastInsertId();

            $translationsTable = TableGateway::getInstance(self::TABLE_TRANSLATIONS);

            foreach ($locales as $language => $value) {
                $translationsTable->insertIgnoreDuplicate([
                    'key_id' => $keyId,
                    'locale' => $language,
                    'value'  => $value,
                ]);
            }
        }

        $database->commit();
    }

    /**
     * Returns all translations.
     *
     * @return array
     */
    public function getAll()
    {
        return $this->getTranslations();
    }

    /**
     * Renames translation key.
     *
     * @param string $key
     * @param string $newKey
     * @param string $module
     * @throws TranslateException
     */
    public function renameKey($key, $newKey, $module = 'default')
    {
        $keysTable = TableGateway::getInstance(self::TABLE_TRANSLATION_KEYS);

        $database = $keysTable->getAdapter();

        $where = [];

        $where[] = $database->quoteInto('`key` = ?', $key);

        $where[] = $database->quoteInto('`module` = ?', $module);

        $data = [
            'key' => $newKey,
        ];

        $database->beginTransaction();

        try {
            $keysTable->update($data, $where);
        } catch (Zend_Db_Statement_Exception $ndbse) {
            throw new TranslateException($ndbse);
        }

        $database->commit();
    }

    /**
     * @param null|string $modules
     * @return array
     */
    public function getTranslationsWithModules($modules = null)
    {
        $table = TableGateway::getInstance(self::TABLE_TRANSLATIONS);

        $select = $table->getAdapter()->select()
            ->from(['t' => 'translations'], ['keys.key', 'locale', 'value', 'keys.module'])
            ->join(['keys' => 'translationkeys'], 't.key_id = keys.id');

        if ($modules !== null) {
            if (is_array($modules)) {
                $select->where('keys.module IN (?)', $modules);
            } else {
                $select->where('keys.module = ?', $modules);
            }
        }

        $rows = $table->getAdapter()->fetchAll($select);

        $result = [];

        foreach ($rows as $row) {
            $key    = $row['key'];
            $locale = $row['locale'];
            $value  = $row['value'];
            $module = $row['module'];

            $result[$key]['module']          = $module;
            $result[$key]['values'][$locale] = $value;
        }

        return $result;
    }

    /**
     * @inheritDoc
     * @return string[]
     */
    public function getModules()
    {
        $table = TableGateway::getInstance(self::TABLE_TRANSLATIONS);

        $select = $table->getAdapter()->select()
            ->from(['keys' => 'translationkeys'], ['keys.module'])->distinct();

        return $table->getAdapter()->fetchCol($select);
    }
}
