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
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus;

use Opus\Common\Config;
use Opus\Common\SeriesInterface;
use Opus\Db\TableGateway;
use Opus\Model\AbstractDb;
use Opus\Model\Field;
use Zend_Db_Table;
use Zend_Validate_Int;
use Zend_Validate_NotEmpty;

use function count;
use function filter_var;
use function intval;
use function strlen;
use function trim;

use const FILTER_VALIDATE_BOOLEAN;

/**
 * Domain model for sets in the Opus framework
 *
 * phpcs:disable
 */
class Series extends AbstractDb implements SeriesInterface
{
    /**
     * Specify then table gateway.
     *
     * @var string Classname of \Zend_DB_Table to use if not set in constructor.
     */
    protected static $tableGatewayClass = Db\Series::class;

    /**
     * Plugins to load
     *
     * @var array
     */
    public function getDefaultPlugins()
    {
        return [
            Model\Plugin\InvalidateDocumentCache::class,
        ];
    }

    /**
     * Initialize model with fields.
     */
    protected function init()
    {
        $title = new Field('Title');
        $title->setMandatory(true)
                ->setValidator(new Zend_Validate_NotEmpty());

        $infobox = new Field('Infobox');
        $infobox->setTextarea(true);

        $visible = new Field('Visible');
        $visible->setCheckbox(true);

        $sortOrder = new Field('SortOrder');
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
     * of Opus\Series.
     *
     * @param int $id
     * @return TableGateway
     */
    public static function createRowWithCustomId($id)
    {
        $tableGatewayModel = TableGateway::getInstance(self::$tableGatewayClass);
        $row               = $tableGatewayModel->createRow();
        $row->id           = $id;
        return $row;
    }

    /**
     * Retrieve all Opus\Series instances from the database.
     *
     * @return array Array of Opus\Series objects.
     */
    public function getAll()
    {
        $config = Config::get();

        if (isset($config->series->sortByTitle) && filter_var($config->series->sortByTitle, FILTER_VALIDATE_BOOLEAN)) {
            $all = self::getAllFrom(self::class, self::$tableGatewayClass, null, 'title');
        } else {
            $all = self::getAllFrom(self::class, self::$tableGatewayClass);
        }

        return $all;
    }

    /**
     * Retrieve all Opus\Series instances sorted by sort_order.
     *
     * @return array Array of Opus\Series objects sorted by sort_order in ascending order.
     */
    public function getAllSortedBySortKey()
    {
        $config = Config::get();

        if (isset($config->series->sortByTitle) && filter_var($config->series->sortByTitle, FILTER_VALIDATE_BOOLEAN)) {
            $all = self::getAll();
        } else {
            $all = self::getAllFrom(
                self::class,
                self::$tableGatewayClass,
                null,
                'sort_order'
            );
        }

        return $all;
    }

    /**
     * Retrieve maximum value in column sort_order.
     * Return 0 if database does not contain any series.
     *
     * TODO return int
     */
    public function getMaxSortKey()
    {
        $db  = Zend_Db_Table::getDefaultAdapter();
        $max = $db->fetchOne('SELECT MAX(sort_order) FROM document_series');

        if ($max === null) {
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
        return $db->fetchCol(
            'SELECT document_id FROM link_documents_series '
            . 'WHERE series_id = ?',
            $this->getId()
        );
    }

    /**
     * Return document ids associated to this series ordered descending by sorting key.
     */
    public function getDocumentIdsSortedBySortKey()
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        return $db->fetchCol(
            'SELECT document_id FROM link_documents_series WHERE series_id = ? ORDER BY doc_sort_order DESC',
            $this->getId()
        );
    }

    /**
     * @return int|null Document id associated with series number or null.
     */
    public function getDocumentIdForNumber($number)
    {
        if (strlen(trim($number)) === 0) {
            return null;
        }
        $adapter    = Zend_Db_Table::getDefaultAdapter();
        $documentId = $adapter->fetchCol(
            'SELECT document_ID FROM link_documents_series WHERE series_id = ? AND number = ?',
            [$this->getId(), $number]
        );

        return count($documentId) === 1 ? $documentId[0] : null;
    }

    /**
     * Return true if given series number is available. Otherwise false.
     *
     * @param string $number
     * @return bool
     */
    public function isNumberAvailable($number)
    {
        $db    = Zend_Db_Table::getDefaultAdapter();
        $count = $db->fetchOne(
            'SELECT COUNT(*) AS rows_count FROM link_documents_series '
            . 'WHERE series_id = ? AND number = ?',
            [$this->getId(), $number]
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
        $db    = Zend_Db_Table::getDefaultAdapter();
        $count = $db->fetchOne(
            'SELECT COUNT(*) AS rows_count FROM link_documents_series '
            . 'WHERE series_id = ?',
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
        $db    = Zend_Db_Table::getDefaultAdapter();
        $count = $db->fetchOne(
            'SELECT COUNT(*) AS rows_count '
            . 'FROM link_documents_series l, documents d '
            . 'WHERE l.document_id = d.id AND d.server_state = \'published\' AND l.series_id = ?',
            $this->getId()
        );
        return intval($count);
    }

    public function getDisplayName()
    {
        return parent::getTitle();
    }

    /**
     * @return string|null
     */
    public function getTitle()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @param string $title
     * @return $this
     */
    public function setTitle($title)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @return string|null
     */
    public function getInfobox()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @param string|null $info
     * @return $this
     */
    public function setInfobox($info)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @return bool
     */
    public function getVisible()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @param bool $visible
     * @return $this
     */
    public function setVisible($visible)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @return int
     */
    public function getSortOrder()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @param int $pos
     * @return $this
     */
    public function setSortOrder($pos)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }
}
