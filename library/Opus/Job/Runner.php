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
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\Job;

use Exception;
use Opus\Common\Job;
use Opus\Common\JobInterface;
use Opus\Job\Worker\WorkerInterface;
use Zend_Log;

use function array_key_exists;
use function array_keys;
use function count;
use function get_class;
use function is_int;
use function json_encode;
use function sleep;

/**
 * Deliver jobs to worker objects.
 */
class Runner
{
    /**
     * Associative array of registered workers. Maps messsage lable
     * to worker instance.
     *
     * @var array
     */
    protected $workers = [];

    /**
     * Pause in seconds before the next worker is run.
     *
     * @var int
     */
    protected $delay = 1;

    /**
     * How many jobs should be done in a run.
     *
     * @var int
     */
    protected $limit;

    /**
     * Holds the instance of the current logger.
     *
     * @var Zend_Log
     */
    protected $logger;

    /**
     * Register a new worker process.
     *
     * @param WorkerInterface $worker Worker instance to register.
     */
    public function registerWorker(WorkerInterface $worker)
    {
        $this->workers[$worker->getActivationLabel()] = $worker;
    }

    /**
     * Set the current logger instance.
     *
     * @param Zend_Log $logger Logger.
     * @return $this Fluent interface.
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * Set the worker delay time in seconds.
     *
     * @param int $seconds Pause in seconds before the next worker runs.
     */
    public function setDelay($seconds)
    {
        $this->delay = (int) $seconds;
        if (null !== $this->logger) {
            $this->logger->info('Set worker delay to ' . $seconds . 's');
        }
    }

    /**
     * Set a limit to number of executing jobs at a run.
     *
     * @param null|int $limit Limit for jobs.
     */
    public function setLimit($limit = null)
    {
        if ((null !== $limit) && (true === is_int($limit))) {
            $this->logger->info('Set job limit to ' . $limit . ' jobs / run.');
            $this->limit = $limit;
        }
    }

    /**
     * Run scheduling of jobs. All jobs currently in the queue get
     * processed and any new jobs get created in the jobs table.
     */
    public function run()
    {
        $jobs = Job::getByLabels(array_keys($this->workers), $this->limit, Job::STATE_UNDEFINED);

        if (null !== $this->logger) {
            $this->logger->info('Found ' . count($jobs) . ' job(s)');
        }

        $runJobs = 0;
        foreach ($jobs as $job) {
            if (true === $this->consume($job)) {
                $runJobs++;
            } else {
                if (null !== $this->logger) {
                    $this->logger->warn('Job with ID ' . $job->getId() . ' failed.');
                }
            }
        }
        if (null !== $this->logger) {
            $this->logger->info('Processed ' . $runJobs . ' job(s).');
        }
    }

    /**
     * Execute a job and remove it from the jobs table on success.
     *
     * @param JobInterface $job Job description model.
     * @return bool Returns true if a job is consumend false if not
     */
    protected function consume($job)
    {
        $label = $job->getLabel();

        if ($job->getState() !== null) {
            return false;
        }

        if (array_key_exists($label, $this->workers)) {
            $worker = $this->workers[$label];

            if (null !== $this->logger) {
                $this->logger->info('Processing ' . $label);
            }

            $job->setState(Job::STATE_PROCESSING);
            $job->store();

            try {
                $worker->setLogger($this->logger);
                $worker->work($job);
                $job->delete();
                sleep($this->delay);
            } catch (Exception $ex) {
                if (null !== $this->logger) {
                    $msg = get_class($worker) . ': ' . $ex->getMessage();
                    $this->logger->err($msg);
                }
                $job->setErrors(json_encode([
                    'exception' => get_class($ex),
                    'message'   => $ex->getMessage(),
                    'trace'     => $ex->getTraceAsString(),
                ]));
                $job->setState(Job::STATE_FAILED);
                $job->store();
                return false;
            }
            return true;
        }
        return false;
    }
}
