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
 * @package     Opus_Job
 * @subpackage  Worker
 * @author      Sascha Szott <szott@zib.de>
 * @copyright   Copyright (c) 2013, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Worker class for checking consistency between documents in database and Solr index.
 *
 */
class Opus_Job_Worker_ConsistencyCheck extends Opus_Job_Worker_Abstract {

    const LABEL = 'opus-consistency-check';

    public function __construct() {
        $this->setLogger();
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
     * Load all published documents from database and check consistency.
     * A document is considered as inconsistent, if
     *
     * - it exists in database, but does not exist in Solr index
     * - it exists in Solr index, but does not exist in database or exists
     *   but with server_state != published
     * - it exists both in database and Solr index, but server_date_modified
     *   timestamps do not coincide
     *
     * @param Opus_Job $job Job description and attached data.
     * @return void
     */
    public function work(Opus_Job $job) {

        // make sure we have the right job
        if ($job->getLabel() != $this->getActivationLabel()) {
            throw new Opus_Job_Worker_InvalidJobException($job->getLabel() . " is not a suitable job for this worker.");
        }

        $consistencyChecker = new Opus_Util_ConsistencyCheck($this->_logger);
        $consistencyChecker->run();
    }

    public function setLogger($logger = null) {
        $config = Zend_Registry::get('Zend_Config');
        $logfilePath = $config->workspacePath . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR . 'opus_consistency-check.log';
        $logfile = @fopen($logfilePath, 'w', false);
        $writer = new Zend_Log_Writer_Stream($logfile);
        
        $format = '[%timestamp%] %priorityName%: %message%' . PHP_EOL;
        $formatter = new Zend_Log_Formatter_Simple($format);
        $writer->setFormatter($formatter);

        parent::setLogger(new Zend_Log($writer));
    }

}
