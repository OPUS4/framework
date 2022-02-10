<?php

/**
 * This file is part of OPUS. The software OPUS has been originally developed
 * at the University of Stuttgart with funding from the German Research Net,
 * the Federal Department of Higher Education and Research and the Ministry
 * of Science, Research and the Arts of the State of Baden-Wuerttemberg.
 *
 * OPUS 4 is a complete rewrite of the original OPUS software and was developed
 * by the Stuttgart University Library, the Library Service Center
 * Baden-Wuerttemberg, the North Rhine-Westphalian Library Service Center,
 * the Cooperative Library Network Berlin-Brandenburg, the Saarland University
 * and State Library, the Saxon State Library - Dresden State and University
 * Library, the Bielefeld University Library and the University Library of
 * Hamburg University of Technology with funding from the German Research
 * Foundation and the European Regional Development Fund.
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
 * @copyright   Copyright (c) 2021, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\Model2;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Exception;

use function json_decode;
use function json_encode;
use function serialize;
use function sha1;

/**
 * @ORM\Entity(repositoryClass="Opus\Db2\JobRepository")
 * @ORM\Table(name="jobs")
 *
 * TODO the timestamp property currently doesn't seem to be used; it is made available here as a readonly property
 * TODO the timestamp property's columnDefinition is specific to MySQL and as such not portable
 */
class Job extends AbstractModel
{
    public const STATE_PROCESSING = 'processing';
    public const STATE_FAILED     = 'failed';
    public const STATE_UNDEFINED  = 'undefined';

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     *
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="string", name="sha1_id", length=40)
     *
     * @var string
     */
    private $sha1Id;

    /**
     * @ORM\Column(type="string", length=50)
     *
     * @var string
     *
     * TODO field is mandatory
     */
    private $label;

    /**
     * @ORM\Column(type="datetime", columnDefinition="TIMESTAMP DEFAULT CURRENT_TIMESTAMP", nullable=true)
     *
     * @var DateTime
     */
    private $timestamp;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     *
     * @var string
     */
    private $state;

    /**
     * @ORM\Column(type="text", length=16777215, nullable=true)
     *
     * @var string
     */
    private $data;

    /**
     * @ORM\Column(type="text", length=16777215, nullable=true)
     *
     * @var string
     */
    private $errors;

    /**
     * Set SHA1 hash column value.
     *
     * @return mixed|null Anything else than null will cancel the storage process.
     */
    protected function preStore()
    {
        $sha1Id = $this->getSha1Id();
        $this->setSha1Id($sha1Id);

        return parent::preStore();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getSha1Id()
    {
        $sha1Id = $this->sha1Id;
        if ($sha1Id === null) {
            $sha1Id = $this->generateSha1Id();
        }

        return $sha1Id;
    }

    /**
     * @param string $sha1Id
     * @return $this
     */
    public function setSha1Id($sha1Id)
    {
        $this->sha1Id = $sha1Id;

        return $this;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     * @return $this
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param string $state
     * @return $this
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * @param  bool $convertObjects (Optional) If true objects are converted into associative arrays. Defaults to false.
     * @throws Exception Thrown if json decoding failed.
     * @return string
     */
    public function getData($convertObjects = false)
    {
        $fieldData = $this->data;

        $jsonDecode = json_decode($fieldData, $convertObjects);
        if ((null !== $fieldData) && (null === $jsonDecode)) {
            throw new Exception('Json decoding failed.');
        }

        return $jsonDecode;
    }

    /**
     * @param string $data
     * @throws Exception Thrown if json encoding failed.
     * @return $this
     */
    public function setData($data)
    {
        $jsonEncode = json_encode($data);
        if ((null !== $data) && (null === $jsonEncode)) {
            throw new Exception('Json encoding failed.');
        }

        $this->data = $jsonEncode;

        return $this;
    }

    /**
     * @return string
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @param string $errors
     * @return $this
     */
    public function setErrors($errors)
    {
        $this->errors = $errors;

        return $this;
    }

    /**
     * Retrieve all Job instances from the database.
     *
     * @param null|int[] $ids (Optional) Set of IDs specifying the models to fetch.
     * @return self[]
     */
    public static function getAll($ids = null)
    {
        if (empty($ids) === false) {
            return self::getRepository()->getJobs($ids);
        }

        return self::getRepository()->getAll();
    }

    /**
     * Retrieve number of Opus\Job entries in the database.
     *
     * @param null|string $state (Optional) only retrieve jobs in given state (@see Job class for state definitions)
     * @return int Number of entries in database.
     */
    public static function getCount($state = null)
    {
        return self::getRepository()->getJobsCount($state);
    }

    /**
     * Retrieve number of Job entries for a given label in the database.
     *
     * @param string      $label only consider jobs with the given label
     * @param null|string $state (Optional) only retrieve jobs in given state (@see Job class for state definitions)
     * @return int Number of entries in database.
     */
    public static function getCountForLabel($label, $state = null)
    {
        return self::getRepository()->getJobsCountForLabel($label, $state);
    }

    /**
     * Retrieve number of Job entries in the database grouped by label.
     *
     * @param null|string $state (Optional) only retrieve jobs in given state (@see Job class for state definitions)
     * @return array Key / Value pairs of label / count for database entries.
     */
    public static function getCountPerLabel($state = null)
    {
        return self::getRepository()->getJobsCountPerLabel($state);
    }

    /**
     * Retrieve all Job instances having the given label(s). Returns null if the given set of labels is empty.
     *
     * @param string[]    $labels Set of labels to get jobs for.
     * @param null|int    $limit (Optional) Number of jobs to retrieve.
     * @param null|string $state (Optional) Only retrieve jobs in given state (@see Job class for state definitions).
     * @return self[]|null
     */
    public static function getByLabels($labels, $limit = null, $state = null)
    {
        return self::getRepository()->getJobsWithLabels($labels, $limit, $state);
    }

    /**
     * Tells whether the Job is unique amongst all other jobs in the queue.
     *
     * @return bool True if job is unique, False otherwise.
     */
    public function isUniqueInQueue()
    {
        $sha1Id = $this->getSha1Id();
        $jobs   = self::getRepository()->getJobsWithSha1Id($sha1Id);

        return empty($jobs);
    }

    /**
     * Create Sha1 Hash unique to the job.
     *
     * @throws Exception
     * @return string SHA1 Hash.
     */
    protected function generateSha1Id()
    {
        $content = $this->getLabel() . serialize($this->getData());

        return sha1($content);
    }

    /**
     * Deletes all jobs currently stored in the datebase.
     *
     * Used especially for setting up unit tests.
     */
    public static function deleteAll()
    {
        self::getRepository()->deleteAll();
    }

    /**
     * Returns the relevant properties of the class
     *
     * @return array
     */
    protected static function describe()
    {
        return ['Label', 'State', 'Data', 'Errors'];
    }
}
