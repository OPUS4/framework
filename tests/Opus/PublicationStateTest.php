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
 * @copyright   Copyright (c) 2024, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace OpusTest;

use Opus\Common\Config\ConfigException;
use Opus\Common\Model\ModelException;
use Opus\Common\PublicationState;
use Opus\Common\Repository;
use Opus\Db\TableGateway;
use OpusTest\TestAsset\TestCase;
use Zend_Db_Table_Exception;

use function array_map;
use function explode;
use function preg_match;
use function trim;

/**
 * Test cases for class Opus\Person.
 */
class PublicationStateTest extends TestCase
{
    public function testPublicationStateValuesMatchEnumDefinition()
    {
        $enumValues = $this->getEnumValues();

        $publicationState = new PublicationState();

        $this->assertEqualsCanonicalizing($publicationState->getAllValues(), $enumValues);
    }

    /**
     * @return string[]
     * @throws ConfigException
     * @throws ModelException
     * @throws Zend_Db_Table_Exception
     */
    protected function getEnumValues()
    {
        $tableGatewayClass = Repository::getInstance()->getModelFactory()->getTableGatewayClass('Document');

        $metadata = TableGateway::getInstance($tableGatewayClass)->info();

        $dataType = $metadata['metadata']['publication_state']['DATA_TYPE'];

        preg_match('/enum\\((.*)\\)/', $dataType, $matches);

        return array_map(function ($value) {
            return trim($value, ' \'');
        }, explode(',', $matches[1]));
    }
}
