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
 * @category    Framework
 * @package     Qucosa_Search
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @author      Henning Gerhardt <henning.gerhardt@slub-dresden.de>
 * @copyright   Copyright (c) 2009-2010
 *              Saechsische Landesbibliothek - Staats- und Universitaetsbibliothek Dresden (SLUB)
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id: Cache.php 5765 2010-06-07 14:15:00Z claussni $
 */

/**
 * Search Hit model class.
 *
 * @category    Framework
 * @package     Qucosa_Search
 * @uses        Opus_Model_Abstract
 */
class Opus_Model_Xml_Cache {

    /**
     * Holds gateway instance to document xml cache table
     *
     * @var Opus_Db_DocumentXmlCache
     */
    private $_table = null;

    /**
     *
     *
     * @return void
     */
    public function __construct() {
        $this->_table = new Opus_Db_DocumentXmlCache;
    }

    /**
     *
     *
     * @param mixed $documentId
     * @param mixed $xmlVersion
     * @return DOMDocument
     */
    public function get($documentId, $xmlVersion) {

        $dom = new DOMDocument('1.0', 'utf-8');

        $rowSet = $this->_table->find($documentId, $xmlVersion);
        if (1 === $rowSet->count()) {
            $xmlData = $rowSet->current()->xml_data;
            $dom->loadXML($xmlData);
        }

        return $dom;
    }

    /**
     * Returns all cache entries as an array.
     *
     * @return array
     */
    public function getAllEntries() {

        $rows = $this->_table->fetchAll();

        if ($rows->count() > 0) {
            $result = $rows->toArray();
        } else {
            $result = array();
        }

        return $result;
    }

    /**
     * Checks if a document is inside cache.
     *
     * @param mixed $documentId
     * @param mixed $xmlVersion
     * @return boolean
     */
    public function hasCacheEntry($documentId, $xmlVersion) {
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
    public function hasValidEntry($documentId, $xmlVersion, $serverDateModified) {

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
     *
     *
     * @param mixed       $documentId
     * @param mixed       $xmlVersion
     * @param mixed       $serverDateModified
     * @param DOMDocument $xmlData
     * @return void
     */
    public function put($documentId, $xmlVersion, $serverDateModified, DOMDocument $xmlData) {
        // skip adding cache entry if it is a valid entry already existing
        if (true === $this->hasValidEntry($documentId, $xmlVersion, $serverDateModified)) {
            return;
        }

        // remove existing cache entry in case of update
        if (true === $this->hasCacheEntry($documentId, $xmlVersion)) {
            $this->remove($documentId, $xmlVersion);
        }

        $newValue = array(
            'document_id' => $documentId,
            'xml_version' => $xmlVersion,
            'server_date_modified' => $serverDateModified,
            'xml_data' => $xmlData->saveXML()
        );

        $this->_table->insert($newValue);
    }

    /**
     * Removes a cache entry.
     *
     * @param mixed $documentId
     * @param mixed $xmlVersion
     * @return boolean
     */
    public function remove($documentId, $xmlVersion) {

        $rowSet = $this->_table->find($documentId, $xmlVersion);

        if (1 === $rowSet->count()) {
            $result = $rowSet->current()->delete();
            if (1 === $result) {
                return true;
            }
        }

        return false;
    }

}
