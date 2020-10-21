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
 * @author      Felix Ostrowski (ostrowski@hbz-nrw.de)
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
*/

namespace Opus;

use Opus\Model\Dependent\AbstractDependentModel;
use Opus\Model\Field;

/**
 * Domain model for document subjects in the Opus framework
 *
 * @category    Framework
 * @package     Opus
 * @uses        \Opus\Model\AbstractModel
 */
class SubjectSwd extends AbstractDependentModel
{
    /**
     * Primary key of the parent model.
     *
     * @var mixed $_parentId.
     */
    protected $_parentColumn = 'document_id';

    /**
     * Specify then table gateway.
     *
     * @var string
     */
    protected static $_tableGatewayClass = 'Opus\Db\DocumentSubjects';

    /**
     * Fields that should not be displayed on a form.
     *
     * @var array  Defaults to array('Type').
     */
    protected $_internalFields = [
        'Type',
        'Language',
    ];

    /**
     * Initialize model with the following fields:
     * - Language
     * - Type
     * - Value
     * - External key
     *
     * @return void
     */
    protected function _init()
    {
        $language = new Field('Language');
        $type = new Field('Type');

        $value = new Field('Value');
        $value->setMandatory(true)
            ->setValidator(new \Zend_Validate_NotEmpty());

        $externalKey = new Field('ExternalKey');

        $this->addField($language)
            ->addField($type)
            ->addField($value)
            ->addField($externalKey);

        $this->_primaryTableRow->language = 'deu';
        $this->_primaryTableRow->type = 'swd';
    }
}
