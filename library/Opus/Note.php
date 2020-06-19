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
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2008-2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

/**
 * Domain model for notes in the Opus framework
 *
 * @category    Framework
 * @package     Opus
 * @uses        Opus_Model_Abstract
 *
 * @method void setMessage(string $message)
 * @method string getMessage()
 *
 * @method void setVisibility(string $visibility)
 * @method string getVisibility
 */
class Opus_Note extends Opus_Model_Dependent_Abstract
{

    const ACCESS_PUBLIC = 'public';

    const ACCESS_PRIVATE = 'private';

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
    protected static $_tableGatewayClass = 'Opus_Db_DocumentNotes';

    /**
     * Initialize model with the following fields:
     * - Language
     * - Title
     *
     * @return void
     */
    protected function _init()
    {
        $message = new Opus_Model_Field('Message');
        $message->setMandatory(true)
            ->setValidator(new Zend_Validate_NotEmpty())
            ->setTextarea(true);

        $visibility = new Opus_Model_Field('Visibility');
        $visibility->setValidator(new \Opus\Validate\NoteVisibility())
            ->setDefault([
                'private' => 'private',
                'public' => 'public'
            ])
            ->setSelection(true);

        $this->addField($visibility)
            ->addField($message);
    }
}
