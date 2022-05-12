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
 */

namespace Opus\Model2;

use Doctrine\ORM\Mapping as ORM;

/**
 * Abstract class for link Person model in the  Opus framework.
 *
 * @ORM\Entity
 * @ORM\Table(name="link_persons_documents",
 *     indexes={
 *         @ORM\Index(name="PRIMARY", columns={"person_id", "document_id", "role"}),
 *         @ORM\Index(name="fk_link_documents_persons", columns={"person_id"}),
 *         @ORM\Index(name="fk_link_documents_documents", columns={"document_id"})
 *     })
 */
class DocumentPerson extends AbstractModel
{
    const ROLE_ADVISOR     = 'advisor';
    const ROLE_AUTHOR      = 'author';
    const ROLE_CONTRIBUTOR = 'contributor';
    const ROLE_EDITOR      = 'editor';
    const ROLE_REFEREE     = 'referee';
    const ROLE_OTHER       = 'other';
    const ROLE_TRANSLATOR  = 'translator';
    const ROLE_SUBMITTER   = 'submitter';

    /**
     * The class of the model that is linked to.
     *
     * @var string
     */
    protected $modelClass = Person::class;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\Column(type="string", columnDefinition="ENUM('advisor','author','contributor','editor','referee','other','translator','submitter')")
     *
     * @var string
     */
    private $role;

    /**
     * @ORM\Column(type="smallint", name="sort_order", nullable=false, options={"unsigned":true, "default":0})
     *
     * @var int
     */
    private $sortOrder = 0;

    /**
     * @ORM\Column(name="allow_email_contact", type="boolean", options={"default":false})
     *
     * @var bool
     */
    private $allowEmailContact = false;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\ManyToOne(targetEntity="Person", cascade={"persist"})
     * @ORM\JoinColumn(name="person_id", referencedColumnName="id", nullable=false)
     *
     * @var Person
     */
    private $person;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\ManyToOne(targetEntity="Document")
     * @ORM\JoinColumn(name="document_id", referencedColumnName="id", nullable=false)
     *
     * @var Document
     */
    private $document;

    /**
     * @return string
     */
    public function getModelClass()
    {
        return $this->modelClass;
    }

    public function setModelClass(string $modelClass)
    {
        $this->modelClass = $modelClass;
    }

    /**
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @param string $role
     */
    public function setRole($role)
    {
        $this->role = $role;
    }

    /**
     * @return int
     */
    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    /**
     * @param int $sortOrder
     */
    public function setSortOrder($sortOrder)
    {
        $this->sortOrder = $sortOrder;
    }

    /**
     * @return bool
     */
    public function isAllowEmailContact()
    {
        return $this->allowEmailContact;
    }

    /**
     * @param bool $allowEmailContact
     */
    public function setAllowEmailContact($allowEmailContact)
    {
        $this->allowEmailContact = $allowEmailContact;
    }

    /**
     * @return Person
     */
    public function getPerson()
    {
        return $this->person;
    }

    /**
     * @param Person $person
     */
    public function setPerson($person)
    {
        $this->person = $person;
    }

    /**
     * @return Document
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * @param Document $document
     */
    public function setDocument($document)
    {
        $this->document = $document;
    }

    /**
     * @return array
     */
    protected static function describe()
    {
        // TODO: Implement describe() method.
        return [];
    }

    /**
     * Return the primary key of the Link Model if it has been persisted.
     *
     * @return int|null
     *
     * TODO: To be clarified
     */
    public function getId()
    {
        if ($this->person === null && $this->document === null) {
            // its a new record, so return null
            return null;
        }

        return $this->person->getId();
    }
}
