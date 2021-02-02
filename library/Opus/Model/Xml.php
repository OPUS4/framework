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
 * @package     Opus\Model
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @author      Henning Gerhardt (henning.gerhardt@slub-dresden.de)
 * @copyright   Copyright (c) 2008 - 2020, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\Model;

use Opus\Log;
use Opus\Model\Xml\Cache;
use Opus\Model\Xml\Conf;
use Opus\Model\Xml\Strategy;
use Opus\Model\Xml\Version1;
use Opus\Uri\Resolver;

/**
 * Provides creation XML from models and creation of models by valid XML respectivly.
 *
 * @category    Framework
 * @package     Opus\Model
 *
 * TODO NAMESPACE rename class?
 */
class Xml
{

    /**
     * Holds current configuration.
     *
     * @var Conf
     */
    private $_config = null;

    /**
     * Holds current xml strategy object.
     *
     * @var Strategy
     */
    private $_strategy = null;

    /**
     * TODO
     * ...
     *
     * @var Cache
     */
    private $_cache = null;

    /**
     * Do some initial stuff like setting of a XML version and an empty
     * configuration.
     */
    public function __construct()
    {
        $this->_strategy = new Version1();
        $this->_config = new Conf();
        $this->_strategy->setup($this->_config);
    }

    /**
     * Set a new XML version with current configuration up.
     *
     * @param Strategy $strategy Version of Xml to process
     * @return Xml fluent interface.
     */
    public function setStrategy(Strategy $strategy)
    {
        $this->_strategy = $strategy;
        $this->_strategy->setup($this->_config);
        return $this;
    }

    /**
     * TODO
     * ...
     *
     * @param Cache $cache
     * @return Xml fluent interface.
     */
    public function setXmlCache(Cache $cache)
    {
        $this->_cache = $cache;
        return $this;
    }

    /**
     * ...
     *
     * @return Xml fluent interface.
     */
    public function removeCache()
    {
        $this->_cache = null;
        return $this;
    }

    /**
     * TODO
     * ...
     *
     * @return Cache ...
     */
    public function getXmlCache()
    {
        return $this->_cache;
    }

    /**
     * Set up base URI for xlink URI generation.
     *
     * @param string $uri Base URI.
     * @return Xml Fluent interface
     */
    public function setXlinkBaseUri($uri)
    {
        $this->_config->baseUri = $uri;
        return $this;
    }

    /**
     * Set up Xlink-Resolver called to obtain contents of Xlink referenced resources.
     *
     * @param Resolver $resolver Resolver implementation that gets called for xlink:ref content.
     * @return Xml Fluent interface
     *
     * TODO seems unused in OPUS
     */
    public function setXlinkResolver(Resolver $resolver)
    {
        $this->_config->xlinkResolver = $resolver;
        return $this;
    }

    /**
     * Define the class name to resource name mapping.
     *
     * If a submodel is referenced by an xlink this map and the base URI are used
     * to generate the full URI. E.g. if a model is Opus\Licence, the array may specify
     * an mapping of this class name to "licence". Assuming a baseURI of "http://pub.service.org"
     * the full URI for a Licence with ID 4711 looks like this:
     * "http://pub.service.org/licence/4711"
     *
     * @param array $map Map of class names to resource names.
     * @return Xml Fluent interface
     */
    public function setResourceNameMap(array $map)
    {
        $this->_config->resourceNameMap = $map;
        return $this;
    }

    /**
     * Set up list of fields to exclude from serialization.
     *
     * @param array Field list
     * @return Xml Fluent interface
     */
    public function exclude(array $fields)
    {
        $this->_config->excludeFields = $fields;
        return $this;
    }

    /**
     * Define that empty fields (value===null) shall be excluded.
     *
     * @return Xml Fluent interface
     */
    public function excludeEmptyFields()
    {
        $this->_config->excludeEmpty = true;
        return $this;
    }

    /**
     * Set XML model representation.
     *
     * @param string $xml XML string representing a model.
     * @return Xml Fluent interface.
     */
    public function setXml($xml)
    {
        $this->_strategy->setXml($xml);
        return $this;
    }

    /**
     * Set a DomDocument instance.
     *
     * @param \DOMDocument $dom DomDocument representing a model.
     * @return Xml Fluent interface.
     */
    public function setDomDocument(\DOMDocument $dom)
    {
        $this->_strategy->setDomDocument($dom);
        return $this;
    }

    /**
     * Set the Model for XML generation.
     *
     * @param AbstractModel $model Model to serialize.
     * @return Xml Fluent interface.
     */
    public function setModel($model)
    {
        $this->_config->model = $model;
        return $this;
    }

    /**
     * Return the current Model instance if there is any. If there is an XML representation set up,
     * a new model is created by unserialising it from the XML data.
     *
     * @return AbstractModel Deserialised or previously set Model.
     */
    public function getModel()
    {
        return $this->_strategy->getModel();
    }

    /**
     * If a model has been set this method generates and returnes
     * DOM representation of it.
     * @return \DOMDocument DOM representation of the current Model.
     */
    public function getDomDocument()
    {
        $model = $this->_config->model;
        $logger = Log::get();

        $result = $this->getDomDocumentFromXmlCache();
        if (! is_null($result)) {
            return $result;
        }

        $result = $this->_strategy->getDomDocument();
        if (is_null($this->_cache)) {
            return $result;
        }

        $this->_cache->put(
            $model->getId(),
            (int) $this->_strategy->getVersion(),
            $model->getServerDateModified()->__toString(),
            $result
        );
        $logger->debug(__METHOD__ . ' cache refreshed for ' . get_class($model) . '#' . $model->getId());
        return $result;
    }

    /**
     * This method tries to load the current model from the xml cache.  Returns
     * null in case of an error/cache miss/cache disabled.  Returns DOMDocument
     * otherwise.
     *
     * @return \DOMDocument DOM representation of the current Model.
     */
    private function getDomDocumentFromXmlCache()
    {
        $model = $this->_config->model;
        $logger = Log::get();

        if (null === $this->_cache) {
            $logger->debug(__METHOD__ . ' skipping cache for ' . get_class($model));
            return null;
        }

        $cached = $this->_cache->hasValidEntry(
            $model->getId(),
            (int) $this->_strategy->getVersion(),
            $model->getServerDateModified()->__toString()
        );

        if (true !== $cached) {
            $logger->debug(__METHOD__ . ' cache miss for ' . get_class($model) . '#' . $model->getId());
            return null;
        }

        $logger->debug(__METHOD__ . ' cache hit for ' . get_class($model) . '#' . $model->getId());
        try {
            return $this->_cache->get($model->getId(), (int) $this->_strategy->getVersion());
        } catch (ModelException $e) {
            $logger->warn(
                __METHOD__ . " Access to XML cache failed on " . get_class($model) . '#' . $model->getId()
                . ".  Trying to recover."
            );
        }

        return null;
    }

    /**
     * Update a model from a given xml string.
     *
     * @param string $xml String of xml structure.
     * @return void
     */
    public function updateFromXml($xml)
    {
        $this->_strategy->updateFromXml($xml);
    }

    /**
     * Returns used strategy main version aka XML Opus version.
     *
     * @return integer
     */
    public function getStrategyVersion()
    {
        return $this->_strategy->getVersion();
    }
}
