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
 * @package     Opus_Validate
 * @author      Ralf Claussnitzer <ralf.claussnitzer@slub-dresden.de>
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Defines an validator interface for opus specific validators.
 * 
 * To classes implementing this interface, "mate" validators can be added. All 
 * added mates then form a validator "circle of friends". If one of those validators 
 * says its given value to be valid, it calls decideAllValid() and all the others will 
 * adopt this decision by returning true everytime isValid() gets on a value.
 * 
 * This behavior is used to form groups of mandatory fields in Zend_Form objects,
 * where the whole group is valid even if some of its fields are empty. 
 *
 * @category    Framework
 * @package     Opus_Validate
 */
interface Opus_Validate_Mate {
    
    /**
     * Add another validator to the list of mates.
     *
     * @param Opus_Validate_Mate $mate Validator implementing Opus_Validate_Mate.
     * @return void
     */
    public function addMate(Opus_Validate_Mate $mate);

    /**
     * Inform all mates that the common validation result.
     * 
     * @return void
     */
    public function decideAllValid();
    
    /**
     * Tell a specific validator to decide for validity.
     *
     * @return void
     */
    public function decideValid();
    
}