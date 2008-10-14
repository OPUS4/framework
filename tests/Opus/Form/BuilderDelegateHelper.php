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
 * @package     Opus_Form
 * @author      Henning Gerhardt <henning.gerhardt@slub-dresden.de>
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * This helper class allowes testing methods to access protected
 * members of Opus_Form_Builder.
 *
 * @category Tests
 * @package  Opus_Form
 */

class Opus_Form_BuilderDelegateHelper extends Opus_Form_Builder {

    /**
     * Enable call to static protected method generateSingleElement()
     *
     * @param string $elementdata Parameter for method generateSingleElement()
     * @param array  $typeinfo    Parameter for method generateSingleElement()
     * @throws InvalidArgumentException The method generateSingleElement() will throw this exception
     * @return array Return value of generateSingleElement()
     */
    public static function generateSingleElementDelegate($elementdata, array $typeinfo) {
        return self::generateSingleElement($elementdata, $typeinfo);
    }

    /**
     * Enable call to static protected method generateSubElements()
     *
     * @param array $elements   Parameter for method generateSubElementsDelegate()
     * @param array $typefields Parameter for method generateSubElementsDelegate()
     * @throws InvalidArgumentException The method generateSubElementsDelegate() will throw this exception
     * @throws Opus_Form_Exception The method generateSubElementsDelegate() will throw this exception
     * @return array Return value of generateSubElementsDelegate()
     */
    public static function generateSubElementsDelegate(array $elements, array $typefields) {
        return self::generateSubElements($elements, $typefields);
    }

    /**
     * Enable call to static protected method findPathToKey()
     *
     * @param string $keypattern Parameter for method findPathToKey()
     * @param array  &$haystack  Parameter for method findPathToKey()
     * @return unknown Return value of findPathToKey()
     */
    public static function findPathToKeyDelegate($keypattern, array &$haystack) {
        return self::findPathToKey($keypattern, $haystack);
    }
}