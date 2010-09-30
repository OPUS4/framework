<?php
/**
 * LICENCE
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @author      Henning Gerhardt <henning.gerhardt@slub-dresden.de>
 * @copyright   Copyright (c) 2010
 *              Saechsische Landesbibliothek - Staats- und Universitaetsbibliothek Dresden (SLUB)
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id: XmlCache.php 5765 2010-06-07 14:15:00Z claussni $
 */


/**
 * Plugin creating and deleting xml cache entries.
 *
 * @category    Framework
 * @package     Opus_Document_Plugin
 * @uses        Opus_Model_Abstract
 */
class Opus_Document_Plugin_XmlCache extends Opus_Model_Plugin_Abstract {

    /**
     * @see {Opus_Model_Plugin_Interface::postStore}
     */
    public function postStore(Opus_Model_AbstractDb $model) {
        return;

        $logger = Zend_Registry::get('Zend_Log');
        if (null !== $logger) {
            $logger->debug('Opus_Document_Plugin_XmlCache::postStore() with id ' . $model->getId());
        }

        $model = new Opus_Document($model->getId());

        $cache = new Opus_Model_Xml_Cache;

        // xml version 1
        $omx = new Opus_Model_Xml();
        $omx->setStrategy(new Opus_Model_Xml_Version1)
            ->excludeEmptyFields()
            ->setModel($model)
            ->setXmlCache($cache);
        $dom = $omx->getDomDocument();

        // xml version 2
        $omx = new Opus_Model_Xml();
        $omx->setStrategy(new Opus_Model_Xml_Version2)
            ->setModel($model)
            ->setXmlCache($cache);
        $dom = $omx->getDomDocument();

    }

    /**
     * @see {Opus_Model_Plugin_Interface::postDelete}
     */
    public function postDelete($modelId) {
        return;

        $cache = new Opus_Model_Xml_Cache();
        $omx = new Opus_Model_Xml;

        // xml version 1
        $omx->setStrategy(new Opus_Model_Xml_Version1);
        $cache->remove($modelId, floor($omx->getStrategyVersion()));

        // xml version 2
        $omx->setStrategy(new Opus_Model_Xml_Version2);
        $cache->remove($modelId, floor($omx->getStrategyVersion()));

    }

}

