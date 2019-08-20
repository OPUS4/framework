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
 * @author      Henning Gerhardt <henning.gerhardt@slub-dresden.de>
 * @copyright   Copyright (c) 2010
 *              Saechsische Landesbibliothek - Staats- und Universitaetsbibliothek Dresden (SLUB)
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2010-2018 OPUS 4 development team
 */

/**
 * Plugin creating and deleting xml cache entries.
 *
 * @category    Framework
 * @package     Opus_Document_Plugin
 * @uses        Opus\Model\Plugin\Abstract
 */
class Opus_Document_Plugin_XmlCache extends Opus\Model\Plugin\AbstractPlugin
{

    use Opus\LoggingTrait;

    /**
     * Function is only called if document was modified.
     *
     * @see {Opus\Model\Plugin\PluginInterface::postStore}
     */
    public function postStore(Opus\Model\ModelInterface $model)
    {
        $logger = $this->getLogger();
        $logger->debug('Opus_Document_Plugin_XmlCache::postStore() with id ' . $model->getId());

        // TODO can that be eleminated? why is it necessary?
        $model = new Opus_Document($model->getId());

        $cache = new Opus_Model_Xml_Cache(false);

        // remove document from cache. This can always be done, because postStore is only called if model was modified.
        $cache->remove($model->getId());

        // refresh cache (TODO does it make sense?)
        $omx = new Opus_Model_Xml();
        $omx->setStrategy(new Opus_Model_Xml_Version1())
            ->excludeEmptyFields()
            ->setModel($model)
            ->setXmlCache($cache);
        $omx->getDomDocument(); // TODO caching as side effect?
    }

    /**
     * @see {Opus\Model\Plugin\PluginInterface::postDelete}
     */
    public function postDelete($modelId)
    {
        $cache = new Opus_Model_Xml_Cache(false);
        $omx = new Opus_Model_Xml;

        // xml version 1
        $omx->setStrategy(new Opus_Model_Xml_Version1());

        $cache->remove($modelId, floor($omx->getStrategyVersion()));
    }
}
