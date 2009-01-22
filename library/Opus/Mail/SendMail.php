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
     * Holds the e-mail address of the recipient
     *
     * @var string
     */
    private $_to;

    /**
     * Holds the name of the recipient
     *
     * @var string
     */
    private $_toName;

    /**
     * Holds the e-mail addresses of the recipients
     *
     * @var array
     */
    private $_multipleTo;

    /**
     * Holds the names of the recipients
     *
     * @var array
     */
    private $_multipleToName;

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
        $this->$_mail = new Zend_Mail();
    }

    /**
     * Set the e-mail address of the recipient
     *
     * @param string $to Recipient's e-mail address
     * @return void
     */
    public function setTo($to) {
        $this->validateAddress($to);
        $this->$_to = $to;
    }

    /**
     * Get the e-mail address of the recipient
     *
     * @return string Recipient's e-mail address
     */
    public function getTo() {
        return $this->$_to;
    }

    /**
     * Set the name of the recipient
     *
     * @param string $toName Recipient's name
     * @return void
     */
    public function setToName($toName) {
        $this->$_toName = $toName;
    }

    /**
     * Get the name of the recipient
     *
     * @return string Recipient's name
     */
    public function getToName() {
        return $this->$_toName;
    }

    /**
     * Set the e-mail addresses of the recipients
     *
     * @param string $multipleTo Recipients' e-mail addresses
     * @return void
     */
    public function setMultipleTo($multipleTo) {
        foreach ($multipleTo as $to) {
            $this->validateAddress($to);
        }
        $this->$_multipleTo = $multipleTo;
    }

    /**
     * Get the e-mail addresses of the recipients
     *
     * @return string Recipients' e-mail addresses
     */
    public function getMultipleTo() {
        return $this->$_multipleTo;
    }

    /**
     * Set the names of the recipients
     *
     * @param string $multipleToName Recipients' names
     * @return void
     */
    public function setMultipleToName($multipleToName) {
        $this->$_multipleTo = $multipleToName;
    }

    /**
     * Get the names of the recipients
     *
     * @return string Recipients' names
     */
    public function getMultipleToName() {
        return $this->$_multipleToName;
    }

    /**
     * Set the e-mail address of the sender
     *
     * @param string $from Sender's e-mail address
     * @return void
     */
    public function setFrom($from) {
        $this->validateAddress($from);
        $this->$_from = $from;
    }

    /**
     * Get the e-mail address of the sender
     *
     * @return string Sender's e-mail address
     */
    public function getFrom() {
        return $this->$_from;
    }

    /**
     * Set the name of the sender
     *
     * @param string $fromName Sender's name
     * @return void
     */
    public function setFromName($fromName) {
        $this->$_fromName = $fromName;
    }

    /**
     * Get the name of the sender
     *
     * @return string Sender's name
     */
    public function getFromName() {
        return $this->$_fromName;
    }

    /**
     * Set the subject of the e-mail
     *
     * @param string $subject Subject
     * @return void
     */
    public function setSubject($subject) {
        $this->$_subject = $subject;
    }

    /**
     * Get the subject of the e-mail
     *
     * @return string Subject
     */
    public function getSubject() {
        return $this->$_subject;
    }

    /**
     * Set the text of the e-mail
     *
     * @param string $bodyText Text
     * @return void
     */
    public function setBodyText($bodyText) {
        $this->$_bodyText = strip_tags($bodyText);
    }

    /**
     * Get the text of the e-mail
     *
     * @return string Text
     */
    public function getBodyText() {
        return $this->$_bodyText;
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
     * @param string $address Address
     * @throws Opus_Mail_Exception Thrown if the e-mail address is not valid
     * @return void
     */
    private function validateAddress($address) {
        $validator = new Zend_Validate_EmailAddress();
        if ($validator->isValid($address) === FALSE) {
            foreach ($validator->getMessages() as $message) {
                throw new Opus_Mail_Exception($message);
            }
        }
    }

    /**
     * Creates and sends an e-mail to the specified recipient using the SMTP transport.
     * This method should be used carefully, particularly with regard to the possibility
     * of sending mails anonymously to user-defined recipients.
     * Recommendation:  Please use the "sendMailTo..." methods
     *
     * @param string $from Sender address
     * @param string $fromName Sender name
     * @param string $subject Subject
     * @param string $bodyText Text
     * @param string $to Recipient address
     * @param string $toName Recipient name
     * @return void
     */
    public function sendMail($from, $fromName, $subject, $bodyText, $to, $toName) {
        $this->setFrom($from);
        $this->setFromName($fromName);
        $this->setSubject($subject);
        $this->setBodyText($bodyText);
        $this->setTo($to);
        $this->setToName($toName);

        $this->composeMail();
        $this->send();
    }

    /**
     * Creates and sends an e-mail to the specified recipient.
     *
     * @param string $to Recipient address
     * @param string $toName Recipient name
     * @param string $subject Subject
     * @param string $bodyText Text
     * @param string $from Sender address - if not set, the administrator's address is taken
     * @param string $fromName Sender name - if not set, the administator's name is taken
     * @return void
     */
    public function sendMailToAuthor($to, $toName, $subject, $bodyText, $from = '', $fromName = '') {
        if ($from === FALSE) {
            //@todo Get the administrator's e-mail address and name from the configuration file
            //Only for test purposes, will be deleted later:
            //$config->Zend_Registry->get('Zend_Config');
            //$from = $config->mail->mail.opus.address;
            //$fromName = $config->mail->mail.opus.name;
            $from = 'eva@o-dyn.de';
            $fromName = 'OPUS';
        }

        $this->sendMail($from, $fromName, $subject, $bodyText, $to, $toName);
    }

    /**
     * Creates and sends an e-mail to the administrator.
     * The author of the secified document will be set as sender.
     *
     * @param integer $documentId Document ID
     * @param string $subject Subject
     * @param string $bodyText Text
     * @throws Opus_Mail_Exception Thrown if the author / the document cannot be found
     * @return void
     *
     * @todo Finish the implementation, i.e. the database queries
     */
    public function sendMailToAdmin($documentId, $subject, $bodyText) {
        // Get the author's ID from the DB via the documentId
        $authorId = 0;
        if ($authorId) {
            // Get the e-mail address and name of the person you find in the DB with the documents_id
            $from = '';
            $fromName = '';
        }
        else
        {
            throw new Opus_Mail_Exception('The author could not be found.');
        }

        // Get the administrator's e-mail address and name from the configuration file
        $to = '';
        $toName = '';

        $this->sendMail($from, $fromName, $subject, $bodyText, $to, $toName);
    }

    /**
     * Creates and sends an e-mail to all persons connected to the specified document / documents.
     *
     * @param mixed $documentId Document ID
     * @param string $subject Subject
     * @param string $bodyText Text
     * @param string $from If not set, the standard sender address is taken
     * @param string $fromName If not set, the standard sender name is taken
     * @return void
     *
     * @todo Method must be implemented.
     */
    public function sendMailToDocument($collectionId, $subject, $bodyText, $from = '', $fromName = '') {
        // Todo:    Implement
    }

    /**
     * Creates and sends an e-mail to all authors of the specified collection / collections.
     *
     * @param mixed $collectionId Collection ID
     * @param string $subject Subject
     * @param string $bodyText Text
     * @return void
     *
     * @todo Method must be implemented.
     */
    public function sendMailToCollection($collectionId, $subject, $bodyText) {
        // Todo:    Implement
    }

    /**
     * Composes the e-mail from the specified components.
     *
     * @return void
     */
    private function composeMail() {
        $this->$_mail->addTo($this->getTo(), $this->getToName());
        $this->$_mail->setFrom($this->getFrom(), $this->getFromName());
        $this->$_mail->setSubject($this->getSubject());
        $this->$_mail->setBodyText($this->getBodyText());
    }

    /**
     * Composes the e-mail for multiple recipients from the specified components.
     *
     * @todo Method must be implemented.
     */
    private function composeMultipleMail() {
        // When the database query is implemented there should be taken action to avoid duplicate recipients.
        if (count($this->_multipleTo) != count($this->_multipleToName)) {
            throw new Opus_Mail_Exception('The number of recipient names is not equal to the number of recipient e-mail addresses.');
        }
        $this->$_mail->addTo($this->getMultipleTo(), $this->getMultipleToName());
        $this->$_mail->setFrom($this->getFrom(), $this->getFromName());
        $this->$_mail->setSubject($this->getSubject());
        $this->$_mail->setBodyText($this->getBodyText());
    }

    /**
     * Finally sends the e-mail with the specified properties.
     *
     * @return void
     */
    private function send() {
        try {
            $this->$_mail->send();
        } catch (Exception $e) {
            throw new Opus_Mail_Exception('The mail could not be sent.');
        }
    }
}