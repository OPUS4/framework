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
 * @package     Opus_Translate
 * @author      Ralf Claussnitzer <ralf.claussnitzer@slub-dresden.de>
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Test case for Opus_Translate_Adapter_Db. 
 *
 * @category    Tests
 * @package     Opus_Translate
 * 
 * @group       TranslateAdapterTest
 */
class Opus_Translate_Adapter_DbTest extends PHPUnit_Framework_TestCase {
    
    /**
     * Provides test translation data.
     *
     * @return array Translation data.
     */
    public function translationDataProvider() {
        return array(
            array('test', 'de', 'test1', 'Übersetzung'),
            array('test', 'es', 'test1', 'Translación'),
            array('kaboom', 'en', 'test2', 'Translation'),
            array('kaboom', 'ru', 'test2', 'Перевод')
        );
    }
    
    /**
     * Setup database with translation information.
     *
     * @return void
     */
    public function setUp() {
        $table = Opus_Db_TableGateway::getInstance('Opus_Db_Translations');
        
        $translations = $this->translationDataProvider();
        foreach ($translations as $translation) {   
            $row = $table->createRow();
            $row->context = $translation[0];
            $row->locale  = $translation[1];
            $row->translation_key  = $translation[2];
            $row->translation_msg  = $translation[3];
            $row->save();
        }
    }

    /**
     * Manually remove all entries from table translations.
     *
     * @return void
     */    
    public function tearDown() {
        TestHelper::clearTable('translations');
    }
    
    
    /**
     * Test if creating a new translation adapter works.
     *
     * @return void
     */
    public function testCreateDirectly() {
        $translate = new Opus_Translate_Adapter_Db(null);
    }
    
    /**
     * Test creation of translation adapter via Zend_Translate construction.
     *
     * @return void
     */
    public function testCreateViaZendTranslate() {
        $translate = new Zend_Translate('Opus_Translate_Adapter_Db', null);
    }   
 
 
    /**
     * Test translation message for specific language makes
     * language available for translation.
     *
     * @param $context Data provider slot for translation context.
     * @param $locale  Date provider slot for translation locale.
     * @return void
     * @dataProvider translationDataProvider
     */
    public function testLanguageAvailable($context, $locale) {
        $translate = new Zend_Translate('Opus_Translate_Adapter_Db', null, $locale);
        $this->assertTrue($translate->isAvailable($locale), 'Expect locale to be available.');
    }

    /**
     * Test if translation works. 
     *
     * @param $context Data provider slot for translation context.
     * @param $locale  Date provider slot for translation locale.
     * @param $context Data provider slot for translation key.
     * @param $locale  Date provider slot for translation string.
     * @return void
     * @dataProvider translationDataProvider
     */
    public function testTranslateMessageString($context, $locale, $key, $msg) {
        $translate = new Zend_Translate('Opus_Translate_Adapter_Db', null, $locale);
        $this->assertEquals($msg, $translate->_($key), 'Wrong translation result.');
    }   

    /**
     * Test if translation with context option. 
     *
     * @param $context Data provider slot for translation context.
     * @param $locale  Date provider slot for translation locale.
     * @param $context Data provider slot for translation key.
     * @param $locale  Date provider slot for translation string.
     * @return void
     * @dataProvider translationDataProvider
     */
    public function testTranslateWithContext($context, $locale, $key, $msg) {
        $translate = new Zend_Translate('Opus_Translate_Adapter_Db', null, $locale, 
            array('context' => $context));
        $this->assertEquals($msg, $translate->_($key), 'Wrong translation result for context: ' . $context);
    }   

}
