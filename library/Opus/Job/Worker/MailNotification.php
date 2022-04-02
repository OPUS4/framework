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
 * @package     Opus\Model
 * @author      Jens Schwidder <schwidder@zib.de>
 * @author      Sascha Szott <szott@zib.de>
 * @copyright   Copyright (c) 2008-2012, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\Job\Worker;

use Opus\Account;
use Opus\Common\Config;
use Opus\Common\Mail\SendMail;
use Opus\Job;
use Zend_Log;

use function implode;
use function is_array;
use function trim;

/**
 * Worker for sending out email notifications for newly published documents.
 *
 * phpcs:disable
 */
class MailNotification extends AbstractWorker
{
    const LABEL = 'opus-mail-publish-notification';
    private $config;
    private $lookupRecipients = true;

    /**
     * Constructs worker.
     *
     * @param null|Zend_Log $logger
     * @param bool          $lookupRecipients wenn true, dann erwartet die Methode Usernamen (d.h. Accounts)
     *                                           und schlägt die E-Mail-Adressen nach; andernfalls werden E-Mail-
     *                                           Adressen erwartet in der Form wie sie Opus\Mail\SendMail erwartet
     */
    public function __construct($logger = null, $lookupRecipients = true)
    {
        $this->setLogger($logger);
        $this->config           = Config::get();
        $this->lookupRecipients = $lookupRecipients;
    }

    /**
     * Return message label that is used to trigger worker process.
     *
     * @return string Message label.
     */
    public function getActivationLabel()
    {
        return self::LABEL;
    }

    /**
     * Perfom work.
     *
     * @param Opus\Job $job Job description and attached data.
     * @return array Array of Jobs to be newly created.
     */
    public function work(Job $job)
    {
        $data    = $job->getData(true);
        $message = $data['message'];
        $subject = $data['subject'];
        $users   = $data['users'];

        $from        = $this->_getFrom();
        $fromName    = $this->_getFromName();
        $replyTo     = $this->_getReplyTo();
        $replyToName = $this->_getReplyToName();
        $returnPath  = $this->_getReturnPath();

        if ($users !== null && ! is_array($users)) {
            $users = [$users];
        }

        $recipient = [];
        if ($this->lookupRecipients) {
            $this->logger->debug(self::class . ': Resolving mail addresses for users = {"' . implode('", "', $users) . '"}');
            $recipient = $this->getRecipients($users);
        } else {
            $recipient = $users;
        }
//        if (empty($recipient)) {
//            $this->_logger->info(__CLASS__ . ': No recipients avaiable. Mail canceled.');
//            return true;
//        }

        $mailSendMail = new SendMail();

        $this->logger->info(self::class . ': Sending notification email...');
        $this->logger->debug(self::class . ': sender: ' . $from);
        $mailSendMail->sendMail($from, $fromName, $subject, $message, $recipient, $replyTo, $replyToName, $returnPath);

        return true;
    }

    /**
     * Returns the 'from' address for notification.
     *
     * @return string
     */
    protected function _getFrom()
    {
        if (isset($this->config->mail->opus->address)) {
            return $this->config->mail->opus->address;
        }
        return 'not configured';
    }

    /**
     * Returns the 'from name' for notification.
     *
     * @return string
     */
    protected function _getFromName()
    {
        if (isset($this->config->mail->opus->name)) {
            return $this->config->mail->opus->name;
        }
        return 'not configured';
    }

    protected function _getReplyTo()
    {
        if (isset($this->config->mail->opus->replyTo)) {
            return $this->config->mail->opus->replyTo;
        }

        return null;
    }

    protected function _getReplyToName()
    {
        if (isset($this->config->mail->opus->replyToName)) {
            return $this->config->mail->opus->replyToName;
        }

        return null;
    }

    protected function _getReturnPath()
    {
        if (isset($this->config->mail->opus->returnPath)) {
            return $this->config->mail->opus->returnPath;
        }

        return null;
    }

    public function getRecipients($users = null)
    {
        if (! is_array($users)) {
            $users = [$users];
        }

        $allRecipients = [];
        foreach ($users as $user) {
            $account = Account::fetchAccountByLogin($user);

            if ($account === null) {
                $this->logger->warn(self::class . ": User '$user' does not exist... skipping mail.");
                continue;
            }

            $mail = $account->getEmail();
            if ($mail === null || trim($mail) === '') {
                $this->logger->warn(self::class . ": No mail address for user '$user'... skipping mail.");
                continue;
            }

            $allRecipients[] = [
                'name'    => $account->getFirstName() . ' ' . $account->getLastName(),
                'address' => $mail,
            ];
        }

        return $allRecipients;
    }
}
