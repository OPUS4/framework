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
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @author      Eva Kranz <s9evkran@stud.uni-saarland.de>
 * @copyright   Copyright (c) 2011, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Methods to send e-mails via Zend_Mail, but with mail server from config.ini.
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
     * Validates an e-mail address
     *
     * @param   string $address Address
     * @throws  Opus_Mail_Exception Thrown if the e-mail address is not valid
     * @return  string              Address
     */
    public static function validateAddress($address) {
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
     *
     * @return  boolean            True if mail was sent
     * @throws Opus_Mail_Exception Thrown if the mail could not be sent.
     * @throws Opus_Mail_Exception Thrown if the from address is invalid.
     */
    public function sendMail($from, $fromName, $subject, $bodyText, array $recipients) {
        $logger = Zend_Registry::get('Zend_Log');

        if (!isset($this->_transport)) {
            $logger->warn('Not sending mail: Mail server not configured.');
            return true;
        }

        if (trim($from) === '') {
            throw new Opus_Mail_Exception('No sender address given.');
        }
        self::validateAddress($from);

        if (trim($subject) === '') {
            throw new Opus_Mail_Exception('No subject text given.');
        }

        $mail = new Zend_Mail('utf-8');
        $mail->setFrom($from, $fromName);
        $mail->setSubject($subject);
        $mail->setBodyText($bodyText);

        foreach ($recipients as $recip) {
            $logger->debug('SendMail: adding recipient <' . $recip['address'] . '>');
            $mail->addTo($recip['address'], $recip['name']);
        }

        try {
            $mail->send($this->_transport);
            $logger->debug('SendMail: Successfully sent mail to ' . $recip['address']);
        } catch (Exception $e) {
            $logger->err('SendMail: Failed sending mail to ' . $recip['address'] . ', error: ' . $e);
            throw new Opus_Mail_Exception('SendMail: Mails could not be sent.');
        }

        return true;
    }
}
