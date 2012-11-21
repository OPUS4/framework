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
 * @category    Tests
 * @package     Opus_Document_Plugin
 * @author      Edouard Simon edouard.simon@zib.de
 * @copyright   Copyright (c) 2010-2012, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id:$
 */
class Opus_Document_Plugin_IndexTest extends TestCase {

    public function testCreateIndexJob() {

        $asyncFlag = $this->setAsynchronousExecution();

        $indexJobsBefore = Opus_Job::getByLabels(array('opus-index-document'));
        $jobCountBefore = count($indexJobsBefore);

        $document = new Opus_Document();
        $document->setServerState('published');
        $documentId = $document->store();

        $indexJobs = Opus_Job::getByLabels(array('opus-index-document'));

        $this->assertEquals(++$jobCountBefore, count($indexJobs), 'Expected new job');

        $newJob = $this->getCreatedJob($documentId, $indexJobs);

        $this->assertNotNull($newJob, 'Expected new job');
        $this->assertEquals('index', $newJob->getData()->task);

        
        
        $document->deletePermanent();
        if (!is_null($newJob))
            $newJob->delete();

        $this->resetAsynchronousExecution($asyncFlag);
    }

    public function testDoNotCreateIndexJobIfAsyncDisabled() {

        $asyncFlag = $this->setAsynchronousExecution(false);
        $indexJobsBefore = Opus_Job::getByLabels(array('opus-index-document'));
        $jobCountBefore = count($indexJobsBefore);

        $document = new Opus_Document();
        $document->setServerState('published');
        $documentId = $document->store();

        $indexJobs = Opus_Job::getByLabels(array('opus-index-document'));

        $this->assertEquals($jobCountBefore, count($indexJobs), 'Expected equal job count before and after storing document.');

        $newJob = $this->getCreatedJob($documentId, $indexJobs);
        $this->assertNull($newJob, 'Expected that no job was created');

        $document->deletePermanent();
        if (!is_null($newJob))
            $newJob->delete();

        $this->resetAsynchronousExecution($asyncFlag);
    }

    public function testCreateRemoveIndexJob() {

        $asyncFlag = $this->setAsynchronousExecution();

        $removeIndexJobsBefore = Opus_Job::getByLabels(array('opus-remove-index-document'));
        $jobCountBefore = count($removeIndexJobsBefore);

        $document = new Opus_Document();
        $document->setServerState('published');
        $documentId = $document->store();

        $indexJobs = Opus_Job::getByLabels(array('opus-index-document'));
        $newIndexJob = $this->getCreatedJob($documentId, $indexJobs);
        $this->assertNotNull($newIndexJob, 'Expected new opus-index-document job');

        if (!is_null($newIndexJob))
            $newIndexJob->delete();

        $document->delete();
        $removeIndexJobs = Opus_Job::getByLabels(array('opus-index-document'));
        $this->assertEquals(++$jobCountBefore, count($removeIndexJobs), 'Expected increased opus-remove-index-document job count');

        $newJob = $this->getCreatedJob($documentId, $removeIndexJobs);
        $this->assertNotNull($newJob, 'Expected new opus-remove-index-document job');
        $this->assertEquals('remove', $newJob->getData()->task);

        $document->deletePermanent();

        if (!is_null($newJob))
            $newJob->delete();

        $this->resetAsynchronousExecution($asyncFlag);
    }

    public function testDoNotCreateRemoveIndexJobIfAsyncDisabled() {

        $asyncFlag = $this->setAsynchronousExecution(false);

        $removeIndexJobsBefore = Opus_Job::getByLabels(array('opus-remove-index-document'));
        $jobCountBefore = count($removeIndexJobsBefore);


        $document = new Opus_Document();
        $document->setServerState('published');
        $documentId = $document->store();

        $newIndexJob = null;
        $indexJobs = Opus_Job::getByLabels(array('opus-index-document'));
        $newIndexJob = $this->getCreatedJob($documentId, $indexJobs);
        $this->assertNull($newIndexJob, 'Expected that no opus-index-document job was created');

        if (!is_null($newIndexJob))
            $newIndexJob->delete();

        $document->delete();

        $removeIndexJobs = Opus_Job::getByLabels(array('opus-remove-index-document'));
        $this->assertEquals($jobCountBefore, count($removeIndexJobs), 'Expected equal job count before and after storing document.');

        $newJob = $this->getCreatedJob($documentId, $removeIndexJobs);
        $this->assertNull($newJob, 'Expected that no new opus-remove-index-document job was created');

        $document->deletePermanent();

        if (!is_null($newJob))
            $newJob->delete();

        $this->resetAsynchronousExecution($asyncFlag);
    }

    private function setAsynchronousExecution($enabled = true) {
        $oldValue = null;
        $config = Zend_Registry::get('Zend_Config');
        if (isset($config->runjobs->asynchronous)) {
            $oldValue = $config->runjobs->asynchronous;
            $config->runjobs->asynchronous = $enabled;
        } else {
            $config->merge(new Zend_Config(array('runjobs' => array('asynchronous' => $enabled))));
        }
        return $oldValue;
    }

    private function resetAsynchronousExecution($oldValue) {
        $config = Zend_Registry::get('Zend_Config');
        if (is_null($oldValue)) {
            unset($config->runjobs->asynchronous);
        } else {
            $config->runjobs->asynchronous = $oldValue;
        }
    }

    private function getCreatedJob($documentId, $jobs) {
        $newJob = null;
        foreach ($jobs as $job) {
            $jobData = $job->getData(true);
            if (isset($jobData['documentId']) && $jobData['documentId'] == $documentId) {
                $newJob = $job;
                break;
            }
        }
        return $newJob;
    }

}

?>
