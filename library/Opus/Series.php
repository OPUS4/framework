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
 * @author      Sascha Szott <szott@zib.de>
 * @author      Susanne Gottwald <gottwald@zib.de>
 * @copyright   Copyright (c) 2008-2012, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Domain model for sets in the Opus framework
 *
 * @category    Framework
 * @package     Opus
 * @uses        Opus_Model_Abstract
 */
class Opus_Series extends Opus_Model_AbstractDb {

    /**
     * Specify then table gateway.
     *
     * @var string Classname of Zend_DB_Table to use if not set in constructor.
     */
    protected static $_tableGatewayClass = 'Opus_Db_Series';

    /**
     * Initialize model with fields.
     *
     * @return void
     */
    protected function _init() {
        $title = new Opus_Model_Field('Title');
        $title->setMandatory(true)
                ->setValidator(new Zend_Validate_NotEmpty());

        $infobox = new Opus_Model_Field('Infobox');
        $infobox->setTextarea(true);

        $visible = new Opus_Model_Field('Visible');
        $visible->setCheckbox(true);

        $sortOrder = new Opus_Model_Field('SortOrder');

        $this->addField($title)                
                ->addField($infobox)
                ->addField($visible)
                ->addField($sortOrder);
    }

    /**
     * Retrieve all Opus_Series instances from the database.
     *
     * @return array Array of Opus_Series objects.
     */
    public static function getAll() {
        return self::getAllFrom('Opus_Series', self::$_tableGatewayClass);
    }

    /**
     * Retrieve all Opus_Series instances sorted by sort_order.
     *
     * @return array Array of Opus_Series objects sorted by sort_order in ascending order.
     */
    public static function getAllSortedBySortKey() {
        return self::getAllFrom('Opus_Series', self::$_tableGatewayClass, null, 'sort_order');
    }

    /**
     * Retrieve maximum value in column sort_order.
     * Return 0 if database does not contain any series.
     * 
     */
    public static function getMaxSortKey() {
        $db = Zend_Db_Table::getDefaultAdapter();
        $max = $db->fetchCol('SELECT MAX(sort_order) FROM document_series');
        
        if (is_null($max[0])) {
            return 0;
        }
        return $max[0];
    }

    /**
     * Return document ids associated to this series.
     */
    public function getDocumentIds() {
        $db = Zend_Db_Table::getDefaultAdapter();
        $rowSet = $db->fetchAll(
                'SELECT document_id FROM link_documents_series WHERE series_id = ' .
                $this->getId());
                
        $ids = array();        
        foreach ($rowSet as $row) {
            array_push($ids, $row['document_id']);
            
        }
        return $ids;
    }

    /**
     * Return document ids associated to this series ordered descending by sorting key.
     */
    public function getDocumentIdsSortedBySortKey() {
        $db = Zend_Db_Table::getDefaultAdapter();
        $rowSet = $db->fetchAll(
                'SELECT document_id FROM link_documents_series WHERE series_id = ' .
                $this->getId() . ' ORDER BY doc_sort_order DESC');
        
        $ids = array();
        foreach ($rowSet as $row) {
            array_push($ids, $row['document_id']);
        }
        return $ids;
    }

    /**
     * Return true if given series number is available. Otherwise false.
     *
     * @param string $number
     * @return boolean
     * 
     */
    public function isNumberAvailable($number) {
        $db = Zend_Db_Table::getDefaultAdapter();
        $rowSet = $db->fetchAll(
                'SELECT COUNT(*) AS rows_count FROM link_documents_series WHERE series_id = ' .
                $this->getId() . ' AND number = ' . $db->quote($number));
        return $rowSet[0]['rows_count'] === '0';
    }

    /**
     * Return number of documents that are associated to a given series.
     *
     * @return int
     */
    public function getNumOfAssociatedDocuments() {
        $db = Zend_Db_Table::getDefaultAdapter();
        $rowSet = $db->fetchAll(
                'SELECT COUNT(*) AS rows_count FROM link_documents_series WHERE series_id = ' . $this->getId());
        return intval($rowSet[0]['rows_count']);

    }
}
