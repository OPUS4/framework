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
 * @category    Framework
 * @package     Opus_Model
 * @author      Felix Ostrowski <ostrowski@hbz-nrw.de>
 * @copyright   Copyright (c) 2009, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Bridges Opus_Collection_Information to Opus_Model_Abstract.
 *
 */
class Opus_Model_Collection_Document extends Opus_Model_Collection_Abstract
{
    /**
     * Fetches the documents in this collection.
     *
     * @return array $documents The documents in the collection.
     */
    public function getEntries() {
        $docIds = Opus_Collection_Information::getAllCollectionDocuments((int) $this->__role, (int) $this->__collection['id']);
        $documents = array();
        foreach ($docIds as $docId) {
            $documents[] = new Opus_Model_Document($docId);
        }
        return $documents;
    }

    /**
     * Adds a document to this collection.
     *
     * @param  Opus_Model_Document  $document The document to add.
     * @return void
     */
    public function addEntry(Opus_Model_Abstract $document) {
        $linkTable = new Opus_Db_LinkDocumentsCollections((int) $this->__role);
        $link = $linkTable->createRow();
        $link->documents_id = $document->getId();
        $link->collections_id = $this->__collection['id'];
        $link->save();
    }

}

