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
 * @category    Framework
 * @package     Opus
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @copyright   Copyright (c) 2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Domain model for reviewers in the Opus framework.
 *
 * @category    Framework
 * @package     Opus
 * @uses        Opus_Model_Abstract
 */
class Opus_Reviewer extends Opus_Model_Abstract {

    /**
     * Variable which holds the reviewers' config.
     *
     * Example (PHP):
     *
     * array (
     *     "collections" => array (
     *         "projects" => array (
     *             "A" => array (
     *                 "username1",
     *                 "tklein",
     *             ),
     *             "B" => array (
     *                 "username1",
     *             ),
     *         ),
     *     ),
     * ),
     *
     * Example (ini file):
     *
     * referees.collections.projects.A[] = 'username1'
     * referees.collections.projects.B[] = 'tklein'
     * referees.collections.projects.B[] = 'username2'
     *
     * @var array
     */
    private static $collections = null;
    private static $reviewers   = null;

    /**
     * @var Zend_Log
     */
    private $log;

    /**
     * The reviewer name for the current instance.
     *
     * @var string
     */
    private $_reviewer = null;

    /**
     * Method to initialize the reviewers' config.  The
     *
     * @param array $review_config
     */
    public static function init(array $review_config) {
        self::$collections = array();
        self::$reviewers   = array();

        if (array_key_exists('collections', $review_config)) {
            self::init_collections( $review_config['collections'] );
        }
    }

    /**
     * Initialize reviewers for collections.
     *
     * @param array $collections
     */
    private static function init_collections(array $collections) {
        $log = Zend_Registry::get('Zend_Log');

        foreach ($collections AS $coll_role_name => $coll_role_config) {

            // Given any role-name, get the role object.
            $c_role = Opus_CollectionRole::fetchByName($coll_role_name);
            if (is_null($c_role)) {
                $message = "Invalid role '$coll_role_name' in reviewer config.";
                $log->err($message);
                throw new Opus_Model_Exception($message);
            }

            // Given any collection-number, get the collection object.
            $c_role_id = $c_role->getId();
            foreach ($coll_role_config AS $coll_number => $coll_reviewers) {

                $colls = Opus_Collection::fetchCollectionsByRoleNumber($c_role_id, $coll_number);
                if (is_null($colls) || 0 === count ($colls)) {
                    $message = "Invalid collection '$coll_role_name'::'$coll_number' in reviewer config.";
                    $log->err($message);
                    throw new Opus_Model_Exception($message);
                }

                if (1 != count ($colls)) {
                    $message = "Ambiguous collection '$coll_role_name'::'$coll_number' in reviewer config. " . count($colls);
                    $log->err($message);
                    throw new Opus_Model_Exception($message);
                }

                self::registerCollectionReviewers($coll_role_name, $coll_number, $coll_reviewers);
                self::registerReviewerCollections($coll_role_name, $coll_number, $coll_reviewers);
            }
        }
    }

    private static function registerCollectionReviewers($role, $number, $reviewers) {
        if (!array_key_exists($role, self::$collections)) {
            self::$collections[$role] = array();
        }

        self::$collections[$role][$number] = $reviewers;
    }

    private static function registerReviewerCollections($role, $number, $reviewers) {
        foreach ($reviewers AS $reviewer) {
            if (!array_key_exists($reviewer, self::$reviewers)) {
                self::$reviewers[$reviewer] = array();
            }

            if (!array_key_exists($role, self::$reviewers[$reviewer])) {
                self::$reviewers[$reviewer][$role] = array();
            }

            array_push(self::$reviewers[$reviewer][$role], $number);
        }
    }

    public static function getNumbersForLoginRole($login, $role) {
        if (false === array_key_exists($login, self::$reviewers)) {
            return array();
        }

        if (false === array_key_exists($role, self::$reviewers[$login])) {
            return array();
        }

        return self::$reviewers[$login][$role];
    }


    /**
     * Reviewer-constructor.  First argument should be a valid user name.
     *
     * @param <type> $reviewer
     */
    public function __construct($reviewer = null) {
        $this->log = Zend_Registry::get('Zend_Log');

        if (is_null(self::$reviewers) or empty(self::$reviewers)) {
            $message = "Reviewers are not initialized.";
            $this->log->err($message);
            throw new Opus_Model_Exception($message);
        }

        if (!array_key_exists($reviewer, self::$reviewers)) {
            // echo "found reviewers: " . implode(",", self::$reviewers) . "\n";
            throw new Opus_Model_NotFoundException("Reviewer '$reviewer' not known.");
        }

        parent::__construct();
        $this->_reviewer = $reviewer;
    }
    

    /**
    *
    */
    protected function _init() {
    }


    /**
     *
     * @param Opus_Document $doc
     */
    public static function fetchAllByCollection(Opus_Collection $c) {

        $role = $c->getRoleName();
        if (!array_key_exists($role, self::$collections)) {
            return array();
        }

        // Get the list of reviewers for each parent.
        foreach ($c->getParents() AS $parent) {
            $number = (string) $parent->getNumber();

            if (array_key_exists($number, self::$collections[$role])) {
                return self::$collections[$role][$number];
            }
        }

        return array();
    }

    /**
     *
     * @param Opus_Document $doc
     */
    public static function fetchAllByDocument(Opus_Document $doc) {
        $reviewers = array();

        // Check collections.
        foreach ($doc->getCollection() AS $collection) {
            $more_reviewers = self::fetchAllByCollection($collection);
            $reviewers = array_merge($reviewers, $more_reviewers);
        }

        return array_unique($reviewers);
    }

    /**
     * Filter document-ids which can be reviewed by the current reviewer.
     */

    public function filterDocumentIds($docIds_input) {
        $docIds = array();

        $config = self::$reviewers[$this->_reviewer];
        foreach ($config AS $coll_role_name => $numbers) {

            // Given any role-name, get the role object.
            $c_role = Opus_CollectionRole::fetchByName($coll_role_name);
            if (is_null($c_role)) {
                continue;
            }

            $c_role_id = $c_role->getId();
            $collections = array();

            foreach ($numbers AS $number) {
                $collections = Opus_Collection::fetchCollectionsByRoleNumber($c_role_id, $number);

                if (count($collections) === 1) {
                    $collection = $collections[0];
                    $docIds_more = $collection->filterSubtreeDocumentIds($docIds_input);
                    $docIds = array_unique( array_merge($docIds, $docIds_more) );
                }
            }
        }

        return $docIds;
    }
    
}
