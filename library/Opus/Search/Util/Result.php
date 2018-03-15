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
 * @category    Framework
 * @package     Opus_Search_Util
 * @author      Sascha Szott <szott@zib.de>
 * @copyright   Copyright (c) 2008-2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

class Opus_Search_Util_Result {

    private $id;
    private $score;
    private $authors;
    private $title;
    private $year;
    private $abstract;
    private $seriesNumber;
    private $serverDateModified;
    private $fulltextIDsSuccess;
    private $fulltextIDsFailure;

    public function  __construct() {
    }

    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function getScore() {
        return $this->score;
    }

    public function setScore($score) {
        $this->score = $score;
    }

    public function getAuthors() {
        return $this->authors;
    }

    public function  setAuthors($authors) {
        $this->authors = is_array($authors) ? $authors : array($authors);
    }

    public function getTitle() {
        return $this->title;
    }

    public function setTitle($title) {
        $this->title = $title;
    }

    public function getYear() {
        return $this->year;
    }

    public function setYear($year) {
        $this->year = $year;
    }

    public function getAbstract() {
        return $this->abstract;
    }

    public function setAbstract($abstract) {
        $this->abstract = $abstract;
    }

    public function getSeriesNumber() {
        return $this->seriesNumber;
    }

    public function setSeriesNumber($seriesNumber) {
        $this->seriesNumber = $seriesNumber;
    }

    public function getServerDateModified() {
        return $this->serverDateModified;
    }

    public function setServerDateModified($serverDateModified) {
        $this->serverDateModified = $serverDateModified;
    }

    public function getFulltextIDsSuccess() {
        return $this->fulltextIDsSuccess;
    }

    public function setFulltextIDsSuccess($fulltextIDsSuccess) {
        $this->fulltextIDsSuccess = is_array($fulltextIDsSuccess) ? $fulltextIDsSuccess : array($fulltextIDsSuccess);
    }

    public function getFulltextIDsFailure() {
        return $this->fulltextIDsFailure;
    }

    public function setFulltextIDsFailure($fulltextIDsFailure) {
        $this->fulltextIDsFailure = is_array($fulltextIDsFailure) ? $fulltextIDsFailure : array($fulltextIDsFailure);
    }

}

