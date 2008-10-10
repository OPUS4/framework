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
 * @category    Test
 * @package     Opus_Validate
 * @author      Ralf Claussnitzer <ralf.claussnitzer@slub-dresden.de>
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id: DocumentType.php 714 2008-09-12 13:15:39Z claussnitzer $
 */

/**
 * Test cases for application of Opus_Validate_MateDecorator.
 *
 * @category    Tests
 * @package     Opus_Validate
 * 
 * @group       MateDecoratorTest
 *
 */
class Opus_Validate_MateDecoratorTest extends PHPUnit_Framework_TestCase {
    
    /**
     * Test if a decorated validator works as normal. 
     *
     * @return void
     */
    public function testDecoratingSingleValidator() {
        $validator = new Zend_Validate_NotEmpty();
        $decorated = Opus_Validate_MateDecorator::decorate($validator);
        
        $this->assertEquals($validator->isValid('content'), $decorated->isValid('content'),
            'Decorated validator returns different result than pure validator.');
    }
    
    /**
     * Test if the validator sticks to its first decision as an effect of
     * the shared common result among all validator mates.
     *
     * @return void
     */
    public function testDecoratedValidatorSticksToValidationResult() {
        $validator = new Zend_Validate_NotEmpty();
        $decorated = Opus_Validate_MateDecorator::decorate($validator);
        
        $decision1 = $decorated->isValid('content');
        $decision2 = $decorated->isValid('');
        
        $this->assertEquals($decision1, $decision2,
            'Decorated validators return different result in second call.');
    }
    
    /**
     * Test if a group of mate validators really decide for "valid" if
     * only one of them actually got a valid value.
     *
     * @return void
     */
    public function testMateGroupDecidesCommon() {
        $decorated1 = Opus_Validate_MateDecorator::decorate(new Zend_Validate_NotEmpty());
        $decorated2 = Opus_Validate_MateDecorator::decorate(new Zend_Validate_NotEmpty());

        $decorated1->addMate($decorated2);
        
        $decision1 = $decorated1->isValid('content');
        $decision2 = $decorated2->isValid('');
        
        $this->assertEquals($decision1, $decision2,
            'Decorated validators return different result in second call.');
    }
    
    /**
     * Test if a larger group of mates agrees to a common validation result.
     *
     * @return void
     */
    public function testLargeMateGroupDecidesCommon() {
        // Create decorated validators.
        $decorated = array();
        $count = 10;
        for ($i=0; $i<$count; $i++) {
            $decorated[$i] = Opus_Validate_MateDecorator::decorate(new Zend_Validate_NotEmpty());
        }
        
        // Link them together in a group of mates.
        for ($i=1; $i<$count; $i++) {
            $decorated[0]->addMate($decorated[$i]);
        }
        
        // Let all but one in the middle decide for invalidity.
        $decision = $decorated[round($count/2)]->isValid('notempty'); 
        for ($i=0; $i<$count; $i++) {
            $decision = ($decision and $decorated[$i]->isValid(''));
        }

        $this->assertTrue($decision, 'Group of mates agreed to wrong validation result');
    }
    
    
}
