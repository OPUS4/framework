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
 * @copyright   Copyright (c) 2014-2020, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * @category    Framework
 * @package     Opus
 * @author      Felix Ostrowski (ostrowski@hbz-nrw.de)
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @author      Tobias Tappe <tobias.tappe@uni-bielefeld.de>
 * @author      Michael Lang <lang@zib.de>
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @author      Simone Finkbeiner <simone.finkbeiner@ub.uni-stuttgart.de>
 * @author      Pascal-Nicolas Becker <becker@zib.de>
 * @author      Jens Schwidder <schwidder@zib.de>
 */

namespace Opus\Model2;

use BadMethodCallException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;

use InvalidArgumentException;
use Opus\Date;
use Opus\Model\DbException;
use Opus\Model\ModelException;

/**
 * @ORM\Entity
 * @ORM\Table(name="documents")
 */
class Document extends AbstractModel
{
    const STATE_DELETED = 'deleted';

    const STATE_INPROGRESS = 'inprogress';

    const STATE_RESTRICTED = 'restricted';

    const STATE_UNPUBLISHED = 'unpublished';

    const STATE_PUBLISHED = 'published';

    const STATE_TEMPORARY = 'temporary';

    const STATE_AUDITED = 'audited';

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     *
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="smallint", name="belongs_to_bibliography")
     *
     * @var bool
     */
    private $belongsToBibliography = false;

    /**
     * @ORM\Column(type="opusDate", name="completed_date")
     * @var Date
     */
    private $completedDate;

    /**
     * @ORM\Column(type="smallint", name="completed_year")
     * @var int
     */
    private $completedYear;

    /**
     * @ORM\Column(type="text", name="contributing_corporation")
     * @var string
     */
    private $contributingCorporation;

    /**
     * @ORM\Column(type="text", name="creating_corporation")
     * @var string
     */
    private $creatingCorporation;

    /**
     * @ORM\Column(type="opusDate", name="thesis_date_accepted")
     * @var Date
     */
    private $thesisDateAccepted;

    /**
     * @ORM\Column(type="smallint", name="thesis_year_accepted")
     * @var int
     */
    private $thesisYearAccepted;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $edition;

    /**
     * @ORM\Column(type="opusDate", name="embargo_date")
     * @var Date
     */
    private $embargoDate;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $issue;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $language;

    /**
     * @ORM\Column(type="string", name="page_first")
     * @var string
     */
    private $pageFirst;

    /**
     * @ORM\Column(type="string", name="page_last")
     * @var string
     */
    private $pageLast;

    /**
     * @ORM\Column(type="string", name="page_number")
     * @var string
     */
    private $pageNumber;

    /**
     * @ORM\Column(type="string", name="article_number")
     * @var string
     */
    private $articleNumber;

    /**
     * @ORM\Column(type="opusDate", name="published_date")
     * @var Date
     */
    private $publishedDate;

    /**
     * @ORM\Column(type="smallint", name="published_year")
     * @var int
     */
    private $publishedYear;

    /**
     * @ORM\Column(type="string", name="publisher_name")
     * @var string
     */
    private $publisherName;

    /**
     * @ORM\Column(type="string", name="publisher_place")
     * @var string
     */
    private $publisherPlace;

    /**
     * @ORM\Column(type="string", name="publication_state", columnDefinition="ENUM('draft','accepted','submitted','published','updated')")
     * @var string
     */
    protected $publicationState = 'draft';

    /**
     * @ORM\Column(type="opusDate", name="server_date_created")
     * @var Date
     */
    private $serverDateCreated;

    /**
     * @ORM\Column(type="opusDate", name="server_date_modified")
     * @var Date
     */
    private $serverDateModified;

    /**
     * @ORM\Column(type="opusDate", name="server_date_published")
     * @var Date
     */
    private $serverDatePublished;

    /**
     * @ORM\Column(type="opusDate", name="server_date_deleted")
     * @var Date
     */
    private $serverDateDeleted;

    /**
     * @ORM\Column(type="string", name="server_state", columnDefinition="ENUM('audited','published','restricted','inprogress','unpublished','deleted','temporary')")
     * @var string
     */
    private $serverState = self::STATE_TEMPORARY;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    protected $type;

    /**
     * @ORM\Column(type="string", length=100)
     * @var string
     */
    private $volume;

    /**
     * @ORM\OneToMany(targetEntity="Title", mappedBy="document", cascade={"persist"})
     * @var Collection|Title[]
     */
    private $titles;

    /**
     * @ORM\OneToMany(targetEntity="DocumentPerson", mappedBy="document", cascade={"persist"})
     * @var Collection|DocumentPerson[]
     */
    private $documentPersons;

    // FIXME: Taken from the original document class due to a dependency on DocumentTest
    private static $defaultPlugins;

    /**
     * Document constructor.
     */
    public function __construct() {
        $this->titles = new ArrayCollection();
        $this->documentPersons = new ArrayCollection();
    }

    /**
     * Perform any actions needed to provide storing.
     *
     * @return mixed|null Anything else than null will cancel the storage process.
     */
    protected function preStore()
    {
        $result = parent::preStore();

        $date = new Date();
        $date->setNow();

        if ($this->isNewRecord()) {
            if ($this->getServerDateCreated() === null) {
                $this->setServerDateCreated($date);
            }
        }
        $this->setServerDateModified($date);

        return $result;
    }


    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    protected static function describe()
    {
        return [
            'BelongsToBibliography',
            'CompletedDate',
            'CompletedYear',
            'ContributingCorporation',
            'CreatingCorporation',
            'ThesisDateAccepted',
            'ThesisYearAccepted',
            'Edition',
            'EmbargoDate',
            'Issue',
            'Language',
            'PageFirst',
            'PageLast',
            'PageNumber',
            'ArticleNumber',
            'PublishedDate',
            'PublishedYear',
            'PublisherName',
            'PublisherPlace',
            'PublicationState',
            'ServerDateCreated',
            'ServerDateModified',
            'ServerDatePublished',
            'ServerDateDeleted',
            'ServerState',
            'Type',
            'Volume',
        ];
    }

    public function setDefaultPlugins($plugins)
    {
        self::$defaultPlugins = $plugins;
    }

    /**
     * Magic method to access the models fields via virtual set/get methods.
     *
     * @param string $name      Name of the method beeing called.
     * @param array  $arguments Arguments for function call.
     * @return mixed Might return a value if a getter method is called.
     */
    public function __call($name, array $arguments)
    {
        $accessor  = substr($name, 0, 3);
        $fieldname = lcfirst(substr($name, 3));

        $argumentGiven = false;
        $argument      = null;
        if (false === empty($arguments)) {
            $argumentGiven = true;
            $argument      = $arguments[0];
        }

        // Filter calls to unknown methods and turn them into an exception
        $validAccessors = ['set', 'get'];
        if (in_array($accessor, $validAccessors) === false) {
            throw new BadMethodCallException($name . ' is no method in this object.');
        }

        // check if requested field is known
        if (!property_exists($this, lcfirst($fieldname))) {
            throw new ModelException('Unknown field: ' . $fieldname);
        }

        // check if set/add has been called with an argument
        if ((false === $argumentGiven) and ($accessor === 'set')) {
            throw new ModelException('Argument required for set() calls, none given.');
        }

        switch ($accessor) {
            case 'get':
                return $this->$fieldname;
                break;

            case 'set':
                $this->$fieldname = $argument;
                break;
            default:
                throw new ModelException('Unknown accessor function: ' . $accessor);
                break;
        }
    }

    /**
     * Generic method for getting titles used by the specific title type methods, e.g. getTitleMain()
     * Returns the collection of title instances related to this document, or just the title with the
     * specified index.
     *
     * @param string   $type The title type: 'main','parent','abstract','sub','additional'
     * @param null|int $index (Optional) The title's index in the title collection of the given type.
     * @return Title|Title[]
     */
    private function getTitle($type, $index = null)
    {
        // We want only titles of the given type
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq("type", $type))
            ->orderBy(["id" => Criteria::ASC])
            ->setFirstResult(0);

        $titles = $this->titles->matching($criteria);

        if ($index !== null) {
            if (! isset($titles[$index])) {
                throw new InvalidArgumentException('Invalid index: ' . $index);
            }
            return $titles[$index];
        }

        return $titles->toArray();
    }

    /**
     * Returns the collection of title instances of the type main related to this document,
     * or just the title with the specified index.
     *
     * @param int|null $index
     * @return Title|Title[]
     */
    public function getTitleMain($index = null)
    {
        return $this->getTitle(Title::TYPE_MAIN, $index);
    }

    /**
     * Returns the collection of title instances  of the type parent related to this document,
     * or just the title with the specified index.
     *
     * @param int|null $index
     * @return Title|Title[]
     */
    public function getTitleParent($index = null)
    {
        return $this->getTitle(Title::TYPE_PARENT, $index);
    }

    /**
     * Returns the collection of title instances  of the type sub related to this document,
     * or just the title with the specified index
     *
     * @param int|null $index
     * @return Title|Title[]
     */
    public function getTitleSub($index = null)
    {
        return $this->getTitle(Title::TYPE_SUB, $index);
    }

    /**
     * Returns the collection of title instances  of the type additional related to this document,
     * or just the title with the specified index.
     *
     * @param int|null $index
     * @return Title[]
     */
    public function getTitleAdditional($index = null)
    {
        return $this->getTitle(Title::TYPE_ADDITIONAL, $index);
    }

    /**
     * Returns the collection of title instances  of the type abstract related to this document,
     * or just the title with the specified index.
     *
     * @param int|null $index
     * @return Title|Title[]
     */
    public function getTitleAbstract($index = null)
    {
        return $this->getTitle(Title::TYPE_ABSTRACT, $index);
    }

    /**
     * Generic method for adding titles used by the specific title type methods, e.g. addTitleMain()
     *
     * @param string $type The title type: 'main','parent','abstract','sub','additional'
     * @param Title|null
     * @return Title
     */
    private function addTitle($type, $title = null)
    {
        if ($title === null) {
            $title = new Title($type);
        }

        if (!$this->titles->contains($title)) {
            $this->titles->add($title);
            // In doctrine we have to set the associated document explicitly
            // TODO: Is there a better way where we do not need a setDocument method in the title class.
            $title->setDocument($this);
        }

        return $title;
    }

    /**
     * Adds a title of the type main
     *
     * @param Title|null $title
     * @return Title
     */
    public function addTitleMain($title = null)
    {
        return $this->addTitle(Title::TYPE_MAIN, $title);
    }

    /**
     * Adds a title of the type main
     *
     * @param Title|null $title
     * @return Title
     */
    public function addTitleParent($title = null)
    {
        return $this->addTitle(Title::TYPE_PARENT, $title);
    }

    /**
     * Adds a title of the type sub
     *
     * @param Title|null $title
     * @return Title
     */
    public function addTitleSub($title = null)
    {
        return $this->addTitle(Title::TYPE_SUB, $title);
    }

    /**
     * Adds a title of the type additional
     *
     * @param Title|null $title
     * @return Title
     */
    public function addTitleAdditional($title = null)
    {
        return $this->addTitle(Title::TYPE_ADDITIONAL, $title);
    }

    /**
     * Adds a title of the type abstract
     *
     * @param Title|null $title
     * @return Title
     */
    public function addTitleAbstract($title = null)
    {
        return $this->addTitle(Title::TYPE_ABSTRACT, $title);
    }

    /**
     * Generic method for setting titles used by the specific title type methods, e.g. setTitleMain()
     *
     * @param string        $type The title type: 'main','parent','abstract','sub','additional'
     * @param Title|Title[] $title
     */
    protected function setTitle($type, $title)
    {
        $titles = $this->getTitle($type);

        foreach ($titles as $element) {
            $this->titles->removeElement($element);
            $element->setDocument(null);
        }

        if ($title instanceof Title) {
            $this->addTitle($type, $title);
        } elseif (is_array($title)) {
            foreach ($title as $newTitle) {
                $this->addTitle($type, $newTitle);
            }
        }
    }

    /**
     * Sets title/titles of the type main
     *
     * @param Title|Title[] $title
     */
    public function setTitleMain($title)
    {
        $this->setTitle(Title::TYPE_MAIN, $title);
    }

    /**
     * Sets title/titles of the type parent
     *
     * @param Title|Title[] $title
     */
    public function setTitleParent($title)
    {
        $this->setTitle(Title::TYPE_MAIN, $title);
    }

    /**
     * Sets title/titles of the type abstract
     *
     * @param Title|Title[] $title
     */
    public function setTitleAbstract($title)
    {
        $this->setTitle(Title::TYPE_MAIN, $title);
    }

    /**
     * Sets title/titles of the type sub
     *
     * @param Title|Title[] $title
     */
    public function setTitleSub($title)
    {
        $this->setTitle(Title::TYPE_MAIN, $title);
    }

    /**
     * Sets title/titles of the type additional
     *
     * @param Title|Title[] $title
     */
    public function setTitleAdditional($title)
    {
        $this->setTitle(Title::TYPE_MAIN, $title);
    }

    /**
     * Generic method for setting persons used by the role specific person methods, e.g. setPersonAuthor()
     *
     * @param ArrayCollection|Collection|DocumentPerson[] $persons
     * @param string|null                                 $role
     */
    protected function setDocumentPerson($persons, $role = null)
    {
        $documentPersons = $this->getPerson($role);

        foreach ($documentPersons as $element) {
            $this->documentPersons->removeElement($element);
            $element->setDocument(null);
        }

        foreach ($persons as $newPerson) {
            $this->addTitle($newPerson, $role);
        }
    }

    /**
     * Generic method for getting persons|document-persons used by role specific person methods, e.g. getPersonAuthor()
     * Returns the collection of DocumentPersons related to this document, or just the DocumentPerson of the
     * specified index.
     *
     * @param string|null $role
     * @param int|null    $index
     * @return ArrayCollection|Collection|DocumentPerson[]|DocumentPerson
     */
    protected function getDocumentPerson($role = null, $index = null)
    {
        $criteria = Criteria::create()
            ->orderBy(["sortOrder" => Criteria::ASC])
            ->setFirstResult(0);

        if ($role !== null) {
            // We want only persons of the given role
            $criteria->where(Criteria::expr()->eq("role", $role));
        }

        $documentPersons = $this->titles->matching($criteria);

        if ($index !== null) {
            if (! isset($documentPersons[$index])) {
                throw new InvalidArgumentException('Invalid index: ' . $index);
            }
            return $documentPersons[$index];
        }

        return $documentPersons->toArray();
    }

    /**
     * Generic method for adding persons used by role specific person methods, e.g. addPersonAuthor()
     *
     * @param Person      $person
     * @param string|null $role
     * @return DocumentPerson
     */
    protected function addDocumentPerson($person, $role = null)
    {
        $documentPerson = DocumentPerson::new();
        $documentPerson->setDocument($this);
        $documentPerson->setPerson($person);
        if (isset($role)) {
            $documentPerson->setRole($role);
        }
        $this->documentPersons->add($documentPerson);

        return $documentPerson;
    }

    /**
     * Sets the persons of the document
     *
     * @param ArrayCollection|Collection|DocumentPerson[] $persons
     */
    public function setPerson($persons)
    {
        $this->setDocumentPerson($persons);
    }

    /**
     * Gets all persons related to this document,
     * or just the person of the specified index.
     *
     * @param int|null $index
     * @return ArrayCollection|Collection|DocumentPerson[]|DocumentPerson
     */
    public function getPerson($index = null)
    {
        return $this->getDocumentPerson(null, $index);
    }

    /**
     * Adds a person to the document
     *
     * @param Person $person
     * @return DocumentPerson
     */
    public function addPerson($person)
    {
        return $this->addDocumentPerson($person);
    }

    /**
     * Sets all persons of the role 'author'
     *
     * @param ArrayCollection|Collection|DocumentPerson[] $persons
     */
    public function setPersonAuthor($persons)
    {
        $this->setDocumentPerson($persons, DocumentPerson::ROLE_AUTHOR);
    }

    /**
     * Gets all persons of the role 'author'
     * or just the person of the specified index.
     *
     * @param int|null $index
     * @return ArrayCollection|Collection|DocumentPerson[]|DocumentPerson
     */
    public function getPersonAuthor($index = null)
    {
        return $this->getDocumentPerson(DocumentPerson::ROLE_AUTHOR, $index);
    }

    /**
     * Adds a person of the role 'author'
     *
     * @param Person $person
     * @return DocumentPerson
     */
    public function addPersonAuthor($person)
    {
        return $this->addDocumentPerson($person, DocumentPerson::ROLE_AUTHOR);
    }

    /**
     * Sets all persons of the role 'advisor'
     *
     * @param ArrayCollection|Collection|DocumentPerson[] $persons
     */
    public function setPersonAdvisor($persons)
    {
        $this->setDocumentPerson($persons, DocumentPerson::ROLE_ADVISOR);
    }

    /**
     * Gets all persons of the role 'advisor'
     * or just the person of the specified index.
     *
     * @param int|null $index
     * @return ArrayCollection|Collection|DocumentPerson[]|DocumentPerson
     */
    public function getPersonAdvisor($index = null)
    {
        return $this->getDocumentPerson(DocumentPerson::ROLE_ADVISOR, $index);
    }

    /**
     * Adds a person of the role 'advisor'
     *
     * @param Person $person
     * @return DocumentPerson
     */
    public function addPersonAdvisor($person)
    {
        return $this->addDocumentPerson($person, DocumentPerson::ROLE_ADVISOR);
    }

    /**
     * Sets all persons of the role 'contributor'
     *
     * @param ArrayCollection|Collection|DocumentPerson[] $persons
     */
    public function setPersonContributor($persons)
    {
        $this->setDocumentPerson($persons, DocumentPerson::ROLE_CONTRIBUTOR);
    }

    /**
     * Gets all persons of the role 'contributor'
     * or just the person of the specified index.
     *
     * @param int|null $index
     * @return ArrayCollection|Collection|DocumentPerson[]|DocumentPerson
     */
    public function getPersonContributor($index = null)
    {
        return $this->getDocumentPerson(DocumentPerson::ROLE_CONTRIBUTOR, $index);
    }

    /**
     * Adds a person of the role 'contributor'
     *
     * @param Person $person
     * @return DocumentPerson
     */
    public function addPersonContributor($person)
    {
        return $this->addDocumentPerson($person, DocumentPerson::ROLE_CONTRIBUTOR);
    }

    /**
     * Sets all persons of the role 'editor'
     *
     * @param ArrayCollection|Collection|DocumentPerson[] $persons
     */
    public function setPersonEditor($persons)
    {
        $this->setDocumentPerson($persons, DocumentPerson::ROLE_EDITOR);
    }

    /**
     * Gets all persons of the role 'editor'
     * or just the person of the specified index.
     *
     * @param int|null $index
     * @return ArrayCollection|Collection|DocumentPerson[]|DocumentPerson
     */
    public function getPersonEditor($index = null)
    {
        return $this->getDocumentPerson(DocumentPerson::ROLE_EDITOR, $index);
    }

    /**
     * Adds a person of the role 'editor'
     *
     * @param Person $person
     * @return DocumentPerson
     */
    public function addPersonEditor($person)
    {
        return $this->addDocumentPerson($person, DocumentPerson::ROLE_EDITOR);
    }

    /**
     * Sets all persons of the role 'referee'
     *
     * @param ArrayCollection|Collection|DocumentPerson[] $persons
     */
    public function setPersonReferee($persons)
    {
        $this->setDocumentPerson($persons, DocumentPerson::ROLE_REFEREE);
    }

    /**
     * Gets all persons of the role 'referee'
     * or just the person of the specified index.
     *
     * @param int|null $index
     * @return ArrayCollection|Collection|DocumentPerson[]|DocumentPerson
     */
    public function getPersonReferee($index = null)
    {
        return $this->getDocumentPerson(DocumentPerson::ROLE_REFEREE, $index);
    }

    /**
     * Adds a person of the role 'referee'
     *
     * @param Person $person
     * @return DocumentPerson
     */
    public function addPersonReferee($person)
    {
        return $this->addDocumentPerson($person, DocumentPerson::ROLE_REFEREE);
    }

    /**
     * Sets all persons of the role 'other'
     *
     * @param ArrayCollection|Collection|DocumentPerson[] $persons
     */
    public function setPersonOther($persons)
    {
        $this->setDocumentPerson($persons, DocumentPerson::ROLE_OTHER);
    }

    /**
     * Gets all persons of the role 'other'
     * or just the person of the specified index.
     *
     * @param int|null $index
     * @return ArrayCollection|Collection|DocumentPerson[]|DocumentPerson
     */
    public function getPersonOther($index = null)
    {
        return $this->getDocumentPerson(DocumentPerson::ROLE_OTHER, $index);
    }

    /**
     * Adds a person of the role 'other'
     *
     * @param Person $person
     * @return DocumentPerson
     */
    public function addPersonOther($person)
    {
        return $this->addDocumentPerson($person, DocumentPerson::ROLE_OTHER);
    }


    /**
     * Sets all persons of the role 'translator'
     *
     * @param ArrayCollection|Collection|DocumentPerson[] $persons
     */
    public function setPersonTranslator($persons)
    {
        $this->setDocumentPerson($persons, DocumentPerson::ROLE_TRANSLATOR);
    }

    /**
     * Gets all persons of the role 'translator'
     * or just the person of the specified index.
     *
     * @param int|null $index
     * @return ArrayCollection|Collection|DocumentPerson[]|DocumentPerson
     */
    public function getPersonTranslator($index = null)
    {
        return $this->getDocumentPerson(DocumentPerson::ROLE_TRANSLATOR, $index);
    }

    /**
     * Adds a person of the role 'translator'
     *
     * @param Person $person
     * @return DocumentPerson
     */
    public function addPersonTranslator($person)
    {
        return $this->addDocumentPerson($person, DocumentPerson::ROLE_TRANSLATOR);
    }

    /**
     * Sets all persons of the role 'submitter'
     *
     * @param ArrayCollection|Collection|DocumentPerson[] $persons
     */
    public function setPersonSubmitter($persons)
    {
        $this->setDocumentPerson($persons, DocumentPerson::ROLE_SUBMITTER);
    }

    /**
     * Gets all persons of the role 'submitter'
     * or just the person of the specified index.
     *
     * @param int|null $index
     * @return ArrayCollection|Collection|DocumentPerson[]|DocumentPerson
     */
    public function getPersonSubmitter($index = null)
    {
        return $this->getDocumentPerson(DocumentPerson::ROLE_SUBMITTER, $index);
    }

    /**
     * Adds a person of the role 'submitter'
     *
     * @param Person $person
     * @return DocumentPerson
     */
    public function addPersonSubmitter($person)
    {
        return $this->addDocumentPerson($person, DocumentPerson::ROLE_SUBMITTER);
    }
}
