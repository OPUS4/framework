<?php

/*
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
 * @category    Tests
 * @package     Opus\Collection
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2010-2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace OpusTest\TestAsset;

use Opus\LoggingTrait;
use Opus\Model2\Collection;

/**
 * Validates the structure of a NestedSet in the database.
 */
class NestedSetValidator
{
    use LoggingTrait;

    /**
     * Position counter in NestedSet.
     *
     * @var int
     */
    private $counter;

    /**
     * Check structure of nested set.
     *
     * @param int $rootId
     * @return bool
     */
    public function validate($rootId)
    {
        /** @var Collection $node */
        $node          = Collection::get($rootId);
        $this->counter = $node->getLeft();
        return $this->validateNode($rootId); // root node
    }

    /**
     * Validates a node and walks NestedSet recursively.
     *
     * @param int $nodeId ID for node in NestedSet
     * @return bool true - valid, false - invalid
     */
    public function validateNode($nodeId)
    {
        $logger = $this->getLogger();

        /** @var Collection $node */
        $node    = Collection::get($nodeId);
        $leftId  = $node->getLeft();
        $rightId = $node->getRight();

        $logger->err("{$this->counter}, $nodeId: $leftId, $rightId");

        if ((int) $leftId !== $this->counter) {
            // invalid
            $logger->err("{$this->counter}, $nodeId: $leftId, $rightId not valid (left_id)");
            return false;
        } else {
            $this->counter++;
        }

        $distance = $rightId - $leftId;

        if ($distance === 1) {
            $this->counter++;
            return true;
        } elseif ($distance > 1) {
            // node
            if ($distance & 1) {
                // odd; valid
                $children = Collection::fetchChildrenByParentId($nodeId, true);
                foreach ($children as $child) {
                    if ($this->validateNode($child->getId()) === false) {
                        return false;
                    }
                }
                 $this->counter++;
            } else {
                // even; invalid
                $logger->err("{$this->counter}, $nodeId: $leftId, $rightId not valid");
                return false;
            }
        } elseif ($distance < 1) {
            // invalid
            $logger->err("{$this->counter}, $nodeId: $leftId, $rightId not valid");
            return false;
        }

        return true;
    }
}
