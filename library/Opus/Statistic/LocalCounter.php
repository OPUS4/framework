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
 * @category    Framework
 * @package     Opus_Statistic
 * @author      Tobias Leidinger <tobias.leidinger@gmail.com>
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2009-2019, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

/**
 * Controller for Opus Applications.
 *
 * @category    Framework
 * @package     Opus_Statistic
 */
class Opus_Statistic_LocalCounter
{

    /**
     * Holds instance of the class
     * @var Statistic_LocalCounter
     */
    private static $localCounter = null;

    /**
     * double click interval for fulltext in sec
     * @var int
     */
    private $doubleClickIntervalPdf = 30;

    /**
     * double click interval for frontdoor in sec
     * @var int
     */
    private $doubleClickIntervalHtml = 10;

    private $spiderList = [
        'Alexandria prototype project',
        'Arachmo',
        'Brutus/AET',
        'Code Sample Web Client',
        'dtSearchSpider',
        'FDM 1',
        'Fetch API Request',
        'GetRight',
        'Goldfire Server',
        'Googlebot',
        'httpget-5.2.2',
        'HTTrack',
        'iSiloX',
        'libwww-perl',
        'LWP::Simple',
        'lwp-trivial',
        'Microsoft URL Control',
        'Milbot',
        'MSNBot',
        'NaverBot',
        'Offline Navigator',
        'playstarmusic.com',
        'Python-urllib',
        'Readpaper',
        'Strider',
        'Teleport Pro',
        'Teoma',
        'T-H-U-N-D-E-R-S-T-O-N-E',
        'Web Downloader',
        'WebCloner',
        'WebCopier',
        'WebReaper',
        'WebStripper',
        'WebZIP',
        'Wget',
        'Xenu Link Sleuth'
    ];

    private function __construct()
    {
    }

    /**
     *
     * @return Opus_Statistic_LocalCounter
     */
    public static function getInstance()
    {
        if (self::$localCounter == null) {
            self::$localCounter = new Opus_Statistic_LocalCounter();
        }
        return self::$localCounter;
    }

    /**
     * check whether user agent contains one of the spiders from the counter list
     *
     * @param $userAgent $_SERVER['user_agent'] string
     * @return bool is spieder?
     */
    private function checkSpider($userAgent)
    {
        $userAgent = strtolower($userAgent);

        foreach ($this->spiderList as $spider) {
            if (stristr($userAgent, $spider) != false || stristr($userAgent, str_replace(' ', '+', $spider)) != false) {
                return true;
            }
        }
        return false;
    }

    private function isRedirectStatusOk($redirectStatus)
    {
        return $redirectStatus == 200 || $redirectStatus == 304;
    }


    public function countFrontdoor($documentId)
    {
        $this->count($documentId, -1, 'frontdoor');
    }

    public function countFiles($documentId, $fileId)
    {
        $this->count($documentId, $fileId, 'files');
    }

    /**
     *
     *
     * @param $documentId
     * @param $fileId
     * @param $ip
     * @param $userAgent
     * @param $redirectStatus

     * @return int new counter value for given doc_id - month -year triple or FALSE if double click or spider
     */
    public function count($documentId, $fileId, $type, $ip = null, $userAgent = null, $redirectStatus = null)
    {
        if (! $this->isLocalCounterEnabled()) {
            return 0;
        }

        if ($type != 'frontdoor' && $type != 'files') {
            //print('type not defined');
            return 0;
        }
        if ($ip == null || $ip == '') {
            if (array_key_exists('REMOTE_ADDR', $_SERVER)) {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
        }
        if ($userAgent == null || $userAgent == '') {
            if (array_key_exists('HTTP_USER_AGENT', $_SERVER)) {
                $userAgent = $_SERVER['HTTP_USER_AGENT'];
            }
        }
        if ($redirectStatus == null || $redirectStatus == '') {
            if (array_key_exists('REDIRECT_STATUS', $_SERVER)) {
                $redirectStatus = $_SERVER['REDIRECT_STATUS'];
            }
        }



        $time = time();
        //determine whether it was a double click or not
        if ($this->isRedirectStatusOk($redirectStatus) == false) {
        //    print('wrong redirect status');
            return 0;
        }
        if ($this->checkSpider($userAgent) == true) {
        //    print('spider found');
            return 0;
        }

        //don't log any file id if the frontdoor is counted
        if ($type == 'frontdoor') {
            $fileId = -1;
        }
        if ($this->logClick($documentId, $fileId, $time) == true) {
        //    print('double click');
            return 0;
        }

        //no double click? increase counter!
        $year = date('Y', $time);
        $month = date('n', $time);

        $ods = Opus_Db_TableGateway::getInstance("Opus_Db_DocumentStatistics");
        $db = $ods->getAdapter();
        $db->beginTransaction();

        try {
            $value = 0;
            $createEntry = true;

            $rowSet = $ods->find($documentId, $year, $month, $type);
            if ($rowSet->count() > 0) {
                $value = $rowSet->current()->count;
                $createEntry = false;
            }

            $value++;
            $data = [
                'document_id' => $documentId,
                'year' => $year,
                'month' => $month,
                'count' => $value,
                'type' => $type,
            ];

            //TODO direct $ods->insert() possible??

            $where = $db->quoteInto('document_id = ?', $documentId) .
            $ods->getAdapter()->quoteInto(' AND year = ?', $year) .
            $ods->getAdapter()->quoteInto(' AND month = ?', $month) .
            $ods->getAdapter()->quoteInto(' AND type = ?', $type);


            if ($createEntry == true) {
                $ods->insert($data);
            } else {
                $ods->update($data, $where);
            }

            $db->commit();
            return $value;
        } catch (Exception $e) {
            $db->rollBack();
            print ($e->getMessage());
            return 0;
        }

        return 0;
    }

    /**
     * log click to temp file and return whether it was a double click or not
     *
     * @param $ip ip of client
     * @param $documentId id of documents table
     * @param $fileId id of document_files table
     * @return bool is it a double click
     */
    public function logClick($documentId, $fileId, $time)
    {
        $ip = '';
        if (array_key_exists('REMOTE_ADDR', $_SERVER)) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        $registry = Zend_Registry::getInstance();
        $tempDir = $registry->get('temp_dir');
        //initialize log data
        $md5Ip = "h".md5($ip);


        //TODO determine file type of file id
        $filetype = 'pdf';

        $dom = new DOMDocument();
        if (file_exists($tempDir . '~localstat.xml') === false) {
            $xmlAccess = $dom->createElement('access');
            $dom->appendChild($xmlAccess);
        } else {
            $dom->load($tempDir . '~localstat.xml');
        }

        $xmlAccess = $dom->getElementsByTagName("access")->item(0);
        if (is_null($xmlAccess)) {
            $message = 'Error loading click-log "' . $tempDir . '~localstat.xml"';
            throw new Opus\Model\Exception($message);
        }

        //if global file access timestamp too old, the whole log file can be removed
        $xmlTime = $dom->getElementsByTagName("time")->item(0);
        if ($xmlTime != null && ($time - $xmlTime->nodeValue) > max($this->doubleClickIntervalHtml, $this->doubleClickIntervalPdf)) {
            $xmlAccess = $dom->getElementsByTagName("access")->item(0);
            $dom->removeChild($xmlAccess);
            $xmlAccess = $dom->createElement('access');
            $dom->appendChild($xmlAccess);
        }

        $xmlTime = $xmlAccess->getElementsByTagName('time')->item(0);
        if ($xmlTime != null) {
            $xmlAccess->removeChild($xmlTime);
        }
        $xmlTime = $dom->createElement('time', $time);
        $xmlAccess->appendChild($xmlTime);
        //get document id, create if not exists
        $xmlDocumentId = $dom->getElementsByTagName('document'. $documentId)->item(0);
        if ($xmlDocumentId == null) {
            $xmlDocumentId = $dom->createElement('document'. $documentId);
            $xmlAccess->appendChild($xmlDocumentId);
        }

        //get ip node
        $xmlIp = $xmlDocumentId->getElementsByTagName($md5Ip)->item(0);
        if ($xmlIp == null) {
            $xmlIp = $dom->createElement($md5Ip);
            $xmlDocumentId->appendChild($xmlIp);
        }

        //get file id, create if not exists
        $xmlFileId = $xmlIp->getElementsByTagName('file'. $fileId)->item(0);
        if ($xmlFileId == null) {
            $xmlFileId = $dom->createElement('file'. $fileId);
            $xmlIp->appendChild($xmlFileId);
        }

        //read last Access for this file id
        $fileIdTime = $xmlFileId->getAttribute('lastAccess');
        $doubleClick = false;

        if ($fileIdTime == null || $time - $fileIdTime > max($this->doubleClickIntervalHtml, $this->doubleClickIntervalPdf)) {
            /*no lastAccess set (new entry for this id) or lastAccess too far away
             -> create entry with actual time -> return no double click*/
        } elseif ((($time - $fileIdTime) <= $this->doubleClickIntervalHtml) && (($filetype == 'html') || ($fileId == -1))) {
            //html file double click
            $doubleClick = true;
        } elseif ((($time - $fileIdTime) <= $this->doubleClickIntervalPdf) && ($filetype == 'pdf') && ($fileId != -1)) {
            //pdf file double click
            $doubleClick = true;
        }

        $xmlFileId->setAttribute('lastAccess', $time);
        $return = $dom->save($tempDir . '~localstat.xml');
        if ($return === false) {
            $message = 'Error saving click-log "' . $tempDir . '~localstat.xml"';
            throw new Opus\Model\Exception($message);
        }

        return $doubleClick;
    }

    public function readMonths($documentId, $datatype = 'files', $year = null)
    {
        if ($year == null) {
            //set current year
            $year = date('Y', time());
        }

        if ($datatype != 'files' && $datatype != 'frontdoor') {
            $datatype = 'files';
        }
        $ods = Opus_Db_TableGateway::getInstance('Opus_Db_DocumentStatistics');
        $select = $ods->select()->where('year = ?', $year)
            ->where('document_id = ?', $documentId)
            ->where('type = ?', $datatype)
            ->order('month');

        $queryResult = $ods->fetchAll($select);
        $result = [];
        foreach ($queryResult as $row) {
            $result[$row->month] = $row->count;
        }

        if (isset($result) === false) {
            for ($i = 1; $i <= 12; $i++) {
                $result[$i] = 0;
            }
        }
        return $result;
    }

    public function readYears($documentId, $datatype = 'files')
    {
        if ($datatype != 'files' && $datatype != 'frontdoor') {
            $datatype = 'files';
        }
        $ods = Opus_Db_TableGateway::getInstance('Opus_Db_DocumentStatistics');

        $select = $ods->select()
            ->from(['stat' => 'document_statistics'], ['count' => 'SUM(stat.count)'])
            ->from(['stat2' => 'document_statistics'], ['year' => 'stat2.year'])
            ->where('stat.type = ?', $datatype)
            ->where('stat.document_id = ?', $documentId)
            ->where('stat.document_id = stat2.document_id')
            ->where('stat.year = stat2.year')
            ->where('stat.month = stat2.month')
            ->where('stat.type = stat2.type')
            ->group('stat2.year')
            ->order('stat2.year');

        $queryResult = $ods->fetchAll($select);
        $result = [];
        foreach ($queryResult as $row) {
            $result[$row->year] = $row->count;
        }

        if (isset($result) === false) {
            $result = [date('Y') => 0];
        }
        return $result;
    }

    public function readTotal($documentId, $datatype = 'files')
    {
        if ($datatype != 'files' && $datatype != 'frontdoor') {
            $datatype = 'files';
        }
        $ods = Opus_Db_TableGateway::getInstance('Opus_Db_DocumentStatistics');

        $select = $ods->select()
            ->from(['stat' => 'document_statistics'], ['count' => 'SUM(stat.count)'])
            ->where('stat.type = ?', $datatype)
            ->where('stat.document_id = ?', $documentId);


        $queryResult = $ods->fetchAll($select);
        unset($result);
        foreach ($queryResult as $row) {
            $result = $row->count;
        }

        if (isset($result) === false) {
            $result = 0;
        }
        return $result;
    }

    /**
     * @return bool
     * TODO review OPUSVIER-4200 and decide if code gets reactivated or removed
     */
    public function isLocalCounterEnabled()
    {
        $config = Opus_Config::get();
        if (isset($config->statistics->localCounterEnabled) &&
            filter_var($config->statistics->localCounterEnabled, FILTER_VALIDATE_BOOLEAN)) {
        } else {
            return false;
        }
    }
}
