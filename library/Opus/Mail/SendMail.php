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
     * Holds the properties of the mail specified by the sender
     *
     * @var Zend_Mail
     */
    private $_mail;

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
        $this->createSmtpTransport();
        $this->_mail = new Zend_Mail();
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
        $this->_bodyText = strip_tags($bodyText);
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
    * Creates and registers the SMTP transport.
    * The SMTP server address should be sourced out to a configuration file.
    *
    * @return void
    */
    private function createSmtpTransport() {
        $transport = new Zend_Mail_Transport_Smtp('mail.example.com');
        Zend_Mail::setDefaultTransport($transport);
    }

    /**
     * Validates an e-mail address
     *
     * @param   string $address Address
     * @throws  Opus_Mail_Exception Thrown if the e-mail address is not valid
     * @return  string              Address
     */
    private function validateAddress($address) {
        $validator = new Zend_Validate_EmailAddress();
        if ($validator->isValid($address) === false) {
            foreach ($validator->getMessages() as $message) {
                throw new Opus_Mail_Exception($message);
            }
        }

        return $address;
    }

    /**
     * Forms an array with address and composed name from a user object.
     *
     * @param   Opus_Model_Person $recipient Recipient
     * @return  array                        Recipients' addresses and names
     */
    private function formRecipient(Opus_Model_Person $recipient) {
        $recip = array('address' => '', 'name' => '');
        $recip['address'] = validateAddress($recipient->getField('EMail'));
        $firstName = $recipient->getField('FirstName');
        $lastName = $recipient->getField('LastName');
        $recip['name'] = $firstName . ' ' . $lastName;

        return $recip;
    }

    /**
     * Creates and sends an e-mail to the specified recipient using the SMTP transport.
     * This method should be used carefully, particularly with regard to the possibility
     * of sending mails anonymously to user-defined recipients.
     * Recommendation:  Please use the "sendMailTo..." methods
     *
     * @param   string $from       Sender address
     * @param   string $fromName   Sender name
     * @param   string $subject    Subject
     * @param   string $bodyText   Text
     * @param   array  $recipients Recipients
     * @return  void
     */
    public function sendMail($from, $fromName, $subject, $bodyText, array $recipients) {
        $this->setRecipients($recipients);
        $this->setSubject($subject);
        $this->setBodyText($bodyText);
        $this->setFrom($from);
        $this->setFromName($fromName);

        $this->send();
    }

    /**
     * Creates and sends an e-mail to the specified recipients.
     *
     * @param   integer|Opus_Model_Person|array $recipients Recipients
     * @param   string                          $subject    Subject
     * @param   string                          $bodyText   Text
     * @param   string                          $from       (Optional) Sender address - if not set, the administrator's address is taken
     * @param   string                          $fromName   (Optional) Sender name - if not set, the administator's name is taken
     * @return  void
     */
    public function sendMailToAuthor($recipients, $subject, $bodyText, $from = '', $fromName = '') {
        if ($from === false) {
            $config = Zend_Registry::get('Zend_Config');
            $from = $config->mail->opus->address;
            $fromName = $config->mail->opus->name;
        }

        if (is_int($recipients) === true) {
            $recipients = array(new Opus_Model_Person($recipients));
        }

        if (is_array($recipients) === true and is_int($recipients[0]) === true) {
            $recs = array();
            $recipients = array_unique($recipients);
            foreach ($recipients as $rec) {
                $recipient = new Opus_Model_Person($rec);
                array_push($recs, $recipient);
            }
            $recipients = $recs;
        }

        if (is_object($recipients) === true) {
            $recipients = array($recipients);
        }

        $recips = array('recipients' => array('address' => '', 'name' => ''));
        if (is_array($recipients) === true and is_object($recipients[0]) === true) {
            foreach ($recipients as $rec) {
                $recFormed = formRecipient($rec);
                array_push($recips, $recFormed);
            }
        }

        $this->sendMail($from, $fromName, $subject, $bodyText, $recips);
    }

    /**
     * Creates and sends an e-mail to the administrator.
     * The author of the specified document will be set as sender.
     *
     * @param   integer|Opus_Model_Document $document Document
     * @param   string                      $subject  Subject
     * @param   string                      $bodyText Text
     * @throws  Opus_Mail_Exception Thrown if the author / the document cannot be found
     * @return  void
     */
    public function sendMailToAdmin($document, $subject, $bodyText) {
        $config = Zend_Registry::get('Zend_Config');
        $to = $config->mail->opus->address;
        $toName = $config->mail->opus->name;
        $recips = array('recipients' => array('address' => $to, 'name' => $toName));

        // @todo Implement: get author's name and e-mail address from the database
        // Dummy code:
        $from = '';
        $fromName = '';

        $this->sendMail($from, $fromName, $subject, $bodyText, $recips);
    }

    /**
     * Creates and sends an e-mail to all persons connected to the specified document / documents.
     *
     * @param   mixed  $document Document
     * @param   string $subject  Subject
     * @param   string $bodyText Text
     * @param   string $from     (Optional) If not set, the standard sender address is taken
     * @param   string $fromName (Optional) If not set, the standard sender name is taken
     * @return  void
     */
    public function sendMailToDocument($document, $subject, $bodyText, $from = '', $fromName = '') {
        // @todo Implement
    }

    /**
     * Creates and sends an e-mail to all authors of the specified collection / collections.
     *
     * @param   integer|array $collection Collection
     * @param   string        $subject    Subject
     * @param   string        $bodyText   Text
     * @return  void
     */
    public function sendMailToCollection($collection, $subject, $bodyText) {
        // @todo Implement
    }

    /**
     * Composes the e-mail for multiple recipients from the specified components.
     *
     * @throws Opus_Mail_Exception Thrown if the number of recipient names and of recipient addresses differ
     * @throws Opus_Mail_Exception Thrown if the mail could not be sent
     * @return void
     */
    private function send() {
        $recipients = $this->getRecipients();
        $from = $this->getFrom();
        $fromName = $this->getFromName();
        $subject = $this->getSubject();
        $text = $this->getBodyText();

        if ($from === '') {
            throw new Opus_Mail_Exception('No sender address given.');
        }

        if ($subject === '') {
            throw new Opus_Mail_Exception('No text given.');
        }

        //if ($recipients['recipients'] === '') {
        //    throw new Opus_Mail_Exception('No recipient address given.');
        //}

        $error = false;
        foreach ($recipients as $recip) {
            $this->$_mail->addTo($recip['address'], $recip['name']);
            $this->$_mail->setFrom($from, $fromName);
            $this->$_mail->setSubject($subject);
            $this->$_mail->setBodyText($text);

            try {
                $this->_mail->send();
            } catch (Exception $e) {
                $error = true;
            }
        }
        if ($error === true) {
            throw new Opus_Mail_Exception('One or more mails could not be sent.');
        }
    }
}