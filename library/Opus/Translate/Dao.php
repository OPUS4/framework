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
 * @copyright   Copyright (c) 2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

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
 */
class Opus_Translate_Dao
{

    public function remove($key, $module = null)
    {
        $keysTable = Opus_Db_TableGateway::getInstance('Opus_Db_TranslationKeys');

        $where = $keysTable->getAdapter()->quoteInto('`key` = ?', $key);

        $keysTable->delete($where);
    }

    /**
     * Deletes all translations.
     */
    public function removeAll()
    {
        $keysTable = Opus_Db_TableGateway::getInstance('Opus_Db_TranslationKeys');

        $keysTable->delete('1 = 1');
    }

    /**
     * Deletes all translations for a module.
     * @param $module
     */
    public function removeModule($module)
    {
        $keysTable = Opus_Db_TableGateway::getInstance('Opus_Db_TranslationKeys');
        $where = $keysTable->getAdapter()->quoteInto('`module` = ?', $module);
        $keysTable->delete($where);
    }

    /**
     * Sets translations for a key.
     *
     * Always adding key even if already present.
     *
     * @param $key
     * @param $translation
     */
    public function setTranslation($key, $translation, $module = null)
    {
        $keysTable = Opus_Db_TableGateway::getInstance('Opus_Db_TranslationKeys');

        $database = $keysTable->getAdapter();

        $database->beginTransaction();

        $keysTable->insertIgnoreDuplicate([
            'key' => $key,
            'module' => $module
        ], 'id');

        $keyId = $database->lastInsertId();

        $translationsTable = Opus_Db_TableGateway::getInstance('Opus_Db_Translations');

        foreach($translation as $language => $value) {
            $translationsTable->insertIgnoreDuplicate([
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
    public function getTranslation($key, $locale = null)
    {
        $table = Opus_Db_TableGateway::getInstance('Opus_Db_Translations');

        $select = $table->getAdapter()->select()
            ->from(array('t' => 'translations'), array('locale', 'value'))
            ->join(array('keys' => 'translationkeys'), 't.key_id = keys.id')
            ->where('keys.key = ?', $key);

        $rows = $table->getAdapter()->fetchAll($select);

        if (count($rows) > 0) {
            $result = [];

            foreach ($rows as $row) {
                $locale = $row['locale'];
                $value = $row['value'];

                $result[$key][$locale] = $value;
            }

            return $result[$key];
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
        $table = Opus_Db_TableGateway::getInstance('Opus_Db_Translations');

        $select = $table->getAdapter()->select()
            ->from(array('t' => 'translations'), array('keys.key', 'locale', 'value'))
            ->join(array('keys' => 'translationkeys'), 't.key_id = keys.id');

        if (!is_null($module)) {
            $select->where('keys.module = ?', $module);
        }

        $rows = $table->getAdapter()->fetchAll($select);

        $result = [];

        foreach($rows as $row) {
            $key = $row['key'];
            $locale = $row['locale'];
            $value = $row['value'];

            $result[$key][$locale] = $value;
        }

        return $result;
    }

    public function getTranslationsByLocale($module = null)
    {
        $table = Opus_Db_TableGateway::getInstance('Opus_Db_Translations');

        $select = $table->getAdapter()->select()
            ->from(array('t' => 'translations'), array('keys.key', 'locale', 'value'))
            ->join(array('keys' => 'translationkeys'), 't.key_id = keys.id');

        if (!is_null($module)) {
            $select->where('keys.module = ?', $module);
        } else {
            $select->where('keys.module = \'\''); // TODO 'null' not working - Why?
        }

        $rows = $table->getAdapter()->fetchAll($select);

        $result = [];

        foreach($rows as $row) {
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
     */
    public function addTranslations($translations, $module = null)
    {
        $keysTable = Opus_Db_TableGateway::getInstance('Opus_Db_TranslationKeys');

        $database = $keysTable->getAdapter();

        $database->beginTransaction();

        foreach ($translations as $key => $locales) {
            $keysTable->insertIgnoreDuplicate([
                'key' => $key,
                'module' => $module
            ]);

            $keyId = $keysTable->getAdapter()->lastInsertId();

            $translationsTable = Opus_Db_TableGateway::getInstance('Opus_Db_Translations');

            foreach ($locales as $language => $value) {
                $translationsTable->insertIgnoreDuplicate([
                    'key_id' => $keyId,
                    'locale' => $language,
                    'value'  => $value
                ]);
            }
        }

        $database->commit();
    }

    /**
     * Returns all translations.
     */
    public function getAll()
    {
        return $this->getTranslations();
    }
}
