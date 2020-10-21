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
 * @package     Opus\Model
 * @author      Sascha Szott <szott@zib.de>
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2008-2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\Model\Dependent\Link;

use Opus\Model\Field;

/**
 * Class Opus\Model\Dependent\Link\DocumentSeries
 *
 * @method void setNumber(string $number)
 * @method string getNumber()
 *
 * @method void setDocSortOrder(integer $pos)
 * @method integer getDocSortOrder()
 */
class DocumentSeries extends AbstractLinkModel
{

    /**
     * Primary key of the parent model.
     *
     * @var mixed $_parentId.
     */
    protected $_parentColumn = 'document_id';

    /**
     * The linked model's foreign key.
     *
     * @var mixed
     */
    protected $_modelKey = 'series_id';

    /**
     * The class of the model that is linked to.
     *
     * @var string
     */
    protected $_modelClass = 'Opus\Series';

    /**
     * Specify then table gateway.
     *
     * @var string Classname of \Zend_DB_Table to use if not set in constructor.
     */
    protected static $_tableGatewayClass = 'Opus\Db\LinkDocumentsSeries';

    /**
     * Fields that should not be displayed on a form.
     *
     * @var array
     */
    protected $_internalFields = [];


    /**
     * Initialize model
     *
     * @return void
     */
    protected function _init()
    {
        $modelClass = $this->_modelClass;
        if (is_null($this->getId()) === false) {
            $this->setModel(new $modelClass($this->_primaryTableRow->{$this->_modelKey}));
        }

        $number = new Field('Number');
        $number->setMandatory(true)
                ->setValidator(new \Zend_Validate_NotEmpty());
        $this->addField($number);

        $docSortOrder = new Field('DocSortOrder');
        $this->addField($docSortOrder);
    }

    /**
     * Persist foreign model & link.
     *
     * @return void
     */
    public function store()
    {
        $this->_primaryTableRow->series_id = $this->_model->store();
        parent::store();
    }

    protected function _storeDocSortOrder()
    {
        $docSortOrderValue = $this->_fields['DocSortOrder']->getValue();
        if (is_null($docSortOrderValue)) {
            $db = \Zend_Db_Table::getDefaultAdapter();
            $max = $db->fetchCol(
                'SELECT MAX(doc_sort_order)' .
                ' FROM link_documents_series' .
                ' WHERE series_id = ' . $this->_primaryTableRow->series_id .
                ' AND document_id != ' . $this->_primaryTableRow->document_id
            );
            if (! is_null($max[0])) {
                $docSortOrderValue = intval($max[0]) + 1;
            } else {
                $docSortOrderValue = 0;
            }
        }
        $this->_primaryTableRow->doc_sort_order = $docSortOrderValue;
    }
}
