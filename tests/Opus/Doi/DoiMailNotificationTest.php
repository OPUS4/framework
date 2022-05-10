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
 * @copyright   Copyright (c) 2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * @category    Tests
 * @package     Opus\Doi
 * @author      Sascha Szott <szott@zib.de>
 * @author      Jens Schwidder <schwidder@zib.de>
 */

namespace OpusTest\Doi;

use Opus\Common\Config;
use Opus\Common\Model\ModelException;
use Opus\Document;
use Opus\Doi\ConfigRecipientProvider;
use Opus\Doi\DoiMailNotification;
use Opus\Identifier;
use OpusTest\TestAsset\TestCase;
use Zend_Config;

/**
 * TODO this test class sends actual emails - that might cause problems with system/network administrator
 */
class DoiMailNotificationTest extends TestCase
{
    private $doiMailNotification;

    public function setUp()
    {
        parent::setUp();

        $this->doiMailNotification = new DoiMailNotification();

        // use recipients configured in INI files for testing
        $this->doiMailNotification->setRecipientProvider(new ConfigRecipientProvider());
    }

    public function testConstructMissingConfig()
    {
        $notification = $this->doiMailNotification;
        $this->assertFalse($notification->isEnabled());
    }

    public function testConstructPartialConfig1()
    {
        $this->adaptDoiConfiguration([
            'notificationEmailEnabled' => self::CONFIG_VALUE_FALSE,
            'notificationEmail'        => ['doe@localhost'],
        ]);
        $notification = $this->doiMailNotification;
        $this->assertFalse($notification->isEnabled());
    }

    public function testConstructPartialConfig2()
    {
        $this->adaptDoiConfiguration([
            'notificationEmailEnabled' => self::CONFIG_VALUE_FALSE,
            'notificationEmail'        => ['doe@localhost'],
        ]);
        $notification = $this->doiMailNotification;
        $this->assertFalse($notification->isEnabled());
    }

    public function testConstructPartialConfig3()
    {
        $this->adaptDoiConfiguration([
            'notificationEmailEnabled' => self::CONFIG_VALUE_TRUE,
            'notificationEmail'        => ['doe@localhost'],
        ]);
        $notification = $this->doiMailNotification;
        $this->assertTrue($notification->isEnabled());
    }

    public function testConstructPartialConfig4()
    {
        $this->adaptDoiConfiguration([
            'notificationEmailEnabled' => self::CONFIG_VALUE_TRUE,
            'notificationEmail'        => ['doe@localhost'],
        ]);
        $notification = $this->doiMailNotification;
        $this->assertTrue($notification->isEnabled());
    }

    public function testConstructPartialConfig5()
    {
        $this->adaptDoiConfiguration([
            'notificationEmailEnabled' => self::CONFIG_VALUE_TRUE,
        ]);
        $notification = $this->doiMailNotification;
        $this->assertFalse($notification->isEnabled());
    }

    public function testConstructPartialConfig6()
    {
        $this->adaptDoiConfiguration([
            'notificationEmailEnabled' => self::CONFIG_VALUE_TRUE,
        ]);
        $notification = $this->doiMailNotification;
        $this->assertFalse($notification->isEnabled());
    }

    public function testSendMailEmpty()
    {
        $this->adaptDoiConfiguration([
            'notificationEmailEnabled' => self::CONFIG_VALUE_TRUE,
            'notificationEmail'        => ['doe@localhost'],
        ]);
        $notification = $this->doiMailNotification;
        $notification->sendRegistrationEmail();
    }

    public function testSendMailSingle()
    {
        Config::get()->merge(new Zend_Config([
            'url' => 'http://localhost/opus4',
        ]));

        $this->adaptDoiConfiguration([
            'notificationEmailEnabled' => self::CONFIG_VALUE_TRUE,
            'notificationEmail'        => ['doe@localhost'],
        ]);

        $docId = $this->createTestDocWithDoi('10.2345/opustest-999');

        $notification = $this->doiMailNotification;
        $notification->addNotification($docId, $this->getDoi($docId), 'error');
        $notification->sendRegistrationEmail();
    }

    public function testSendMailMultiple()
    {
        Config::get()->merge(new Zend_Config([
            'url' => 'http://localhost/opus4',
        ]));

        $this->adaptDoiConfiguration([
            'notificationEmailEnabled' => self::CONFIG_VALUE_TRUE,
            'notificationEmail'        => ['doe@localhost'],
        ]);

        $doc1Id = $this->createTestDocWithDoi('10.2345/opustest-888');
        $doc2Id = $this->createTestDocWithDoi('10.2345/opustest-999');

        $notification = $this->doiMailNotification;
        $notification->addNotification(
            '888',
            $this->getDoi($doc1Id),
            "http://localhost/opus4/$doc1Id",
            'error'
        );
        $notification->addNotification('999', $this->getDoi($doc2Id), "http://localhost/opus4/$doc2Id");
        $notification->sendRegistrationEmail();
    }

    /**
     * @param Zend_Config $doiConfig
     */
    private function adaptDoiConfiguration($doiConfig)
    {
        Config::get()->merge(new Zend_Config(['doi' => $doiConfig]));
    }

    /**
     * @param string $doiValue
     * @return int
     * @throws ModelException
     */
    private function createTestDocWithDoi($doiValue)
    {
        $doc = new Document();

        $doi = new Identifier();
        $doi->setType('doi');
        $doi->setValue($doiValue);
        $doc->setIdentifier([$doi]);

        return $doc->store();
    }

    /**
     * @param int $docId
     * @return Identifier
     * @throws ModelException
     */
    private function getDoi($docId)
    {
        $doc  = new Document($docId);
        $dois = $doc->getIdentifier();
        return $dois[0];
    }
}
