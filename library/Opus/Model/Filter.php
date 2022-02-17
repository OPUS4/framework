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
 *
 * @category    Framework
 * @package     Opus\Model
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 */

namespace Opus\Model;

use DOMDocument;
use InvalidArgumentException;
use Opus\Model\Xml\StrategyInterface;
use Opus\Model\Xml\Version1;
use Opus\Security\SecurityException;

use function array_diff;
use function array_intersect;
use function array_merge;
use function in_array;
use function is_string;
use function rtrim;
use function substr;

/**
 * Wrapper class for all domain models in the Opus framework.
 * Defines field blacklist to restrict access and field reporting
 * of concrete Models.
 */
class Filter extends AbstractModel
{
    /**
     * Model instance that gets filtered.
     *
     * @var parent
     */
    private $model;

    /**
     * List of fields to be filtered.
     *
     * @var array Array of fieldnames.
     */
    private $blacklist = [];

    /**
     * List of fields to define sort order.
     *
     * @var array Array of fieldnames defining sort order.
     */
    private $sortorder = [];

    /**
     * Just here to implement abstract interface.
     *
     * @see \Opus\Model\Abstract#_init()
     */
    protected function init()
    {
    }

    /**
     * Set model to filter.
     *
     * @param parent $model Filter source.
     * @return $this Fluent interface.
     */
    public function setModel(parent $model)
    {
        $this->model = $model;
        return $this;
    }

    /**
     * Set List of fields to be filtered.
     *
     * @param array $list Array of fields that shall be filtered.
     * @return $this Fluent interface.
     */
    public function setBlacklist(array $list)
    {
        $this->blacklist = $list;
        return $this;
    }

    /**
     * Set list of fields to allow access to.
     *
     * @param array $list Array of fields that shall be allowed to be accessed.
     * @return $this Fluent interface.
     */
    public function setWhitelist(array $list)
    {
        $this->blacklist = array_diff($this->model->describe(), $list);
        return $this;
    }

    /**
     * Define field sort order for result of describe().
     *
     * @param array $sort Array of field names specifying the order.
     * @return $this Fluent interface.
     */
    public function setSortOrder(array $sort)
    {
        $this->sortorder = $sort;
        return $this;
    }

    /**
     * Get a list of all fields attached to the model. Filters all fieldnames
     * that are listed on the blacklist.
     *
     * @see    \Opus\Model\Abstract::_internalFields
     *
     * @return array    List of fields
     */
    public function describe()
    {
        $result = $this->model->describe();

        // ensure sort order by removing all sorted fields from output
        // and put sort order list on top of the result
        $sortorder = array_intersect($this->sortorder, $result);
        $result    = array_diff($result, $sortorder);
        $result    = array_merge($sortorder, $result);

        $result = array_diff($result, $this->blacklist);
        return $result;
    }

    /**
     * Return a reference to an actual field if not on the blacklist.
     *
     * @param string $name Name of the requested field.
     * @throws ModelException If the requested field is hidden by the blacklist.
     * @return Field The requested field instance. If no such instance can be found, null is returned.
     */
    public function getField($name)
    {
        if (in_array($name, $this->blacklist)) {
            throw new ModelException('Requested field is hidden by the blacklist.');
        }
        return $this->model->getField($name);
    }

    /**
     * Magic method to access the models fields via virtual set/get methods.
     * Restricts all access to blacklisted fields.
     *
     * @param string $name      Name of the method beeing called.
     * @param array  $arguments Arguments for function call.
     * @throws InvalidArgumentException When adding a link to a field without an argument.
     * @throws ModelException     If an unknown field or method is requested.
     * @throws SecurityException  If the current role has no permission for the requested operation.
     * @return mixed Might return a value if a getter method is called.
     */
    public function __call($name, array $arguments)
    {
        $fieldname = substr($name, 3);
        if (in_array($fieldname, $this->blacklist)) {
            throw new ModelException('Requested field is hidden by the blacklist.');
        }
        $argstring = '';
        foreach ($arguments as $i => $argument) {
            if (true === is_string($argument)) {
                $argstring .= '\'' . $argument . '\',';
            } else {
                $argstring .= '$arguments[' . $i . '],';
            }
        }
        $result = null;
        eval('$result = $this->model->$name(' . rtrim($argstring, ',') . ');');
        return $result;
    }

    /**
     * Get a nested associative array representation of the model.
     *
     * @return array A (nested) array representation of the model.
     */
    public function toArray()
    {
        $modelArray = $this->model->toArray();

        $filteredFields = $this->describe();
        $result         = [];
        foreach ($filteredFields as $filteredField) {
            $result[$filteredField] = $modelArray[$filteredField];
        }

        return $result;
    }

    /**
     * Returns a DOM representation of the filtered model.
     *
     * @param null|array             $excludeFields Array of fields that shall not be serialized.
     * @param null|StrategyInterface $strategy Version of Xml to process
     * @param bool                   $excludeEmptyFields If set to false, fields with empty values are included in the resulting DOM.
     * @return DOMDocument A Dom representation of the model.
     */
    public function toXml(?array $excludeFields = null, $strategy = null, $excludeEmptyFields = true)
    {
        if ($excludeFields === null) {
            $excludeFields = [];
        }
        if ($strategy === null) {
            $strategy = new Version1();
        }
        $xml = new Xml();
        $xml->setModel($this)
            ->exclude($excludeFields)
            ->setStrategy($strategy);
        if ($excludeEmptyFields === true) {
            $xml->excludeEmptyFields();
        }
        return $xml->getDomDocument();
    }

    /**
     * Returns the filtered model.
     *
     * @return parent The filtered model.
     */
    public function getModel()
    {
        return $this->model;
    }
}
