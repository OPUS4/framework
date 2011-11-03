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
 * @package     Opus_Model
 * @author      Felix Ostrowski <ostrowski@hbz-nrw.de>
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Domain model for document references in the Opus framework
 *
 * @category    Framework
 * @package     Opus_Model
 * @uses        Opus_Model_Dependent_Abstract
 */
class Opus_Reference extends Opus_Model_Dependent_Abstract
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
    protected static $_tableGatewayClass = 'Opus_Db_DocumentReferences';

    /**
     * Initialize model with the following fields:
     * - Value
     * - Label
     *
     * @return void
     */
    protected function _init() {
        $value = new Opus_Model_Field('Value');
        $value->setMandatory(true)
            ->setValidator(new Zend_Validate_NotEmpty());

        $label = new Opus_Model_Field('Label');
        $label->setMandatory(true)
            ->setValidator(new Zend_Validate_NotEmpty());

        $relation = new Opus_Model_Field('Relation');
        $relation->setMandatory(false);
        $relation->setSelection(true);
        $relation->setDefault(array(
            'updates' => 'updates',
            'updated-by' => 'updated-by',
            'other' => 'other'
        ));

        $type = new Opus_Model_Field('Type');
        $type->setMandatory(false); // TODO change later
        $type->setSelection(true);
        $type->setDefault(array(
            'isbn' => 'isbn',
            'urn' => 'urn',
            'doi' => 'doi',
            'handle' => 'handle',
            'url' => 'url',
            'issn' => 'issn',
            'std-doi' => 'std-doi',
            'cris-link' => 'cris-link',
            'splash-url' => 'splash-url',
            'opus4-id' => 'opus4-id'
                ));

        $this->addField($value);
        $this->addField($label);
        $this->addField($relation);
        $this->addField($type);
    }

}
