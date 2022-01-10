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
 * @copyright   Copyright (c) 2022, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\DocumentFinder;

use Opus\DocumentFinder;
use Opus\DocumentFinderInterface;

use function is_array;

/**
 * Wrapper implementing DocumentFinderInterface around old DocumentFinder using Zend_Db.
 *
 * Mit diesem Zwischenschritt kann die Application von der DocumentFinder Klasse abgekoppelt werden ohne sofort einen
 * neuen DocumentFinder entwickeln zu müssen.
 *
 * TODO all the Date functions are still work in progress (requirements and design not clear yet)
 */
class DefaultDocumentFinder implements DocumentFinderInterface
{
    private $finder;

    public function __construct()
    {
        $this->finder = new DocumentFinder();
    }

    /**
     * @return int[]
     */
    public function getIds()
    {
        return $this->finder->ids();
    }

    /**
     * @return int
     */
    public function getCount()
    {
        return $this->finder->count();
    }

    /**
     * @param string $criteria Sort criteria
     * @param string $ascending Sort direction
     * @return $this
     *
     * TODO use constants for parameters
     */
    public function setOrder($criteria, $ascending = true)
    {
        switch ($criteria) {
            case 'Id':
                $this->finder->orderById($ascending);
                break;
            case 'Author':
                $this->finder->orderByAuthorLastname($ascending);
                break;
            case 'Title':
                $this->finder->orderByTitleMain($ascending);
                break;
            case 'Type':
                $this->finder->orderByType($ascending);
                break;
            case 'ServerDatePublished':
                $this->finder->orderByServerDatePublished($ascending);
                break;
            default:
                break;
        }
        return $this;
    }

    /**
     * @param string $serverState
     * @return $this
     */
    public function setServerState($serverState)
    {
        if (is_array($serverState)) {
            $this->finder->setServerStateInList($serverState);
        } else {
            $this->finder->setServerState($serverState);
        }
        return $this;
    }

    /**
     * @param int $roleId
     * @return $this
     */
    public function setCollectionRoleId($roleId)
    {
        $this->finder->setCollectionRoleId($roleId);
        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setIdentifierExists($name)
    {
        $this->finder->setIdentifierTypeExists($name);
        return $this;
    }

    /**
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function setIdentifierValue($name, $value)
    {
        $this->finder->setIdentifierTypeValue($name, $value);
        return $this;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setDocumentType($type)
    {
        $this->finder->setType($type);
        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setEnrichmentExists($name)
    {
        $this->finder->setEnrichmentKeyExists($name);
        return $this;
    }

    /**
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function setEnrichmentValue($key, $value)
    {
        $this->finder->setEnrichmentKeyValue($key, $value);
        return $this;
    }

    /**
     * @param string $date
     * @return $this
     */
    public function setServerDatePublishedBefore($date)
    {
        $this->finder->setServerDatePublishedBefore($date);
        return $this;
    }

    /**
     * @param string $from
     * @param string $until
     * @return $this
     */
    public function setServerDatePublishedRange($from, $until)
    {
        $this->finder->setServerDatePublishedRange($from, $until);
        return $this;
    }

    /**
     * @param string $date
     * @return $this
     */
    public function setServerDateModifiedBefore($date)
    {
        $this->finder->setServerDateModifiedBefore($date);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function findEmbargoDateBeforeNotModifiedAfter()
    {
        // TODO: Implement findEmbargoDateBeforeNotModifiedAfter() method.
    }

    /**
     * @param bool $includeCount
     * @return array
     */
    public function getDocumentTypes($includeCount = false)
    {
        if ($includeCount) {
            return $this->finder->groupedTypesPlusCount();
        } else {
            return $this->finder->groupedTypes();
        }
    }

    /**
     * @return array
     */
    public function getYearsPublished()
    {
        return $this->finder->groupedServerYearPublished();
    }
}
