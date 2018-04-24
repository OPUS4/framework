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
 * @package     Opus_Model
 * @author      Henning Gerhardt (henning.gerhardt@slub-dresden.de)
 * @copyright   Copyright (c) 2009, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * General interface for Opus XML representations.
 */
interface Opus_Model_Xml_Strategy {

    /**
     * If a model has been set this method generates and returnes
     * DOM representation of it.
     *
     * @throws Opus\Model\Exception Thrown if no Model is given.
     * @return DOMDocument DOM representation of the current Model.
     */
    public function getDomDocument();

    /**
     * Return the current Model instance if there is any. If there is an XML representation set up,
     * a new model is created by unserialising it from the XML data.
     *
     * @throws Opus\Model\Exception If an error occured during deserialisation
     * @return Opus_Model_Abstract Deserialised or previously set Model.
     */
    public function getModel();

    /**
     * Returns version of current xml representation.
     *
     * @return integer
     */
    public function getVersion();

    /**
     * Set a DomDocument instance.
     *
     * @param DOMDocument $dom DomDocument representing a model.
     * @return void
     */
    public function setDomDocument(DOMDocument $dom);

    /**
     * Setup a representation with a configuration.
     *
     * @param Opus_Model_Xml_Conf $conf
     * @return void
     */
    public function setup(Opus_Model_Xml_Conf $conf);

    /**
     * Set XML model representation.
     *
     * @param string $xml XML string representing a model.
     * @throws Opus\Model\Exception Thrown if XML loading failed.
     * @return void
     */
    public function setXml($xml);

    /**
     * Update a model from a given xml string.
     *
     * @param string $xml String of xml structure.
     * @return void
     */
    public function updateFromXml($xml);

}
