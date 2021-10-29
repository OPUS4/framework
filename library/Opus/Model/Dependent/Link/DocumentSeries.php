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
 * @copyright   Copyright (c) 2008-2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * @category    Framework
 * @package     Opus\Model
 * @author      Sascha Szott <szott@zib.de>
 * @author      Jens Schwidder <schwidder@zib.de>
 */

namespace Opus\Model\Dependent\Link;

use Opus\Db\LinkDocumentsSeries;
use Opus\Model\Field;
use Opus\Series;
use Zend_Db_Table;
use Zend_Validate_NotEmpty;

use function intval;

/**
 * phpcs:disable
 *
 * @method void setNumber(string $number)
 * @method string getNumber()
 * @method void setDocSortOrder(integer $pos)
 * @method integer getDocSortOrder()
 */
class DocumentSeries extends AbstractLinkModel
{
    /**
     * Primary key of the parent model.
     *
     * @var mixed
     */
    protected $parentColumn = 'document_id';

    /**
     * The linked model's foreign key.
     *
     * @var mixed
     */
    protected $modelKey = 'series_id';

    /**
     * The class of the model that is linked to.
     *
     * @var string
     */
    protected $modelClass = Series::class;

    /**
     * Specify then table gateway.
     *
     * @var string Classname of \Zend_DB_Table to use if not set in constructor.
     */
    protected static $tableGatewayClass = LinkDocumentsSeries::class;

    /**
     * Fields that should not be displayed on a form.
     *
     * @var array
     */
    protected $internalFields = [];

    /**
     * Initialize model
     */
    protected function init()
    {
        $modelClass = $this->modelClass;
        if ($this->getId() !== null) {
            $this->setModel(new $modelClass($this->primaryTableRow->{$this->modelKey}));
        }

        $number = new Field('Number');
        $number->setMandatory(true)
                ->setValidator(new Zend_Validate_NotEmpty());
        $this->addField($number);

        $docSortOrder = new Field('DocSortOrder');
        $this->addField($docSortOrder);
    }

    /**
     * Persist foreign model & link.
     */
    public function store()
    {
        $this->primaryTableRow->series_id = $this->model->store();
        parent::store();
    }

    protected function _storeDocSortOrder()
    {
        $docSortOrderValue = $this->fields['DocSortOrder']->getValue();
        if ($docSortOrderValue === null) {
            $db  = Zend_Db_Table::getDefaultAdapter();
            $max = $db->fetchCol(
                'SELECT MAX(doc_sort_order)'
                . ' FROM link_documents_series'
                . ' WHERE series_id = ' . $this->primaryTableRow->series_id
                . ' AND document_id != ' . $this->primaryTableRow->document_id
            );
            if ($max[0] !== null) {
                $docSortOrderValue = intval($max[0]) + 1;
            } else {
                $docSortOrderValue = 0;
            }
        }
        $this->primaryTableRow->doc_sort_order = $docSortOrderValue;
    }
}
