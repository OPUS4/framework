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
 * @category    Tests
 * @package     Opus_Security
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @copyright   Copyright (c) 2008-2011, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Test for Opus_Security_Realm.
 *
 * @package Opus_Security
 * @category Tests
 *
 * @group RealmTest
 */
class Opus_Security_RealmTest extends TestCase {

    protected $_config_backup = null;

    protected function setUp() {
        $this->_config_backup = Zend_Registry::get('Zend_Config');
        parent::setUp();
    }

    protected function tearDown() {
        parent::tearDown();
        Zend_Registry::set('Zend_Config', $this->_config_backup);
    }

    private function setUpUserAdmin() {
        // create role
        $rol = Opus_Db_TableGateway::getInstance('Opus_Db_UserRoles');
        $rolId = $rol->insert(array('name' => 'administrator'));

        // create account
        $acc = Opus_Db_TableGateway::getInstance('Opus_Db_Accounts');
        $accId = $acc->insert(array('login' => 'admin', 'password' => md5('adminadmin')));

        // connect role and account
        $lar = Opus_Db_TableGateway::getInstance('Opus_Db_LinkAccountsRoles');
        $lar->insert(array('account_id' => $accId, 'role_id' => $rolId));
    }

    private function setUpUserUser() {
        // create role
        $rol = Opus_Db_TableGateway::getInstance('Opus_Db_UserRoles');
        $rolId = $rol->insert(array('name' => 'userrole'));

        // connect role and module
        $lar = Opus_Db_TableGateway::getInstance('Opus_Db_AccessModules');
        $lar->insert(array('role_id' => $rolId, 'module_name' => 'admin'));


        // create account
        $acc = Opus_Db_TableGateway::getInstance('Opus_Db_Accounts');
        $accId = $acc->insert(array('login' => 'user', 'password' => md5('useruser')));

        // connect role and account
        $lar = Opus_Db_TableGateway::getInstance('Opus_Db_LinkAccountsRoles');
        $lar->insert(array('account_id' => $accId, 'role_id' => $rolId));
    }

    private function setUpIp() {
        // create role
        $rol = Opus_Db_TableGateway::getInstance('Opus_Db_UserRoles');
        $rolId = $rol->insert(array('name' => 'iprole'));

        // connect role and module
        $lar = Opus_Db_TableGateway::getInstance('Opus_Db_AccessModules');
        $lar->insert(array('role_id' => $rolId, 'module_name' => 'oai'));


        // create ip
        $acc = Opus_Db_TableGateway::getInstance('Opus_Db_Ipranges');
        $ipFrom = ip2long('127.0.0.1');
        $ipTo = ip2long('127.0.0.42');
        $ipId = $acc->insert(array('startingip' => $ipFrom, 'endingip' => $ipTo));

        // connect role and account
        $lir = Opus_Db_TableGateway::getInstance('Opus_Db_LinkIprangesRoles');
        $lir->insert(array('iprange_id' => $ipId, 'role_id' => $rolId));
    }

    private function setUpDocument($rolId) {
        // document
        $doc = Opus_Db_TableGateway::getInstance('Opus_Db_Documents');
        $docId = $doc->insert(array());

        // connect document and role
        $ad = Opus_Db_TableGateway::getInstance('Opus_Db_AccessDocuments');
        $adId = $ad->insert(array('document_id' => $docId, 'role_id' => $rolId));

        return $docId;
    }

    private function setUpFile($rolId) {
        $docId = $this->setUpDocument($rolId);

        // file
        $file = Opus_Db_TableGateway::getInstance('Opus_Db_DocumentFiles');
        $fileId = $file->insert(array('document_id' => $docId));

        // connect file and role
        $af = Opus_Db_TableGateway::getInstance('Opus_Db_AccessFiles');
        $afId = $af->insert(array('file_id' => $fileId, 'role_id' => $rolId));

        return $fileId;
    }

    /**
     * Test getting singleton instance.
     *
     * @return void
     */
    public function testGetInstance() {
        $realm = Opus_Security_Realm::getInstance();
        $this->assertNotNull($realm, 'Expected instance');
        $this->assertType('Opus_Security_Realm', $realm, 'Expected object of type Opus_Security_Realm.');
    }

    public function testSetUserSuccess() {
        $this->setUpUserUser();

        $realm = Opus_Security_Realm::getInstance();
        $realm->setUser('user');
        $realm->setUser(null);
        $realm->setUser('');
    }

    public function testSetUserFailsOnUnknownUser() {
        $realm = Opus_Security_Realm::getInstance();

        $this->setExpectedException('Opus_Security_Exception');
        $realm->setUser('userbla');
    }

    public function testSetUserFailsOnUnknownUserAndResetsRoles() {
        $this->setUpUserUser();

        // OAI permitted for given IP
        $realm = Opus_Security_Realm::getInstance();
        $realm->setUser('user');
        $realm->setIp('');
        $this->assertTrue($realm->checkModule('admin'),
                'Expect successful admin-access by user.');

        // Try to set invalid IP address
        try {
            $realm->setUser('userbla');
            $this->fail('Expecting. security exception.');
        }
        catch (Opus_Security_Exception $e) {
            $this->assertFalse($realm->checkModule('admin'),
                'Expect denied admin-access after failed setUser().');
        }
    }

    public function testSetIpSuccess() {
        $this->setUpIp();

        $realm = Opus_Security_Realm::getInstance();
        $realm->setIp('1.1.1.1');
        $realm->setIp('127.0.0.1');
        $realm->setIp('127.0.0.23');
        $realm->setIp('127.0.0.42');
        $realm->setIp('255.255.255.255');
        $realm->setIp(null);
        $realm->setIp('');
    }

    public function testSetIpFailsOnInvalidIp() {
        $realm = Opus_Security_Realm::getInstance();

        $this->setExpectedException('Opus_Security_Exception');
        $realm->setIp('12.7.0.0.1');
    }

    public function testSetIpFailsOnInvalidIpAndResetsRoles() {
        $this->setUpIp();

        // OAI permitted for given IP
        $realm = Opus_Security_Realm::getInstance();
        $realm->setUser('');
        $realm->setIp('127.0.0.22');
        $this->assertTrue($realm->checkModule('oai'),
                'Expect successful oai-access by IP.');

        // Try to set invalid IP address
        try {
            $realm->setIp('12.7.0.0.1');
            $this->fail('Expecting. security exception.');
        }
        catch (Opus_Security_Exception $e) {
            $this->assertFalse($realm->checkModule('oai'),
                'Expect denied oai-access after failed setIp().');
        }
    }

    /**
     * checkModule()
     */
    public function testcheckModuleForUser() {
        $this->setUpUserUser();

        $realm = Opus_Security_Realm::getInstance();
        $realm->setUser('user');
        $realm->setIp('');

        $this->assertTrue($realm->checkModule('admin'),
                'Expect successful admin-access by user.');
        $this->assertFalse($realm->checkModule('oai'),
                'Expect failed oai-access by user.');

        $this->assertFalse($realm->checkModule('foobar'),
                'Expect failed foobar-access by user.');
        $this->assertFalse($realm->checkModule(''),
                'Expect failed empty module.');
    }

    public function testcheckModuleForIp() {
        $this->setUpIp();

        $realm = Opus_Security_Realm::getInstance();
        $realm->setUser('');
        $realm->setIp('127.0.0.22');

        $this->assertFalse($realm->checkModule('admin'),
                'Expect failed admin-access by IP.');
        $this->assertTrue($realm->checkModule('oai'),
                'Expect successful oai-access by IP.');

        $this->assertFalse($realm->checkModule('foobar'),
                'Expect failed foobar-access by IP.');
        $this->assertFalse($realm->checkModule(''),
                'Expect failed empty module.');
    }

    public function testcheckModuleForUserAndIp() {
        $this->setUpUserUser();
        $this->setUpIp();

        $realm = Opus_Security_Realm::getInstance();
        $realm->setUser('user');
        $realm->setIp('127.0.0.22');

        $this->assertTrue($realm->checkModule('admin'),
                'Expect successful admin-access by user-ip.');
        $this->assertTrue($realm->checkModule('oai'),
                'Expect successful oai-access by user-ip.');

        $this->assertFalse($realm->checkModule('foobar'),
                'Expect failed foobar-access by user.');
        $this->assertFalse($realm->checkModule(''),
                'Expect failed empty module.');
    }

    public function testcheckModuleForDisabledSecurity() {
        $config = new Zend_Config(array(
            'security' => '0',
        ));
        Zend_Registry::set('Zend_Config', $config);

        $this->setUpUserUser();

        $realm = Opus_Security_Realm::getInstance();
        $realm->setUser('user');
        $realm->setIp('');

        $this->assertTrue($realm->checkModule('admin'),
                'Expect successful admin-access by admin.');
        $this->assertTrue($realm->checkModule('oai'),
                'Expect successful oai-access by admin.');

        $this->assertTrue($realm->checkModule('foobar'),
                'Expect successful foobar-access by admin.');
        $this->assertTrue($realm->checkModule(''),
                'Expect successful empty module.');
    }

    public function testcheckModuleForAdmin() {
        $this->setUpUserAdmin();

        $realm = Opus_Security_Realm::getInstance();
        $realm->setUser('admin');
        $realm->setIp('');

        $this->assertTrue($realm->checkModule('admin'),
                'Expect successful admin-access by admin.');
        $this->assertTrue($realm->checkModule('oai'),
                'Expect successful oai-access by admin.');

        $this->assertTrue($realm->checkModule('foobar'),
                'Expect successful foobar-access by admin.');
        $this->assertTrue($realm->checkModule(''),
                'Expect successful empty module.');
    }

    /**
     * checkDocument()
     */
    public function testCheckDocumentForUser() {
        $this->setUpUserUser();
        $docId = $this->setUpDocument(1);

        $realm = Opus_Security_Realm::getInstance();
        $realm->setUser('user');
        $realm->setIp('');

        $this->assertFalse($realm->checkDocument(),
                'Expect failed document check by user for missing document-id.');
        $this->assertFalse($realm->checkDocument(''),
                'Expect failed document check by user for empty document-id.');
        $this->assertFalse($realm->checkDocument(null),
                'Expect failed document check by user for NULL document-id.');

        $this->assertTrue($realm->checkDocument($docId),
                'Expect successfull document check by user for valid document-id.');
        $this->assertFalse($realm->checkDocument(100),
                'Expect failed document check by user for unknown document-id.');
    }

    public function testCheckDocumentForAdmin() {
        $this->setUpUserAdmin();
        $docId = $this->setUpDocument(1);

        $realm = Opus_Security_Realm::getInstance();
        $realm->setUser('admin');
        $realm->setIp('');

        $this->assertTrue($realm->checkDocument($docId),
                'Expect successfull document check by user for valid document-id.');
        $this->assertTrue($realm->checkDocument(100),
                'Expect successful document check by admin for unknown document-id.');
    }

    /**
     * checkFile()
     */
    public function testCheckFileForUser() {
        $this->setUpUserUser();
        $fileId = $this->setUpFile(1);

        $realm = Opus_Security_Realm::getInstance();
        $realm->setUser('user');
        $realm->setIp('');

        $this->assertFalse($realm->checkFile(),
                'Expect failed file check by user for missing file-id.');
        $this->assertFalse($realm->checkFile(''),
                'Expect failed file check by user for empty file-id.');
        $this->assertFalse($realm->checkFile(null),
                'Expect failed file check by user for NULL file-id.');

        $this->assertTrue($realm->checkFile($fileId),
                'Expect failed file ckeck by user for unknown file-id.');
        $this->assertFalse($realm->checkFile(100),
                'Expect failed file ckeck by user for unknown file-id.');
    }

    public function testCheckFileForAdmin() {
        $this->setUpUserAdmin();
        $fileId = $this->setUpFile(1);

        $realm = Opus_Security_Realm::getInstance();
        $realm->setUser('admin');
        $realm->setIp('');

        $this->assertTrue($realm->checkFile($fileId),
                'Expect successful file ckeck by user for valid file-id.');
        $this->assertTrue($realm->checkFile(100),
                'Expect successful file ckeck by user for unknown file-id.');
    }

    public function testGetRolesForUnknown() {
        $realm = Opus_Security_Realm::getInstance();
        $realm->setUser(''); // TODO otherwise also gets role 'administrator'

        $roles = $realm->getRoles();

        var_dump($roles);

        $this->assertEquals(1, count($roles));
        $this->assertContains('guest', $roles);
    }

    public function testGetRolesForUser() {
        $this->setUpUserUser();
        $realm = Opus_Security_Realm::getInstance();
        $realm->setUser('user');
        $realm->setIp('');

        $roles = $realm->getRoles();

        $this->assertEquals(2, count($roles));
        $this->assertContains('userrole', $roles);
        $this->assertContains('guest', $roles);
    }

    public function testGetRolesForAdmin() {
        $this->setUpUserAdmin();
        $realm = Opus_Security_Realm::getInstance();
        $realm->setUser('admin');
        $realm->setIp('');

        $roles = $realm->getRoles();

        $this->assertEquals(2, count($roles));
        $this->assertContains('administrator', $roles);
        $this->assertContains('guest', $roles);
    }

    public function testGetRolesForUserAndIp() {
        $this->setUpUserUser();
        $this->setUpIp();
        $realm = Opus_Security_Realm::getInstance();
        $realm->setUser('user');
        $realm->setIp('127.0.0.22');

        $roles = $realm->getRoles();

        $this->assertEquals(3, count($roles));
        $this->assertContains('userrole', $roles);
        $this->assertContains('guest', $roles);
        $this->assertContains('iprole', $roles);
    }

}
