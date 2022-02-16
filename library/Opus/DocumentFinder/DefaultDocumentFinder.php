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

use Doctrine\DBAL\Connection;
use Opus\Db2\Database;
use Opus\DocumentFinderInterface;

use function array_unique;
use function count;
use function is_array;

/**
 * Implementing DocumentFinderInterface using Doctrine DBAL.
 *
 * Mit diesem Zwischenschritt kann die Application von der DocumentFinder Klasse abgekoppelt werden ohne sofort einen
 * neuen DocumentFinder entwickeln zu müssen.
 *
 * TODO all the Date functions are still work in progress (requirements and design not clear yet)
 */
class DefaultDocumentFinder implements DocumentFinderInterface
{
    /**
     * @var int
     */
    private $namedParameterCounter = 0;

    /** @var Connection  */
    private $connection;

    /** @var Doctrine\DBAL\Query\QueryBuilder */
    private $select;

    public function __construct()
    {
        $this->connection = Database::getConnection();
        $queryBuilder     = $this->connection->createQueryBuilder();
        $this->select     = $queryBuilder->select('*')->from('documents', 'd');
    }

    /**
     * @return int[]
     */
    public function getIds()
    {
        $this->select->select('d.id');
        $result = $this->select->execute()->fetchFirstColumn();

        return array_unique($result);
    }

    /**
     * @return int
     */
    public function getCount()
    {
        $this->select->select("count(d.id)")->distinct();
        return $this->select->execute()->fetchOne();
    }

    /**
     * @param string $criteria Sort criteria
     * @param string $ascending Sort direction
     * @return $this
     */
    public function setOrder($criteria, $ascending = true)
    {
        $order = $ascending ? 'ASC' : 'DESC';

        switch ($criteria) {
            case self::ORDER_ID:
                // Order by id
                $this->select->orderBy('d.id', $order);
                break;
            case self::ORDER_AUTHOR:
                // Order by author lastname
                $this->select
                    ->leftJoin(
                        'd',
                        'link_persons_documents',
                        'pd',
                        'd.id = pd.document_id AND pd.role = "author"'
                    )
                    ->leftJoin('pd', 'persons', 'p', 'pd.person_id = p.id')
                    ->groupBy('d.id')
                    ->addGroupBy('p.last_name')
                    ->orderBy('p.last_name ', $order);
                break;
            case self::ORDER_TITLE:
                // Order by title main
                $this->select
                    ->leftJoin(
                        'd',
                        'document_title_abstracts',
                        't',
                        't.document_id = d.id AND t.type = "main"'
                    )
                    ->groupBy('d.id')
                    ->addGroupBy('t.value')
                    ->orderBy('t.value', $order);
                break;
            case self::ORDER_DOCUMENT_TYPE:
                // Order by type
                $this->select->orderBy('d.type ', $order);
                break;
            case self::ORDER_SERVER_DATE_PUBLISHED:
                // Order by server date published
                $this->select->orderBy('d.server_date_published', $order);
                break;
            default:
                break;
        }
        return $this;
    }

    /**
     * @param int[] $documentIds
     * @return $this
     */
    public function setDocumentIds($documentIds)
    {
        $queryParam = $this->createQueryParameterName('setDocumentIdsParam');

        // Hotfix: If $subset is empty, return empty set.
        if (! is_array($documentIds) || count($documentIds) < 1) {
            $this->select->andWhere('1 = 0');
            return $this;
        }

        $this->select->andWhere('d.id IN (:' . $queryParam . ')')
            ->setParameter($queryParam, $documentIds, Connection::PARAM_STR_ARRAY);

        return $this;
    }

    /**
     * @param int|null $start
     * @param int|null $end
     * @return $this
     */
    public function setDocumentIdRange($start = null, $end = null)
    {
        $queryParamStart = $this->createQueryParameterName('setDocumentIdRangeStart');
        $queryParamEnd = $this->createQueryParameterName('setDocumentIdRangeEnd');

        if ($start !== null) {
            $this->select->andWhere('d.id >= :' . $queryParamStart)
                ->setParameter($queryParamStart, $start);
        }

        if ($end !== null) {
            $this->select->andWhere('d.id <= :' . $queryParamEnd)
                ->setParameter($queryParamEnd, $end);
        }
        return $this;
    }

    /**
     * @param  string|string[] $serverState
     * @return $this
     */
    public function setServerState($serverState)
    {
        $queryParam = $this->createQueryParameterName('setServerStateParam');

        if (is_array($serverState)) {
            $this->select->andWhere('server_state IN (:' . $queryParam . ')', $serverState)
                ->setParameter($queryParam, $serverState, Connection::PARAM_STR_ARRAY);
        } else {
            $this->select->andWhere('server_state = :' . $queryParam)
                ->setParameter($queryParam, $serverState);
        }

        return $this;
    }

    /**
     * @param bool $partOfBibliography
     * @return $this
     */
    public function setBelongsToBibliography($partOfBibliography = true)
    {
        $queryParam = $this->createQueryParameterName('setBelongsToBibliographyParam');

        $this->select->andWhere('d.belongs_to_bibliography = :' . $queryParam)
            ->setParameter($queryParam, $partOfBibliography);

        return $this;
    }

    /**
     * @param int $collectionId
     * @return $this
     */
    public function setCollectionId($collectionId)
    {
        $queryParam = $this->createQueryParameterName('setCollectionIdParam');

        $queryBuilder = $this->connection->createQueryBuilder();

        $subSelect = $queryBuilder->select('l.document_id')
            ->from('link_documents_collections', 'l')
            ->where('l.document_id = d.id')
            ->andWhere('l.collection_id IN (:' . $queryParam . ')');

        $this->select->andWhere("EXISTS (" . $subSelect->getSQL() . ")");

        if (is_array($collectionId)) {
            $this->select->setParameter($queryParam, $collectionId, Connection::PARAM_STR_ARRAY);
        } else {
            $this->select->setParameter($queryParam, $collectionId);
        }

        return $this;
    }

    /**
     * @param int $roleId
     * @return $this
     */
    public function setCollectionRoleId($roleId)
    {
        $queryParam = $this->createQueryParameterName('setCollectionRoleIdParam');

        $queryBuilder = $this->connection->createQueryBuilder();

        $subSelect = $queryBuilder->select('l.document_id')
            ->from('collections', 'c')
            ->from('link_documents_collections', 'l')
            ->where('l.document_id = d.id')
            ->andWhere('l.collection_id = c.id')
            ->andWhere('c.role_id = :' . $queryParam);

        $this->select->andWhere("EXISTS (" . $subSelect->getSQL() . ")")
            ->setParameter($queryParam, $roleId);

        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setIdentifierExists($name)
    {
        $queryParam = $this->createQueryParameterName('setIdentifierExistsParam');

        $queryBuilder = $this->connection->createQueryBuilder();

        $subSelect = $queryBuilder->select('i.id')
            ->from('document_identifiers', 'i')
            ->where('i.document_id = d.id')
            ->andWhere('type = :' . $queryParam);

        $this->select->andWhere("EXISTS (" . $subSelect->getSQL() . ")")
            ->setParameter($queryParam, $name);

        return $this;
    }

    /**
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function setIdentifierValue($name, $value)
    {
        $queryParamName = $this->createQueryParameterName('setIdentifierValueName');
        $queryParamValue = $this->createQueryParameterName('setIdentifierValue');

        $queryBuilder = $this->connection->createQueryBuilder();

        $subSelect = $queryBuilder->select('id')
            ->from('document_identifiers', 'i')
            ->where('i.document_id = d.id')
            ->andWhere('type = :' . $queryParamName)
            ->andWhere('value = :' . $queryParamValue);

        $this->select->andWhere("EXISTS (" . $subSelect->getSQL() . ")")
            ->setParameter($queryParamName, $name)
            ->setParameter($queryParamValue, $value);

        return $this;
    }

    /**
     * @param string|string[] $type
     * @return $this
     */
    public function setDocumentType($type)
    {
        $queryParam = $this->createQueryParameterName('setDocumentTypeParam');

        if (is_array($type)) {
            $this->select->andWhere('type IN (:' . $queryParam . ')')
                ->setParameter($queryParam, $type, Connection::PARAM_STR_ARRAY);
        } else {
            $this->select->andWhere('type = :' . $queryParam)
                ->setParameter($queryParam, $type);
        }
        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setEnrichmentExists($name)
    {
        $queryParam = $this->createQueryParameterName('setEnrichmentExistsParam');

        $queryBuilder = $this->connection->createQueryBuilder();

        $subSelect = $queryBuilder->select('d.id')
            ->from('document_enrichments', e)
            ->where('document_id = d.id')
            ->andWhere('key_name = :' . $queryParam);

        $this->select->andWhere('EXISTS (' . $subSelect->getSQL() . ')')
            ->setParameter($queryParam, $name);

        return $this;
    }

    /**
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function setEnrichmentValue($key, $value)
    {
        $queryParamKey = $this->createQueryParameterName('setEnrichmentValueKey');
        $queryParamValue = $this->createQueryParameterName('setEnrichmentValue');

        $queryBuilder = $this->connection->createQueryBuilder();

        $subSelect = $queryBuilder->select('d.id')
            ->from('document_enrichments', 'e')
            ->where('document_id = d.id')
            ->andWhere('key_name = :' . $queryParamKey)
            ->andWhere('value = :' . $queryParamValue);

        $this->select->andWhere("EXISTS (" . $subSelect->getSQL() . ")")
            ->setParameter($queryParamKey, $key)
            ->setParameter($queryParamValue, $value);

        return $this;
    }

    /**
     * @param string $date
     * @return $this
     */
    public function setServerDatePublishedBefore($date)
    {
        $queryParam = $this->createQueryParameterName('setServerDatePublishedBeforeParam');

        $this->finder->setServerDatePublishedBefore($date);

        $this->select->andWhere('d.server_date_published < :' . $queryParam)
            ->setParameter($queryParam, $date);

        return $this;
    }

    /**
     * @param string $from
     * @param string $until
     * @return $this
     */
    public function setServerDatePublishedRange($from, $until)
    {
        $queryParamFrom = $this->createQueryParameterName('setServerDatePublishedRangeFrom');
        $queryParamUntil = $this->createQueryParameterName('setServerDatePublishedRangeUntil');

        $this->select->andWhere('d.server_date_published >= :' . $queryParamFrom)
            ->andWhere('d.server_date_published < :' . $queryParamUntil)
            ->setParameter($queryParamFrom, $from)
            ->setParameter($queryParamUntil, $until);

        return $this;
    }

    /**
     * @param string $date
     * @return $this
     */
    public function setServerDateModifiedBefore($date)
    {
        $queryParam = $this->createQueryParameterName('setServerDateModifiedBeforeDate');

        $this->select->andWhere('d.server_date_modified < :' . $queryParam)
            ->setParameter($queryParam, $date);
        return $this;
    }

    /**
     * @param string $date
     * @return $this
     */
    public function setServerDateModifiedAfter($date)
    {
        $queryParam = $this->createQueryParameterName('setServerDateModifiedAfterDate');

        $this->select->andWhere('d.server_date_modified >= :' . $queryParam)
            ->setParameter($queryParam, $date);

        return $this;
    }

    /**
     * @param string $date
     * @return $this
     */
    public function setEmbargoDateBefore($date)
    {
        $queryParam = $this->createQueryParameterName('setEmbargoDateBeforeDate');

        $this->select->andWhere('d.embargo_date < :' . $queryParam)
            ->setParameter($queryParam, $date);

        return $this;
    }

    /**
     * @param string $date
     * @return $this
     */
    public function setNotEmbargoedOn($date)
    {
        $queryParam = $this->createQueryParameterName('setNotEmbargoedOnDate');

        $this->select->andWhere('d.embargo_date < :' . $queryParam . ' or d.embargo_date IS NULL')
            ->setParameter($queryParam, $date);

        return $this;
    }

    /**
     * @return $this
     */
    public function setNotModifiedAfterEmbargoDate()
    {
        $this->select->andWhere('d.server_date_modified < d.embargo_date');
        return $this;
    }

    /**
     * @return $this
     */
    public function setHasFilesVisibleInOai()
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $subSelect = $queryBuilder->select('f.document_id')
            ->from('document_files', 'f')
            ->where('f.document_id = d.id')
            ->andWhere('f.visible_in_oai=1');

        $this->select->andWhere('d.id IN (' . $subSelect->getSQL() . ')');

        return $this;
    }

    /**
     * @return $this
     */
    public function setNotInXmlCache()
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        // get all IDs in XML cache
        $subSelect = $queryBuilder->select('dxc.document_id')
            ->from('document_xml_cache', 'dxc');

        $this->select->andWhere(' NOT d.id IN (' . $subSelect->getSQL() . ')');

        return $this;
    }

    /**
     * @param bool $includeCount
     * @return array
     */
    public function getDocumentTypes($includeCount = false)
    {
        if ($includeCount) {
            $this->select->select('type AS type', 'count(DISTINCT id) AS count')
                ->groupBy('type');
            return $this->select->execute()->fetchAllAssociative();
        } else {
            $this->select->select("type")->distinct();
            return $this->select->execute()->fetchFirstColumn();
        }
    }

    /**
     * @return array
     */
    public function getYearsPublished()
    {
        $this->select->select("substr(server_date_published, 1, 4)")->distinct();
        return $this->select->execute()->fetchFirstColumn();
    }

    /**
     * Creates a unique named query parameter.
     *
     * @param string $prefix
     */
    protected function createQueryParameterName($prefix)
    {
        return $prefix . $this->namedParameterCounter++;
    }

}
