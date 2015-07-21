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
 * @package     Opus_Job
 * @author      Henning Gerhardt (henning.gerhardt@slub-dresden.de)
 * @copyright   Copyright (c) 2009, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Test cases for running Opus_Jobs.
 *
 * @category    Tests
 * @package     Opus_Job
 *
 * @group       RunnerTest
 */
class Opus_Job_RunnerTest extends TestCase {

    /**
     * Simple test for catching code coverage.
     *
     * @return void
     */
    public function testRunnerInit() {
        $runner = new Opus_Job_Runner();
        $this->assertNotNull($runner, 'Simple initializing of Opus_Job_Runner failed.');
    }

    public function testRunIndexWorkerWithInvalidJob() {

        $document = new Opus_Document();
        $document->setServerState('published');
        $documentId = $document->store();


        $job = new Opus_Job();
        $job->setLabel('opus-index-document');
        $job->setData(array(
            'documentId' => $documentId,
            'task' => 'get-me-a-coffee'));
        $jobId = $job->store();

        $indexWorker = new Opus_Job_Worker_IndexOpusDocument;
        $indexWorker->setIndex(new Opus_SolrSearch_Index_Indexer(false));

        $runner = new Opus_Job_Runner();
        $runner->registerWorker($indexWorker);
        $runner->run();

        $job = new Opus_Job($jobId);
        $this->assertEquals(Opus_Job::STATE_FAILED, $job->getState());
        $error = $job->getErrors();
        $this->assertNotEquals('', $error, 'Expected error message from job.');
//        $job->delete();

    }

    public function testRunIndexWorkerWithValidJob() {

        $document = new Opus_Document();
        $document->setServerState('published');
        $documentId = $document->store();


        $job = new Opus_Job();
        $job->setLabel('opus-index-document');
        $job->setData(array(
            'documentId' => $documentId,
            'task' => 'index'));
        $jobId = $job->store();

        $indexWorker = new Opus_Job_Worker_IndexOpusDocument;
        $indexWorker->setIndex(new Opus_SolrSearch_Index_Indexer(false));

        $runner = new Opus_Job_Runner();
        $runner->registerWorker($indexWorker);
        $runner->run();
        $this->setExpectedException('Opus_Model_NotFoundException');
        $job = new Opus_Job($jobId);
        if($job instanceof Opus_Job)
            $job->delete();
    }


}

