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
 * @copyright   Copyright (c) 2008-2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public Licens
 */

namespace Opus\Model2;

use Doctrine\ORM\Mapping as ORM;

use function array_search;
use function substr;

/**
 * Domain model for document identifiers in the Opus framework
 *
 * @uses \Opus\Model2\\AbstractModel
 *
 * TODO DOI and URN functions have been removed, we still have to find a new solution where to implement that functions
 *
 * TODO desing issues - see below
 * The OPUS 4 framework is mapping objects to database tables (ORM). All identifiers are stored in the same table. The
 * table was extended with fields relevant only to DOI identifiers. In a pure object model it would make more sense to
 * extend the basic Opus\Identifier class for specific identifier types to add fields and functionality. Those classes
 * would then have to be mapped to different table, however they could also still be mapped to the same table. At some
 * point this will have to be revisited. We need a consistent object model independent of how the data is stored in the
 * end.
 *
 * @ORM\Entity
 * @ORM\Table(name="document_identifiers",
 *     indexes={
 *         @ORM\Index(name="fk_document_identifiers_documents", columns={"document_id"}),
 *         @ORM\Index(name="fk_document_identifiers_documents_type", columns={"document_id", "type"})
 *     })
 */
class Identifier extends AbstractModel
{
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
    /**
     * @ORM\Column(name="document_id", type="integer")
     *
     * @var int
     */
    private $document;

    /**
     * @ORM\Column(name="value", type="text")
     *
     * @var string
     */
    private $value;

    /**
     * @ORM\Column(name="type", type="string")
     *
     * @var string
     */
    private $type;

    /**
     * @ORM\Column(name="status", type="string", columnDefinition="ENUM('registered','verified')", nullable=true)
     *
     * @var string
     */
    private $status;

    /**
     * @ORM\Column(name="registration_ts", type="string", nullable=true)
     *
     * @var string
     */
    private $registrationTs;

    /**
     * Mapping between identifier type and field name.
     *
     * @var array
     */
    private static $identifierMapping = [
        'Old'       => 'old',
        'Serial'    => 'serial',
        'Uuid'      => 'uuid',
        'Isbn'      => 'isbn',
        'Urn'       => 'urn',
        'Doi'       => 'doi',
        'Handle'    => 'handle',
        'Url'       => 'url',
        'Issn'      => 'issn',
        'StdDoi'    => 'std-doi',
        'CrisLink'  => 'cris-link',
        'SplashUrl' => 'splash-url',
        'Opus3'     => 'opus3-id',
        'Opac'      => 'opac-id',
        'Arxiv'     => 'arxiv',
        'Pubmed'    => 'pmid',
    ];

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
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getRegistrationTs()
    {
        return $this->registrationTs;
    }

    /**
     * @param string $registrationTs
     */
    public function setRegistrationTs($registrationTs)
    {
        $this->registrationTs = $registrationTs;
    }

    /**
     * Returns the relevant properties of the class
     *
     * @return array
     */
    protected static function describe()
    {
        return [
            'Value',
            'Type',
            'Status',
            'RegistrationTs',
        ];
    }

    /**
     * @param string $fieldname
     * @return string
     */
    public static function getTypeForFieldname($fieldname)
    {
        return self::$identifierMapping[substr($fieldname, 10)];
    }

    /**
     * @param string $type
     * @return string
     */
    public static function getFieldnameForType($type)
    {
        return 'Identifier' . array_search($type, self::$identifierMapping);
    }

    /**
     * @return string
     */
    public function getModelType()
    {
        return 'identifier';
    }
}
