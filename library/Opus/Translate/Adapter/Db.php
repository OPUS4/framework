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
 * @package     Opus_Translate
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Translate adapter for retrieving language information from the database.
 * 
 * @category    Framework
 * @package     Opus_Translate
 */
class Opus_Translate_Adapter_Db extends Zend_Translate_Adapter {

    /**
     * Retrieve language information for a specified locale.
     *
     * @param Zend_Db_Adapter $dba     Ignored. Standard database adapter is always used.
     * @param string          $locale  Locale for that language information is requested.
     * @param array           $options (Optional) Adapter options. If option['clear'] => true is set
     *                                 all information will be completely removed for the specified
     *                                 locale before it again get read.
     *                                 The 'context' option can be set to a string representing an 
     *                                 specific context for which language information can be retrieved.
     * 
     * @throws Zend_Translate_Exception Thrown if an exception occured while trying to read the data.
     * 
     * @return void
     */
    protected function _loadTranslationData($dba = null, $locale, array $options = array())
    {
        try {
            $translations = Opus_Db_TableGateway::getInstance('Opus_Db_Translations');

            $select = new Zend_Db_Table_Select($translations);
            $select->where('locale = ?',$locale);
            if (array_key_exists('context',$options) === true) {
                $select->where('context = ?',$options['context']);
            }
            $data = $translations->fetchAll($select);

            $options = $options + $this->_options;
            if (($options['clear'] == true) ||  !isset($this->_translate[$locale])) {
                $this->_translate[$locale] = array();
            }
            
            foreach($data as $row) {
                $this->_translate[$locale][$row->translation_key] = $row->translation_msg;
            }

        } catch (Exception $ex) {
            throw new Zend_Translate_Exception('Error reading translation data: ' . $ex->getMessage());
        }

    }

    
    /**
     * Sets locale. If the locale information has not yet been retrieved, it gets loaded
     * from the database.
     *
     * @param  string|Zend_Locale $locale Locale to set
     * @throws Zend_Translate_Exception
     * @return Zend_Translate_Adapter Provides a fluid interface
     */
    public function setLocale($locale)
    {
        if ($this->isAvailable($locale) === false) {
            try {
                $this->addTranslation(null, $locale);
            } catch (Exception $ex) {
                require_once 'Zend/Translate/Exception.php';
                throw new Zend_Translate_Exception('Error dynamicly reading translation data: ' . $ex->getMessage());
            }
        }
        parent::setLocale($locale);
    }
    
    
    /**
     * Returns the adapters name.
     *
     * @return string Name of the adapter.
     */
    public function toString()
    {
        return "Db";
    }

}
