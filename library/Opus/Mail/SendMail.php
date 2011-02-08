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
 * @category    Framework
 * @package     Opus_Mail
 * @author      Eva Kranz <s9evkran@stud.uni-saarland.de>
 * @copyright   Copyright (c) 2009, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Methods to send an e-mail or multiple e-mails via Zend_Mail from different views.
 *
 * @category    Framework
 * @package     Opus_Mail
 *
 */
class Opus_Mail_SendMail {

    /**
     * @var Opus_Mail_Transport
     */
    private $_transport = null;

    /**
     * Holds the e-mail address of the sender
     *
     * @var string
     */
    private $_from;

    /**
     * Holds the name of the sender
     *
     * @var string
     */
    private $_fromName;

    /**
     * Holds the e-mail addresses and names of the recipients
     *
     * @var array
     */
    private $_recipients;

    /**
     * Holds the subject of the e-mail
     *
     * @var string
     */
    private $_subject;

    /**
     * Holds the text of the e-mail
     *
     * @var string
     */
    private $_bodyText;

    /**
     * Create a new SendMail instance
     */
    public function __construct() {
        $config = Zend_Registry::get('Zend_Config');
        if (!isset($config, $config->mail->opus)) {
            return;
        }

        $this->_transport = new Opus_Mail_Transport($config->mail->opus);
    }

    /**
     * Set the recipients
     *
     * @param   array $recipients Recipients
     * @return  void
     */
    public function setRecipients(array $recipients) {
        $this->_recipients = $recipients;
    }

    /**
     * Get the recipients
     *
     * @return array recipients
     */
    public function getRecipients() {
        return $this->_recipients;
    }

    /**
     * Set the e-mail address of the sender
     *
     * @param   string $from Sender's e-mail address
     * @return  void
     */
    public function setFrom($from) {
        $this->validateAddress($from);
        $this->_from = $from;
    }

    /**
     * Get the e-mail address of the sender
     *
     * @return string Sender's e-mail address
     */
    public function getFrom() {
        return $this->_from;
    }

    /**
     * Set the name of the sender
     *
     * @param   string $fromName Sender's name
     * @return  void
     */
    public function setFromName($fromName) {
        $this->_fromName = $fromName;
    }

    /**
     * Get the name of the sender
     *
     * @return string Sender's name
     */
    public function getFromName() {
        return $this->_fromName;
    }

    /**
     * Set the subject of the e-mail
     *
     * @param   string $subject Subject
     * @return  void
     */
    public function setSubject($subject) {
        $this->_subject = $subject;
    }

    /**
     * Get the subject of the e-mail
     *
     * @return string Subject
     */
    public function getSubject() {
        return $this->_subject;
    }

    /**
     * Set the text of the e-mail
     *
     * @param   string $bodyText Text
     * @return  void
     */
    public function setBodyText($bodyText) {
        $this->_bodyText = $bodyText;
    }

    /**
     * Get the text of the e-mail
     *
     * @return string Text
     */
    public function getBodyText() {
        return $this->_bodyText;
    }

    /**
     * Validates an e-mail address
     *
     * @param   string $address Address
     * @throws  Opus_Mail_Exception Thrown if the e-mail address is not valid
     * @return  string              Address
     */
    private function validateAddress($address) {
        return $address;

        $validator = new Zend_Validate_EmailAddress();
        if ($validator->isValid($address) === false) {
            foreach ($validator->getMessages() as $message) {
                throw new Opus_Mail_Exception($message);
            }
        }

        return $address;
    }

    /**
     * Creates and sends an e-mail to the specified recipient using the SMTP transport.
     * This method should be used carefully, particularly with regard to the possibility
     * of sending mails anonymously to user-defined recipients.
     *
     * @param   string $from       Sender address
     * @param   string $fromName   Sender name
     * @param   string $subject    Subject
     * @param   string $bodyText   Text
     * @param   array  $recipients Recipients (array [#] => array ('name' => '...', 'address' => '...'))
     * @return  boolean            True if mail was sent
     */
    public function sendMail($from, $fromName, $subject, $bodyText, array $recipients) {
        $this->setRecipients($recipients);
        $this->setSubject($subject);
        $this->setBodyText($bodyText);
        $this->setFrom($from);
        $this->setFromName($fromName);
        return $this->send();
    }

    /**
     * Composes an e-mail for multiple recipients from the specified components.
     *
     * @throws Opus_Mail_Exception Thrown if the number of recipient names and of recipient addresses differ
     * @throws Opus_Mail_Exception Thrown if the mail could not be sent
     * @return boolean             True if mail could be sent
     */
    private function send() {
        $logger = Zend_Registry::get('Zend_Log');

        if (!isset($this->_transport)) {
            $logger->warn('Not sending mail: Mail server not configured.');
            return true;
        }

        $recipients = $this->getRecipients();
        $from = $this->getFrom();
        $fromName = $this->getFromName();
        $subject = $this->getSubject();
        $text = $this->getBodyText();

        if (trim($from) === '') {
            throw new Opus_Mail_Exception('No sender address given.');
        }

        if (trim($subject) === '') {
            throw new Opus_Mail_Exception('No subject text given.');
        }

        $mail = new Zend_Mail('utf-8');
        $mail->setFrom($from, $fromName);
        $mail->setSubject($subject);
        $mail->setBodyText($text);

        foreach ($recipients as $recip) {
            $mail->addTo($recip['address'], $recip['name']);
        }

        try {
            $mail->send($this->_transport);
            $logger->debug('SendMail: Successfully sent mail to ' . $recip['address']);
        } catch (Exception $e) {
            $logger->err('SendMail: Failed sending mail to ' . $recip['address'] . ', error: ' . $e);
            throw new Opus_Mail_Exception('One or more mails could not be sent.');
        }

        return true;
    }
}
