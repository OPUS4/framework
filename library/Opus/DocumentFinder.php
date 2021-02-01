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
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2011-2016, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus;

use Opus\Db\TableGateway;
use Opus\DocumentFinder\DocumentFinderException;
use Opus\Model\AbstractDb;
use Opus\Model\Dependent\AbstractDependentModel;
use Opus\Model\Dependent\Link\AbstractLinkModel;

/**
 * Domain model for documents in the Opus framework
 *
 * @category    Framework
 * @package     Opus
 * @uses        \Opus\Db\Documents
 *
 * TODO IDEA shows '.d' in sub selects as error, because it cannot detect the declaration in constructor
 *      maybe SQL string can be replaced by using the API (that would solve the problem) - OPUSVIER-4428
 */
class DocumentFinder
{

    /**
     * Table gateway class for the documents table.
     *
     * @var string
     */
    protected static $_tableGatewayClass = 'Opus\Db\Documents';

    /**
     * @var Opus\Db\Table\Abstract
     */
    private $_db = null;

    /**
     * @var \Zend_Db_Table_Select
     */
    private $_select = null;

    /**
     * Create new instance of Opus\DocumentList class.  The created object
     * allows to get custom subsets (or lists) of all existing Opus\Documents.
     */
    public function __construct()
    {
        $table = TableGateway::getInstance(self::$_tableGatewayClass);

        $this->_db = $table->getAdapter();
        $this->_select = $this->_db->select()->from(['d' => 'documents']);
    }

    /**
     * Returns the number of (distinct) documents for the given constraint set.
     *
     * @return int
     */
    public function count()
    {
        $this->_select->reset('columns');
        $this->_select->distinct(true)->columns("count(id)");
        return $this->_db->fetchOne($this->_select);
    }

    /**
     * Returns a list of (distinct) document ids for the given constraint set.
     *
     * NOTE: It was not possible to make sure only DISTINCT identifiers are returned. Therefore array_unique is used.
     * See OPUSVIER-3644 for more information.
     *
     * @return array
     */
    public function ids()
    {
        return array_unique($this->_db->fetchCol($this->getSelectIds()));
    }

    /**
     * Returns the \Zend_Db_Select object used to build query
     *
     * @return \Zend_Db_Select
     */
    public function getSelect()
    {
        return $this->_select;
    }

    /**
     * Returns the \Zend_Db_Select object used to build query
     *
     * @return \Zend_Db_Select
     */
    public function getSelectIds()
    {
        $this->_select->reset('columns');
        $this->_select->distinct(false)->columns('id');
        return $this->_select;
    }

    /**
     * Debug method
     *
     * @return DocumentFinder Fluent interface.
     */
    public function debug()
    {
        Log::get()->debug($this->_select->__toString());
        return $this;
    }

    /**
     * Returns a list of distinct document types for the given constraint set.
     *
     * @return array
     */
    public function groupedTypes()
    {
        $this->_select->reset('columns');
        $this->_select->columns("type")->distinct(true);
        return $this->_db->fetchCol($this->_select);
    }

    /**
     * Returns a list of distinct document types for the given constraint set.
     *
     * @return array
     */
    public function groupedTypesPlusCount()
    {
        $this->_select->reset('columns');
        $this->_select->columns(["type" => "type", "count" => "count(DISTINCT id)"]);
        $this->_select->group('type');
        return $this->_db->fetchAssoc($this->_select);
    }

    /**
     * Returns a list of distinct years given by server_date_published
     *
     * @return array
     */
    public function groupedServerYearPublished()
    {
        $this->_select->reset('columns');
        $this->_select->columns("substr(server_date_published, 1, 4)")->distinct(true);
        return $this->_db->fetchCol($this->_select);
    }

    /**
     * Add range-constraints to be applied on the result set.
     *
     * @param  string $type
     * @return DocumentFinder Fluent interface.
     */
    public function setIdRange($start, $end)
    {
        $this->setIdRangeStart($start)->setIdRangeEnd($end);
        return $this;
    }

    /**
     * Add range-start-constraints to be applied on the result set.
     *
     * @param  string $type
     * @return DocumentFinder Fluent interface.
     */
    public function setIdRangeStart($start)
    {
        $this->_select->where('d.id >= ?', $start);
        return $this;
    }

    /**
     * Add range-end-constraints to be applied on the result set.
     *
     * @param  string $type
     * @return DocumentFinder Fluent interface.
     */
    public function setIdRangeEnd($end)
    {
        $this->_select->where('d.id <= ?', $end);
        return $this;
    }

    /**
     * Add subset-constraints to be applied on the result set.
     *
     * @param  array $subset
     * @return DocumentFinder Fluent interface.
     */
    public function setIdSubset($subset)
    {
        // Hotfix: If $subset is empty, return empty set.
        if (! is_array($subset) or count($subset) < 1) {
            $this->_select->where('1 = 0');
            return $this;
        }

        $quotedSubset = [];
        foreach ($subset as $id) {
            $quotedSubset[] = $this->_db->quote($id);
        }

        $this->_select->where('id IN (?)', $subset);
        return $this;
    }

    /**
     * Add constraints to be applied on the result set.
     *
     * @param  string $type
     * @return DocumentFinder Fluent interface.
     */
    public function setType($type)
    {
        $this->_select->where('type = ?', $type);
        return $this;
    }

    /**
     * Add constraints to be applied on the result set.
     *
     * @param  string $typeArray
     * @return DocumentFinder Fluent interface.
     */
    public function setTypeInList($typeArray)
    {
        $this->_select->where('type IN (?)', $typeArray);
        return $this;
    }

    /**
     * Add constraints to be applied on the result set.
     *
     * @param  string $type
     * @return DocumentFinder Fluent interface.
     */
    public function setServerState($serverState)
    {
        $this->_select->where('server_state = ?', $serverState);
        return $this;
    }

    /**
     * Add constraints to be applied on the result set.
     *
     * @param  string $serverStateArray
     * @return DocumentFinder Fluent interface.
     */
    public function setServerStateInList($serverStateArray)
    {
        $this->_select->where('server_state IN (?)', $serverStateArray);
        return $this;
    }

    /**
     * Add range-constraints to be applied on the result set.  Constrain
     * result set to all documents with ServerDateCreated < $until.
     *
     * @param  string $until
     * @return DocumentFinder Fluent interface.
     */
    public function setServerDateCreatedBefore($until)
    {
        $this->_select->where('d.server_date_created < ?', $until);
        return $this;
    }

    /**
     * Add range-constraints to be applied on the result set.  Constrain
     * result set to all documents with ServerDateCreated > $until.
     *
     * @param  string $until
     * @return DocumentFinder Fluent interface.
     */
    public function setServerDateCreatedAfter($until)
    {
        $this->_select->where('d.server_date_created > ?', $until);
        return $this;
    }

    /**
     * Add range-constraints to be applied on the result set.  Constrain
     * result set to all documents with ServerDatePublished < $until.
     *
     * @param  string $until
     * @return DocumentFinder Fluent interface.
     */
    public function setServerDatePublishedBefore($until)
    {
        $this->_select->where('d.server_date_published < ?', $until);
        return $this;
    }

    /**
     * Add range-constraints to be applied on the result set.
     *
     * @param  string $from
     * @param  string $until
     * @return DocumentFinder Fluent interface.
     */
    public function setServerDatePublishedRange($from, $until)
    {
        $this->_select->where('d.server_date_published >= ?', $from)
                ->where('d.server_date_published < ?', $until);
        return $this;
    }

    /**
     * Add range-constraints to be applied on the result set.
     *
     * @param  string $from
     * @param  string $until
     * @return DocumentFinder Fluent interface.
     */
    public function setServerDateModifiedRange($from, $until)
    {
        $this->setServerDateModifiedAfter($from)
                ->setServerDateModifiedBefore($until);
        return $this;
    }

    /**
     * Add range-constraints to be applied on the result set.
     *
     * @param  string $from
     * @return DocumentFinder Fluent interface.
     */
    public function setServerDateModifiedAfter($from)
    {
        $this->_select->where('d.server_date_modified >= ?', $from);
        return $this;
    }

    /**
     * Add range-constraints to be applied on the result set.
     *
     * @param  string $from
     * @return DocumentFinder Fluent interface.
     */
    public function setServerDateModifiedBefore($until)
    {
        $this->_select->where('d.server_date_modified < ?', $until);
        return $this;
    }

    /**
     * Add range constraint for embargo date applied to result set.
     * @param string $from
     * @param string $until
     * @return DocumentFinder fluent interface
     */
    public function setEmbargoDateRange($from, $until)
    {
        $this->_select->where('d.embargo_date >= ?', $from)
            ->where('d.embargo_date < ?', $until);
        return $this;
    }

    /**
     * Add range constraint for embargo date applied to result set.
     * @param string $until
     * @return DocumentFinder fluent interface
     */
    public function setEmbargoDateBefore($until)
    {
        $this->_select->where('d.embargo_date < ?', $until);
        return $this;
    }

    /**
     * Add range constraint for embargo date applied to result set.
     * @param string $from Start date of range constraint for result set.
     * @return DocumentFinder fluent interface
     */
    public function setEmbargoDateAfter($from)
    {
        $this->_select->where('d.embargo_date >= ?', $from);
        return $this;
    }

    public function setNotEmbargoedOn($date)
    {
        $this->_select->where('d.embargo_date < ? or d.embargo_date IS NULL', $date);
        return $this;
    }

    /**
     * Add constraint for documents that have not been saved after the embargo expired.
     *
     * This is important in order to determine which documents need to be saved to update ServerDateModified in order
     * to include the documents in harvesting by the DNB, for instance. The expiration of the embargo does not change
     * the documents and therefore they do not appear as now available documents.
     *
     * @param string $until Date of expiration of embargo
     * @return DocumentFinder fluent interface
     */
    public function setEmbargoDateBeforeNotModifiedAfter($until)
    {
        $this->_select->where('d.embargo_date < ?', $until)
            ->where('d.server_date_modified < d.embargo_date');
        return $this;
    }

    /**
     * Add constraints to be applied on the result set.
     *
     * @param  string $type
     * @return DocumentFinder Fluent interface.
     */
    public function setEnrichmentKeyExists($keyName)
    {
        $this->_select->where(
            'EXISTS (SELECT id FROM document_enrichments AS e WHERE document_id = d.id AND key_name = ?)',
            $keyName
        );
        return $this;
    }

    /**
     * Add constraints to be applied on the result set.
     *
     * @param  string $type
     * @return DocumentFinder Fluent interface.
     */
    public function setEnrichmentKeyValue($keyName, $value)
    {
        $quotedKeyName = $this->_db->quote($keyName);
        $quotedValue    = $this->_db->quote($value);
        $subselect = "SELECT id FROM document_enrichments AS e "
            . "WHERE document_id = d.id AND key_name = $quotedKeyName AND value = $quotedValue";

        $this->_select->where("EXISTS ($subselect)");
        return $this;
    }

    /**
     * Add constraints to be applied on the result set.
     *
     * @param  string $type
     * @return DocumentFinder Fluent interface.
     */
    public function setIdentifierTypeValue($type, $value)
    {
        $quotedType  = $this->_db->quote($type);
        $quotedValue = $this->_db->quote($value);
        $subselect = "SELECT id FROM document_identifiers AS i "
            . "WHERE i.document_id = d.id AND type = $quotedType AND value = $quotedValue";

        $this->_select->where("EXISTS ($subselect)");
        return $this;
    }

    /**
     * Add constraints to be applied on the result set.
     *
     * @param  string $type
     * @return DocumentFinder Fluent interface.
     */
    public function setIdentifierTypeExists($type)
    {
        $quotedType  = $this->_db->quote($type);
        $subselect = "SELECT id FROM document_identifiers AS i WHERE i.document_id = d.id AND type = $quotedType";

        $this->_select->where("EXISTS ($subselect)");
        return $this;
    }

    /**
     * Add constraints to be applied on the result set.
     *
     * @param  string $value
     * @return DocumentFinder Fluent interface.
     */
    public function setBelongsToBibliography($value)
    {
        $this->_select->where('d.belongs_to_bibliography = ?', $value);
        return $this;
    }

    /**
     * Add constraints to be applied on the result set.
     *
     * @param  string $value
     * @return DocumentFinder Fluent interface.
     */
    public function setCollectionRoleId($roleId)
    {
        $quotedRoleId  = $this->_db->quote($roleId);
        $subselect = "SELECT document_id
            FROM collections AS c, link_documents_collections AS l
            WHERE l.document_id = d.id
              AND l.collection_id = c.id
              AND c.role_id = $quotedRoleId";

        $this->_select->where("EXISTS ($subselect)");
        return $this;
    }

    /**
     * Add constraints to be applied on the result set.
     *
     * @param  int|array|\Zend_Select $value id, array of ids of collections
     * or \Zend_Select instance to set. If a \Zend_Select-object is provided,
     * the resulting statement must return a list of collection ids.
     * @return DocumentFinder Fluent interface.
     */
    public function setCollectionId($collectionId)
    {
        if ($collectionId instanceof \Zend_Select) {
            $quotedCollectionId = $collectionId->assemble();
        } else {
            $quotedCollectionId  = $this->_db->quote($collectionId);
        }
        $subselect = "SELECT document_id
            FROM link_documents_collections AS l
            WHERE l.document_id = d.id
              AND l.collection_id IN ($quotedCollectionId)";

        $this->_select->where("EXISTS ($subselect)");
        return $this;
    }

    /**
     *
     * Add instance of dependent model as constraint.
     *
     * @param AbstractDb $model Instance of dependent model.
     *
     * @return DocumentFinder Fluent interface.
     */
    public function setDependentModel($model)
    {
        if (! ($model instanceof AbstractDb)) {
            throw new DocumentFinderException('Expected instance of Opus\Model\AbstractDb.');
        }
        $id = null;
        if ($model instanceof AbstractLinkModel) {
            $id = $model->getModel()->getId();
        } else {
            $id = $model->getId();
        }

        if (empty($id)) {
            throw new DocumentFinderException('Id not set for model ' . get_class($model));
        }

        // workaround for Opus\Collection[|Role] which are implemented differently
        if ($model instanceof Collection) {
            return $this->setCollectionId($id);
        }
        if ($model instanceof CollectionRole) {
            return $this->setCollectionRoleId($id);
        }

        if (! ($model instanceof AbstractDependentModel ||
                $model instanceof AbstractLinkModel)) {
            $linkModelClass = $this->_getLinkModelClass($model);
            if (is_null($linkModelClass)) {
                throw new DocumentFinderException('link model class unknown for model '.get_class($model));
            }
            $model = new $linkModelClass();
        }
        if (! is_null($id)) {
            $id = $this->_db->quote($id);
        }
        $idCol = $model->getParentIdColumn();
        $tableGatewayClass = $model->getTableGatewayClass();
        if (empty($tableGatewayClass)) {
            throw new DocumentFinderException('No table gateway class provided for '.get_class($model));
        }
        $table = TableGateway::getInstance($tableGatewayClass)->info('name');
        if (empty($idCol) || empty($table)) {
            throw new DocumentFinderException('Cannot create subquery from dependent model ' . get_class($model));
        }
        $idCol = $this->_db->quoteIdentifier($idCol);
        $table = $this->_db->quoteIdentifier($table);

        if ($model instanceof AbstractLinkModel) {
            $linkedModelKey = $model->getModelKey();
            if (empty($linkedModelKey)) {
                throw new DocumentFinderException(
                    'Cannot create subquery from dependent model ' . get_class($model)
                );
            }
            $linkedModelKey = $this->_db->quoteIdentifier($linkedModelKey);

            $subselect = "SELECT $idCol
                FROM $table AS l
                WHERE l.$idCol = d.id
                AND l.$linkedModelKey = $id";
        } elseif ($model instanceof AbstractDependentModel) {
            $subselect = "SELECT $idCol
                FROM $table AS l
                WHERE l.$idCol = d.id
                AND l.id = $id";
        } else {
            throw new DocumentFinderException('Cannot create constraint for Model ' . get_class($model));
        }
        $this->_select->where("EXISTS ($subselect)");
        return $this;
    }

    // helper method for mapping Opus\Model\AbstractDb instances to their
    // corresponding link model class (extending Opus\Model\Dependent\Link\AbstractLinkModel)
    private function _getLinkModelClass(AbstractDb $model)
    {
        $linkModelClass = null;
        $modelClass = get_class($model);
        switch ($modelClass) {
            case 'Opus\Series':
                $linkModelClass = 'Opus\Model\Dependent\Link\DocumentSeries';
                break;
            case 'Opus\Person':
                $linkModelClass = 'Opus\Model\Dependent\Link\DocumentPerson';
                break;
            case 'Opus\Licence':
                $linkModelClass = 'Opus\Model\Dependent\Link\DocumentLicence';
                break;
            case 'Opus\DnbInstitute':
                $linkModelClass = 'Opus\Model\Dependent\Link\DocumentDnbInstitute';
                break;
        }
        return $linkModelClass;
    }

    /**
     * Add a subselect as constraint
     *
     * @param \Zend_Db_Select $select A select object used as subselect in query.
     * The subquery must return a list of document ids.
     *
     * @return DocumentFinder Fluent interface.
     */
    public function setSubSelectExists($select)
    {

        $this->_select->where('d.id IN ('.$select->assemble().')');
        return $this;
    }

    /**
     * Add a subselect as constraint
     *
     * @param \Zend_Db_Select $select A select object used as subselect in query.
     * The subquery must return a list of document ids.
     *
     * @return DocumentFinder Fluent interface.
     */
    public function setSubSelectNotExists($select)
    {

        $this->_select->where(' NOT d.id IN ('.$select->assemble().')');
        return $this;
    }

    /**
     * Only return documents with at leat one file marked as visible in oai.
     *
     * @return DocumentFinder Fluent interface.
     */
    public function setFilesVisibleInOai()
    {

            $subselect = "SELECT DISTINCT document_id
            FROM document_files AS f
            WHERE f.document_id = d.id
            AND f.visible_in_oai=1";

            $this->_select->where('d.id IN ('.$subselect.')');
            return $this;
    }

    /**
     * Ordering to be applied on the result set.
     *
     * @param  boolean $order Sort ascending if true, descending otherwise.
     * @return DocumentFinder Fluent interface.
     */
    public function orderByAuthorLastname($order = true)
    {
        $this->_select
                ->joinLeft(
                    ['pd' => 'link_persons_documents'],
                    'd.id = pd.document_id AND pd.role = "author"',
                    []
                )
                ->joinLeft(['p' => 'persons'], 'pd.person_id = p.id', [])
                ->group(['d.id', 'p.last_name'])
                ->order('p.last_name ' . ($order ? 'ASC' : 'DESC'));
        return $this;
    }

    /**
     * Ordering to be applied on the result set.
     *
     * @param  boolean $order Sort ascending if true, descending otherwise.
     * @return DocumentFinder Fluent interface.
     */
    public function orderByTitleMain($order = true)
    {
        $this->_select
                ->joinLeft(
                    ['t' => 'document_title_abstracts'],
                    't.document_id = d.id AND t.type = "main"',
                    []
                )
                ->group(['d.id', 't.value'])
                ->order('t.value ' . ($order ? 'ASC' : 'DESC'));
        return $this;
    }

    /**
     * Ordering to be applied on the result set.
     *
     * @param  boolean $order Sort ascending if true, descending otherwise.
     * @return DocumentFinder Fluent interface.
     */
    public function orderById($order = true)
    {
        $this->_select->order('d.id ' . ($order ? 'ASC' : 'DESC'));
        return $this;
    }

    /**
     * Ordering to be applied on the result set.
     *
     * @param  boolean $order Sort ascending if true, descending otherwise.
     * @return DocumentFinder Fluent interface.
     */
    public function orderByType($order = true)
    {
        $this->_select->order('d.type ' . ($order ? 'ASC' : 'DESC'));
        return $this;
    }

    /**
     * Ordering to be applied on the result set.
     *
     * @param  boolean $order Sort ascending if true, descending otherwise.
     * @return DocumentFinder Fluent interface.
     */
    public function orderByServerDatePublished($order = true)
    {
        $this->_select->order('d.server_date_published ' . ($order ? 'ASC' : 'DESC'));
        return $this;
    }
}
