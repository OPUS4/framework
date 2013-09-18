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
 * @author      Edouard Simon <edouard.simon@zib.de>
 * @copyright   Copyright (c) 2013
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Plugin deleting xml cache entries and updating the modification date of documents related to the model.
 *
 */
class Opus_Model_Plugin_InvalidateDocumentCache extends Opus_Model_Plugin_Abstract {

    /**
     * Run method invalidateDocumentCacheFor() in postStore if true.
     */
    protected $postStoreUpdateDocuments = true;

    /**
     * @see {Opus_Model_Plugin_Interface::preStore}
     * Check wether to update documents on postStore.
     * If there is no information about a Model
     * the postStore hook is not triggered.
     * 
     */
    public function preStore(Opus_Model_AbstractDb $model) {

        $modelClass = get_class($model);
        $config = new Zend_Config_Ini(dirname(__FILE__) . '/updatedocument_filter.ini');
        if (isset($config->{$modelClass})) {
            $this->postStoreUpdateDocuments = false;
            $filter = new Opus_Model_Filter();
            $filter->setModel($model);
            $filter->setBlacklist($config->{$modelClass}->toArray());
            $whitelist = $filter->describe();
            foreach ($whitelist as $fieldName) {
                if ($model->hasField($fieldName) && $model->getField($fieldName)->isModified()) {
                    $this->postStoreUpdateDocuments = true;
                    break;
                }
            }
        }
    }

    /**
     * @see {Opus_Model_Plugin_Interface::postStore}
     */
    public function postStore(Opus_Model_AbstractDb $model) {
        if ($this->postStoreUpdateDocuments) {
            $this->invalidateDocumentCacheFor($model);
        }
    }

    /**
     * @see {Opus_Model_Plugin_Interface::preDelete}
     */
    public function preDelete(Opus_Model_AbstractDb $model) {
        if (!$model->isNewRecord()) {
            $this->invalidateDocumentCacheFor($model);
        }
    }

    protected function invalidateDocumentCacheFor(Opus_Model_AbstractDb $model) {
        $documentFinder = new Opus_DocumentFinder();

        $documentFinder->setDependentModel($model);
        $select = $documentFinder->getSelectIds();
        $ids = $documentFinder->Ids();

        $xmlCache = new Opus_Model_Xml_Cache();
        $xmlCache->removeAllEntriesWhereSubSelect($select);

        $date = new Opus_Date();
        $date->setNow();
        Opus_Document::setServerDateModifiedByIds($date, $ids);
    }

}

