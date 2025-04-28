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
 * @copyright   Copyright (c) 2022, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus;

use Opus\Common\Date;
use Opus\Common\Model\ModelException;
use Opus\Common\Model\ModelFactoryInterface;

use function array_key_exists;
use function call_user_func;
use function class_exists;

/**
 * Creates model and model repository objects.
 *
 * TODO add function to get TableGatewayClass for model - for use in DbFieldDescriptor
 */
class ModelFactory implements ModelFactoryInterface
{
    /** @var string[] Mapping of model types to separate DocumentRepository classes */
    protected $repositoryClasses = [
        'Document'   => DocumentRepository::class,
        'Person'     => PersonRepository::class,
        'Collection' => CollectionRepository::class,
    ];

    /** @var string[] Custom mapping of model types to model classes */
    protected $modelClasses = [
        'Date'      => Date::class,
        'HashValue' => HashValues::class,
    ];

    /**
     * @param string $type
     * @return mixed
     */
    public function create($type)
    {
        $modelClass = $this->getModelClass($type);

        return new $modelClass();
    }

    /**
     * @param string $type
     * @param int    $modelId
     * @return mixed
     */
    public function get($type, $modelId)
    {
        $modelClass = $this->getModelClass($type);

        return new $modelClass($modelId);
    }

    /**
     * @param string $type
     * @return mixed
     *
     * TODO reuse repository instance
     * TODO Using class_exists causes exceptions in the autoloader, because it tries to load the class. If autoload
     *      is disabled, it doesn't find existing classes. Therefore mapping in $repositoryClasses was created to
     *      avoid having to check if a class exists for the decision which one to use.
     */
    public function getRepository($type)
    {
        if (array_key_exists($type, $this->repositoryClasses)) {
            $repositoryClass = $this->repositoryClasses[$type];
            return new $repositoryClass();
        } else {
            // TODO in old implementation model classes also serve as "repositories"
            return $this->create($type);
        }
    }

    /**
     * @param string $type
     * @return string
     * @throws ModelException
     */
    public function getTableGatewayClass($type)
    {
        $modelClass = $this->getModelClass($type);

        return call_user_func([$modelClass, 'getTableGatewayClass']);
    }

    /**
     * @param string $type
     * @return string
     * @throws ModelException
     *
     * TODO check if supported type?
     */
    public function getModelClass($type)
    {
        if (array_key_exists($type, $this->modelClasses)) {
            $modelClass = $this->modelClasses[$type];
        } else {
            $modelClass = 'Opus\\' . $type;
        }

        if (! class_exists($modelClass)) {
            throw new ModelException("Model class not found: $modelClass");
        }

        return $modelClass;
    }
}
