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
 * @copyright   Copyright (c) 2013
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\Model\Plugin;

use Opus\Common\Date;
use Opus\Common\Model\ModelInterface;
use Opus\Common\Model\Plugin\AbstractPlugin;
use Opus\Common\Repository;
use Opus\Document;
use Opus\DocumentFinder;
use Opus\DocumentFinder\DocumentFinderException;
use Opus\Model\AbstractDb;
use Opus\Model\Filter;
use Opus\Model\Xml\Cache;
use Zend_Config;
use Zend_Config_Exception;
use Zend_Config_Ini;

use function dirname;
use function get_class;
use function in_array;

/**
 * Plugin deleting xml cache entries and updating the modification date of documents related to the model.
 *
 * This plugin is attached to all the model classes except Opus\Document that contain information that is part of the
 * aggregated metadata of a document in OPUS 4.
 *
 * TODO filter configuration should only exclude from server_date_modified change (should check date) - OPUSVIER-3760
 * TODO models should define their own lists (decentralized, object-oriented) - OPUSVIER-3759
 * TODO cache should be transparent - important is updating ServerDateModified
 *
 * phpcs:disable
 */
class InvalidateDocumentCache extends AbstractPlugin
{
    /**
     * Run method invalidateDocumentCacheFor() in postStore if true.
     */
    protected $_postStoreUpdateDocuments = true;

    protected $_updateServerDateModified = true;

    /**
     * Configuration for ignored model fields.
     *
     * This configuration should be shared across all objects.
     *
     * @var Zend_Config
     */
    private static $filterConfig;

    /**
     * @see {Opus\Model\Plugin\PluginInterface::preStore}
     *
     * Check wether to update documents on postStore.
     * If there is no information about a Model
     * the postStore hook is not triggered.
     *
     * TODO break up function
     */
    public function preStore(ModelInterface $model)
    {
        $modelClass = get_class($model);

        $config = self::getFilterConfig();

        if (isset($config->{$modelClass})) {
            $blacklist = $config->{$modelClass}->toArray();

            $filter = new Filter();
            $filter->setModel($model);
            $filter->setBlacklist($blacklist);
            $whitelist = $filter->describe();

            foreach ($whitelist as $fieldName) {
                if ($model->hasField($fieldName) && $model->getField($fieldName)->isModified()) {
                    // change modifies metadata
                    $this->_postStoreUpdateDocuments = true;
                    $this->_updateServerDateModified = true;
                    return;
                }
            }

            $configKey = 'cache.' . $modelClass;

            if (isset($config->{$configKey})) {
                $cacheList = $config->{$configKey}->toArray();

                // check if cache should be deleted for blacklisted field
                foreach ($blacklist as $fieldName) {
                    if (
                        $model->hasField($fieldName)
                        && $model->getField($fieldName)->isModified()
                        && in_array($fieldName, $cacheList)
                    ) {
                        $this->_postStoreUpdateDocuments = true;
                        $this->_updateServerDateModified = false;
                        return;
                    }
                }
            }

            $this->_postStoreUpdateDocuments = false;
            $this->_updateServerDateModified = false;
        }
    }

    /**
     * @see {Opus\Model\Plugin\PluginInterface::postStore}
     */
    public function postStore(ModelInterface $model)
    {
        if ($this->_postStoreUpdateDocuments) {
            $this->invalidateDocumentCacheFor($model);
        }
    }

    /**
     * @see {Opus\Model\Plugin\PluginInterface::preDelete}
     *
     * Run plugin for documents depending on to-be-deleted model.
     * If model is not persistent (i. e. modelId is not set and /or model states to be a new record)
     * preDelete operation is skipped.
     */
    public function preDelete(ModelInterface $model)
    {
        $modelId = $model->getId();
        if (! $model->isNewRecord() && ! empty($modelId)) {
            $this->invalidateDocumentCacheFor($model);
        }
    }

    /**
     * Removes cache entries and updates last modified dates for documents.
     *
     * Finds all documents that are linked to the provided model and removes them from the xml cache and updates the
     * last modified date.
     *
     * NOTE: logically it is the reverse. The document has been modified (the date changes), therefore the cache
     *       needs to be invalidated.
     *
     * @param AbstractDb $model
     * @throws DocumentFinderException
     */
    protected function invalidateDocumentCacheFor(ModelInterface $model)
    {
        $documentFinder = new DocumentFinder();

        $documentFinder->setDependentModel($model);
        $select = $documentFinder->getSelectIds();
        $ids    = $documentFinder->Ids();

        $xmlCache = new Cache();
        $xmlCache->removeAllEntriesWhereSubSelect($select);

        if ($this->_updateServerDateModified) {
            $date = new Date();
            $date->setNow();
            Repository::getInstance()->getModelRepository(Document::class)->setServerDateModifiedForDocuments(
                $date,
                $ids
            );
        }
    }

    /**
     * Return configuration of ignored model fields.
     *
     * @throws Zend_Config_Exception
     * @return Zend_Config
     */
    public static function getFilterConfig()
    {
        if (self::$filterConfig === null) {
            self::$filterConfig = new Zend_Config_Ini(dirname(__FILE__) . '/updatedocument_filter.ini');
        }

        return self::$filterConfig;
    }

    /**
     * Set global configuration fir ignored model fields.
     *
     * @param $config null|\Zend_Config
     */
    public static function setFilterConfig($config)
    {
        self::$filterConfig = $config;
    }
}
