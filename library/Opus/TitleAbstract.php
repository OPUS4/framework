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

use Opus\Common\TitleAbstractInterface;
use Zend_Db_Table_Abstract;

use function array_key_exists;

/**
 * Domain model for document abstracts in the Opus framework
 *
 * This is not an 'abstract' class like the name would suggest, but objects of this class
 * represent abstracts for documents.
 *
 * TODO Is this class necessary?
 * TODO Should modifying Type be suppressed in this class?
 * TODO Opus\Title can be used instead of Opus\TitleAbstract for addTitleAbstract, but then Type does not get set properly.
 *
 * phpcs:disable
 */
class TitleAbstract extends Title implements TitleAbstractInterface
{
    public function __construct($id = null, ?Zend_Db_Table_Abstract $tableGatewayModel = null)
    {
        parent::__construct($id, $tableGatewayModel);

        $this->setType(self::TYPE_ABSTRACT); // setting in _init() does not work
    }

    /**
     * Set textarea flag for Value field.
     */
    protected function init()
    {
        parent::init();

        $this->getField('Value')->setTextarea(true);

        $type = $this->getField('Type');
        $type->setMandatory(false);
        $type->setSelection(true);
        $type->setDefault([self::TYPE_ABSTRACT => self::TYPE_ABSTRACT]);
        $type->setValue(self::TYPE_ABSTRACT); // TODO this does not work - why? because modified flag gets reset afterwards
    }

    public function updateFromArray($data)
    {
        if (! array_key_exists('Type', $data)) {
            $data['Type'] = self::TYPE_ABSTRACT;
        }

        parent::updateFromArray($data);
    }
}
