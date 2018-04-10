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
 * @package     Opus_Util
 * @author      Sascha Szott <szott@zib.de>
 * @copyright   Copyright (c) 2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

class Opus_Util_ClassLoaderHelper {

    public static function classExists($className) {
        // Anpassung des Zend-Autoloaders erforderlich, damit keine PHP Warning erzeugt wird, wenn Generator-Klasse
        // nicht existiert: PHPUnit erzeugt sonst aus PHP Warning (wenn Klasse nicht gefunden wird) eine Exception, weil
        // in Konfiguration convertWarningsToExceptions="true gesetzt -> das führt zu verändertem Exception-Verhalten
        $autoloader = Zend_Loader_Autoloader::getInstance();
        
        // Default-Wert für späteres Zurücksetzen speichern
        $suppressNotFoundWarnings = $autoloader->suppressNotFoundWarnings();
        
        $autoloader->suppressNotFoundWarnings(true);
        $classExists = class_exists($className);
        
        // Wiederherstellen des Default-Wertes
        $autoloader->suppressNotFoundWarnings($suppressNotFoundWarnings);
        
        return $classExists;
    }
}
