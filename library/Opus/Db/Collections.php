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
 * @category	Framework
 * @package	Opus_Collections
 * @author     	Thoralf Klein <thoralf.klein@zib.de>
 * @copyright  	Copyright (c) 2010, OPUS 4 development team
 * @license    	http://www.gnu.org/licenses/gpl.html General Public License
 * @version    	$Id$
 */

/**
 * Table gateway class to table 'collections'.
 *
 * @category    Framework
 * @package     Opus_Db
 *
 */

class Opus_Db_Collections extends Opus_Db_NestedSet {

    /**
     * Table name of the nested set table.
     *
     * @var string
     */
    protected $_name = 'collections';

    /**
     * Table column holding the left-id for the nested set structure.
     *
     * @var string
     */
    protected $_left   = 'left_id';

    /**
     * Table column holding the right-id for the nested set structure.
     *
     * @var string
     */
    protected $_right  = 'right_id';

    /**
     * Table column holding the parent-id for the structure.  This actually is
     * more than a nested set structure, but we need this for fast retrieval of
     * one nodes' children.
     *
     * @var string
     */
    protected $_parent = 'parent_id';

    /**
     * Table column holding the tree-id for the structure.  We're holding more
     * than one nested-set structure in the table and we're distinguishing the
     * different trees by this ID.
     *
     * @var string
     */
    protected $_tree   = 'role_id';


    /**
     * Map foreign keys in this table to the column in the table they originate
     * from
     *
     * @var array $_referenceMap
     */
    protected $_referenceMap = array(
            'Role' => array(
                            'columns' => 'role_id',
                            'refTableClass' => 'Opus_Db_CollectionsRoles',
                            'refColumns' => 'id',
            ),
            'Parent' => array(
                            'columns' => 'parent_id',
                            'refTableClass' => 'Opus_Db_Collections',
                            'refColumns' => 'id',
            ),
    );


    /**
     * All dependant Tables,
     * i.e. those that contain our id as a foreign key.
     *
     * @var array $_dependantTables
     */
    protected $_dependentTables = array(
            'Opus_Db_Collections',
    );

}
