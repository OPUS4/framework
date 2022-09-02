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

namespace Opus\Db\Model;

use Opus\Common\Log;
use Opus\Common\Model\FieldDescriptor as CommonFieldDescriptor;
use Opus\Db\TableGateway;
use Zend_Db_Table_Exception;
use Zend_Exception;

use function preg_replace;
use function strtolower;

/**
 * FieldDescriptorInterface implementation that queries the database for the supported
 * length of fields.
 */
class FieldDescriptor extends CommonFieldDescriptor
{
    /**
     * @return int
     *
     * TODO get table for model
     * TODO get max size of field from database
     */
    public function getMaxSize()
    {
        // TODO get max size from database

        return parent::getMaxSize();
    }

    /**
     * @return int
     * @throws Zend_Db_Table_Exception
     * @throws Zend_Exception
     */
    protected function getColumnSizeFromDatabase()
    {
        $column = $this->getColumnName();

        $table = TableGateway::getInstance(self::getTableGatewayClass()); // TODO get gatewayClass from ModelDescriptor

        $metadata = $table->info();

        if (isset($metadata['metadata'][$column]['LENGTH'])) {
            return $metadata['metadata'][$column]['LENGTH'];
        } else {
            // TODO throw exception ModelException
            $class = static::class;
            Log::get()->err("Call to $class::getFieldMaxLength for unknown field '$name'.");
            return 0;
        }
    }

    /**
     * @return string Database column name for field
     */
    public function getColumnName()
    {
        return strtolower(preg_replace('/(?!^)[[:upper:]]/', '_\0', $this->getName()));
    }
}
