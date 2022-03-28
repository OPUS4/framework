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
 * @copyright   Copyright (c) 2010-2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\Db2;

/**
 * Table gateway class to table 'collections'.
 */

class Collections extends NestedSet
{
    /**
     * Table name of the nested set table.
     *
     * @var string
     */
    protected $name = 'collections';

    /**
     * Table column holding the primary key for the nested set structure.
     *
     * @var string
     */
    protected $primary = 'id';

    /**
     * Table column holding the left-id for the nested set structure.
     *
     * @var string
     */
    protected $left = 'left_id';

    /**
     * Table column holding the right-id for the nested set structure.
     *
     * @var string
     */
    protected $right = 'right_id';

    /**
     * Table column holding the parent-id for the structure.  This actually is
     * more than a nested set structure, but we need this for fast retrieval of
     * one nodes' children.
     *
     * @var string
     */
    protected $parent = 'parent_id';

    /**
     * Table column holding the tree-id for the structure.  We're holding more
     * than one nested-set structure in the table and we're distinguishing the
     * different trees by this ID.
     *
     * @var string
     */
    protected $tree = 'role_id';
}
