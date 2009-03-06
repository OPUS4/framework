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
 * @copyright   Copyright (c) 2008, OPUS 4 development team
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
class Opus_Security_RealmTest extends PHPUnit_Framework_TestCase {

    /**
     * Tear down access control list.
     *
     * @return void
     */
    public function tearDown() {
        Opus_Security_Realm::getInstance()->setAcl(null);
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


    /**
     * Test if every permission is granted if no Acl is set up.
     *
     * @return void
     */
    public function testAllowAllWhenNoAcl() {
        $realm = Opus_Security_Realm::getInstance();
        $perm = $realm->isAllowed('whatever', 'everthing');
        $this->assertTrue($perm, 'Expected permission to be granted when no Acl is initialized.');
    }

    /**
     * Test if a privileg gets granted through the Realm.
     *
     * @return void
     */
    public function testIsAllowed() {
        $realm = Opus_Security_Realm::getInstance();
        $realm->setAcl(new Zend_Acl);
        $realm->getAcl()->add(new Zend_Acl_Resource('resource'));
        $realm->getAcl()->addRole(new Zend_Acl_Role('user'));
        $realm->setRole(new Zend_Acl_Role('user'));
        $realm->getAcl()->allow('user', 'resource', 'edit');
        
        $perm = $realm->isAllowed('edit', 'resource');
        $this->assertTrue($perm, 'Expected permission to be granted.');
    }

}
