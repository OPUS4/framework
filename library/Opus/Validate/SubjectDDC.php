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
 * @version     $Id: Language.php 2525 2009-04-22 09:31:32Z gerhardt $
 */

/**
 * Validator for Language field. Only accept standard Zend_Locale locale names.
 *
 * @category    Framework
 * @package     Opus_Validate
 */
class Opus_Validate_SubjectDDC extends Zend_Validate_Abstract {
    /**
     * Error message key.
     *
     */
    const MSG_SUBJECTDDC = 'subjectDDC';

    /**
     * Error message templates.
     *
     * @var array
     */
    protected $_messageTemplates = array(
        self::MSG_SUBJECTDDC => "'%value%' is not a valid DDC class."
    );

    public function isValid($value) {
        $log = Zend_Registry::get('Zend_Log');

        if (false === is_string($value)) {
            $value = (string) ($value);
        }

        if (true === !empty($value)) {
            $this->_setValue($value);

            $role = Opus_CollectionRole::fetchByOaiName('ddc');

            if (isset($role)) {
                        
                $collArray = Opus_Collection::fetchCollectionsByRoleNumber($role->getId(), $value);

                if (true === empty($collArray) || count($collArray) > 1) {
                    $this->_error(self::MSG_SUBJECTDDC);
                    return false;
                }
            }
            else {
                $log->err("ERROR in Opus_Validate_SubjectDDC => NO DDC CLASSES FOUND IN COLLECTION TABLE!!! Value can only be stored in subject table.");
                return true;
            }
        }
        return true;
    }

}