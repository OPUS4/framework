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
 * @category    Framework
 * @package     Opus_Model_Plugin
 * @author      Edouard Simon (edouard.simon@zib.de)
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2008-2019, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

/**
 * Base class for plugins that need to update documents associated with collection tree.
 *
 */
abstract class Opus_Model_Plugin_AbstractCollection extends Opus\Model\Plugin\AbstractPlugin
{

    /**
     * make sure documents related to Collection[Role]s in subtree are updated
     * (XML-Cache and server_date_published)
     *
     * @param Opus_Collection Starting point for recursive update to documents
     */
    protected function updateDocuments($model)
    {
        if (is_null($model) || is_null($model->getId())) {
            // TODO explain why this is right
            return;
        }

        $collections = Opus_Db_TableGateway::getInstance('Opus_Db_Collections');

        $collectionIdSelect = $collections->selectSubtreeById($model->getId(), 'id');

        $documentFinder = new Opus_DocumentFinder();
        $documentFinder->setCollectionId($collectionIdSelect);

        // clear affected documents from cache
        $xmlCache = new Opus_Model_Xml_Cache();
        $xmlCache->removeAllEntriesWhereSubSelect($documentFinder->getSelectIds());

        // update ServerDateModified for affected documents
        $date = new Opus_Date();
        $date->setNow();

        Opus_Document::setServerDateModifiedByIds($date, $documentFinder->ids());
    }
}
