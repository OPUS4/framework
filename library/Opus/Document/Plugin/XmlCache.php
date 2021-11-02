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
 * @copyright   Copyright (c) 2010
 *              Saechsische Landesbibliothek - Staats- und Universitaetsbibliothek Dresden (SLUB)
 * @copyright   Copyright (c) 2010-2020 OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * @author      Henning Gerhardt <henning.gerhardt@slub-dresden.de>
 * @author      Jens Schwidder <schwidder@zib.de>
 */

namespace Opus\Document\Plugin;

use Opus\Document;
use Opus\LoggingTrait;
use Opus\Model\ModelInterface;
use Opus\Model\Plugin\AbstractPlugin;
use Opus\Model\Xml;
use Opus\Model\Xml\Cache;
use Opus\Model\Xml\Version1;

use function floor;

/**
 * Plugin creating and deleting xml cache entries.
 *
 * @uses        \Opus\Model\Plugin\AbstractPlugin
 *
 * @category    Framework
 * @package     Opus\Document\Plugin
 */
class XmlCache extends AbstractPlugin
{
    use LoggingTrait;

    /**
     * Function is only called if document was modified.
     *
     * @see PluginInterface::postStore
     */
    public function postStore(ModelInterface $model)
    {
        $logger = $this->getLogger();
        $logger->debug(__METHOD__ . ' with id ' . $model->getId());

        // TODO can that be eleminated? why is it necessary?
        $model = Document::get($model->getId());

        $cache = new Cache(false);

        // remove document from cache. This can always be done, because postStore is only called if model was modified.
        $cache->remove($model->getId());

        // refresh cache (TODO does it make sense?)
        $omx = new Xml();
        $omx->setStrategy(new Version1())
            ->excludeEmptyFields()
            ->setModel($model)
            ->setXmlCache($cache);
        $omx->getDomDocument(); // TODO caching as side effect?
    }

    /**
     * @see PluginInterface::postDelete
     *
     * @param int $modelId Model ID
     */
    public function postDelete($modelId)
    {
        $cache = new Cache(false);
        $omx   = new Xml();

        // xml version 1
        $omx->setStrategy(new Version1());

        $cache->remove($modelId, floor($omx->getStrategyVersion()));
    }
}
