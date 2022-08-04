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
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\Model;

use DOMDocument;
use Opus\Common\Log;
use Opus\Common\Model\ModelException;
use Opus\Common\Model\ModelInterface;
use Opus\Model\Xml\Cache;
use Opus\Model\Xml\Conf;
use Opus\Model\Xml\StrategyInterface;
use Opus\Model\Xml\Version1;
use Opus\Uri\ResolverInterface;

use function get_class;

/**
 * Provides creation XML from models and creation of models from valid XML respectively.
 *
 * This class is a wrapper around implementations of the StrategyInterface like Version1
 * and Version1. It adds support for caching XML.
 *
 * TODO rename StrategyInterface as well
 * TODO NAMESPACE rename class?
 */
class Xml
{
    /**
     * Holds current configuration.
     *
     * @var Conf
     */
    private $config;

    /**
     * Holds current xml strategy object.
     *
     * @var StrategyInterface
     */
    private $strategy;

    /**
     * TODO
     * ...
     *
     * @var Cache
     */
    private $cache;

    /**
     * Do some initial stuff like setting of a XML version and an empty
     * configuration.
     */
    public function __construct()
    {
        $this->strategy = new Version1();
        $this->config   = new Conf();
        $this->strategy->setup($this->config);
    }

    /**
     * Set a new XML version with current configuration up.
     *
     * @param StrategyInterface $strategy Version of Xml to process
     * @return $this fluent interface.
     */
    public function setStrategy(StrategyInterface $strategy)
    {
        $this->strategy = $strategy;
        $this->strategy->setup($this->config);
        return $this;
    }

    /**
     * TODO
     * ...
     *
     * @return $this fluent interface.
     */
    public function setXmlCache(Cache $cache)
    {
        $this->cache = $cache;
        return $this;
    }

    /**
     * ...
     *
     * @return $this fluent interface.
     */
    public function removeCache()
    {
        $this->cache = null;
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
        return $this->cache;
    }

    /**
     * Set up base URI for xlink URI generation.
     *
     * @param string $uri Base URI.
     * @return $this Fluent interface
     */
    public function setXlinkBaseUri($uri)
    {
        $this->config->baseUri = $uri;
        return $this;
    }

    /**
     * Set up Xlink-Resolver called to obtain contents of Xlink referenced resources.
     *
     * @param ResolverInterface $resolver Resolver implementation that gets called for xlink:ref content.
     * @return $this Fluent interface
     *
     * TODO seems unused in OPUS
     */
    public function setXlinkResolver(ResolverInterface $resolver)
    {
        $this->config->xlinkResolver = $resolver;
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
     * @return $this Fluent interface
     */
    public function setResourceNameMap(array $map)
    {
        $this->config->resourceNameMap = $map;
        return $this;
    }

    /**
     * Set up list of fields to exclude from serialization.
     *
     * @param array|null $fields List of Field names
     * @return $this Fluent interface
     */
    public function exclude($fields)
    {
        $this->config->excludeFields = $fields;
        return $this;
    }

    /**
     * Define that empty fields (value===null) shall be excluded.
     *
     * @return $this Fluent interface
     */
    public function excludeEmptyFields()
    {
        $this->config->excludeEmpty = true;
        return $this;
    }

    /**
     * Set XML model representation.
     *
     * @param string $xml XML string representing a model.
     * @return $this Fluent interface.
     */
    public function setXml($xml)
    {
        $this->strategy->setXml($xml);
        return $this;
    }

    /**
     * Set a DomDocument instance.
     *
     * @param DOMDocument $dom DomDocument representing a model.
     * @return $this Fluent interface.
     */
    public function setDomDocument(DOMDocument $dom)
    {
        $this->strategy->setDomDocument($dom);
        return $this;
    }

    /**
     * Set the Model for XML generation.
     *
     * @param ModelInterface $model Model to serialize.
     * @return $this Fluent interface.
     */
    public function setModel($model)
    {
        $this->config->model = $model;
        return $this;
    }

    /**
     * Return the current Model instance if there is any. If there is an XML representation set up,
     * a new model is created by unserialising it from the XML data.
     *
     * @return ModelInterface Deserialised or previously set Model.
     */
    public function getModel()
    {
        return $this->strategy->getModel();
    }

    /**
     * If a model has been set this method generates and returnes
     * DOM representation of it.
     *
     * @return DOMDocument DOM representation of the current Model.
     */
    public function getDomDocument()
    {
        $model  = $this->config->model;
        $logger = Log::get();

        $result = $this->getDomDocumentFromXmlCache();
        if ($result !== null) {
            return $result;
        }

        $result = $this->strategy->getDomDocument();
        if ($this->cache === null) {
            return $result;
        }

        $this->cache->put(
            $model->getId(),
            (int) $this->strategy->getVersion(),
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
     * @return DOMDocument|null DOM representation of the current Model.
     */
    private function getDomDocumentFromXmlCache()
    {
        $model  = $this->config->model;
        $logger = Log::get();

        if (null === $this->cache) {
            $logger->debug(__METHOD__ . ' skipping cache for ' . get_class($model));
            return null;
        }

        $cached = $this->cache->hasValidEntry(
            $model->getId(),
            (int) $this->strategy->getVersion(),
            $model->getServerDateModified()->__toString()
        );

        if (true !== $cached) {
            $logger->debug(__METHOD__ . ' cache miss for ' . get_class($model) . '#' . $model->getId());
            return null;
        }

        $logger->debug(__METHOD__ . ' cache hit for ' . get_class($model) . '#' . $model->getId());
        try {
            return $this->cache->get($model->getId(), (int) $this->strategy->getVersion());
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
     */
    public function updateFromXml($xml)
    {
        $this->strategy->updateFromXml($xml);
    }

    /**
     * Returns used strategy main version aka XML Opus version.
     *
     * @return int
     */
    public function getStrategyVersion()
    {
        return $this->strategy->getVersion();
    }
}
