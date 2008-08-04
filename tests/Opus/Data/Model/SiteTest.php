<?php
/**
 * Test suite for Site model.
 *
 * This file is part of OPUS. The software OPUS has been developed at the
 * University of Stuttgart with funding from the German Research Net
 * (Deutsches Forschungsnetz), the Federal Department of Higher Education and
 * Research (Bundesministerium fuer Bildung und Forschung) and The Ministry of
 * Science, Research and the Arts of the State of Baden-Wuerttemberg
 * (Ministerium fuer Wissenschaft, Forschung und Kunst des Landes
 * Baden-Wuerttemberg).
 *
 * PHP versions 4 and 5
 *
 * OPUS is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * OPUS is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OPUS; if not, write to the Free Software Foundation,
 * Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @category    Tests
 * @package     Opus_Application_Framework
 * @author      Ralf Claussnitzer <ralf.claussnitzer@slub-dresden.de>
 * @copyright   Universitaetsbibliothek Stuttgart, 1998-2008
 * @license     http://www.gnu.org/licenses/gpl.html
 * @version     $Id$
 */

/**
 * Test cases for Site entity.
 *
 * @package     Opus_Application_Framework
 * @subpackage  Data_Model
 *
 * @group       SiteTest
 */
class Opus_Data_Model_SiteTest extends PHPUnit_Framework_TestCase {

    /**
     * Ensure a clean database table.
     *
     * @return void
     */
    public function setUp() {
        TestHelper::clearTable('SITES');
    }

    /**
     * Test if an empty array of sites can be retrieved.
     *
     * @return void
     */
    public function testGetAllEmptyDatabase() {
        $sites = Opus_Data_Model_Site::getAll();
        $this->assertNotNull($sites);
        $this->assertEquals(array(),$sites, 'Returned array is not empty.');
    }

    /**
     * Test if a all Site entities can be retrieved.
     *
     * @return void
     */
    public function testGetAll() {
        $site1 = new Opus_Data_Model_Site();
        $site1->name = 'SLUBDD';
        $site1->fullName = 'Sächsische Staats- und Universitätsbibliothek';
        $site1->save();

        $site2 = new Opus_Data_Model_Site();
        $site2->name = 'UBB';
        $site2->fullName = 'Universitätsbibliothek Bielefeld';
        $site2->save();

        $sites = Opus_Data_Model_Site::getAll();
        $this->assertNotNull($sites);
        $this->assertEquals(2, count($sites), 'Wrong nuber of entries in returned.');


        // TODO Fix this ugly assertion. Use better equals() function.
        $cond = ($sites[$site1->name]->fullName === $site1->fullName)
            and ($sites[$site2->name]->fullName === $site2->fullName);

        $this->assertTrue($cond, 'Wrong entries returned');
    }


    /**
     * Test if a call on get() with invalid identifier returns null.
     *
     * @return void
     */
    public function testGetNonexistent() {
        $site = Opus_Data_Model_Site::get(4711);
        $this->assertNull($site, 'Get should return null when called with invalid identifier.');
    }

    /**
     * Test if adding and retrieving a Site entity works.
     *
     * @return void
     */
    public function testAddSite()
    {
        $name = 'SLUBDD';
        $fullName = 'Sächsische Staats- und Universitätsbibliothek';

        $site1 = new Opus_Data_Model_Site();
        $site1->name = $name;
        $site1->fullName = $fullName;
        $id = $site1->save();

        $this->assertGreaterThanOrEqual(1, $id, 'Invalid id returned.');

        $site2 = Opus_Data_Model_Site::get($id);

        $this->assertNotNull($site2, 'No Site entity returned.');
        $this->assertEquals($name, $site2->name, 'Fields "name" are not equal.');
        $this->assertEquals($fullName, $site2->fullName, 'Fields "fullName" are not equal.');
    }

    /**
     * Test if the same identifier is returned and set to the objects attributes
     * after saving.
     *
     * @return void
     */
    public function testGetIdAfterSave() {
        $name = 'SLUBDD';
        $fullName = 'Sächsische Staats- und Universitätsbibliothek';

        $site = new Opus_Data_Model_Site();
        $site->name = $name;
        $site->fullName = $fullName;
        $id = $site->save();

        $this->assertEquals($id, $site->getId(), 'Identifiers do not match.');
    }

    /**
     * Test updating an previosly stored Site entity.
     *
     * @return void
     */
    public function testUpdate() {
        $name = 'SLUBDD';
        $fullName = 'Sächsische Staats- und Universitätsbibliothek';

        $site = new Opus_Data_Model_Site();
        $site->name = $name;
        $site->fullName = $fullName;
        $id1 = $site->save();

        $site->name = $site->name . '2';
        $id2 = $site->save();

        $this->assertEquals($id1, $id2, 'Identifier must not change on update.');
    }

}
