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
 * @author      Ralf Claussnitzer (ralf.claussnitzer@slub-dresden.de)
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Deliver jobs to worker objects.
 *
 * @category    Framework
 * @package     Opus_Job
 */
class Opus_Job_Runner {

    /**
     * Associative array of registered workers. Maps messsage lable
     * to worker instance.
     *
     * @var array
     */
    protected $_workers = array();

    /**
     * Pause in seconds before the next worker is run.
     *
     * @var int
     */
    protected $_delay = 1;

    /**
     * How many jobs should be done in a run.
     *
     * @var integer
     */
    protected $_limit = null;

    /**
     * Holds the instance of the current logger.
     *
     * @var Zend_Log
     */
    protected $_logger = null;

    /**
     * Register a new worker process.
     *
     * @param Opus_Job_Worker_Interface $worker Worker instance to register.
     * @return void
     */
    public function registerWorker(Opus_Job_Worker_Interface $worker) {
        $this->_workers[$worker->getActivationLabel()] = $worker;
    }

    /**
     * Set the current logger instance.
     *
     * @param Zend_Log $log Logger.
     * @return Opus_Job_Runner Fluent interface.
     */
    public function setLogger(Zend_Log $logger) {
        $this->_logger = $logger;
    }

    /**
     * Set the worker delay time in seconds.
     *
     * @param int $seconds Pause in seconds before the next worker runs.
     * @return void
     */
    public function setDelay($seconds) {
        $this->_delay = (int) $seconds;
        if (null !== $this->_logger) {
            $this->_logger->info('Set worker delay to ' . $seconds . 's');
        }
    }

    /**
     * Set a limit to number of executing jobs at a run.
     *
     * @param int $limit Limit for jobs.
     * @return void
     */
    public function setLimit($limit = null) {
        if ((null !== $limit) and (true === is_int($limit))) {
            $this->_logger->info('Set job limit to ' . $limit . ' jobs / run.');
            $this->_limit = $limit;
        }
    }

    /**
     * Run scheduling of jobs. All jobs currently in the queue get
     * processed and any new jobs get created in the jobs table.
     *
     * @return void
     */
    public function run() {
        $jobs = Opus_Job::getByLabels(array_keys($this->_workers), $this->_limit, Opus_Job::STATE_UNDEFINED);
    
        if (null !== $this->_logger)
            $this->_logger->info('Found ' . count($jobs). ' job(s)');

        $runJobs = 0;
        foreach ($jobs as $job) {
            if (true === $this->consume($job)) {
                $runJobs++;
            } else {
                if (null !== $this->_logger)
                    $this->_logger->warn('Job with ID ' . $job->getId(). ' failed.');
            }
        }
        if (null !== $this->_logger)
            $this->_logger->info('Processed ' . $runJobs. ' job(s).');
    }

    /**
     * Execute a job and remove it from the jobs table on success.
     *
     * @param Opus_Job $job Job description model.
     * @return boolean Returns true if a job is consumend false if not
     */
    protected function consume(Opus_Job $job) {
        $label = $job->getLabel();

        if ($job->getState() !== null) {
            return false;
        }

        if (array_key_exists($label, $this->_workers)) {
            $worker = $this->_workers[$label];

            if (null !== $this->_logger) {
                $this->_logger->info('Processing ' . $label);
            }

            $job->setState(Opus_Job::STATE_PROCESSING);
            $job->store();

            try {
                $worker->setLogger($this->_logger);
                $worker->work($job);
                $job->delete();
                sleep($this->_delay);
            } catch (Exception $ex) {
                if (null !== $this->_logger) {
                    $msg = get_class($worker) . ': ' . $ex->getMessage();
                    $this->_logger->err($msg);
                }
                $job->setErrors(json_encode(array(
                   'exception'  => get_class($ex),
                   'message'  => $ex->getMessage(),
                   'trace' => $ex->getTraceAsString()
                )));
                $job->setState(Opus_Job::STATE_FAILED);
                $job->store();
                return false;
            }
            return true;
        }
        return false;
    }

}

