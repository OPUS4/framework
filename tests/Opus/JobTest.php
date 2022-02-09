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
 * @copyright   Copyright (c) 2009-2018
 *              Saechsische Landesbibliothek - Staats- und Universitaetsbibliothek Dresden (SLUB)
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * @category    Tests
 * @package     Opus
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @author      Henning Gerhardt <henning.gerhardt@slub-dresden.de>
 * @author      Jens Schwidder <schwidder@zib.de>
 */

namespace OpusTest;

use Opus\Model2\Job;
use OpusTest\TestAsset\TestCase;
use ReflectionClass;

use function count;

/**
 * Test cases for Opus\Job
 *
 * @category    Tests
 * @package     Opus
 */
class JobTest extends TestCase
{
    public function tearDown()
    {
        Job::deleteAll();
        parent::tearDown();
    }

    /**
     * Test if sha1_id column gets set.
     */
    public function testCreatedJobWritesSha1ToHashColumn()
    {
        $job = new Job();
        $job->setLabel('JobTest');
        $job->setData('somedata');
        $jobId = $job->store();

        $storedJob = Job::get($jobId);

        // get raw value from private Job->sha1Id property
        $refJob      = new ReflectionClass(Job::class);
        $refProperty = $refJob->getProperty('sha1Id');
        $refProperty->setAccessible(true);
        $storedSha1Id = $refProperty->getValue($storedJob);

        $this->assertEquals($job->getSha1Id(), $storedSha1Id, 'Job SHA1 hash has not been set in database.');
    }

    /**
     * Test if equal initialized jobs returns same SHA1 id.
     */
    public function testEqualJobsHaveEqualHashes()
    {
        $job1 = new Job();
        $job1->setLabel('JobTest');
        $job1->setData('somedata');

        $job2 = new Job();
        $job2->setLabel('JobTest');
        $job2->setData('somedata');

        $this->assertEquals($job1->getSha1Id(), $job2->getSha1Id(), 'Job hash ids are different but should not.');
    }

    /**
     * Test if isUniqueInQueue() return True if no jobs exists.
     */
    public function testUniquenessTestReturnsTrueIfNoJobIsPresent()
    {
        $job = new Job();
        $job->setLabel('JobTest');
        $job->setData('somedata');

        $this->assertTrue($job->isUniqueInQueue(), 'No other jobs stored. Uniqueness should be given.');
    }

    /**
     * Test if isUniqueInQueue() return False if a job with same
     * data setup has already been stored.
     */
    public function testUniquenessTestReturnsFalseIfJobWithSameHashIsPresent()
    {
        $job1 = new Job();
        $job1->setLabel('JobTest');
        $job1->setData('somedata');
        $job1->store();

        $job2 = new Job();
        $job2->setLabel('JobTest');
        $job2->setData('somedata');

        $this->assertFalse($job2->isUniqueInQueue(), 'Other jobs stored. Uniqueness should not be given.');
    }

    public function testGetAllJobs()
    {
        $job = new Job();
        $job->setLabel('Job1');
        $job->setData('data1');
        $job->store();

        $job = new Job();
        $job->setLabel('Job2');
        $job->setData('data2');
        $jobId2 = $job->store();

        $job = new Job();
        $job->setLabel('Job3');
        $job->setData('data3');
        $jobId3 = $job->store();

        $jobs = Job::getAll();

        $this->assertEquals(3, count($jobs));

        $jobs = Job::getAll([$jobId2, $jobId3]);

        $this->assertEquals('Job2', $jobs[0]->getLabel());
        $this->assertEquals('Job3', $jobs[1]->getLabel());
    }

    public function testDeleteAll()
    {
        $job = new Job();
        $job->setLabel('Job1');
        $job->setData('data1');
        $job->store();

        $job = new Job();
        $job->setLabel('Job2');
        $job->setData('data2');
        $job->store();

        $this->assertEquals(2, Job::getCount());

        Job::deleteAll();

        $this->assertEquals(0, Job::getCount());
    }

    public function testGetCount()
    {
        $job = new Job();
        $job->setLabel('Job1');
        $job->setData('data1');
        $job->store();

        $this->assertEquals(1, Job::getCount());

        $job = new Job();
        $job->setLabel('Job2');
        $job->setData('data2');
        $job->store();

        $this->assertEquals(2, Job::getCount());
    }

    public function testGetCountForLabel()
    {
        $job = new Job();
        $job->setLabel('JobType1');
        $job->setData('data1');
        $job->store();

        $label = 'JobType2';

        $job = new Job();
        $job->setLabel($label);
        $job->setData('data2');
        $job->store();

        $this->assertEquals(1, Job::getCountForLabel($label));

        $job = new Job();
        $job->setLabel($label);
        $job->setData('data3');
        $job->store();

        $this->assertEquals(2, Job::getCountForLabel($label));
    }

    public function testGetCountForLabelWithState()
    {
        $job = new Job();
        $job->setLabel('JobType1');
        $job->setData('data1');
        $job->store();

        $label = 'JobType2';

        $job = new Job();
        $job->setLabel($label);
        $job->setState(Job::STATE_PROCESSING);
        $job->setData('data2');
        $job->store();

        $this->assertEquals(1, Job::getCountForLabel($label));
        $this->assertEquals(1, Job::getCountForLabel($label, Job::STATE_PROCESSING));
        $this->assertEquals(0, Job::getCountForLabel($label, Job::STATE_UNDEFINED));

        $job = new Job();
        $job->setLabel($label);
        $job->setState(Job::STATE_FAILED);
        $job->setData('data3');
        $job->store();

        $this->assertEquals(2, Job::getCountForLabel($label));
        $this->assertEquals(1, Job::getCountForLabel($label, Job::STATE_PROCESSING));
        $this->assertEquals(1, Job::getCountForLabel($label, Job::STATE_FAILED));
    }

    public function testGetCountForLabelWithStateUndefined()
    {
        $label = 'JobType2';

        $job = new Job();
        $job->setLabel($label);
        $job->setState(Job::STATE_PROCESSING);
        $job->setData('data1');
        $job->store();

        $job = new Job();
        $job->setLabel($label);
        $job->setState(null); // TODO cannot use Opus\Job::STATE_UNDEFINED
        $job->setData('data2');
        $job->store();

        $this->assertEquals(2, Job::getCountForLabel($label));
        $this->assertEquals(1, Job::getCountForLabel($label, Job::STATE_UNDEFINED));
    }

    public function testGetCountPerLabel()
    {
        $job = new Job();
        $job->setLabel('EventType1');
        $job->setData('data1');
        $job->store();

        $job = new Job();
        $job->setLabel('EventType1');
        $job->setData('data2');
        $job->store();

        $job = new Job();
        $job->setLabel('EventType2');
        $job->setData('data3');
        $job->store();

        $job = new Job();
        $job->setLabel('EventType2');
        $job->setData('data4');
        $job->store();

        $count = Job::getCountPerLabel();

        $this->assertEquals([
            'EventType1' => 2,
            'EventType2' => 2,
        ], $count);
    }

    public function testGetCountPerLabelWithState()
    {
        $job = new Job();
        $job->setLabel('EventType1');
        $job->setData('data1');
        $job->setState(Job::STATE_PROCESSING);
        $job->store();

        $job = new Job();
        $job->setLabel('EventType1');
        $job->setData('data2');
        $job->store();

        $job = new Job();
        $job->setLabel('EventType2');
        $job->setData('data3');
        $job->store();

        $job = new Job();
        $job->setLabel('EventType2');
        $job->setData('data4');
        $job->store();

        $count = Job::getCountPerLabel(Job::STATE_PROCESSING);

        $this->assertEquals([
            'EventType1' => 1,
        ], $count);
    }

    public function testGetByLabels()
    {
        $label1 = 'JobType1';
        $label2 = 'JobType2';

        $job = new Job();
        $job->setLabel($label1);
        $job->setData('data1');
        $job->setState(Job::STATE_PROCESSING);
        $job->store();

        $job = new Job();
        $job->setLabel($label1);
        $job->setData('data1');
        $job->store();

        $job = new Job();
        $job->setLabel($label2);
        $job->setData('data2');
        $job->store();

        $job = new Job();
        $job->setLabel('JobType3');
        $job->setData('data3');
        $job->store();

        $jobs = Job::getByLabels([$label1]);

        $this->assertEquals(2, count($jobs));
        $this->assertEquals('JobType1', $jobs[0]->getLabel());
        $this->assertEquals('JobType1', $jobs[1]->getLabel());

        $jobs = Job::getByLabels([$label1], 1);

        $this->assertEquals(1, count($jobs));
        $this->assertEquals('JobType1', $jobs[0]->getLabel());

        $jobs = Job::getByLabels([$label1, $label2]);

        $this->assertEquals(3, count($jobs));
        $this->assertEquals('JobType1', $jobs[0]->getLabel());
        $this->assertEquals('JobType1', $jobs[1]->getLabel());
        $this->assertEquals('JobType2', $jobs[2]->getLabel());

        $jobs = Job::getByLabels([$label1, $label2], null, Job::STATE_PROCESSING);

        $this->assertEquals(1, count($jobs));
        $this->assertEquals('JobType1', $jobs[0]->getLabel());
    }
}
