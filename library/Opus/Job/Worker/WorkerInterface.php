<?php

/**
 * LICENCE
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @copyright   Copyright (c) 2009-2010
 *              Saechsische Landesbibliothek - Staats- und Universitaetsbibliothek Dresden (SLUB)
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * @author      Ralf Claussnitzer (ralf.claussnitzer@slub-dresden.de)
 */

namespace Opus\Job\Worker;

use Opus\Common\JobInterface;
use Zend_Log;

/**
 * Basic process interface as required to define
 * worker processes for Job_Runnner.
 */
interface WorkerInterface
{
    /**
     * Return message label that is used to trigger worker process.
     *
     * @return string Message label.
     */
    public function getActivationLabel();

    /**
     * Perfom work.
     *
     * @param JobInterface $job Job description and attached data.
     * @return array Array of Jobs to be newly created.
     */
    public function work($job);

    /**
     * Set logging facility.
     *
     * @param Zend_Log $logger Logger instance.
     */
    public function setLogger($logger);
}
