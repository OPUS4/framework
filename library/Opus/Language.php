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
 * @author      Felix Ostrowski <ostrowski@hbz-nrw.de>
 * @author      Simone Finkbeiner <simone.finkbeiner@ub.uni-stuttgart.de>
 * @copyright   Copyright (c) 2009, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Domain model for languages in the Opus framework
 *
 * @category    Framework
 * @package     Opus
 * @uses        Opus_Model_AbstractDb
 */
class Opus_Language extends Opus_Model_AbstractDb {

    /**
     * Specify then table gateway.
     *
     * @var string Classname of Zend_DB_Table to use if not set in constructor.
     */
    protected static $_tableGatewayClass = 'Opus_Db_Languages';

    /**
     * Initialize model with fields.
     *
     * @return void
     */
    protected function _init() {
        $part2B = new Opus_Model_Field('Part2B');

        $part2T = new Opus_Model_Field('Part2T');
        $part2T->setMandatory(true)
            ->setValidator(new Zend_Validate_NotEmpty());

        $part1 = new Opus_Model_Field('Part1');
        $scope = new Opus_Model_Field('Scope');
        $type = new Opus_Model_Field('Type');

        $ref_name = new Opus_Model_Field('RefName');
        $ref_name->setMandatory(true)
            ->setValidator(new Zend_Validate_NotEmpty());

        $comment = new Opus_Model_Field('Comment');
        $active = new Opus_Model_Field('Active');
        $active->setCheckbox(true);

        $this->addField($part2B)
            ->addField($part2T)
            ->addField($part1)
            ->addField($scope)
            ->addField($type)
            ->addField($ref_name)
            ->addField($comment)
            ->addField($active);
    }

    /**
     * Retrieve all Opus_Language instances from the database.
     *
     * @return array Array of Opus_Language objects.
     */
    public static function getAll() {
        return self::getAllFrom('Opus_Language', 'Opus_Db_Languages');
    }

    /**
     * Get all active languages.
     *
     * @return array Array of Opus_Language objects which are active.
     */
    public static function getAllActive() {
        $table = Opus_Db_TableGateway::getInstance('Opus_Db_Languages');
        $rows = $table->fetchAll($table->select()->where('active = ?', 1));
        $result = array();
        foreach ($rows as $row) {
            $result[] = new Opus_Language($row);
        }
        return $result;
    }

    /**
     * Retrieve languages by natural name.
     *
     * @param  string  $letter Letter(s) the wanted languages begin(s) with.
     * @return array Array of Opus_Language objects.
     */
    public static function getAllByName($letter) {
        $table = Opus_Db_TableGateway::getInstance('Opus_Db_Languages');
        $rows = $table->fetchAll($table->select()->where("ref_name LIKE ?",
                    $letter . '%')->order('ref_name ASC'));
        $result = array();
        foreach ($rows as $row) {
            $result[] = new Opus_Language($row);
        }
        return $result;
    }

    /**
     * Fetch language by ISO 639-1 (two-letter) code.
     *
     * @param  string  $part1 ISO 639-1 (two-letter) code
     * @return Opus_Language The language that corresponds to the ISO 639-1 code.
     */
    public static function getByPart1($part1) {
        $table = Opus_Db_TableGateway::getInstance('Opus_Db_Languages');
        $row = $table->fetchRow($table->select()->where("part1 = ?", $part1));
        return new Opus_Language($row);
    }

    /**
     * Returns reference language name.
     *
     * @see library/Opus/Model/Opus_Model_Abstract#getDisplayName()
     */
    public function getDisplayName() {
       return $this->getRefName();
    }
}
