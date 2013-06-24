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
 * @package     Opus_Model
 * @author      Gunar Maiwald <maiwald@zib.de>
 * @copyright   Copyright (c) 2008-2012, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Worker for sending out email notifications for newly published documents.
 */
class Opus_Job_Worker_MetadataImport extends Opus_Job_Worker_Abstract {

    const LABEL = 'opus-metadata-import-notification';

    /**
     * Constructs worker.
     * @param Zend_Log $logger
     */
    public function __construct($logger = null) {
        $this->setLogger($logger);
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


        if ($job->getLabel() != $this->getActivationLabel()) {
            throw new Opus_Job_Worker_InvalidJobException($job->getLabel() . " is not a suitable job for this worker.");
        }

        $data = $job->getData();

        if (!(is_object($data) && isset($data->xml) && !is_null($data->xml))) {
             throw new Opus_Job_Worker_InvalidJobException("Incomplete or missing data.");
        }

        if (null !== $this->_logger) {
            $this->_logger->info('Importing Metadata : ' . $data->xml );
        }
	
	$doc = new DOMDocument();
	$doc->loadXML($data->xml);
	
	$importer = new Opus_Util_MetadataImport($doc);
	$importer->run();
    }

}
