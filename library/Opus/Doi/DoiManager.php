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

class Opus_Doi_DoiManager
{

    /**
     * Logger for DOI specific information kept separat for convenience, easy access.
     * @var Zend_Log
     */
    private $doiLog;

    /**
     * Logger for normal messages, debugging.
     * @var Zend_Log
     */
    private $defaultLog;

    /**
     * Configuration of the entire application.
     * @var Zend_Config
     */
    private $config;

    private $landingPageBaseUrl;

    /**
     * Enables/disables storing of DataCite registration XML in files.
     * @var bool
     */
    private $keepRegistrationXml = true;

    /**
     * Opus_Doi_DoiManager constructor.
     * @throws Zend_Exception
     *
     * TODO create logger only if necessary
     * TODO use OPUS functions to get configuration and default log
     */
    public function __construct()
    {
        $this->config = Zend_Registry::get('Zend_Config');
        $this->defaultLog = Zend_Registry::get('Zend_Log');
        $this->doiLog = $this->getDoiLogger();
    }

    /**
     * Creates logger for DOI messages.
     *
     * This code might be executed as part of a script or as part of a web request. Therefore it is possible that the
     * log file is created with different permissions. The 'chmod' in the function tries to alleviate the problem by
     * making sure that the owner and the group (usually 'wwwdata' or apache2 in general) have read/write permissions.
     *
     * TODO centralize code for creating log files (like a logging service that can be used by all modules)
     */
    public function getDoiLogger()
    {
        if (is_null($this->doiLog)) {
            $logfilePath = $this->config->workspacePath . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR
                . 'opus-doi.log';

            $logfile = @fopen($logfilePath, 'a', false);
            $writer = new Zend_Log_Writer_Stream($logfile);

            $format = '%timestamp% %priorityName%: %message%' . PHP_EOL;
            $formatter = new Zend_Log_Formatter_Simple($format);
            $writer->setFormatter($formatter);

            $this->doiLog = new Zend_Log($writer);

            chmod($logfilePath, 0660); // grant owner and group read/write permission
        }

        return $this->doiLog;
    }

    /**
     * Registriert die mit dem übergebenen Opus_Document verknüpfte lokale DOI bei DataCite.
     * Liefert im Erfolgsfall die registrierte DOI zurück. Liefert null zurück, wenn das Dokument keine lokale
     * DOI besitzt, die registriert werden kann.
     *
     * @param $doc   Opus_Document oder ID eines Opus_Document als String
     * @param $store wenn true, dann wird am Ende der Methode store() auf dem übergebenen $doc aufgerufen
     *               wenn die Methode im Kontext eines Store-Plugins aufgerufen wird, dann erfolgt der Aufruf
     *               von store() an anderer Stelle (sonst gibt es eine Endlosschleife)
     *
     * @throws DoiException wenn das referenzierte Dokument nicht in der Datenbank existiert
     * @throws RegistrationException wenn bei dem Versuch der Registrierung bei DataCite ein Fehler auftritt
     */
    public function register($doc, $store = false)
    {
        if (is_string($doc)) {
            $docId = $doc;
            try {
                $doc = new Opus_Document($docId);
            }
            catch (Opus_Model_NotFoundException $e) {
                $message = 'could not find document with ID ' . $docId . ' in database';
                $this->defaultLog->err($message);
                throw new Opus_Doi_DoiException($message);
            }
        }

        if (is_null($doc) || !($doc instanceof Opus_Document)) {
            $message = 'unexpected document class';
            if (!is_null($doc)) {
                $message .= ' ' . get_class($doc);
            }
            $this->defaultLog->err($message);
            throw new Opus_Doi_DoiException($message);
        }

        // prüfe, ob es überhaupt eine lokale DOI gibt, die registriert werden kann
        $localDoi = $this->checkForLocalRegistrableDoi($doc);
        if (is_null($localDoi)) {
            $message = 'document ' . $doc->getId() .
                ' does not provide a local DOI that can be registered: abort DOI registration process';
            $this->doiLog->info($message);
            $this->defaultLog->info($message);
            return null;
        }

        // prüfe, dass die lokale DOI nicht bereits registriert wurde
        if (!is_null($localDoi->getStatus())) {
            $message = 'document ' . $doc->getId() .
                ' does not provide a local unregistered DOI: abort DOI registration process';
            $this->doiLog->info($message);
            $this->defaultLog->info($message);
            return null;
        }

        // nun müssen wir noch prüfen, ob die lokale DOI tatsächlich nur genau einmal in der Instanz vorkommt
        if (!$this->checkDoiUniqueness($localDoi)) {
            $message = 'document ' . $doc->getId() .
                ' does not provide a unique local DOI: abort DOI registration process';
            $this->doiLog->err($message);
            $this->defaultLog->err($message);
            return null;
        }

        $xmlGen = $this->getXmlGenerator();

        try {
            $xmlStr = $xmlGen->getXml($doc);
        }
        catch (Opus_Doi_DataCiteXmlGenerationException $e) {
            $message = 'could not generate DataCite-XML for DOI registration of document ' . $doc->getId() . ': ' .
                $e->getMessage();
            $this->doiLog->err($message);
            $this->defaultLog->err($message);
            $doiException = new Opus_Doi_RegistrationException($message);
            $doiException->setDoi($localDoi);
            throw $doiException;
        }

        if ($this->isKeepRegistrationXml()) {
            $this->storeRegistrationXml($doc, $xmlStr);
        }

        try {
            $client = new \Opus\Doi\Client($this->config, $this->defaultLog);
            $client->registerDoi($localDoi->getValue(), $xmlStr, $this->getLandingPageUrlOfDoc($doc));
        }
        catch (\Opus\Doi\ClientException $e) {
            $message = 'an error occurred while registering DOI ' . $localDoi->getValue() . ' for document ' .
                $doc->getId() . ': ' . $e->getMessage();
            $this->doiLog->err($message);
            $this->defaultLog->err($message);
            $doiException = new Opus_Doi_RegistrationException($message);
            $doiException->setDoi($localDoi);
            throw $doiException;
        }

        // set status and timestamp after successful DOI registration
        $localDoi->setStatus('registered');
        // TODO timestamp should always be UTC
        $dateTimeZone = new DateTimeZone(date_default_timezone_get());
        $dateTime = new DateTime('now', $dateTimeZone);
        $localDoi->setRegistrationTs($dateTime->format('Y-m-d H:i:s'));
        if ($store) {
            $doc->store();
        }

        $message = 'DOI ' . $localDoi->getValue() . ' of document ' . $doc->getId() . ' was registered successfully';
        $this->doiLog->info($message);
        $this->defaultLog->info($message);

        return $localDoi;
    }

    /**
     * Returns XML generator for document registration.
     *
     * @return Opus_Doi_DataCiteXmlGenerator
     */
    public function getXmlGenerator()
    {
        $generator = new Opus_Doi_DataCiteXmlGenerator();
        $generator->setDoiLog($this->getDoiLogger());
        return $generator;
    }

    /**
     * Gibt true zurück, wenn der Wert der übergebenen DOI nur genau einmal innerhalb der OPUS-Datenbank existiert.
     *
     * @param $doi Opus_Identifier (vom Typ doi)
     */
    private function checkDoiUniqueness($doi)
    {
        return $doi->isDoiUnique();
    }

    private function getDoi($doc)
    {
        $identifiers = $doc->getIdentifier();
        if (is_null($identifiers) || empty($identifiers)) {
            return null;
        }

        foreach ($identifiers as $identifier) {
            if ($identifier->getType() != 'doi') {
                continue;
            }

            // wenn das Dokument mehr als eine DOI hat, dann ist es ein Dokument, das bereits vor der Einführung des
            // DOI-Supports in OPUS4 erstellt wurde: in diesem Fall wird für die Überprüfung
            // nur die erste DOI betrachtet
            if (is_null($identifier->getStatus())) {
                // lokale DOI kann nur registriert werden, wenn ihr status auf null gesetzt ist
                return $identifier;
            }
        }

        return null;
    }

    /**
     * Liefert null zurück, wenn das übergebene Opus_Document keine lokale DOI hat, die bei DataCite registriert werden
     * kann oder eine bereits registrierte lokale DOI hat. Andernfalls gibt die Methode die lokale DOI (Objekt vom
     * Typ Opus_Identifier) für die weitere Verarbeitung, d.h. Registrierung, zurück.
     *
     * Mit Jens vereinbart: eine lokale DOI wird anhand des "prefix" identifiziert. Der "localPrefix" wird bei der
     * Erkennung von lokalen DOIs nur berücksichtigt, wenn er gesetzt ist.
     *
     */
    private function checkForLocalRegistrableDoi($doc)
    {
        $doiToBeChecked = $this->getDoi($doc);
        if (is_null($doiToBeChecked)) {
            $this->defaultLog->debug(
                'document ' . $doc->getId() . ' does not provide an identifier of type DOI that can be registered'
            );
            return null;
        }

        // prüfe, dass es sich um eine lokale DOI handelt
        $doiValue = $doiToBeChecked->getValue();
        $this->defaultLog->debug('check DOI ' . $doiValue);

        if (!isset($this->config->doi->prefix)) {
            $message = 'configuration setting doi.prefix is not set - DOI registration cannot be applied';
            $this->doiLog->err($message);
            $this->defaultLog->err($message);
            return null;
        }

        if ($this->isLocalDoi($doiValue)) {
            return $doiToBeChecked;
        }

        return null;
    }

    /**
     * Liefert true, wenn der übergebene Wert den Wert einer lokale DOI darstellt; andernfalls false.
     *
     * @param $value Wert einer DOI, der auf Lokalität geprüft werden soll
     */
    private function isLocalDoi($value)
    {
        $doi = new Opus_Identifier();
        $doi->setValue($value);
        return $doi->isLocalDoi();
    }

    /**
     * Registriert alle lokalen DOIs mit status == null (d.h. die noch nicht bei DataCite registrierten lokalen DOIs)
     * und liefert als Ergebnis die Anzahl der erfolgreich registrierten lokalen DOIs zurück.
     *
     * Wenn nicht anders gesetzt, dann werden DOIs nur für Dokumente im ServerState published registriert. Sollen
     * alle Dokumente unabhängig von ihrem ServerState betrachtet werden, so muss als Aufrufargument null übergeben
     * werden.
     *
     * @param $filterServerState Filter für Attribut ServerState (es werden nur Dokumente mit dem angegeben ServerState
     *                           bei der Registrierung betrachtet); um alle Dokumente unabhängig vom ServerState zu
     *                           betrachten, muss der Wert null übergeben werden (Default: published)
     *
     * @return Opus_Doi_DoiManagerStatus
     *
     */
    public function registerPending($filterServerState = 'published')
    {
        $status = new Opus_Doi_DoiManagerStatus();

        $docFinder = new Opus_DocumentFinder();
        $docFinder->setIdentifierTypeExists('doi');
        if (!is_null($filterServerState)) {
            $docFinder->setServerState($filterServerState);
        }

        $ids = $docFinder->ids();
        if (empty($ids)) {
            $this->defaultLog->info('could not find documents that provide DOIs');
            return $status;
        }

        $this->defaultLog->debug(
            'registerPending found ' . count($ids) . ' published documents with DOIs that need to be checked'
        );

        $numOfSuccessfulRegistrations = 0;
        $notification = new Opus_Doi_DoiMailNotification();

        foreach ($ids as $id) {
            try {
                $doc = new Opus_Document($id);
            }
            catch (Opus_Model_NotFoundException $e) {
                $this->defaultLog->err('could not find document ' . $id . ' in database');
                continue;
            }

            $landingPageUrl = $this->getLandingPageUrlOfDoc($doc);

            // Registrierung der DOI durchführen, sofern es eine lokale DOI gibt, die noch nicht registriert wurde
            try {
                $registeredDoi = $this->register($doc, true);
                if (!is_null($registeredDoi)) {
                    $numOfSuccessfulRegistrations++;
                    $status->addDocWithDoiStatus($id, $registeredDoi->getValue());

                    if ($notification->isEnabled()) {
                        $notification->addNotification($id, $registeredDoi, $landingPageUrl);
                    }
                }
            }
            catch (Opus_Doi_RegistrationException $e) {
                $message = 'an error occurred in registration of DOI ' . $e->getDoi()->getValue() .
                    ' of document ' . $id . ': ' . $e->getMessage();
                $this->defaultLog->err($message);
                $this->doiLog->err($message);
                $status->addDocWithDoiStatus($id, $message, true);
                if ($notification->isEnabled()) {
                    $notification->addNotification($id, $e->getDoi(), $landingPageUrl, $message);
                }
            }
            catch (Opus_Doi_DoiException $e) {
                $message = 'an error occurred in DOI registration for document ' . $id . ': ' . $e->getMessage();
                $this->defaultLog->err($message);
                $this->doiLog->err($message);
                $status->addDocWithDoiStatus($id, $message, true);
                // hier kann kein Eintrag für die E-Mail erzeugt werden, da es keine DOI als Bezug gibt
            }
        }

        if ($numOfSuccessfulRegistrations > 0) {
            $message = 'number of successful DOI registrations: ' . $numOfSuccessfulRegistrations;
            $this->defaultLog->info($message);
            $this->doiLog->info($message);
        }

        if ($notification->isEnabled()) {
            $notification->sendRegistrationEmail();
        }

        return $status;
    }

    /**
     * Prüfe alle registrierten DOIs (im Status registered) für alle OPUS-Dokumente in der Datenbank.
     *
     * @return Opus_Doi_DoiManagerStatus
     */
    public function verifyRegistered()
    {
        return $this->verifyRegisteredBefore();
    }

    /**
     * Prüft, ob die lokale DOI des Dokuments mit der übergebenen ID erfolgreich auflösbar ist.
     * Ist $allowReverification auf false, so werden nur DOIs berücksichtigt, die noch nicht geprüft wurden (Status ist
     * registered); andernfalls werden auch DOIs mit Status verified erneut geprüft.
     *
     * Es kann eine zusätzliche Einschränkung der zu prüfenden DOIs auf Basis des Zeitstempels der DOI-Registrierung
     * vorgenommen, sofern der Parameter $beforeDate gesetzt wird.
     *
     * Die Methode gibt im Erfolgsfall als auch bei fehlerhaftem Prüfungsergebnis die geprüfte lokale DOI zurück.
     * Ist eine Prüfung nicht möglich, so gibt die Methode null zurück, z.B. wenn das Dokument mit der übergebenen ID
     * gar keine DOI hat.
     *
     * @param $docId ID des zu überprüfenden OPUS-Dokuments
     * @param $allowReverification wenn true, dann werden DOIs, die bereits geprüft wurden, erneut geprüft
     * @param $beforeDate Nur DOIs prüfen, deren Registrierung vor dem übergebenen Zeitpunkt liegt
     * @param Opus_Doi_DoiManagerStatus $managerStatus Objekt zum Ablegen von Statusinformationen der DOI-Prüfung
     *
     */
    public function verify($docId, $allowReverification = true, $beforeDate = null, $managerStatus = null)
    {
        try {
            $doc = new Opus_Document($docId);
        }
        catch (Opus_Model_NotFoundException $e) {
            $message = 'could not find document with ID ' . $docId . ' in database';
            $this->doiLog->err($message);
            $this->defaultLog->err($message);
            return null;
        }

        $dois = $doc->getIdentifierDoi();
        if (empty($dois)) {
            // dieser Fall darf eigentlich nicht auftreten, da die Methode nur für Dokumente mit DOIs aufgerufen wird
            $message = 'document ' . $docId . ' does not provide a DOI for verification';
            $this->doiLog->err($message);
            $this->defaultLog->err($message);
            return null;
        }

        if (count($dois) > 1) {
            // es wird grundsätzlich nur die erste DOI eines Dokuments betrachtet
            // hat ein Dokument mehr als eine DOI, so muss es sich um ein Altdokument handeln, das vor der Einführung
            // des DOI-Supports in OPUS4 angelegt wurde und bei dem noch mehrere DOIs angegeben werden durften
            $this->defaultLog->info(
                'document ' . $docId . ' provides ' . count($dois) .
                ' DOIs - consider only the first one for verification'
            );
        }

        $doi = $dois[0];
        if (is_null($doi->getStatus())) {
            // DOI wurde noch nicht registriert, so dass keine Prüfung möglich ist
            $message = 'document ' . $docId . ' does not provide a registered DOI for verification';
            $this->doiLog->debug($message);
            $this->defaultLog->debug($message);
            return null;
        }

        if (!$allowReverification && $doi->getStatus() == 'verified') {
            // erneute Prüfung von bereits geprüften DOIs ist nicht gewünscht
            $message = 'document ' . $docId . ' provides already verified DOI ' . $doi->getValue() .
                ' but DOI reverification is disabled';
            $this->doiLog->debug($message);
            $this->defaultLog->debug($message);
            return null;
        }

        if (is_null($beforeDate) || (!is_null($beforeDate) && $doi->getRegistrationTs() <= $beforeDate)) {

            // prüfe, ob DOI $doi bei DataCite erfolgreich registriert ist und setze dann DOI-Status auf "verified"
            try {
                $client = new \Opus\Doi\Client($this->config, $this->defaultLog);
                $result = $client->checkDoi($doi->getValue(), $this->getLandingPageUrlOfDoc($doc));
                if ($result) {
                    $message = 'verification of DOI ' . $doi->getValue() . ' of document ' . $docId . ' was successful';
                    $this->doiLog->info($message);
                    $this->defaultLog->debug($message);
                    // Status-Upgrade durchführen
                    if ($doi->getStatus() != 'verified') {
                        $doi->setStatus('verified');
                        $doc->store();
                    }
                    if (!is_null($managerStatus)) {
                        $managerStatus->addDocWithDoiStatus($docId, $doi->getValue());
                    }
                }
                else {
                    $message = 'verification of DOI ' . $doi->getValue() . ' in document ' . $docId . ' failed';
                    $this->doiLog->err($message);
                    $this->defaultLog->err($message);
                    // Status-Downgrade durchführen
                    if ($doi->getStatus() == 'verified') {
                        $doi->setStatus('registered');
                        $doc->store();
                    }
                    if (!is_null($managerStatus)) {
                        $managerStatus->addDocWithDoiStatus($docId, $doi->getValue(), true);
                    }
                }
                return $doi;
            }
            catch (Exception $e) {
                $message = 'could not get registration status of DOI ' . $doi->getValue() . ' in document ' .
                    $docId . ': ' . $e->getMessage();
                $this->doiLog->err($message);
                $this->defaultLog->err($message);
                if (!is_null($managerStatus)) {
                    $managerStatus->addDocWithDoiStatus($docId, $message, true);
                }
                return $doi;
            }
        }

        return null;
    }

    /**
     * Prüfung aller registrierten DOIs, die vor einem definierten Zeitpunkt registriert wurden (z.B. vor 24 Stunden).
     * Wird kein Zeitpunkt angegeben, so werden alle registrierten DOIs unabhängig vom Registrierungszeitraum geprüft.
     *
     * @param $beforeDate Zeitstempel der für die Bestimmung der zu prüfenden DOIs verwendet wird: es werden nur DOIs
     *                    geprüft, die vor dem Zeitpunkt, der durch $beforeDate definiert ist, registriert wurden
     *
     * @return Opus_Doi_DoiManagerStatus
     *
     */
    public function verifyRegisteredBefore($beforeDate = null)
    {
        $status = new Opus_Doi_DoiManagerStatus();

        $docFinder = new Opus_DocumentFinder();
        $docFinder->setIdentifierTypeExists('doi');
        $ids = $docFinder->ids();

        if (empty($ids)) {
            return $status;
        }

        $notification = new Opus_Doi_DoiMailNotification();

        foreach ($ids as $id) {
            $doi = $this->verify($id, false, $beforeDate, $status);

            if (is_null($doi)) {
                $this->defaultLog->info('could not check DOI registration status of document ' . $id);
                continue;
            }

            $landingPageUrl = $this->getLandingPageUrlOfDoc($id);

            if ($notification->isEnabled()) {
                if ($doi->getStatus() == 'verified') {
                    // erfolgreiche Prüfung der DOI durchgeführt: Erfolg per E-Mail melden
                    $notification->addNotification($id, $doi, $landingPageUrl);
                }
                else {
                    // fehlgeschlagene Prüfung der DOI: Fehler per E-Mail melden
                    $notification->addNotification(
                        $id, $doi, $landingPageUrl, 'DOI-Prüfung war nicht erfolgreich'
                    );
                }
            }
        }

        if ($notification->isEnabled()) {
            $notification->sendVerificationEmail();
        }

        return $status;
    }

    /**
     * Ermittelt alle OPUS-Dokumente, die eine lokale DOI mit dem übergebenen Status haben.
     * Wenn $status nicht angegeben werden, so werden alle lokalen DOIs unabhängig vom Status betrachtet.
     *
     * @param $statusFilter Erlaubt die Filterung der zu berücksichtigenden DOIs nach ihrem Status.
     */
    public function getAll($statusFilter = null)
    {
        // ermittle alle Dokumente, die eine lokale DOI haben
        // wenn ein Dokument mehr als eine DOI haben sollte (Altdokument, das noch vor der
        // Einführung des DOI-Supports angelegt wurde), dann wird nur die erste DOI betrachtet
        // weil nur diese für eine DOI-Registrierung überhaupt in Frage kommt

        $result = [];

        $docFinder = new Opus_DocumentFinder();
        $docFinder->setIdentifierTypeExists('doi');

        foreach ($docFinder->ids() as $id) {
            $doc = new Opus_Document($id);
            $dois = $doc->getIdentifierDoi();
            $firstDoi = $dois[0];

            // handelt es sich um eine lokale DOI?
            if (!$this->isLocalDoi($firstDoi->getValue())) {
                continue;
            }

            // hat die lokale DOI den gesuchten Registrierungsstatus
            $status = $firstDoi->getStatus();
            if (is_null($statusFilter) ||
                ($statusFilter == 'unregistered' && is_null($status)) ||
                ($statusFilter != 'unregistered' && $status == $statusFilter)) {
                $result[] = $doc;
            }
        }

        return $result;
    }

    /**
     * Erzeugt auf Basis der konfigurierten DOI-Generator-Klasse einen DOI-Wert für das übergebene Dokument.
     * Gibt den Wert zurück oder wirft eine Exception, wenn die Generierung nicht möglich ist.
     *
     * @param $doc Opus_Document, für das ein DOI-Wert generiert werden soll oder ID eines Dokuments
     *             für eine ID (string), wird versucht das zugehörige Opus_Document aus der Datenbank zu laden
     *
     * @throws DoiException
     */
    public function generateNewDoi($doc)
    {
        $generator = null;
        try {
            $generator = Opus_Doi_Generator_DoiGeneratorFactory::create();
        }
        catch (Opus_Doi_DoiException $e) {
            $this->defaultLog->err($e->getMessage());
            $this->doiLog->err($e->getMessage());
            throw $e;
        }

        if (is_string($doc) && is_numeric($doc)) {
            $docId = $doc;
            try {
                $doc = new Opus_Document($docId);
            }
            catch (Opus_Model_NotFoundException $e) {
                $message = 'could not find document ' . $docId . ' in database';
                $this->defaultLog->err($message);
                throw new Opus_Doi_DoiException($message);
            }
        }

        if (is_null($doc) || !($doc instanceof Opus_Document)) {
            $message = 'unexpected document class';
            $this->defaultLog->err($message);
            throw new Opus_Doi_DoiException($message);
        }

        try {
            $doiValue = $generator->generate($doc);
        }
        catch (Opus_Doi_Generator_DoiGeneratorException $e) {
            $message = 'could not generate DOI using generator class: ' . $e->getMessage();
            $this->defaultLog->err($message);
            $this->doiLog->err($message);
            throw new Opus_Doi_DoiException($message);
        }

        return $doiValue;
    }

    /**
     * Markiert den Datensatz, der mit der lokalen DOI bei DataCite registriert ist, als inaktiv.
     *
     * @param $doc ID des Dokuments
     */
    public function deleteMetadataForDoi($doc)
    {
        $dois = $doc->getIdentifierDoi();
        if (empty($dois)) {
            $this->defaultLog->debug(
                'document ' . $doc->getId() . ' does not provide a DOI - deregistration of DOI is not required'
            );
            return;
        }

        // wenn mehrere DOIs vorhanden, so wird nur die erste DOI betrachtet
        $doi = $dois[0];
        if (!$doi->isLocalDoi()) {
            // keine Behandlung von nicht-lokale DOIs erforderlich
            $this->defaultLog->debug(
                'document ' . $doc->getId() . ' does not provide a local DOI - deregistration of DOI is not required'
            );
            return;
        }

        $status = $doi->getStatus();
        if ($status != 'registered' && $status != 'verified') {
            $this->defaultLog->debug('document ' . $doc->getId() .
                ' does not provide a registered local DOI - deregistration of DOI is not required');
            return;
        }

        try {
            $client = new \Opus\Doi\Client($this->config, $this->defaultLog);
            $client->deleteMetadataForDoi($doi->getValue());
            $message = 'metadata deletion of DOI ' . $doi->getValue() . ' of document ' . $doc->getId()
                . ' was successful';
            $this->defaultLog->debug($message);
            $this->doiLog->info($message);
            // TODO sollte der Status der lokalen DOI auf "inactive" o.ä. gesetzt werden
        }
        catch (\Opus\Doi\ClientException $e) {
            $message = 'an error occurred while deregistering DOI ' . $doi->getValue() . ' of document ' .
                $doc->getId() . ': ' . $e->getMessage();
            $this->doiLog->err($message);
            $this->defaultLog->err($message);
            // Exception wird nicht nach oben durchgereicht, weil bislang nur Aufruf aus Plugin erfolgt
        }
    }

    public function updateLandingPageUrlOfDoi($doiValue, $landingPageURL)
    {
        try {
            $client = new \Opus\Doi\Client($this->config);
            $client->updateUrlForDoi($doiValue, $landingPageURL);
        }
        catch (\Opus\Doi\ClientException $e) {
            $message = 'could not update landing page URL of DOI ' . $doiValue . ' to ' . $landingPageURL;
            $this->doiLog->err($message);
            $this->defaultLog->err($message);
            throw new Opus_Doi_DoiException($message);
        }
    }

    public function getLandingPageBaseUrl()
    {
        if (is_null($this->landingPageBaseUrl)) {
            if (isset($this->config->url)) {
                $baseUrl = rtrim($this->config->url, '/') . '/';

                if (isset($this->config->doi->landingPageBaseUri)) {
                    $baseUrl .= ltrim($this->config->doi->landingPageBaseUri, '/');
                }

                $this->landingPageBaseUrl = rtrim($baseUrl, '/') . '/';
            }
            else {
                // TODO is this too harsh? recover how?
                throw new Opus_Doi_DoiException(
                    'No URL for repository configured. Cannot generate landing page URL.'
                );
            }
        }
        return $this->landingPageBaseUrl;
    }

    public function getLandingPageUrlOfDoc($doc)
    {
        $baseUrl = $this->getLandingPageBaseUrl();

        if (is_null($baseUrl)) {
            return null;
        }

        if ($doc instanceof Opus_Document) {
            $result = $baseUrl . $doc->getId();
        } else {
            $result = $baseUrl . $doc;
        }

        return $result;
    }

    public function isKeepRegistrationXml()
    {
        return $this->keepRegistrationXml;
    }

    public function setKeepRegistrationXml($enabled)
    {
        $this->keepRegistrationXml = $enabled;
    }

    /**
     * Store registration XML for error analyis and backup.
     * @param $doc Opus_Document
     * @param $xml string
     */
    public function storeRegistrationXml($doc, $xml)
    {
        $config = $this->config;

        $path = $config->workspacePath . '/log/doi/';

        if (!is_dir($path)) {
            // TODO optimize? wait for exception?
            // create path
            mkdir($path);
            chmod($path, 0775);
        }

        $timestamp = new DateTime();
        $basename = 'doc' . $doc->getId() . $timestamp->format('_Y-m-d\TH:i:s');

        $index = 2;
        $filename = "$basename.xml";

        while(file_exists($path . $filename)) {
            $filename = "$basename-$index.xml";
            $index++;
        }

        $filePath = $path . $filename;

        file_put_contents($filePath, $xml);
    }
}
