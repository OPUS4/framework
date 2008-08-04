<?php
/**
 * Test suite for Account model.
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
 * @group       AccountTest
 *
 */
class Opus_Data_Model_AccountTest extends PHPUnit_Framework_TestCase {

    /**
     * Ensure clean database tables.
     *
     * @return void
     */
    public function setUp() {
        TestHelper::clearTable('ACCOUNTS');
        TestHelper::clearTable('SITES');
    }


    /**
     * Test if an empty array of sites can be retrieved.
     *
     * @return void
     */
    public function testGetAllEmptyDatabase() {
        $accounts = Opus_Data_Model_Account::getAll();
        $this->assertNotNull($accounts);
        $this->assertEquals(array(),$accounts, 'Returned array is not empty.');
    }

    /**
     * Test if all added entities can be retrieved.
     *
     * @return void
     */
    public function testGetAll() {
        $site = new Opus_Data_Model_Site();
        $site->name = 'SLUB';
        $site->fullName = 'Sächsische Staats- und Universitätsbibliothek';
        $site->save();

        $accountData = array(
        array('John', 'secret'),
        array('José', 'secreto')
        );

        foreach ($accountData as $item) {
            $account = new Opus_Data_Model_Account();
            $account->username = $item[0];
            $account->password = $item[1];
            $account->site     = $site;
            $account->save();
        }

        $accounts = Opus_Data_Model_Account::getAll();
        $this->assertNotNull($accounts);
        $this->assertEquals(count($accountData), count($accounts), 'Wrong number of entities returned.');

        // TODO Fix this ugly assertion. Use better equals() function.
        $cond = ($accounts[$accountData[0][0]]->username === $accountData[0][0])
        and ($accounts[$accountData[0][0]]->password === $accountData[0][1])
        and ($accounts[$accountData[1][0]]->username === $accountData[1][0])
        and ($accounts[$accountData[1][0]]->password === $accountData[1][1]);

        $this->assertTrue($cond, 'Wrong entries returned');
    }

    /**
     * Test if a call on get() with invalid identifier returns null.
     *
     * @return void
     */
    public function testGetNonexistent() {
        $account = Opus_Data_Model_Account::get(4711);
        $this->assertNull($account, 'Get should return null when called with invalid identifier.');
    }

    /**
     * Creating a new account and retrieving its details.
     *
     * @return void
     */
    public function testAddAccount() {
        $site = new Opus_Data_Model_Site();
        $site->name = 'SLUB';
        $site->fullName = 'Sächsische Staats- und Universitätsbibliothek';
        $site->save();

        $account = new Opus_Data_Model_Account();
        $account->username = 'John';
        $account->password = 'secret';
        $account->site     = $site;
        $id = $account->save();

        $this->assertGreaterThanOrEqual(1, $id, 'Invalid id returned.');

        $result = Opus_Data_Model_Account::get($id);
        $this->assertNotNull($result, 'No Account entity returned.');
        $this->assertEquals('John', $result->username, 'Fields "username" are not equal.');
        $this->assertEquals('secret', $result->password, 'Fields "password" are not equal.');
        $this->assertEquals($site->getId(), $result->site, 'Wrong Site assigned.');
    }

    /**
     * Test if the same identifier is returned and set to the objects attributes
     * after saving.
     *
     * @return void
     */
    public function testGetIdAfterSave() {
        $site = new Opus_Data_Model_Site();
        $site->name = 'SLUB';
        $site->fullName = 'Sächsische Staats- und Universitätsbibliothek';
        $site->save();

        $account = new Opus_Data_Model_Account();
        $account->username = 'John';
        $account->password = 'secret';
        $account->site     = $site;
        $id = $account->save();

        $this->assertEquals($id, $account->getId(), 'Identifiers do not match.');
    }


    /**
     * Test updating an previosly stored Account entity.
     *
     * @return void
     */
    public function testUpdate() {
        $site = new Opus_Data_Model_Site();
        $site->name = 'SLUB';
        $site->fullName = 'Sächsische Staats- und Universitätsbibliothek';
        $site->save();

        $account = new Opus_Data_Model_Account();
        $account->username = 'John';
        $account->password = 'secret';
        $account->site     = $site->getId();
        $id1 = $account->save();

        $account->password = 'otherother';
        $id2 = $account->save();

        $this->assertEquals($id1, $id2, 'Identifier must not change on update.');
    }


    /**
     * Test behavior of Account::isUserFromSite().
     *
     * @return void
     */
    public function testIsUserFromSite() {
        $site = new Opus_Data_Model_Site();
        $site->name = 'SLUB';
        $site->fullName = 'Sächsische Staats- und Universitätsbibliothek';
        $siteId = $site->save();

        $account = new Opus_Data_Model_Account();
        $account->username = 'John';
        $account->password = 'secret';
        $account->site     = $siteId;
        $accountId = $account->save();

        $this->assertTrue(Opus_Data_Model_Account::isUserFromSite($account->username, $siteId),
            'Account has not been assigned to Site correctly.');
    }
}