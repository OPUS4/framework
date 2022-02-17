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
 * @copyright   Copyright (c) 2009, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * @category    Framework
 * @package     Opus\Model
 * @author      Henning Gerhardt (henning.gerhardt@slub-dresden.de)
 */

namespace Opus\Model\Xml;

use DOMDocument;
use Opus\Model\AbstractModel;
use Opus\Model\ModelException;

/**
 * General interface for Opus XML representations.
 */
interface StrategyInterface
{
    /**
     * If a model has been set this method generates and returnes
     * DOM representation of it.
     *
     * @throws ModelException Thrown if no Model is given.
     * @return DOMDocument DOM representation of the current Model.
     */
    public function getDomDocument();

    /**
     * Return the current Model instance if there is any. If there is an XML representation set up,
     * a new model is created by unserialising it from the XML data.
     *
     * @throws ModelException If an error occured during deserialisation.
     * @return AbstractModel Deserialised or previously set Model.
     */
    public function getModel();

    /**
     * Returns version of current xml representation.
     *
     * @return int
     */
    public function getVersion();

    /**
     * Set a DomDocument instance.
     *
     * @param DOMDocument $dom DomDocument representing a model.
     */
    public function setDomDocument(DOMDocument $dom);

    /**
     * Setup a representation with a configuration.
     */
    public function setup(Conf $conf);

    /**
     * Set XML model representation.
     *
     * @param string $xml XML string representing a model.
     * @throws ModelException Thrown if XML loading failed.
     */
    public function setXml($xml);

    /**
     * Update a model from a given xml string.
     *
     * @param string $xml String of xml structure.
     */
    public function updateFromXml($xml);
}
