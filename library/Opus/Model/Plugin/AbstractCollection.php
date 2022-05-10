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
 * @copyright   Copyright (c) 2008-2022, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\Model\Plugin;

use Opus\Collection;
use Opus\Common\Model\Plugin\AbstractPlugin;
use Opus\Date;
use Opus\Db\Collections;
use Opus\Db\TableGateway;
use Opus\Document;
use Opus\DocumentFinder;
use Opus\Model\Xml\Cache;

/**
 * Base class for plugins that need to update documents associated with collection tree.
 */
abstract class AbstractCollection extends AbstractPlugin
{
    /**
     * make sure documents related to Collection[Role]s in subtree are updated
     * (XML-Cache and server_date_published)
     *
     * @param Collection $collection Starting point for recursive update to documents
     */
    protected function updateDocuments($collection)
    {
        if ($collection === null || $collection->getId() === null) {
            // no collection provided or collection has not been saved, so there is no ID
            return;
        }

        $collections = TableGateway::getInstance(Collections::class);

        $collectionIdSelect = $collections->selectSubtreeById($collection->getId(), 'id');

        $documentFinder = new DocumentFinder();
        $documentFinder->setCollectionId($collectionIdSelect);

        // clear affected documents from cache
        $xmlCache = new Cache();
        $xmlCache->removeAllEntriesWhereSubSelect($documentFinder->getSelectIds());

        // update ServerDateModified for affected documents
        $date = new Date();
        $date->setNow();

        Document::setServerDateModifiedByIds($date, $documentFinder->ids());
    }
}
