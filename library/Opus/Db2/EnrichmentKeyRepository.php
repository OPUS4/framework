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

namespace Opus\Db2;

use Doctrine\ORM\EntityRepository;
use Opus\Model2\EnrichmentKey;

/**
 * Database specific class for Language functions.
 *
 * This class keeps the database (Doctrine) specific code out of the model class.
 */
class EnrichmentKeyRepository extends EntityRepository
{
    /**
     * Löscht alle Enrichments aus den Dokumenten, die den Namen des vorliegenden
     * EnrichmentKey verwenden.
     *
     * Achtung: diese Methode löscht *nicht* den EnrichmentKey aus der Tabelle
     * enrichmentkeys.
     *
     * @param string $name
     */
    public function deleteFromDocuments($name)
    {
        // TODO: We are mixing in a DBAL queryBuilder because the model for "documents_enrichment"
        // is a none ORM mapped model at the moment.

        // TODO: Perhaps manipulative operations on a table that do not belong
        // to this repository are not a good idea

        $conn         = $this->getEntityManager()->getConnection();
        $queryBuilder = $conn->createQueryBuilder();
        $select       = $queryBuilder
            ->delete('document_enrichments')
            ->where("key_name = :name")
            ->setParameter('name', $name);

        $select->execute();
    }

    /**
     * ALTERNATE CONSTRUCTOR: Retrieve Opus\Model2\EnrichmentKey instance by name.  Returns
     * null if name is null *or* nothing found.
     *
     * @param null|string $name
     * @return EnrichmentKey|null
     */
    public function fetchByName($name = null)
    {
        if (false === isset($name)) {
            return null;
        }

        $enrichmentKey = $this->findOneBy(['id' => $name]);

        if ($enrichmentKey instanceof EnrichmentKey) {
            return $enrichmentKey;
        }

        return null;
    }

    /**
     * Retrieve all Opus\EnrichmentKeys which are referenced by at
     * least one document from the database.
     *
     * @return array Array of Opus\Model2\EnrichmentKeys objects.
     */
    public function getAllReferenced()
    {
        // TODO: We are mixing in a DBAL query because the model for "documents_enrichment"
        // is a none ORM mapped model at the moment.

        $conn = $this->getEntityManager()->getConnection();

        $queryBuilder = $conn->createQueryBuilder();

        $select = $queryBuilder
            ->select('key_name')
            ->from('document_enrichments')
            ->distinct();

        $keyNames       = $select->execute()->fetchFirstColumn();
        $enrichmentKeys = [];

        foreach ($keyNames as $keyName) {
            $enrichmentKey = $this->findOneBy(['id' => $keyName]);

            if ($enrichmentKey instanceof EnrichmentKey) {
                $enrichmentKeys[] = $enrichmentKey;
            } else {
                // TODO: The need for this is weird, but related to testDeleteReferencedEnrichmentKeyWithoutCascading
                // TODO: in EnrichmentKeyTest line 177
                $newEnrichmentKey = new EnrichmentKey();
                $newEnrichmentKey->setName($keyName);
                $enrichmentKeys[] = $newEnrichmentKey;
            }
        }

        return $enrichmentKeys;
    }

    /**
     * Ändert den Namen des vorliegenden EnrichmentKey in allen Dokumenten, die
     * Enrichments mit diesem Namen verwenden.
     *
     * Achtung: diese Methode ändert *nicht* den Namen des EnrichmentKeys in der
     * Tabelle enrichmentkeys.
     *
     * @param string $newName neuer Name des EnrichmentKey
     * @param string $oldName ursprünglicher Name des EnrichmentKey
     */
    public function rename($newName, $oldName)
    {
        // TODO: We are mixing in a DBAL query because the model for "documents_enrichment"
        // is a none ORM mapped model at the moment.

        // TODO: Perhaps manipulative operations on a table that do not belong
        // to this repository are not a good idea

        $conn         = $this->getEntityManager()->getConnection();
        $queryBuilder = $conn->createQueryBuilder();
        $select       = $queryBuilder
            ->update('document_enrichments')
            ->where("key_name = :oldName")
            ->set('key_name', ':newName')
            ->setParameter('oldName', $oldName)
            ->setParameter('newName', $newName);

        $select->execute();
    }

    /**
     * Returns names of all enrichment keys.
     *
     * @return array
     */
    public function getKeys()
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $select       = $queryBuilder->select('e.id')
            ->from(EnrichmentKey::class, 'e');

        $query = $select->getQuery();
        return $query->getSingleColumnResult();
    }
}
