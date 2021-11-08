<?php

/**
 * LICENCE
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @copyright   Copyright (c) 2009-2018
 *              Saechsische Landesbibliothek - Staats- und Universitaetsbibliothek Dresden (SLUB)
 * @copyright   Copyright (c) 2010-2018 OPUS 4 development team
 *
 * TODO add interface (database implementation just one option)
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * @category    Framework
 * @package     Opus\Model
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @author      Henning Gerhardt <henning.gerhardt@slub-dresden.de>
 * @author      Jens Schwidder <schwidder@zib.de>
 */

namespace Opus\Model\Xml;

use DOMDocument;
use Opus\Db\DocumentXmlCache;
use Opus\Document;
use Opus\DocumentFinder;
use Opus\Log;
use Opus\LoggingTrait;
use Opus\Model\ModelException;
use Opus\Model\NotFoundException;
use Zend_Db_Select;
use Zend_Db_Table;

use function count;
use function libxml_clear_errors;
use function libxml_get_errors;

/**
 * TODO NAMESPACE rename class
 *
 * phpcs:disable
 */
class Cache implements XmlCacheInterface
{
    use LoggingTrait;

    /**
     * Holds gateway instance to document xml cache table
     *
     * @var DocumentXmlCache
     */
    private $_table;

    /**
     * Perform document reindexing after a new cache entry is created
     *
     * @var bool
     */
    private $_reindexDocumentAfterAddingCacheEntry = true;

    /**
     * Plugin for updating the index.
     *
     * @var
     *
     * TODO this is a temporary hack - see _postPut below
     */
    private static $indexPluginClass;

    public function __construct()
    {
        $this->_table = new DocumentXmlCache();
    }

    /**
     * Gets DOMDocument object for document from cache.
     *
     * @param mixed $documentId
     * @param mixed $xmlVersion
     * @throws ModelException in case an XML processing error occurred
     * @return DOMDocument
     */
    public function get($documentId, $xmlVersion)
    {
        $dom = new DOMDocument('1.0', 'utf-8');

        $xmlData = $this->getData($documentId, $xmlVersion);

        if ($xmlData !== null) {
            libxml_clear_errors();
            $result = $dom->loadXML($xmlData);
            $errors = libxml_get_errors();
            if ($result === false) {
                $errMsg = 'XML processing error for document with id ' . $documentId . "\n"
                    . 'number of errors: ' . count($errors) . "\n";
                foreach ($errors as $errnum => $error) {
                    $errMsg .= "\n" . 'error #' . $errnum . "\n\t"
                        . 'error level: ' . $error->level . "\n\t"
                        . 'error code: ' . $error->code . "\n\t"
                        . 'error message: ' . $error->message . "\n\t"
                        . 'line:column: ' . $error->line . ':' . $error->column;
                }
                Log::get()->err($errMsg);
                throw new ModelException($errMsg);
            }
        }

        return $dom;
    }

    /**
     * Returns document XML from cache.
     *
     * @param $documentId int Database ID of document
     * @param $xmlVersion string Version of XML
     * @return null|string Document XML from cache
     */
    public function getData($documentId, $xmlVersion)
    {
        $rowSet = $this->_table->find($documentId, $xmlVersion);

        $xmlData = null;

        if (1 === $rowSet->count()) {
            $xmlData = $rowSet->current()->xml_data;
        }

        return $xmlData;
    }

    /**
     * Returns all cache entries as an array.
     *
     * @return array
     */
    public function getAllEntries()
    {
        $rows = $this->_table->fetchAll();

        if ($rows->count() > 0) {
            $result = $rows->toArray();
        } else {
            $result = [];
        }

        return $result;
    }

    /**
     * Checks if a document is inside cache.
     *
     * @param mixed $documentId
     * @param mixed $xmlVersion
     * @return bool
     */
    public function hasCacheEntry($documentId, $xmlVersion)
    {
        $rowSet = $this->_table->find($documentId, $xmlVersion);

        if (1 === $rowSet->count()) {
            return true;
        }

        return false;
    }

    /**
     * Check if a document in a specific xml version is already cached or not.
     *
     * @param mixed $documentId
     * @param mixed $xmlVersion
     * @param mixed $serverDateModified
     * @return bool Returns true on cached hit else false.
     */
    public function hasValidEntry($documentId, $xmlVersion, $serverDateModified)
    {
        $select = $this->_table->select()->from($this->_table);
        $select->where('document_id = ?', $documentId)
            ->where('xml_version = ?', $xmlVersion)
            ->where('server_date_modified = ?', $serverDateModified);

        $row = $this->_table->fetchRow($select);

        if (null === $row) {
            $result = false;
        } else {
            $result = true;
        }

        return $result;
    }

    /**
     * @param mixed $documentId
     * @param mixed $xmlVersion
     * @param mixed $serverDateModified
     */
    public function put($documentId, $xmlVersion, $serverDateModified, $xmlData)
    {
        // skip adding cache entry if it is a valid entry already existing
        if (true === $this->hasValidEntry($documentId, $xmlVersion, $serverDateModified)) {
            return;
        }

        // remove existing cache entry in case of update
        if (true === $this->hasCacheEntry($documentId, $xmlVersion)) {
            $this->remove($documentId, $xmlVersion);
        }

        $newValue = [
            'document_id'          => $documentId,
            'xml_version'          => $xmlVersion,
            'server_date_modified' => $serverDateModified,
            'xml_data'             => $xmlData->saveXML(),
        ];

        $this->_table->insert($newValue);

        $this->_postPut($documentId);
    }

    /**
     * Removes a cache entry.
     *
     * @param mixed      $documentId
     * @param null|mixed $xmlVersion
     * @return bool
     *
     * TODO simplify (remove all entries for document-id)
     */
    public function remove($documentId, $xmlVersion = null)
    {
        if ($xmlVersion === null) {
            $this->removeAllEntriesWhereDocumentId($documentId);
        } else {
            $rowSet = $this->_table->find($documentId, $xmlVersion);

            if (1 === $rowSet->count()) {
                $result = $rowSet->current()->delete();
                if (1 !== $result) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Removes a all cache entries for a given document.
     *
     * @param mixed $documentId
     */
    public function removeAllEntriesWhereDocumentId($documentId)
    {
        $where = ['document_id' => $documentId];
        $this->_table->deleteWhereArray($where);
    }

    /**
     * Removes a all cache entries matching given constraint.
     *
     * @param Zend_Db_Select $select Select statement to use as subselect
     *  The statement MUST return a list of document ids
     */
    public function removeAllEntriesWhereSubSelect($select)
    {
        $where = 'document_id IN (' . $select->assemble() . ')';
        $this->_table->delete($where);
    }

    /**
     * Removes all cache entries.
     */
    public function clear()
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->query('truncate table document_xml_cache');
    }

    /**
     * Removes all entries that are linked to model.
     *
     * @param $model
     */
    public function removeAllEntriesForDependentModel($model)
    {
        $documentFinder = new DocumentFinder();

        $documentFinder->setDependentModel($model);
        $select = $documentFinder->getSelectIds();

        $this->removeAllEntriesWhereSubSelect($select);
    }

    /**
     * Post cache put hook. Functionality needed to keep
     * document in a consistent state after cache update.
     *
     * @param int $documentId Id of document to process
     *
     * TODO cache and index should be indenpendent from each other (refactor)
     *      Currently this is the easiest way to keep OPUS 4 working properly.
     */
    protected function _postPut($documentId)
    {
        if (! $this->_reindexDocumentAfterAddingCacheEntry || self::$indexPluginClass === null) {
            return;
        }

        try {
            $doc = new Document($documentId);
        } catch (NotFoundException $e) {
            // document requested for indexing does not longer exist: we could simply ignore this
            return;
        }

        $pluginClass = self::$indexPluginClass;

        $indexPlugin = new $pluginClass();
        $indexPlugin->postStore($doc);
    }

    public static function setIndexPluginClass($pluginClass)
    {
        self::$indexPluginClass = $pluginClass;
    }
}
