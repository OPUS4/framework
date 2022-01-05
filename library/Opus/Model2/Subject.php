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
 */

namespace Opus\Model2;

use Doctrine\ORM\Mapping as ORM;

/**
 * Domain model for document subjects in the Opus framework
 *
 * @uses  \Opus\Model2\AbstractModel
 *
 * @ORM\Entity(repositoryClass="Opus\Db2\SubjectRepository")
 * @ORM\Table(name="document_subjects")
 */
class Subject extends AbstractModel
{
    const SWD = 'swd';

    const PSYNDEX = 'psyndex';

    const UNCONTROLLED = 'uncontrolled';

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     *
     * @var int
     */
    private $id;

    // TODO: Mapping as one-to-many relation with a document
    // The @var type should then be changed to Model2\Document instead of int
    // TODO: A index key "fk_document_subjects_document" is needed.
    /**
     * @ORM\Column(name="document_id", type="integer")
     *
     * @var int
     */
    private $document;

    /**
     * @ORM\Column(name="language", type="string", length=3, nullable=true, options={"fixed" = true})
     *
     * @var string
     */
    private $language;

    /**
     * @ORM\Column(name="type", type="string", length=30, nullable=true)
     *
     * @var string
     */
    private $type;

    /**
     * @ORM\Column(name="value", type="string", length=255)
     *
     * @var string
     */
    private $value;

    /**
     * @ORM\Column(name="external_key", type="string", length=255, nullable=true)
     *
     * @var string
     */
    private $externalKey;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * @param int $document
     */
    public function setDocument($document)
    {
        $this->document = $document;
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param string $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getExternalKey()
    {
        return $this->externalKey;
    }

    /**
     * @param string $externalKey
     */
    public function setExternalKey($externalKey)
    {
        $this->externalKey = $externalKey;
    }

    /**
     * Returns the relevant properties of the class
     *
     * @return array
     */
    protected static function describe()
    {
        return [
            'Language',
            'Type',
            'Value',
            'ExternalKey',
        ];
    }

    /**
     * Return matching keywords for use in autocomplete function.
     *
     * @param string $term String that must be included in keyword
     * @param string $type Type of keywords
     * @param int    $limit Maximum number of returned results
     * @return array
     */
    public static function getMatchingSubjects($term, $type = 'swd', $limit = 20)
    {
        return self::getRepository()->getMatchingSubjects($term, $type, $limit);
    }
}
