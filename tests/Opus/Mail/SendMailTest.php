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
 * @package     Opus_Mail
 * @author      Eva Kranz <s9evkran@stud.uni-saarland.de>
 * @copyright   Copyright (c) 2009, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Test cases for class Opus_Mail.
 *
 * @category Tests
 * @package  Opus_Mail
 *
 * @group    MailSendMailTest
 */
class Opus_Mail_SendMailTest extends PHPUnit_Framework_TestCase {

    /**
     * Holds a syntactically correct sender e-mail address.
     *
     * @var string
     */
    protected $_addressSender = 'sender@testmail.de';

    /**
     * Holds a syntactically correct recipient e-mail address.
     *
     * @var string
     */
    protected $_addressRecipient = 'recipient@testmail.de';

    /**
     * Holds a sender name
     *
     * @var string
     */
    protected $_nameSender = 'John S. Public';

    /**
     * Holds a recipient name
     *
     * @var string
     */
    protected $_nameRecipient = 'John R. Public';

    /**
     * Holds a recipient (address and name)
     *
     * @var array
     */
    protected $_recipient = array('recipients' => array('address' => 'recipient@testmail.de', 'name' => 'John R. Public'));

    /**
     * Holds a subject
     *
     * @var string
     */
    protected $_subject = 'My subject';

    /**
     * Holds a text
     *
     * @var string
     */
    protected $_text = 'Lorem ipsum dolor sit amet, consectetuer ad.';

    /**
     * Holds a mail object
     *
     * @var OPUS_MAIL
     */
    protected $_mail = null;

    /**
     * Set up test fixtures.
     *
     * @return void
     */
    public function setUp() {
        $this->_mail = new Opus_Mail_SendMail();
    }

    /**
     * Tests the setter for a mail text.
     *
     * @return void
     */
    public function testSetBodyText() {
        $this->_mail->setBodyText($this->_text);
        $this->assertEquals($this->_mail->getBodyText(), $this->_text);
    }

    /**
     * Tests the setter for a subject.
     *
     * @return void
     */
    public function testSetSubject() {
        $this->_mail->setSubject($this->_subject);
        $this->assertEquals($this->_mail->getSubject(), $this->_subject);
    }

    /**
     * Tests the setter for a sender address.
     *
     * @return void
     */
    public function testSetFrom() {
        $this->_mail->setFrom($this->_addressSender);
        $this->assertEquals($this->_mail->getFrom(), $this->_addressSender);
    }

    /**
     * Tests the setter for a sender name.
     *
     * @return void
     */
    public function testSetFromName() {
        $this->_mail->setFromName($this->_nameSender);
        $this->assertEquals($this->_mail->getFromName(), $this->_nameSender);
    }

    /**
     * Tests the setter for recipients.
     *
     * @return void
     */
    public function testSetRecipients() {
        $this->_mail->setRecipients($this->_recipient);
        $this->assertEquals($this->_mail->getRecipients(), $this->_recipient);
    }

    /**
     * Tests the sending of an e-mail to an author
     *
     * @return void
     */
    public function testSendMailToAuthor() {
        $this->assertTrue($this->sendMailToAuthor(1, $this->_subject, $this->_text, $this->_addressSender, $this->_nameSender));
        $this->assertTrue($this->sendMailToAuthor(1, $this->_subject, $this->_text));
        $this->assertTrue($this->sendMailToAuthor(array(1), $this->_subject, $this->_text, $this->_addressSender, $this->_nameSender));
    }
}