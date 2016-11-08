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
 * @author      Felix Ostrowski (ostrowski@hbz-nrw.de)
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Domain model for document subjects in the Opus framework
 *
 * @category    Framework
 * @package     Opus
 * @uses        Opus_Model_Abstract
 */
class Opus_Subject extends Opus_Model_Dependent_Abstract {
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
    protected static $_tableGatewayClass = 'Opus_Db_DocumentSubjects';

    /**
     * Initialize model with the following fields:
     * - Language
     * - Type
     * - Value
     * - External key
     *
     * @return void
     */
    protected function _init() {
        $language = new Opus_Model_Field('Language');
        if (Zend_Registry::isRegistered('Available_Languages') === true) {
            $language->setDefault(Zend_Registry::get('Available_Languages'));
        }
        $language->setSelection(true);
        $language->setMandatory(true);

        $type = new Opus_Model_Field('Type');
        $type->setMandatory(true);
        $type->setSelection(true);
        $type->setDefault(
            array(
            'swd' => 'swd',
            'psyndex' => 'psyndex',
            'uncontrolled' => 'uncontrolled'
            )
        );
        
        $value = new Opus_Model_Field('Value');
        $value->setMandatory(true)
            ->setValidator(new Zend_Validate_NotEmpty());
        
        $externalKey = new Opus_Model_Field('ExternalKey');
    
        $this->addField($language)
            ->addField($type)
            ->addField($value)
            ->addField($externalKey);
    }

    /**
     * Return matching keywords for use in autocomplete function.
     *
     * @param string $term String that must be included in keyword
     * @param string $type Type of keywords
     * @param integer $limit Maximum number of returned results
     * @return array
     */
    public static function getMatchingSubjects($term, $type = 'swd', $limit = 20) {
        $table = Opus_Db_TableGateway::getInstance(self::$_tableGatewayClass);

        $select = $table->select()
            ->where('value like ?', "%$term%")
            ->order('value ASC')
            ->group(array('value', 'external_key'));

        if (!is_null($type)) {
            $select->where('type = ?', $type);
        }

        if (!is_null($limit)) {
            $select->limit($limit, 0);
        }

        $rows = $table->fetchAll($select);

        $values = array();

        foreach ($rows as $row) {
            $columns = $row->toArray();

            $subject = array();
            $subject['value'] = $columns['value'];
            $subject['extkey'] = $columns['external_key'];

            $values[] = $subject;
        }

        return $values;
    }

}

