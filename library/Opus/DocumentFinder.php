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
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @copyright   Copyright (c) 2011, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Domain model for documents in the Opus framework
 *
 * @category    Framework
 * @package     Opus
 * @uses        Opus_Db_Documents
 */
class Opus_DocumentFinder {

    /**
     * Table gateway class for the documents table.
     *
     * @var string
     */
    protected static $_tableGatewayClass = 'Opus_Db_Documents';

    /**
     * @var Opus_Db_Table_Abstract
     */
    private $db = null;

    /**
     * @var Zend_Db_Table_Select
     */
    private $select = null;

    /**
     * Create new instance of Opus_DocumentList class.  The created object
     * allows to get custom subsets (or lists) of all existing Opus_Documents.
     */
    public function __construct() {
        $table = Opus_Db_TableGateway::getInstance(self::$_tableGatewayClass);

        $this->db = $table->getAdapter();
        $this->select = $this->db->select()->from(array('d' => 'documents'));
    }

    /**
     * Returns the number of (distinct) documents for the given constraint set.
     *
     * @return int
     */
    public function count() {
        $this->select->reset('columns');
        $this->select->distinct(true)->columns("count(id)");
        return $this->db->fetchOne($this->select);
    }

    /**
     * Returns a list of (distinct) documents for the given constraint set.
     *
     * @return array
     */
    public function ids() {
        $this->select->reset('columns');
        $this->select->distinct(true)->columns("id");
        return $this->db->fetchCol($this->select);
    }

    /**
     * Returns a list of distinct document types for the given constraint set.
     *
     * @return array
     */
    public function groupedTypes() {
        $this->select->reset('columns');
        $this->select->columns("type")->distinct(true);
        return $this->db->fetchCol($this->select);
    }

    /**
     * Returns a list of distinct years given by server_date_published
     *
     * @return array
     */
    public function groupedServerYearPublished() {
        $this->select->reset('columns');
        $this->select->columns("substr(server_date_published, 1, 4)")->distinct(true);
        return $this->db->fetchCol($this->select);
    }

    /**
     * Add range-constraints to be applied on the result set.
     *
     * @param  string $type
     * @return Opus_DocumentFinder Fluent interface.
     */
    public function setIdRange($start, $end) {
        $this->setIdRangeStart($start)->setIdRangeEnd($end);
        return $this;
    }

    /**
     * Add range-start-constraints to be applied on the result set.
     *
     * @param  string $type
     * @return Opus_DocumentFinder Fluent interface.
     */
    public function setIdRangeStart($start) {
        $this->select->where('d.id >= ?', $start);
        return $this;
    }

    /**
     * Add range-end-constraints to be applied on the result set.
     *
     * @param  string $type
     * @return Opus_DocumentFinder Fluent interface.
     */
    public function setIdRangeEnd($end) {
        $this->select->where('d.id <= ?', $end);
        return $this;
    }

    /**
     * Add subset-constraints to be applied on the result set.
     *
     * @param  string $type
     * @return Opus_DocumentFinder Fluent interface.
     */
    public function setIdSubset($subset) {
        // Hotfix: If $subset is empty, return empty set.
        if (!is_array($subset) or count($subset) < 1) {
            $this->select->where('1 = 0');
            return $this;
        }

        $quoted_subset = array();
        foreach ($subset AS $id) {
            $quoted_subset[] = $this->db->quote($id);
        }

        $this->select->where('id IN (?)', $subset);
        return $this;
    }

    /**
     * Add constraints to be applied on the result set.
     *
     * @param  string $type
     * @return Opus_DocumentFinder Fluent interface.
     */
    public function setType($type) {
        $this->select->where('type = ?', $type);
        return $this;
    }

    /**
     * Add constraints to be applied on the result set.
     *
     * @param  string $type
     * @return Opus_DocumentFinder Fluent interface.
     */
    public function setServerState($server_state) {
        $this->select->where('server_state = ?', $server_state);
        return $this;
    }

    /**
     * Add range-constraints to be applied on the result set.
     *
     * @param  string $type
     * @return Opus_DocumentFinder Fluent interface.
     */
    public function setServerDatePublishedRange($from, $until) {
        $this->select->where('d.server_date_published >= ?', $from)
                ->where('d.server_date_published < ?', $until);
        return $this;
    }

    /**
     * Add range-constraints to be applied on the result set.
     *
     * @param  string $type
     * @return Opus_DocumentFinder Fluent interface.
     */
    public function setServerDateModifiedRange($from, $until) {
        $this->select->where('d.server_date_modified >= ?', $from)
                ->where('d.server_date_modified < ?', $until);
        return $this;
    }

    /**
     * Add constraints to be applied on the result set.
     *
     * @param  string $type
     * @return Opus_DocumentFinder Fluent interface.
     */
    public function setEnrichmentKeyExists($key_name) {
        $this->select->where('EXISTS (SELECT id FROM document_enrichments AS e WHERE document_id = d.id AND key_name = ?)', $key_name);
        return $this;
    }

    /**
     * Add constraints to be applied on the result set.
     *
     * @param  string $type
     * @return Opus_DocumentFinder Fluent interface.
     */
    public function setEnrichmentKeyValue($key_name, $value) {
        $quoted_key_name = $this->db->quote($key_name);
        $quoted_value    = $this->db->quote($value);
        $subselect = "SELECT id FROM document_enrichments AS e WHERE document_id = d.id AND key_name = $quoted_key_name AND value = $quoted_value";

        $this->select->where("EXISTS ($subselect)");
        return $this;
    }

    /**
     * Add constraints to be applied on the result set.
     *
     * @param  string $type
     * @return Opus_DocumentFinder Fluent interface.
     */
    public function setIdentifierTypeValue($type, $value) {
        $quoted_type  = $this->db->quote($type);
        $quoted_value = $this->db->quote($value);
        $subselect = "SELECT id FROM document_identifiers AS i WHERE i.document_id = d.id AND type = $quoted_type AND value = $quoted_value";

        $this->select->where("EXISTS ($subselect)");
        return $this;
    }

    /**
     * Ordering to be applied on the result set.
     *
     * @param  boolean $order Sort ascending if true, descending otherwise.
     * @return Opus_DocumentFinder Fluent interface.
     */
    public function orderByAuthorLastname($order = true) {
        $this->select
                ->joinLeft(array('pd' => 'link_persons_documents'), 'd.id = pd.document_id AND pd.role = "author"', array())
                ->joinLeft(array('p' => 'persons'), 'pd.person_id = p.id', array())
                ->group('d.id')
                ->order('p.last_name ' . ($order ? 'ASC' : 'DESC'));
        return $this;
    }

    /**
     * Ordering to be applied on the result set.
     *
     * @param  boolean $order Sort ascending if true, descending otherwise.
     * @return Opus_DocumentFinder Fluent interface.
     */
    public function orderByTitleMain($order = true) {
        $this->select
                ->joinLeft(array('t' => 'document_title_abstracts'), 't.document_id = d.id AND t.type = "main"', array())
                ->group('d.id')
                ->order('t.value ' . ($order ? 'ASC' : 'DESC'));
        return $this;
    }

    /**
     * Ordering to be applied on the result set.
     *
     * @param  boolean $order Sort ascending if true, descending otherwise.
     * @return Opus_DocumentFinder Fluent interface.
     */
    public function orderById($order = true) {
        $this->select->order('d.id ' . ($order ? 'ASC' : 'DESC'));
        return $this;
    }

    /**
     * Ordering to be applied on the result set.
     *
     * @param  boolean $order Sort ascending if true, descending otherwise.
     * @return Opus_DocumentFinder Fluent interface.
     */
    public function orderByType($order = true) {
        $this->select->order('d.type ' . ($order ? 'ASC' : 'DESC'));
        return $this;
    }

    /**
     * Ordering to be applied on the result set.
     *
     * @param  boolean $order Sort ascending if true, descending otherwise.
     * @return Opus_DocumentFinder Fluent interface.
     */
    public function orderByServerDatePublished($order = true) {
        $this->select->order('d.server_date_published ' . ($order ? 'ASC' : 'DESC'));
        return $this;
    }

}
