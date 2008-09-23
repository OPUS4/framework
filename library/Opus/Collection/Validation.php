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
 * @category	Framework
 * @package		Opus_Collections
 * @author     	Tobias Tappe <tobias.tappe@uni-bielefeld.de>
 * @copyright  	Copyright (c) 2008, OPUS 4 development team
 * @license    	http://www.gnu.org/licenses/gpl.html General Public License
 * @version    	$Id$
 */

/**
 * Provides validation functions.
 *
 * @category Framework
 * @package  Opus_Collection
 */
class Opus_Collection_Validation {
    
    /**
     * Verify if given argument is a valid Constructor ID (roles_id). 
     *
     * @param   integer $ID Argument to verify.
     * @throws  InvalidArgumentException Is thrown on invalid arguments.
     * @return void
     */
    static public function constructorID($ID) {
        if ($ID === 'institute') {
            return true;
        } else if (is_int($ID) === false) {
            throw new InvalidArgumentException($ID . ' is neither integer nor "institute".');
        } else if ($ID < 1) {
            throw new InvalidArgumentException($ID . ' is neither positive integer nor "institute".');
        }
    }
    
    /**
     * Verify if given argument is a valid ID. 
     *
     * @param   integer $ID Argument to verify.
     * @throws  InvalidArgumentException Is thrown on invalid arguments.
     * @return void
     */
    static public function ID($ID) {
        if (is_int($ID) === false) {
            throw new InvalidArgumentException($ID . ' is not an integer.');
        } else if ($ID < 1) {
            throw new InvalidArgumentException($ID . ' is not a positive integer.');
        }
    }
    
    /**
     * Verify if given argument is a valid collection structure node (LEFT or RIGHT). 
     *
     * @param   integer $node Argument to verify.
     * @throws  InvalidArgumentException Is thrown on invalid arguments.
     * @return void
     */
    static public function node($node) {
        if (is_int($node) === false) {
            throw new InvalidArgumentException($node . ' is not an integer.');
        } elseif ($node < 0) {
            throw new InvalidArgumentException($node . ' is negative.');
        }
    }
    
    /**
     * Verify if given argument is a valid language code). 
     *
     * @param   integer $language Argument to verify.
     * @throws  InvalidArgumentException Is thrown on invalid arguments.
     * @return void
     */
    static public function language($language) {
        if (is_string($language) === false) {
            throw new InvalidArgumentException('Language code must be a string.');
        } elseif (strlen($language) != 3) {
            throw new InvalidArgumentException($ID . ' is not a three letter language code.');
        }
    }
}