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
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2008-2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

/**
 * Domain model for document abstracts in the Opus framework
 *
 * This is not an 'abstract' class like the name would suggest, but objects of this class
 * represent abstracts for documents.
 *
 * @category    Framework
 * @package     Opus
 *
 * TODO Is this class necessary?
 * TODO Should modifying Type be suppressed in this class?
 * TODO Opus_Title can be used instead of Opus_TitleAbstract for addTitleAbstract, but then Type does not get set properly.
 */
class Opus_TitleAbstract extends Opus_Title
{

    const TYPE_ABSTRACT = 'abstract';

    public function __construct($id = null, Zend_Db_Table_Abstract $tableGatewayModel = null) {
        parent::__construct($id, $tableGatewayModel);

        $this->setType(self::TYPE_ABSTRACT); // setting in _init() does not work
    }

    /**
     * Set textarea flag for Value field.
     *
     * @return void
     */
    protected function _init()
    {
        parent::_init();

        $this->getField('Value')->setTextarea(true);

        $type = $this->getField('Type');
        $type->setMandatory(false);
        $type->setSelection(true);
        $type->setDefault([self::TYPE_ABSTRACT => self::TYPE_ABSTRACT]);
        $type->setValue(self::TYPE_ABSTRACT); // TODO this does not work - why?
    }

    public function updateFromArray($data)
    {
        if (!array_key_exists('Type', $data)) {
            $data['Type'] = self::TYPE_ABSTRACT;
        }

        parent::updateFromArray($data);
    }
}
