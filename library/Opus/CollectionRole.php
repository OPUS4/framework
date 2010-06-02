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
 * @category    Framework
 * @package     Opus
 * @author     	Thoralf Klein <thoralf.klein@zib.de>
 * @author      Felix Ostrowski <ostrowski@hbz-nrw.de>
 * @author      Tobias Tappe <tobias.tappe@uni-bielefeld.de>
 * @copyright  	Copyright (c) 2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Domain model for collection roles in the Opus framework
 *
 * @category    Framework
 * @package     Opus
 * @uses        Opus_Model_Abstract
 */
class Opus_CollectionRole extends Opus_Model_AbstractDb {

    /**
     * Specify then table gateway.
     *
     * @var string Classname of Zend_DB_Table to use if not set in constructor.
     */
    protected static $_tableGatewayClass = 'Opus_Db_CollectionsRoles';


    /**
     * The documents external fields, i.e. those not mapped directly to the
     * Opus_Db_Documents table gateway.
     *
     * @var array
     * @see Opus_Model_Abstract::$_externalFields
     */
    protected $_externalFields = array(
            // Will contain the Root Node of this Role.
            'RootNode' => array(
                            'model'   => 'Opus_CollectionNode',
                            'options' => array('left_id' => 1),
                            'fetch'   => 'lazy',
            ),

            // Will contain additional attributes of Collections
            'Attributes' => array(
                            'fetch'   => 'lazy',
            ),
    );


    /**
     * Initialize model with the following fields:
     *
     * - Name
     * - Position
     * - LinkDocsPathToRoot
     * - Visible
     * - Collections
     * - ...
     *
     * @return void
     */
    protected function _init() {
        $this->logger('init');

        // Attributes, which are defined by the database schema.

        // TODO: This fields contents should be moved to the root node.
        $name = new Opus_Model_Field('Name');
        $this->addField($name);

        $oaiName  = new Opus_Model_Field('OaiName');
        $this->addField($oaiName);

        // TODO: This field shouldn't be modified directly.
        $position = new Opus_Model_Field('Position');
        $this->addField($position);


        // Attributes for defining visibility.
        $visible = new Opus_Model_Field('Visible');
        $visible->setCheckbox(true);
        $this->addField($visible);

        $links_docs_path_to_root = new Opus_Model_Field('LinkDocsPathToRoot');
        $mapping = array('none'=>'none', 'count'=>'count', 'display'=>'display', 'both'=>'both');
        $links_docs_path_to_root->setDefault($mapping)->setSelection(true);
        $this->addField($links_docs_path_to_root);

        $visibleBrowsingStart = new Opus_Model_Field('VisibleBrowsingStart');
        $visibleBrowsingStart->setCheckbox(true);
        $this->addField($visibleBrowsingStart);

        $visibleFrontdoor = new Opus_Model_Field('VisibleFrontdoor');
        $visibleFrontdoor->setCheckbox(true);
        $this->addField($visibleFrontdoor);

        $visibleOai = new Opus_Model_Field('VisibleOai');
        $visibleOai->setCheckbox(true);
        $this->addField($visibleOai);


        // Attributes for defining output formats.
        $displayBrowsing = new Opus_Model_Field('DisplayBrowsing');
        $this->addField($displayBrowsing);

        $displayFrontdoor = new Opus_Model_Field('DisplayFrontdoor');
        $this->addField($displayFrontdoor);

        $displayOai = new Opus_Model_Field('DisplayOai');
        $this->addField($displayOai);


        // Virtual attributes, which depend on other tables.
        $rootNode = new Opus_Model_Field('RootNode');
        $this->addField($rootNode);

        // TODO: This field shouldn't be modified directly.
        $attributes = new Opus_Model_Field('Attributes');
        $attributes->setMultiplicity('*');
        $this->addField($attributes);

    }


    /**
     * Returns long name.
     *
     * @see library/Opus/Model/Opus_Model_Abstract#getDisplayName()
     * @return string Model class name and identifier (e.g. Opus_CollectionRole#1234).
     *
     * TODO: Outsource this->getName to this->getRootNode->getName
     */
    public function getDisplayName() {
        return $this->getName();
    }


    /**
     * Overwrite standard storage procedure to shift positions.  The parameter
     * describes the new position of the current role.
     *
     * TODO: This method belongs to Opus_Db_CollectionsRoles.
     *
     * @return void
     */
    protected function _storePosition($to) {
        // TODO: Check, if this is "the OPUS4 way" to check changed fields.
        $field = $this->_getField('Position', true);
        if (false === $field->isModified()) {
            return;
        }

        echo "target position: $to\n";
        if ($to < 1) {
            $to = 1;
        }

        $row = $this->_primaryTableRow;
        $db = $row->getTable()->getAdapter();

        // Re-Order.
        // TODO: This reorder-query is only nesseccary, if someone destroyed the strict ordering.
        // TODO: If the table is strictly ordered, then the code below will preserve this property.
        $reorder_query = 'SET @pos = 0; '
                . ' UPDATE collections_roles '
                . ' SET position = ( SELECT @pos := @pos + 1 ) '
                . ' ORDER BY position, id ASC;';
        // echo "reorder: $reorder_query\n";
        $db->query($reorder_query);

        // Find the current position of the current row in the new ordering.
        // Case 1: If row is new, shift all nodes plus one.
        // Case 2: If row is old, shift nodes in between plus/minus one.
        $range = $db->quoteInto("position >= ?", $to);
        $pos_shift = ' + 1 ';

        if (! $this->isNewRecord()) {
            $pos_query = 'SELECT position FROM collections_roles WHERE id = ?';
            $pos = $db->fetchOne($pos_query, $this->getId());

            $range = "position BETWEEN ? AND ?";
            if ($to < $pos) {
                $range = $db->quoteInto($range, $to, null, 1);
                $range = $db->quoteInto($range, $pos, null, 1);
                $pos_shift = ' + 1 ';
            }
            else {
                $range = $db->quoteInto($range, $pos, null, 1);
                $range = $db->quoteInto($range, $to, null, 1);
                $pos_shift = ' - 1 ';
            }
        }

        // Move.
        $move_query = 'UPDATE collections_roles '
                . ' SET position = position ' . $pos_shift
                . ' WHERE ' . $range;
        echo "move: $move_query\n";
        $db->query($move_query);

        // Update this row.
        $row->{'position'} = (int) $to;

        return;
    }


    /**
     * Overwrites standard toArray() to prevent infinite recursion due to parent
     * collections.
     *
     * @return array A (nested) array representation of the model.
     *
     * FIXME: Only basic refactoing done.  Needs testing!  Which fields to use?
     * TODO: Check why this method isn't used any more.
     */
    public function toArray() {
        $this->logger('toArray');
        $result = array();
        foreach ($this->getSubCollections() as $subCollection) {
            $result[] = array(
                    'Id'             => $subCollection->getId(),
                    'Name'           => $subCollection->getName(),
//                    'RootCollection' => $this->getRootCollection()->getId(),
//                    'SubCollection'  => $subCollection->toArray(),
            );
        }
        return $result;
    }


    /**
     * Extend standard deletion to delete collection roles tables.
     *
     * FIXME: Do we *really* want to DROP or set to invisible?
     * FIXME: Put both deletes in one transaction.
     * FIXME: Uses too much information from other models.
     *
     * @return void
     */
    public function delete() {
        if ($this->isNewRecord()) {
            return;
        }

        $row = $this->_primaryTableRow;
        $db = $row->getTable()->getAdapter();

        // TODO: Don't use internal knowledge from "collections_nodes"
        $statement_1 = 'DELETE FROM collections_nodes WHERE role_id = ? ORDER BY left_id DESC';
        $statement_1 = $db->quoteInto($statement_1, $this->getId());
        $db->query($statement_1);

        // TODO: Don't use internal knowledge from "link_documents_collections"
        $statement_2 = 'DELETE FROM link_documents_collections WHERE role_id = ?';
        $statement_2 = $db->quoteInto($statement_2, $this->getId());
        $db->query($statement_2);

        // TODO: Don't use internal knowledge from "collections"
        $statement_3 = 'DELETE FROM collections WHERE role_id = ?';
        $statement_3 = $db->quoteInto($statement_3, $this->getId());
        $db->query($statement_3);

        parent::delete();
    }

 
    /**
     * ALTERNATE CONSTRUCTOR: Retrieve Opus_CollectionRole instance by name.
     * Returns null if name is null *or* nothing found.
     *
     * @return Opus_CollectionRole
     */

    public static function fetchByName($name = null) {
        if (false === isset($name)) {
            return;
        }

        $table = Opus_Db_TableGateway::getInstance( self::$_tableGatewayClass );
        $select = $table->select()->where('name = ?', $name);
        $row = $table->fetchRow($select);

        if (isset($row)) {
            return new Opus_CollectionRole($row);
        }

        return;
    }


    /**
     * ALTERNATE CONSTRUCTOR: Retrieve Opus_CollectionRole instance by oaiName.
     * Returns null if name is null *or* nothing found.
     *
     * @return Opus_CollectionRole
     */

    public static function fetchByOaiName($oai_name = null) {
        if (false === isset($oai_name)) {
            return;
        }

        $table = Opus_Db_TableGateway::getInstance( self::$_tableGatewayClass );
        $select = $table->select()->where('oai_name = ?', $oai_name);
        $row = $table->fetchRow($select);

        if (isset($row)) {
            return new Opus_CollectionRole($row);
        }

        return;
    }


    /**
     * Retrieve all Opus_CollectionRole instances from the database.
     *
     * @return array Array of Opus_CollectionRole objects.
     *
     * TODO: Parametrize query to account for hidden collection roles.
     */

    public static function fetchAll($show_invisible = false) {
        // $this->logger('fetchAll()');

        if (true === $show_invisible) {
            $where = "";
        }

        $table = Opus_Db_TableGateway::getInstance( self::$_tableGatewayClass );
        $roles = $table->fetchAll("id > 1", 'position');
        // $roles = $table->fetchAll(null, 'position');

        return self::createObjects($roles);
    }


    /**
     * Mass-constructur.
     *
     * @param array $array Array of whatever new Opus_Collection(...) takes.
     * @return array|Opus_Collection Array of constructed Opus_Collections.
     *
     * TODO: Refactor this method as fetchAllFromSubselect(...) in AbstractDb?
     * TODO: Code duplication from/in Opus_Collection!
     */

    public static function createObjects($array) {
        $results = array();

        // FIXME: get_called_class() only supported in PHP5 >= 5.3
        //   $class   = get_called_class();
        // FIXME: Add Model_AbstractDb::createObjects(...) when using PHP 5.3

        foreach ($array AS $element) {
            $c = new Opus_CollectionRole($element);
            $results[] = $c;
        }

        return $results;
    }


    /* ********************************************************************** *
     * Everything which deals with OAI sets goes here:
     * ********************************************************************** */

    /**
     * Returns all valid oai set names (i.e. for those collections that contain
     * at least one document) for this role.
     *
     * FIXME: Unit-tests, if empty OaiSets are returned.
     * FIXME: How to count documents?  Only this collections or recursive?
     * FIXME: Check if $this->getDisplayOai() contains proper field names!
     *
     * @return array An array of strings containing oai set names.
     */
    public function getOaiSetNames() {
        $oaiPrefix        = $this->getOaiName();
        $oaiPostfixColumn = $this->getDisplayOai();

        if (is_null($oaiPrefix) || $oaiPrefix == '') {
            throw new Exception('Missing OAI set name.');
        }

        $db = Zend_Db_Table::getDefaultAdapter();

        $quotePrefix  = $db->quote("$oaiPrefix:");
        $quotePostfix = $db->quoteIdentifier("c.$oaiPostfixColumn");
        $quoteRoleId  = $db->quote($this->getId());

        $select = "SELECT DISTINCT CONCAT( $quotePrefix, $quotePostfix ) "
              . " FROM collections AS c "
              . " JOIN link_documents_collections AS l "
              . " ON (c.id = l.collection_id AND c.role_id = l.role_id) "
              . " WHERE c.role_id = $quoteRoleId AND l.role_id = $quoteRoleId";

        $this->logger( "$select" );

        // FIXME: Add error handling for failed DB requests!

        $results = $db->fetchCol($select);
        return $results;
    }

    /**
     * Return the ids of documents in an oai set.
     *
     * @param  string $oaiSetName The name of the oai set.
     * @return array The ids of the documents in the set.
     *
     * FIXME: Need Collection constructor-by-oaiSetName.
     * FIXME: Check OAI set names for invalid characters (i.e. ':')
     * FIXME: Belongs to Opus_Collection
     */
    public function existsDocumentIdsInSet($oaiSetName) {
        $colonPos = strrpos($oaiSetName, ':');
        $oaiPrefix = substr($oaiSetName, 0, $colonPos);
        $oaiPostfix = substr($oaiSetName, $colonPos + 1);

        if ($this->getOaiName() !== $oaiPrefix) {
            throw new Exception("Given OAI prefix does not match this role.");
        }

        $oaiPrefix  = $this->getOaiName();

        $db = Zend_Db_Table::getDefaultAdapter();

        $oaiPostfixColumn = $this->getDisplayOai();
        $quotePostfixColumn = $db->quoteIdentifier("c.$oaiPostfixColumn");
        $quotePostfix = $db->quote("$oaiPostfix");
        $quoteRoleId  = $db->quote($this->getId());

        $select = " SELECT c.id FROM collections AS c "
                . " WHERE $quotePostfixColumn = $quotePostfix "
                . " AND c.role_id = $quoteRoleId "
                . " AND EXISTS ( "
                . "    SELECT l.document_id "
                . "    FROM link_documents_collections AS l "
                . "    WHERE l.collection_id = c.id AND l.role_id = c.role_id "
                . " )";

        $db = Zend_Db_Table::getDefaultAdapter();
        $result = $db->fetchOne($select);
        $this->logger("$oaiSetName: $result");

        if (true === isset($result)) {
            return true;
        }

        return false;
    }


    /**
     * LEGACY.
     *
     * @deprecated
     */

    public static function getAll() {
        return self::fetchAll();
    }

    /**
     *  Debugging helper.  Sends the given message to Zend_Log.
     *
     * @param string $message
     */

    protected function logger($message) {
        $registry = Zend_Registry::getInstance();
        $logger = $registry->get('Zend_Log');

        $logger->info("Opus_CollectionRole: $message");
    }


    /* ********************************************************************** *
     * Everything which depends on $this->getRootNode() goes here:
     * ********************************************************************** */

    /**
     * Forward method to same method in RootCollection.  Returns NULL, if no
     * RootCollection is found.
     *
     * @return array|Opus_Collection SubCollections of root node.
     *
     * @deprecated
     */
    public function getSubCollection() {
        $root = $this->getRootNode();
        if (! is_null($root)) {
            return $root->getSubCollection();
        }
        return array();
    }

    /**
     * Store root node: Delegate storing of external field.  Initialize Node.
     *
     * @param array|Opus_CollectionNode $node CollectionNode to store as Root.
     * @see Opus_Model_AbstractDb
     */
    public function _storeRootNode($node) {
        if (isset($node)) {

            if ($node->isNewRecord()) {
                $node->setPositionKey('Root');
                $node->setRoleId( $this->getId() );
            }

            $node->store();
        }
    }

    /**
     * Get the path back to the root node.  Note: Only works in trees, where
     * each collection is linked to one node.
     *
     * @return array|Opus_Collection
     */
    public function getParents() {
        return $this->getRootNode()->getCollection()->getParents();
    }

    /**
     * Internal methods to handle attribute fields.
     *
     * TODO: Should be a method on the Opus_Db_Collections Model.
     */

    protected function _fetchAttributes() {
        $table = new Opus_Db_Collections();
        $info = $table->info();

        $skipFields = $info['primary'];
        $dbFields = $info['metadata'];

        $results = array();

        foreach (array_keys($dbFields) as $dbField) {
            if (in_array($dbField, $skipFields)) {
                continue;
            }

            $results[] = $dbField;
        }

        return $results;
    }

    /**
     * Internal methods to handle attribute fields.
     *
     * TODO: Should be a method on the Opus_Db_Collections Model.
     */

    protected function _storeAttributes($attributes) {
        $table = new Opus_Db_Collections();
        $info = $table->info();

        $dbFields = $info['cols'];
        $dbTableName = $info['name'];
        $db = $table->getAdapter();

        $this->logger("dbfields known: " . implode(",", $dbFields));

        foreach ($attributes as $attribute) {
            $attribute = trim(strtolower($attribute));

            if ($attribute !== '' && false === in_array($attribute, $dbFields)) {
                $this->logger("processing field $attribute");

                $stmt = 'ALTER TABLE ' . $db->quoteIdentifier($dbTableName)
                        . ' ADD COLUMN ' . $db->quoteIdentifier($attribute)
                        . ' VARCHAR(255); ';
                $this->logger("Table change: $stmt");

                // FIXME: Error handling!
                $rh = $db->query($stmt);
            }
            else {
                $this->logger("skipping field $attribute");
            }
        }

        return;
    }

}

?>
