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
 * @author      Ralf Claussnitzer (ralf.claussnitzer@slub-dresden.de)
 * @copyright   Copyright (c) 2009-2010
 *              Saechsische Landesbibliothek - Staats- und Universitaetsbibliothek Dresden (SLUB)
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id: Interface.php 5765 2010-06-07 14:15:00Z claussni $
 */

/**
 * Basic process interface as required to define
 * worker processes for Job_Runnner.
 *
 * @category    Framework
 * @package     Opus_Job
 * @subpackage  Worker
 */
interface Opus_Job_Worker_Interface {
    /**
     * Return message label that is used to trigger worker process.
     *
     * @return string Message label.
     */
    public function getActivationLabel();
    
    /**
     * Perfom work.
     *
     * @param Opus_Job $job Job description and attached data.
     * @return array Array of Jobs to be newly created.
     */
    public function work(Opus_Job $job);
    
    /**
     * Set logging facility.
     *
     * @param Zend_Log $logger Logger instance.
     * @return void
     */
    public function setLogger($logger);
    
}

