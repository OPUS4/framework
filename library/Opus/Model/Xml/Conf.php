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
 */

namespace Opus\Model\Xml;

use DOMDocument;
use Opus\Common\Model\ModelInterface;
use Opus\Uri\ResolverInterface;

/**
 * Simple configuration object.
 */
class Conf
{
    /**
     * Holds the current model either directly set or deserialized from XML.
     *
     * @var ModelInterface
     */
    public $model;

    /**
     * Holds the current DOM representation.
     *
     * @var DOMDocument
     */
    public $dom;

    /**
     * List of fields to skip on serialization.
     *
     * @var array
     */
    public $excludeFields = [];

    /**
     * True, if empty fields get excluded from serialization.
     *
     * @var bool
     */
    public $excludeEmpty = false;

    /**
     * Base URI for xlink:ref elements
     *
     * @var string
     */
    public $baseUri = '';

    /**
     * Holds Resolver instance to get contents of xlink referenced resources.
     *
     * @var ResolverInterface
     */
    public $xlinkResolver;

    /**
     * Map of model class names to resource names for URI generation.
     *
     * @var array
     */
    public $resourceNameMap = [];
}
