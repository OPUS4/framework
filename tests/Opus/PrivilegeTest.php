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
 * @package     Opus
 * @author      Pascal-Nicolas Becker <becker@zib.de>
 * @copyright   Copyright (c) 2008-2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Test cases for Opus_Privilege.
 *
 * @package Opus
 * @category Tests
 * @group PrivilegeTests
 */
class Opus_PrivilegeTest extends TestCase {
    private $_roles;
    private $_serverStates;

    public function setUp() {
        $this->markTestIncomplete("TODO: Remove, since not supported any more.");

        parent::setUp();

        $this->_roles = array();
        for ($i=1; $i<=10; $i++) {
            $role = new Opus_Role();
            $role->setName('Role' . rand());
            $role->store();
            $this->_roles[] = $role;
        }

        $this->_serverStates = array('published','unpublished','deleted');
    }

    public function tearDown() {
        foreach ($this->_roles as $role) {
            $role->delete();
        }
        parent::tearDown();
    }

    protected function reloadRoles() {
        $ids = array();
        foreach ($this->_roles as $role) {
            $ids[] = $role->getId();
        }
        $this->_roles = array();
        foreach($ids as $id) {
            $this->_roles[] = new Opus_Role($id);
        }
    }

    protected function createFiles() {
        $path = '/tmp/opus4-test/' . uniqid() . '/src';
        mkdir($path, 0777, true);

        $files = array();
        for ($i=0; $i<5; $i++) {
            $filename = rand() . '.pdf';
            $filepath = $path . DIRECTORY_SEPARATOR . $filename;
            touch($filepath);

            $doc = new Opus_Document;
            $file = $doc->addFile();

            $file->setTempFile($filepath);
            $file->setPathName('copied-' . $filename);
            $file->setLabel('Volltextdokument (PDF)');

            $doc->store();

            $files [] = $file;
        }
        return $files;
    }

    /**
     * Tests storing and loading of privileges, that does not need futher attributes.
     */
    public function testStoreAndLoadSimplePrivileges() {
        $simple_privileges = array(
            'administrate',
            'clearance',
            'publish',
            'publishUnvalidated'
        );
        foreach($simple_privileges as $type) {
            foreach($this->_roles as $role) {
                $privilege = $role->addPrivilege();
                $privilege->setPrivilege($type);
                $role->store();
            }
        }

        // Reload the roles, so that the privileges has to be loaded out of db.
        $this->reloadRoles();

        foreach($this->_roles as $role) {
            $privileges = $role->getPrivilege();
            $types = array();
            foreach ($privileges as $privilege) {
                $types[] = $privilege->getPrivilege();
            }
            foreach($simple_privileges as $type) {
                $this->assertContains($type, $types,
                        'Can not load privously stored simple privilege.');
            }
        }
    }

    public function testStoreAndLoadReadMetadataPrivilege() {
        $type = 'readMetadata';
        for ($i=0; $i<10; $i++) {
            $role = $this->_roles[$i];
            $priv = $role->addPrivilege();
            $priv->setPrivilege($type);
            $priv->setDocumentServerState($this->_serverStates[$i%3]);
            $role->store();
        }
        // Reload the roles, so that the privileges has to be loaded out of db.
        $this->reloadRoles();
        for ($i=0; $i<10; $i++) {
            $priv = $this->_roles[$i]->getPrivilege();
            $this->assertEquals(1, count($priv),
                    'Stored one privilege. Loaded more or less privileges!');
            $priv = $priv[0];
            $this->assertEquals($type, $priv->getPrivilege(), 
                    'Loaded another privlege then aspected.');
            $this->assertEquals($this->_serverStates[$i%3],
                    $priv->getDocumentServerState(),
                    'Loaded another document server state then we stored!'
            );
        }
    }

    public function testStoreAndLoadReadFilePrivilegeByFileId() {
        $files = $this->createFiles();

        $type = 'readFile';
        for ($i=0; $i<10; $i++) {
            $role = $this->_roles[$i];
            $priv = $role->addPrivilege();
            $priv->setPrivilege($type);
            $priv->setFileId($files[$i % count($files)]->getId());
            $role->store();
        }
        // Reload the roles, so that the privileges has to be loaded out of db.
        $this->reloadRoles();
        for ($i=0; $i<10; $i++) {
            $priv = $this->_roles[$i]->getPrivilege();
            $this->assertEquals(1, count($priv),
                    'Stored one privilege. Loaded more or less privileges!');
            $priv = $priv[0];
            $this->assertEquals($type, $priv->getPrivilege(),
                    'Loaded another privlege then aspected.');
            $this->assertEquals($files[$i%5]->getId(), 
                    $priv->getFileId(),
                    'Loaded another file id state then we stored!');
        }
    }

    public function testStoreAndLoadReadFilePrivilegeByFile() {
        $files = $this->createFiles();

        $type = 'readFile';
        for ($i=0; $i<10; $i++) {
            $role = $this->_roles[$i];
            $priv = $role->addPrivilege();
            $priv->setPrivilege($type);
            $priv->setFile($files[$i % count($files)]);
            $role->store();
        }
        // Reload the roles, so that the privileges has to be loaded out of db.
        $this->reloadRoles();
        for ($i=0; $i<10; $i++) {
            $priv = $this->_roles[$i]->getPrivilege();
            $this->assertEquals(1, count($priv),
                    'Stored one privilege. Loaded more or less privileges!');
            $priv = $priv[0];
            $this->assertEquals($type, $priv->getPrivilege(), 
                    'Loaded another privlege then aspected.');
            $this->assertEquals($files[$i%5]->getPathName(),
                    $priv->getFile()->getPathName(),
                    'Loaded another file state then we stored!'
            );
        }
    }

    public function testLoadPrivilegesByFileId() {
        // create Files
        $files = $this->createFiles();
        $privilegeIds_by_file = array();
        $roles_by_file = array();
        foreach ($files as $file) {
            $privilegeIds_by_file[$file->getPathName()] = array();
            $roles_by_file[$file->getPathName()] = array();
        }

        // create and store Privileges
        $type = 'readFile';

        $r = 0;
        // store for each file to roles that can access the file.
        foreach($files as $file) {
            for ($i=0; $i<=1 && $r<count($this->_roles); $i++) {
                $role = $this->_roles[$r++];
                $priv = $role->addPrivilege();
                $priv->setPrivilege($type);
                $priv->setFile($file);
                $role->store();

                // save privilege by file.
                $privilegeIds_by_file[$file->getPathName()] =
                        array_merge($privilegeIds_by_file[$file->getPathName()], array($priv));
                // save role names by file
                $roles_by_file[$file->getPathName()] =
                        array_merge($roles_by_file[$file->getPathName()], array($role->getName()));
            }
        }

        // reload
        $this->reloadRoles();

        // check each file
        foreach ($files as $file) {
            // load privilege by File
            $privilegeIds = Opus_Privilege::fetchPrivilegeIdsByFile($file);

            // check number of privileges
            $this->assertEquals(
                    count($privilegeIds_by_file[$file->getPathName()]),
                    count($privilegeIds),
                    'Loaded different number of privileges by file then stored!'
            );

            // check each privilege
            foreach ($privilegeIds as $id) {
                $priv = new Opus_Privilege($id);
                // check type
                $this->assertEquals($type, $priv->getPrivilege(), 
                        'Expected to load readFile privileges only!');
                // check File
                $this->assertEquals($file->getPathName(),
                        $priv->getFile()->getPathName(),
                        'Got privilege for another file!'
                );

                $role = new Opus_Role($priv->getParentId());
                // check Role
                $this->assertContains($role->getName(),
                        $roles_by_file[$file->getPathName()],
                        'Loaded a readFile privilege for a role that should not be allowed to read this file!'
                );
            }
        }

    }

}