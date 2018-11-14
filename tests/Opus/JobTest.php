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
 * @category    Tests
 * @package     Opus
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @author      Henning Gerhardt <henning.gerhardt@slub-dresden.de>
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2009-2018
 *              Saechsische Landesbibliothek - Staats- und Universitaetsbibliothek Dresden (SLUB)
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

/**
 * Test cases for Opus_Job
 *
 * @category    Tests
 * @package     Opus
 */
class Opus_JobTest extends TestCase
{

    public function tearDown()
    {
        Opus_Job::deleteAll();
        parent::tearDown();
    }

    /**
     * Test if sha1_id column gets set.
     *
     * @return void
     */
    public function testCreatedJobWritesSha1ToHashColumn()
    {
        $job = new Opus_Job();
        $job->setLabel('JobTest');
        $job->setData('somedata');
        $jobId = $job->store();

        $jobTable = Opus_Db_TableGateway::getInstance('Opus_Db_Jobs');
        $jobRow = $jobTable->fetchRow("id = $jobId");

        $this->assertEquals($job->getSha1Id(), $jobRow->sha1_id, 'Job SHA1 hash has not been set in database.');
    }

    /**
     * Test if equal insitialized jobs returns same SHA1 id.
     *
     * @return void
     */
    public function testEqualJobsHaveEqualHashes()
    {
        $job1 = new Opus_Job();
        $job1->setLabel('JobTest');
        $job1->setData('somedata');

        $job2 = new Opus_Job();
        $job2->setLabel('JobTest');
        $job2->setData('somedata');

        $this->assertEquals($job1->getSha1Id(), $job2->getSha1Id(), 'Job hash ids are different but should not.');
    }

    /**
     * Test if isUniqueInQueue() return True if no jobs exists.
     *
     * @return void
     */
    public function testUniquenessTestReturnsTrueIfNoJobIsPresent()
    {
        $job = new Opus_Job();
        $job->setLabel('JobTest');
        $job->setData('somedata');

        $this->assertTrue($job->isUniqueInQueue(), 'No other jobs stored. Uniqueness should be given.');
    }

    /**
     * Test if isUniqueInQueue() return False if a job with same
     * data setup has already been stored.
     *
     * @return void
     */
    public function testUniquenessTestReturnsFalseIfJobWithSameHashIsPresent()
    {
        $job1 = new Opus_Job();
        $job1->setLabel('JobTest');
        $job1->setData('somedata');
        $job1->store();

        $job2 = new Opus_Job();
        $job2->setLabel('JobTest');
        $job2->setData('somedata');

        $this->assertFalse($job2->isUniqueInQueue(), 'Other jobs stored. Uniqueness should not be given.');
    }

    public function testDeleteAll()
    {
        $job = new Opus_Job();
        $job->setLabel('Job1');
        $job->setData('data1');
        $job->store();

        $job = new Opus_Job();
        $job->setLabel('Job2');
        $job->setData('data2');
        $job->store();

        $this->assertEquals(2, Opus_Job::getCount());

        Opus_Job::deleteAll();

        $this->assertEquals(0, Opus_Job::getCount());
    }

    public function testGetCount()
    {
        $job = new Opus_Job();
        $job->setLabel('Job1');
        $job->setData('data1');
        $job->store();

        $this->assertEquals(1, Opus_Job::getCount());

        $job = new Opus_Job();
        $job->setLabel('Job2');
        $job->setData('data2');
        $job->store();

        $this->assertEquals(2, Opus_Job::getCount());
    }

    public function testGetCountForLabel()
    {
        $job = new Opus_Job();
        $job->setLabel('JobType1');
        $job->setData('data1');
        $job->store();

        $label = 'JobType2';

        $job = new Opus_Job();
        $job->setLabel($label);
        $job->setData('data2');
        $job->store();

        $this->assertEquals(1, Opus_Job::getCountForLabel($label));

        $job = new Opus_Job();
        $job->setLabel($label);
        $job->setData('data3');
        $job->store();

        $this->assertEquals(2, Opus_Job::getCountForLabel($label));
    }

    public function testGetCountForLabelWithState()
    {
        $job = new Opus_Job();
        $job->setLabel('JobType1');
        $job->setData('data1');
        $job->store();

        $label = 'JobType2';

        $job = new Opus_Job();
        $job->setLabel($label);
        $job->setState(Opus_Job::STATE_PROCESSING);
        $job->setData('data2');
        $job->store();

        $this->assertEquals(1, Opus_Job::getCountForLabel($label));
        $this->assertEquals(1, Opus_Job::getCountForLabel($label, Opus_Job::STATE_PROCESSING));
        $this->assertEquals(0, Opus_Job::getCountForLabel($label, Opus_Job::STATE_UNDEFINED));

        $job = new Opus_Job();
        $job->setLabel($label);
        $job->setState(Opus_Job::STATE_FAILED);
        $job->setData('data3');
        $job->store();

        $this->assertEquals(2, Opus_Job::getCountForLabel($label));
        $this->assertEquals(1, Opus_Job::getCountForLabel($label, Opus_Job::STATE_PROCESSING));
        $this->assertEquals(1, Opus_Job::getCountForLabel($label, Opus_Job::STATE_FAILED));
    }

    public function testGetCountForLabelWithStateUndefined()
    {
        $label = 'JobType2';

        $job = new Opus_Job();
        $job->setLabel($label);
        $job->setState(Opus_Job::STATE_PROCESSING);
        $job->setData('data1');
        $job->store();

        $job = new Opus_Job();
        $job->setLabel($label);
        $job->setState(null); // TODO cannot use Opus_Job::STATE_UNDEFINED
        $job->setData('data2');
        $job->store();

        $this->assertEquals(2, Opus_Job::getCountForLabel($label));
        $this->assertEquals(1, Opus_Job::getCountForLabel($label, Opus_Job::STATE_UNDEFINED));
    }

    public function testGetCountPerLabel()
    {
        $job = new Opus_Job();
        $job->setLabel('EventType1');
        $job->setData('data1');
        $job->store();

        $job = new Opus_Job();
        $job->setLabel('EventType1');
        $job->setData('data2');
        $job->store();

        $job = new Opus_Job();
        $job->setLabel('EventType2');
        $job->setData('data3');
        $job->store();

        $job = new Opus_Job();
        $job->setLabel('EventType2');
        $job->setData('data4');
        $job->store();

        $count = Opus_Job::getCountPerLabel();

        $this->assertEquals([
            'EventType1' => 2,
            'EventType2' => 2
        ], $count);
    }

    public function testGetCountPerLabelWithState()
    {
        $job = new Opus_Job();
        $job->setLabel('EventType1');
        $job->setData('data1');
        $job->setState(Opus_Job::STATE_PROCESSING);
        $job->store();

        $job = new Opus_Job();
        $job->setLabel('EventType1');
        $job->setData('data2');
        $job->store();

        $job = new Opus_Job();
        $job->setLabel('EventType2');
        $job->setData('data3');
        $job->store();

        $job = new Opus_Job();
        $job->setLabel('EventType2');
        $job->setData('data4');
        $job->store();

        $count = Opus_Job::getCountPerLabel(Opus_Job::STATE_PROCESSING);

        $this->assertEquals([
            'EventType1' => 1
        ], $count);
    }
}
