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
 * @author      Ralf Claussnitzer <ralf.claussnitzer@slub-dresden.de>
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2008-2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\Model\Dependent\Link;

use Opus\Date;
use Opus\Model\Field;

/**
 * Abstract class for link Person model in the Opus framework.
 *
 * @category    Framework
 * @package     Opus\Model
 *
 * @method void setRole(string $role)
 * @method string getRole()
 *
 * @method void setSortOrder(integer $pos)
 * @method integer getSortOrder()
 *
 * @method void setAllowEmailContact(boolean $allowContact)
 * @method boolean getAllowEmailContact()
 *
 * Methods proxied to Opus\Person
 *
 * @method void setAcademicTitle(string $title)
 * @method string getAcademicTitle()
 *
 * @method void setFirstName(string $firstName)
 * @method string getFirstName()
 *
 * @method void setLastName(string $lastName)
 * @method string getLastName()
 *
 * @method void setDateOfBirth(Date $date)
 * @method Date getDateOfBirth()
 *
 * @method void setPlaceOfBirth(string $place)
 * @method string getPlaceOfBirth()
 *
 * @method void setIdentifierOrcid(string $orcid)
 * @method string getIdentifierOrcid()
 *
 * @method void setIdentifierGnd(string $gnd)
 * @method string getIdentifierGnd()
 *
 * @method void setIdentifierMisc(string $misc)
 * @method string getIdentifierMisc()
 *
 * @method void setEmail(string $email)
 * @method string getEmail()
 *
 * @method void setOpusId(string $internalId)
 * @method string getOpusId()
 */
class DocumentPerson extends AbstractLinkModel
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
    protected $_modelKey = 'person_id';

    /**
     * The class of the model that is linked to.
     *
     * @var string
     */
    protected $_modelClass = 'Opus\Person';

    /**
     * Specify then table gateway.
     *
     * @var string Classname of \Zend_DB_Table to use if not set in constructor.
     */
    protected static $_tableGatewayClass = 'Opus\Db\LinkPersonsDocuments';

    /**
     * Fields that should not be displayed on a form.
     *
     * @var array
     */
    protected $_internalFields = [
//            'Role',
//            'SortOrder',
    ];


    /**
     * Initialize model with the following values:
     * - Institute
     * - Role
     * - SortOrder
     *
     * @return void
     */
    protected function _init()
    {
        $modelClass = $this->_modelClass;
        if (is_null($this->getId()) === false) {
            $this->setModel(new $modelClass($this->_primaryTableRow->{$this->_modelKey}));
        }

        $role = new Field('Role');
        $role->setSelection(true);
        $role->setMandatory(false); // TODO change later maybe
        $role->setDefault([
            'advisor' => 'advisor',
            'author' => 'author',
            'contributor' => 'contributor',
            'editor' => 'editor',
            'referee' => 'referee',
            'other' => 'other',
            'translator' => 'translator',
            'submitter' => 'submitter'
        ]);

        $sortOrder = new Field('SortOrder');
        $allowEmailContact = new Field('AllowEmailContact');
        $allowEmailContact->setCheckbox(true);

        $this->addField($role)
            ->addField($sortOrder)
            ->addField($allowEmailContact);

        $this->setModified(false);
    }

    /**
     * Persist foreign model & link.
     *
     * @return void
     */
    public function store()
    {
        $this->_primaryTableRow->person_id = $this->_model->store();
        parent::store();
    }
}
