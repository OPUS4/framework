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
 * @author      Gunar Maiwald <maiwald@zib.de>
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2008-2021, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
*/

namespace Opus\Job\Worker;

use Laminas\Log\Logger;
use Opus\Job;

/**
 * Worker for importing metadata
 */
class MetadataImport extends AbstractWorker
{

    const LABEL = 'opus-metadata-import';

    /**
     * Constructs worker.
     * @param Logger $logger
     */
    public function __construct($logger = null)
    {
        $this->setLogger($logger);
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
     * @param Job $job Job description and attached data.
     * @return array Array of Jobs to be newly created.
     */
    public function work(Job $job)
    {

        if ($job->getLabel() != $this->getActivationLabel()) {
            throw new InvalidJobException($job->getLabel() . " is not a suitable job for this worker.");
        }

        $data = $job->getData();

        if (! (is_object($data) && isset($data->xml) && ! is_null($data->xml))) {
             throw new InvalidJobException("Incomplete or missing data.");
        }

        if (null !== $this->_logger) {
            $this->_logger->debug("Importing Metadata:\n" . $data->xml);
        }

        $importer = new \Opus\Util\MetadataImport($data->xml);
        $importer->run();
    }
}
