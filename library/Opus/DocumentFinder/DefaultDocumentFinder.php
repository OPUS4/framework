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
        $this->select->select("d.id");

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
        // Hotfix: If $subset is empty, return empty set.
        if (! is_array($documentIds) || count($documentIds) < 1) {
            $this->select->andWhere('1 = 0');
            return $this;
        }

        $this->select->andWhere('d.id IN (:setDocumentIds_documentIds)')
            ->setParameter('setDocumentIds_documentIds', $documentIds, Connection::PARAM_STR_ARRAY);

        return $this;
    }

    /**
     * @param int|null $start
     * @param int|null $end
     * @return $this
     */
    public function setDocumentIdRange($start = null, $end = null)
    {
        if ($start !== null) {
            $this->select->andWhere('d.id >= :setDocumentIdRange_start')
                ->setParameter('setDocumentIdRange_start', $start);
        }

        if ($end !== null) {
            $this->select->andWhere('d.id <= :setDocumentIdRange_end')
                ->setParameter('setDocumentIdRange_end', $end);
        }
        return $this;
    }

    /**
     * @param  string|string[] $serverState
     * @return $this
     */
    public function setServerState($serverState)
    {
        if (is_array($serverState)) {
            $this->select->andWhere('server_state IN (:setServerState_serverStates)', $serverState)
                ->setParameter('setServerState_serverStates', $serverState, Connection::PARAM_STR_ARRAY);
        } else {
            $this->select->andWhere('server_state = :setServerState_serverState')
                ->setParameter('setServerState_serverState', $serverState);
        }

        return $this;
    }

    /**
     * @param bool $partOfBibliography
     * @return $this
     */
    public function setBelongsToBibliography($partOfBibliography = true)
    {
        $this->select->andWhere('d.belongs_to_bibliography = :setBelongsToBibliography_partOfBibliography')
            ->setParameter('setBelongsToBibliography_partOfBibliography', $partOfBibliography);

        return $this;
    }

    /**
     * @param int $collectionId
     * @return $this
     */
    public function setCollectionId($collectionId)
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $subSelect = $queryBuilder->select('l.document_id')
            ->from('link_documents_collections', 'l')
            ->where('l.document_id = d.id')
            ->andWhere('l.collection_id IN (:setCollectionId_collectionId)');

        $this->select->andWhere("EXISTS (" . $subSelect->getSQL() . ")");

        if (is_array($collectionId)) {
            $this->select->setParameter(
                'setCollectionId_collectionId',
                $collectionId,
                Connection::PARAM_STR_ARRAY
            );
        } else {
            $this->select->setParameter('setCollectionId_collectionId', $collectionId);
        }

        return $this;
    }

    /**
     * @param int $roleId
     * @return $this
     */
    public function setCollectionRoleId($roleId)
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $subSelect = $queryBuilder->select('l.document_id')
            ->from('collections', 'c')
            ->from('link_documents_collections', 'l')
            ->where('l.document_id = d.id')
            ->andWhere('l.collection_id = c.id')
            ->andWhere('c.role_id = :setCollectionRoleId_roleId');

        $this->select->andWhere("EXISTS (" . $subSelect->getSQL() . ")")
            ->setParameter('setCollectionRoleId_roleId', $roleId);

        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setIdentifierExists($name)
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $subSelect = $queryBuilder->select('i.id')
            ->from('document_identifiers', 'i')
            ->where('i.document_id = d.id')
            ->andWhere('type = :setIdentifierExists_name');

        $this->select->andWhere("EXISTS (" . $subSelect->getSQL() . ")")
            ->setParameter('setIdentifierExists_name', $name);

        return $this;
    }

    /**
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function setIdentifierValue($name, $value)
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $subSelect = $queryBuilder->select('d.id')
            ->from('document_identifiers', 'i')
            ->where('i.document_id = d.id')
            ->andWhere('type = :setIdentifierValue_name')
            ->andWhere('value = :setIdentifierValue_value');

        $this->select->andWhere("EXISTS (" . $subSelect->getSQL() . ")")
            ->setParameter('setIdentifierValue_name', $name)
            ->setParameter('setIdentifierValue_value', $value);

        return $this;
    }

    /**
     * @param string|string[] $type
     * @return $this
     */
    public function setDocumentType($type)
    {
        if (is_array($type)) {
            $this->select->andWhere('type IN (:setDocumentType_types)')
                ->setParameter('setDocumentType_types', $type, Connection::PARAM_STR_ARRAY);
        } else {
            $this->select->andWhere('type = :setDocumentType_type')
                ->setParameter('setDocumentType_type', $type);
        }
        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setEnrichmentExists($name)
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $subSelect = $queryBuilder->select('d.id')
            ->from('document_enrichments', e)
            ->where('document_id = d.id')
            ->andWhere('key_name = :setEnrichmentExists_name');

        $this->select->andWhere('EXISTS (' . $subSelect->getSQL() . ')')
            ->setParameter('setEnrichmentExists_name', $name);

        return $this;
    }

    /**
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function setEnrichmentValue($key, $value)
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $subSelect = $queryBuilder->select('d.id')
            ->from('document_enrichments', 'e')
            ->where('document_id = d.id')
            ->andWhere('key_name = :setEnrichmentValue_key')
            ->andWhere('value = :setEnrichmentValue_value');

        $this->select->andWhere("EXISTS (" . $subSelect->getSQL() . ")")
            ->setParameter('setEnrichmentValue_key', $key)
            ->setParameter('setEnrichmentValue_value', $value);

        return $this;
    }

    /**
     * @param string $date
     * @return $this
     */
    public function setServerDatePublishedBefore($date)
    {
        $this->finder->setServerDatePublishedBefore($date);

        $this->select->andWhere('d.server_date_published < :setServerDatePublishedBefore_date')
            ->setParameter('setServerDatePublishedBefore_date', $date);

        return $this;
    }

    /**
     * @param string $from
     * @param string $until
     * @return $this
     */
    public function setServerDatePublishedRange($from, $until)
    {
        $this->select->andWhere('d.server_date_published >= :setServerDatePublishedRange_from')
            ->andWhere('d.server_date_published < :setServerDatePublishedRange_until')
            ->setParameter('setServerDatePublishedRange_from', $from)
            ->setParameter('setServerDatePublishedRange_until', $until);

        return $this;
    }

    /**
     * @param string $date
     * @return $this
     */
    public function setServerDateModifiedBefore($date)
    {
        $this->select->andWhere('d.server_date_modified < :setServerDateModifiedBefore_date')
            ->setParameter('setServerDateModifiedBefore_date', $date);
        return $this;
    }

    /**
     * @param string $date
     * @return $this
     */
    public function setServerDateModifiedAfter($date)
    {
        $this->select->andWhere('d.server_date_modified >= :setServerDateModifiedAfter_date')
            ->setParameter('setServerDateModifiedAfter_data', $date);

        return $this;
    }

    /**
     * @param string $date
     * @return $this
     */
    public function setEmbargoDateBefore($date)
    {
        $this->select->andWhere('d.embargo_date < :setEmbargoDateBefore_date')
            ->setParameter('setEmbargoDateBefore_date', $date);

        return $this;
    }

    /**
     * @param string $date
     * @return $this
     */
    public function setNotEmbargoedOn($date)
    {
        $this->select->andWhere('d.embargo_date < :setNotEmbargoedOn_date or d.embargo_date IS NULL')
            ->setParameter('setNotEmbargoedOn_date', $date);

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
}
