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
 * @copyright   Copyright (c) 2021, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\Model2;

use Doctrine\ORM\Mapping as ORM;

use function in_array;

/**
 * Model class for languages in OPUS 4.
 *
 * Documents, titles and other data have language attributes. The languages stored in the OPUS 4 database are a
 * reference for all the languages, that can be used within a OPUS 4 repository, with all there describing attributes
 * and codes. The language entities are not linked to other object using foreign keys. Other objects use string values
 * to specify the language used. So the stored languages are really just "configuration".
 *
 * TODO field Part2T is mandatory - enforcement in model? tests?
 * TODO field RefName is mandatory - enforcement in model? tests?
 *
 * TODO is there a source for all the language information, so it does not need to be managed within the OPUS 4
 *      database?
 * TODO since Language objects are not linked to other objects using foreign keys, the list of languages could also
 *      be managed as a configuration file.
 *
 * TODO disable caching ?
 * TODO define allowed types as constants ? use full names, so the meaning becomes clear
 * TODO define allowed scopes as constants ? use full names, so the meaning becomes clear
 *
 * @ORM\Entity(repositoryClass="Opus\Db2\LanguageRepository")
 * @ORM\Table(name="languages")
 */
class Language extends AbstractModel
{
    public const PROPERTY_COMMENT = 'Comment';
    public const PROPERTY_PART2B  = 'Part2B';
    public const PROPERTY_PART2T  = 'Part2T';
    public const PROPERTY_PART1   = 'Part1';
    public const PROPERTY_SCOPE   = 'Scope';
    public const PROPERTY_TYPE    = 'Type';
    public const PROPERTY_REFNAME = 'RefName';
    public const PROPERTY_ACTIVE  = 'Active';

    /**
     * Cache used languages to reduce database queries.
     *
     * This caching happens because it is assumed that the operation to determine the used languages is
     * expensive and it might be used multiple times during a request to OPUS 4. The current implementation
     * requires multiple database queries.
     *
     * @var null|array
     */
    private static $usedLanguages;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     *
     * TODO is it possible to force an ID, not a generated value, for instance for importing the master data?
     *
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="string", name="part2_b", length=3, options={"fixed" = true})
     *
     * @var string
     */
    private $part2B;

    /**
     * @ORM\Column(type="string", name="part2_t", length=3, options={"fixed" = true})
     *
     * @var string
     */
    private $part2T;

    /**
     * @ORM\Column(type="string", length=2, options={"fixed" = true})
     *
     * @var string
     */
    private $part1;

    /**
     * @ORM\Column(type="string", columnDefinition="ENUM('I','M','S')")
     *
     * @var string
     */
    private $scope;

    /**
     * @ORM\Column(type="string", columnDefinition="ENUM('A','C','E','H','L','S')")
     *
     * @var string
     */
    private $type;

    /**
     * @ORM\Column(type="string", name="ref_name", length=150)
     *
     * @var string
     */
    private $refName;

    /**
     * @ORM\Column(type="string", length=150)
     *
     * @var string
     */
    private $comment;

    /**
     * @ORM\Column(type="smallint")
     *
     * @var int
     */
    private $active = 0;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getPart2B()
    {
        return $this->part2B;
    }

    /**
     * @param string $part2B
     */
    public function setPart2B($part2B)
    {
        $this->part2B = $part2B;
    }

    /**
     * @return string
     */
    public function getPart2T()
    {
        return $this->part2T;
    }

    /**
     * @param string $part2T
     */
    public function setPart2T($part2T)
    {
        $this->part2T = $part2T;
    }

    /**
     * @return string
     */
    public function getPart1()
    {
        return $this->part1;
    }

    /**
     * @param string $part1
     */
    public function setPart1($part1)
    {
        $this->part1 = $part1;
    }

    /**
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * TODO throw IllegalArgumentException if invalid value?
     *
     * @param string $scope
     */
    public function setScope($scope)
    {
        $this->scope = $scope;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * TODO throw IllegalArgumentException if invalid value?
     *
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getRefName()
    {
        return $this->refName;
    }

    /**
     * @param string $refName
     */
    public function setRefName($refName)
    {
        $this->refName = $refName;
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
    }

    /**
     * @return int
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * @param int $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * Returns reference language name.
     *
     * @return string
     */
    public function getDisplayName()
    {
        return $this->getRefName();
    }

    /**
     * Retrieve all Opus\Language instances from the database.
     *
     * @return array Array of Opus\Language objects.
     */
    public static function getAll()
    {
        return self::getRepository()->getAll();
    }

    /**
     * Get all active languages.
     *
     * @return array Array of Opus\Language objects which are active.
     */
    public static function getAllActive()
    {
        return self::getRepository()->getAllActive();
    }

    /**
     * Get all active languages.
     *
     * @return array Array of Opus\Language objects which are active.
     */
    public static function getAllActiveTable()
    {
        return self::getRepository()->getAllActiveTable();
    }

    /**
     * Get properties of language object as array for a specific terminology code
     *
     * @param string $code ISO639-2 terminology code to retrieve properties for
     * @return self|null Language model or null if object not found in database
     */
    public static function getLanguageByPart2T($code)
    {
        return self::getRepository()->getLanguageByPart2T($code);
    }

    /**
     * Returns part2_t language code for locale (part1 code).
     *
     * @param string $locale
     * @return null|string
     */
    public static function getPart2tForPart1($locale)
    {
        return self::getRepository()->getPart2tForPart1($locale);
    }

    /**
     * Returns language code for internal language identifier.
     *
     * @param string      $language Internal language identifier (e.g. 'deu')
     * @param null|string $part string Field to use for language code
     * @return string Language code
     */
    public static function getLanguageCode($language, $part = null)
    {
        return self::getRepository()->getLanguageCode($language, $part);
    }

    /**
     * Checks if a language is being used in database.
     *
     * @return bool
     */
    public function isUsed()
    {
        $languages = $this->getUsedLanguages();
        return in_array($this->getPart2T(), $languages);
    }

    /**
     * Returns all languages used in database.
     *
     * @return mixed
     */
    public static function getUsedLanguages()
    {
        if (self::$usedLanguages === null) {
            self::$usedLanguages = self::getRepository()->getUsedLanguages();
        }

        return self::$usedLanguages;
    }

    /**
     * Removes cached values for used languages.
     *
     * TODO used for testing - alternative solution? Using Reflection?
     */
    public static function clearCache()
    {
        self::$usedLanguages = null;
    }

    /**
     * Returns the relevant properties of the class
     *
     * @return array
     */
    protected static function describe()
    {
        return [
            self::PROPERTY_COMMENT,
            self::PROPERTY_PART2B,
            self::PROPERTY_PART2T,
            self::PROPERTY_PART1,
            self::PROPERTY_SCOPE,
            self::PROPERTY_TYPE,
            self::PROPERTY_REFNAME,
            self::PROPERTY_ACTIVE,
        ];
    }
}
