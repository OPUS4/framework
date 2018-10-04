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
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2008-2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

/**
 * Domain model for sets in the Opus framework
 *
 * @category    Framework
 * @package     Opus
 * @uses        Opus_Model_Abstract
 *
 * @method void setTitle(string $title)
 * @method string getTitle()
 *
 * @method void setInfobox(string $info)
 * @method string getInfobox()
 *
 * @method void setVisible(boolean $visible)
 * @method boolean getVisible()
 *
 * @method void setSortOrder(integer $pos)
 * @method integer getSortOrder()
 */
class Opus_Series extends Opus_Model_AbstractDb
{

    /**
     * Specify then table gateway.
     *
     * @var string Classname of Zend_DB_Table to use if not set in constructor.
     */
    protected static $_tableGatewayClass = 'Opus_Db_Series';

    /**
     * Plugins to load
     *
     * @var array
     */
    protected $_plugins = array(
        'Opus_Model_Plugin_InvalidateDocumentCache' => null,
    );


    /**
     * Initialize model with fields.
     *
     * @return void
     */
    protected function _init()
    {
        $title = new Opus_Model_Field('Title');
        $title->setMandatory(true)
                ->setValidator(new Zend_Validate_NotEmpty());

        $infobox = new Opus_Model_Field('Infobox');
        $infobox->setTextarea(true);

        $visible = new Opus_Model_Field('Visible');
        $visible->setCheckbox(true);

        $sortOrder = new Opus_Model_Field('SortOrder');
        $sortOrder->setValidator(new Zend_Validate_Int());

        $this->addField($title)
                ->addField($infobox)
                ->addField($visible)
                ->addField($sortOrder);
    }

    /**
     * Factory that tries to create a series with the given id.
     * Note that the series is *not* persisted to the database.
     * You need to explicitly call store() on the corresponding model instance
     * of Opus_Series.
     *
     * @param integer $id
     * @return Opus_Db_TableGateway
     */
    public static function createRowWithCustomId($id)
    {
        $tableGatewayModel = Opus_Db_TableGateway::getInstance(self::$_tableGatewayClass);
        $row = $tableGatewayModel->createRow();
        $row->id = $id;
        return $row;
    }

    /**
     * Retrieve all Opus_Series instances from the database.
     *
     * @return array Array of Opus_Series objects.
     */
    public static function getAll()
    {
        $config = Zend_Registry::get('Zend_Config');

        if (isset($config->series->sortByTitle) && $config->series->sortByTitle == '1' ) {
            $all = self::getAllFrom('Opus_Series', self::$_tableGatewayClass, null, 'title');
        } else {
            $all = self::getAllFrom('Opus_Series', self::$_tableGatewayClass);
        }

        return $all;
    }

    /**
     * Retrieve all Opus_Series instances sorted by sort_order.
     *
     * @return array Array of Opus_Series objects sorted by sort_order in ascending order.
     */
    public static function getAllSortedBySortKey()
    {
        $config = Zend_Registry::get('Zend_Config');

        if (isset($config->series->sortByTitle) && $config->series->sortByTitle == '1' ) {
            $all = self::getAll();
        } else {
            $all = self::getAllFrom(
                'Opus_Series', self::$_tableGatewayClass, null, 'sort_order'
            );
        }

        return $all;
    }

    /**
     * Retrieve maximum value in column sort_order.
     * Return 0 if database does not contain any series.
     *
     */
    public static function getMaxSortKey()
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $max = $db->fetchOne('SELECT MAX(sort_order) FROM document_series');

        if (is_null($max)) {
            return 0;
        }

        return $max;
    }

    /**
     * Return document ids associated to this series.
     */
    public function getDocumentIds()
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $ids = $db->fetchCol(
            'SELECT document_id FROM link_documents_series ' .
            'WHERE series_id = ?',
            $this->getId()
        );
        return $ids;
    }

    /**
     * Return document ids associated to this series ordered descending by sorting key.
     */
    public function getDocumentIdsSortedBySortKey()
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $ids = $db->fetchCol(
            'SELECT document_id FROM link_documents_series WHERE series_id = ? ORDER BY doc_sort_order DESC',
            $this->getId()
        );
        return $ids;
    }

    /**
     * @return int|null Document id associated with series number or null.
     */
    public function getDocumentIdForNumber($number)
    {
        if (strlen(trim($number)) == 0) {
            return null;
        }
        $adapter = Zend_Db_Table::getDefaultAdapter();
        $documentId = $adapter->fetchCol(
            'SELECT document_ID FROM link_documents_series WHERE series_id = ? AND number = ?',
            array($this->getId(), $number)
        );

        return (count($documentId) == 1) ? $documentId[0] : null;
    }

    /**
     * Return true if given series number is available. Otherwise false.
     *
     * @param string $number
     * @return boolean
     *
     */
    public function isNumberAvailable($number)
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $count = $db->fetchOne(
            'SELECT COUNT(*) AS rows_count FROM link_documents_series ' .
            'WHERE series_id = ? AND number = ?',
            array($this->getId(), $number)
        );
        return $count === '0';
    }

    /**
     * Return number of documents that are associated to a given series.
     *
     * @return int
     */
    public function getNumOfAssociatedDocuments()
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $count = $db->fetchOne(
            'SELECT COUNT(*) AS rows_count FROM link_documents_series ' .
            'WHERE series_id = ?',
            $this->getId()
        );
        return intval($count);
    }

    /**
     * Return number of documents in server state published that are
     * associated to a given series.
     *
     * @return int
     */
    public function getNumOfAssociatedPublishedDocuments()
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $count = $db->fetchOne(
            'SELECT COUNT(*) AS rows_count ' .
            'FROM link_documents_series l, documents d ' .
            'WHERE l.document_id = d.id AND d.server_state = \'published\' AND l.series_id = ?',
            $this->getId()
        );
        return intval($count);
    }

    public function getDisplayName()
    {
        return parent::getTitle();
    }
}
