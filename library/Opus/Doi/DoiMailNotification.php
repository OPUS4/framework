<?php
/**
 * This file is part of OPUS. The software OPUS has been originally developed
 * at the University of Stuttgart with funding from the German Research Net,
 * the Federal Department of Higher Education and Research and the Ministry
 * of Science, Research and the Arts of the State of Baden-Wuerttemberg.
 *
 * OPUS 4 is a complete rewrite of the original OPUS software and was developed
 * by the Stuttgart University Library, the Library Service Center
 * Baden-Wuerttemberg, the North Rhine-Westphalian Library Service Center,
 * the Cooperative Library Network Berlin-Brandenburg, the Saarland University
 * and State Library, the Saxon State Library - Dresden State and University
 * Library, the Bielefeld University Library and the University Library of
 * Hamburg University of Technology with funding from the German Research
 * Foundation and the European Regional Development Fund.
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
 * @category    Application
 * @author      Sascha Szott <szott@zib.de>
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

class Opus_Doi_DoiMailNotification
{

    private $config;

    private $log;

    /**
     * @var bool Ist die Benachrichtigung über DOI-Ereignisse via E-Mail aktiviert
     */
    private $enabled;

    /**
     * @var array Namen und E-Mail-Adressen der Empfänger von DOI-Benachrichtigungen
     */
    private $recipients;

    public function __construct()
    {
        $this->notifications = [];
        $this->config = Zend_Registry::get('Zend_Config');
        $this->log = Zend_Registry::get('Zend_Log');

        // check if email notifications for DOI events are enabled in general
        if (isset($this->config->doi->notificationEmailEnabled) &&
            ($this->config->doi->notificationEmailEnabled || $this->config->doi->notificationEmailEnabled == '1')) {
            $this->enabled = true;
        }
        else {
            $this->log->info('configuration setting doi.notificationEmailEnabled was not set - DOI notifications are disabled');
            $this->enabled = false;
            return;
        }

        // check if any recipients for DOI notification emails are configured (otherwise notifications are disabled)
        if (!isset($this->config->doi->notificationEmail) || empty($this->config->doi->notificationEmail->toArray()) || $this->config->doi->notificationEmail->toArray()[0] == '') {
            $this->log->info('configuration setting doi.notificationEmail[] was not set - DOI notifications are disabled');
            $this->enabled = false;
        }
        else {
            $this->initRecipients();
        }
    }

    /**
     * Initialisiert die Namen und Adressen der Empfänger von DOI-Benachrichtigungen.
     */
    private function initRecipients()
    {
        $recipientAddresses = $this->config->doi->notificationEmail->toArray();
        $this->recipients = [];
        foreach ($recipientAddresses as $recipient) {
            $entry = [];
            $entry['name'] = $recipient;
            $entry['address'] = $recipient;
            $this->recipients[] = $entry;
        }
    }

    /**
     * Liefert true, wenn das Verschicken von E-Mail-Benachrichtigungen aktiviert und korrekt konfiguriert ist.
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * Fügt eine Benachrichtigung für die übergebene DOI zur E-Mail hinzu.
     *
     * @param $docId
     * @param $doi
     * @param null $errorMessage
     */
    public function addNotification($docId, $doi, $errorMessage = null)
    {
        $entry = [];
        $entry['docId'] = $docId;
        $entry['doi'] = $doi;
        $entry['errorMessage'] = $errorMessage;
        $this->notifications[] = $entry;
    }

    /**
     * Versand einer E-Mail-Benachrichtigung nach der Registrierung von DOIs.
     * Grundsätzlich werden E-Mail-Benachrichtigungen nur bei der asynchronen Registierung von DOIs verschickt.
     *
     * Wird statt einer einzelnen DOI eine Menge von DOIs in einem Vorgang registriert, so erfolgt
     * der gebündelte Versand aller DOI-Benachrichtigungen in einer E-Mail.
     *
     */
    public function sendRegistrationEmail()
    {
        $this->prepareMail('Registrierung');
    }

    /**
     * Versand einer E-Mail-Benachrichtigung nach der Prüfung von DOIs.
     * Grundsätzlich werden E-Mail-Benachrichtigungen nur bei der asynchronen Prüfung von DOIs verschickt.
     *
     * Wird statt einer einzelnen DOI eine Menge von DOIs in einem Vorgang geprüft, so erfolgt
     * der gebündelte Versand aller DOI-Benachrichtigungen in einer E-Mail.
     *
     */
    public function sendVerificationEmail()
    {
        $this->prepareMail('Prüfung');
    }

    /**
     * Liefert eine Zeile für die zu erzeugende E-Mail-Benachrichtigung.
     *
     * @param $docId ID des OPUS-Dokuments, zu dem die DOI gehört
     * @param $doi DOI auf die sich die Nachricht bezieht
     * @param $errorMessage ggf. Meldung des aufgetretenen Fehlers bei Registrierung oder Prüfung
     */
    private function buildMessageLine($docId, $doi, $errorMessage)
    {
        $result = $doi->getValue() . ' ';

        $frontdoorUrl = $this->getUrl('frontdoor/index/index/docId/' . $docId);
        $result .= $frontdoorUrl . ' ' . $doi->getStatus();
        if (!is_null($errorMessage)) {
            $result .= ' Fehlermeldung: ' . $errorMessage;
        }
        $result .= "\r\n";
        return $result;
    }

    /**
     * Bereitet Betreff und Inhalt für die E-Mail-Benachrichtigung vor. Löst am Ende den Versand
     * der E-Mail-Benachrichtigung aus.
     *
     * @param $mode Registrierung oder Prüfung von DOIs (wird in den Betreff der E-Mail geschrieben)
     */
    private function prepareMail($mode)
    {
        if (!$this->enabled || empty($this->notifications)) {
            // E-Mail-Versand ist nicht aktiviert / konfiguriert bzw. es gibt keinen Inhalt für den Bericht
            return;
        }

        $subject = 'Statusbericht über DOI-' . $mode;

        if (count($this->notifications) == 1) {
            // Benachrichtigung für genau eine DOI: erweitere den Betreff der E-Mail-Benachrichtigung
            $notification = $this->notifications[0];
            $doi = $notification['doi'];
            $docId = $notification['docId'];
            $errorMessage = $notification['errorMessage'];
            $subject .= ' von DOI ' . $doi->getValue() . ' für Dokument mit ID ' . $docId;
            $message = $this->buildMessageLine($docId, $doi, $errorMessage);
        }
        else {
            // Versand einer gebündelten E-Mail-Benachrichtigung für mehrere DOIs
            $message = '';
            foreach ($this->notifications as $notification) {
                $doi = $notification['doi'];
                $docId = $notification['docId'];
                $errorMessage = $notification['errorMessage'];
                $message .= $this->buildMessageLine($docId, $doi, $errorMessage);
            }
        }

        $message .= $this->addMailFooter();

        // lösche den Nachrichtenpuffer, so dass Benachrichtigungen nicht mehrfach verschickt werden
        $this->notifications = [];

        $this->sendEmailNotification($subject, $message);
    }

    /**
     * Liefert den Footer für die E-Mail-Benachrichtigung.
     *
     * @return string
     */
    private function addMailFooter()
    {
        $result = "\r\n--\r\nDiese automatische E-Mail-Benachrichtigung wurde von OPUS4 verschickt.\r\n";
        $reportUrl = $this->getUrl('admin/report/doi');
        $result .= "Unter $reportUrl können Sie den Registrierungstatus aller lokalen DOIs einsehen.\r\n";
        return $result;
    }

    private function sendEmailNotification($subject, $message)
    {
        $from = $this->_getFrom();
        $fromName = $this->_getFromName();
        $replyTo = $this->_getReplyTo();
        $replyToName = $this->_getReplyToName();
        $returnPath = $this->_getReturnPath();

        $this->log->debug('try to send DOI notification email with subject ' . $subject);
        try {
            $mailSendMail = new Opus_Mail_SendMail();
            $mailSendMail->sendMail(
                $from,
                $fromName,
                $subject,
                $message,
                $this->recipients,
                $replyTo,
                $replyToName,
                $returnPath);
            $this->log->info('successful sending of DOI notification email with subject ' . $subject);
        }
        catch (Opus_Mail_Exception $e) {
            $this->log->err('could not send DOI notification email with subject ' . $subject . ': ' . $e->getMessage());
        }
    }

    protected function _getFrom()
    {
        if (isset($this->config->mail->opus->address)) {
            return $this->config->mail->opus->address;
        }
        return 'not configured';
    }

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

    /**
     * Erzeugt eine absolute URL auf Basis der in der Konfiguration definierten Basis-URL.
     * Wird benötigt, damit in den E-Mail-Benachrichtigungen z.B. URLs von Dokument-Frontdoors
     * aufgenommen werden können (Zend-MVC und die zugehörigen View-Helper stehen bei CLI-Skripten
     * nicht zur Verfügung, so dass die Basis-URL nicht aus dem Request ermittelt werden kann)
     *
     * @param $path
     * @return string
     */
    private function getUrl($path)
    {
        $result = '';
        if (isset($this->config->url)) {
            $result .= $this->config->url;
            // check if $result ends with '/' otherwise add one
            if (!(substr($result, -strlen($result)) === '/')) {
                $result .= '/';
            }
        }

        $result .= $path;
        return $result;
    }
}
