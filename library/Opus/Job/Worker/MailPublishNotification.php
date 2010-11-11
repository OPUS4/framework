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
 * @category    Framework
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2008-2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Worker for sending out email notifications for newly published documents.
 */
class Opus_Job_Worker_MailPublishNotification extends Opus_Job_Worker_Abstract {

    const LABEL = 'opus-mail-publish-notification';

    private $config = null;

    /**
     * Constructs worker.
     * @param Zend_Log $logger
     */
    public function __construct($logger = null) {
        $this->setLogger($logger);
        $this->config = Zend_Registry::get('Zend_Config');
    }

    /**
     * Return message label that is used to trigger worker process.
     *
     * @return string Message label.
     */
    public function getActivationLabel() {
        return self::LABEL;
    }

    /**
     * Perfom work.
     *
     * @param Opus_Job $job Job description and attached data.
     * @return array Array of Jobs to be newly created.
     */
    public function work(Opus_Job $job) {
        $data = $job->getData();

        $message = $data->message;
        $subject = $data->subject;
        $projects = $data->projects;

        $from = $this->_getFrom();
        $fromName = $this->_getFromName();
        $recipient = $this->getRecipients($projects);

        if (empty($recipient)) {
            $this->_logger->info('No referees configured. Mail canceled.');
            return true;
        }

        $mailSendMail = new Opus_Mail_SendMail();

        try {
            $this->_logger->debug('Send publish notification.');
            $this->_logger->debug('address = ' . $from);
            $mailSendMail->sendMail(
                    $from, $fromName, $subject, $message, $recipient);
        } catch (Exception $e) {
            $this->_logger->err($e);
            return false;
        }

        return true;
    }

    /**
     * Returns the 'from' address for notification.
     *
     * @return string
     */
    protected function _getFrom() {
        if (isset($this->config->mail->opus->address)) {
            $from = $this->config->mail->opus->address;
        }
        else {
            return 'not configured';
        }

        return $from;
    }

    /**
     * Returns the 'from name' for notification.
     * @return string
     */
    protected function _getFromName() {
        if (isset($this->config->mail->opus->name)) {
            $fromName = $this->config->mail->opus->name;
        }
        else {
            return 'not configured';
        }

        return $fromName;
    }

    /**
     *
     * @return <type>
     */
    public function getRecipients($projects = null) {
        if (!is_array($projects)) {
            $projects = array($projects);
        }

        $allRecipients = $this->getGlobalRecipients();

        if (empty($allRecipients)) {
            $allRecipients = array();
        }

        if (!empty($projects)) {
            foreach ($projects as $project) {
                $collection = substr($project, 0, 1); // MATHEON get first letter of project

                $collection = strtolower($collection);

                $recipients = $this->getRecipientsForCollection($collection);

                if (!empty($recipients)) {
                    $allRecipients = array_merge($allRecipients, $recipients);
                }
            }
        }

        // TODO remove duplicates

        return $allRecipients;
    }

    /**
     *
     * @param <type> $collection
     * @return <type>
     */
    public function getRecipientsForCollection($collection) {
        $config = Zend_Registry::get('Zend_Config');

        if (!isset($config->events->collections->$collection)) {
            return null;
        }

        $referees = $config->events->collections->$collection->referees;

        $recipients = $this->_readRecipients($referees);

        return $recipients;
    }

    /**
     * Returns recipients for publish notifications.
     *
     * @return array
     */
    public function getGlobalRecipients() {
        $config = Zend_Registry::get('Zend_Config');

        $referees = $config->referees;

        $recipients = $this->_readRecipients($referees);

        return $recipients;
    }

    /**
     *
     * @param <type> $referees
     * @return string
     */
    protected function _readRecipients($referees) {
        $recipients = array();

        if (!empty($referees)) {
            $index = 1;
            foreach ($referees as $name => $address) {
                $recipients[$index] = array('name' => $name, 'address' => $address);
                $index++;
            }
        }
        else {
            $recipients = null;
        }

        return $recipients;
    }

}

?>
