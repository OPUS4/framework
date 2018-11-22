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
 * @package     Opus
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

/**
 * Class for adding color to strings for output on console.
 */
class Opus_Util_ConsoleColors
{

    /**
     * Constants for some colors that can be used for foreground and background.
     */
    const
        BLACK = 'black',
        RED = 'red',
        GREEN = 'green',
        YELLOW = 'yellow',
        BLUE = 'blue',
        CYAN = 'cyan',
        LIGHT_GRAY = 'light_gray';

    /**
     * Names and codes for background colors.
     */
    const BACKGROUND = [
        'black' => '40',
        'red' => '41',
        'green' => '42',
        'yellow' => '43',
        'blue' => '44',
        'magenta' => '45',
        'cyan' => '46',
        'light_gray' => '47'
    ];

    /**
     * Names and codes for foreground colors.
     */
    const FOREGROUND = [
        'black' => '0;30',
        'dark_gray' => '1;30',
        'blue' => '0;34',
        'light_blue' => '1;34',
        'green' => '0;32',
        'light_green' => '1;32',
        'cyan' => '0;36',
        'light_cyan' => '1;36',
        'red' => '0;31',
        'light_red' => '1;31',
        'purple' => '0;35',
        'light_purple' => '1;35',
        'brown' => '0;33',
        'yellow' => '1;33',
        'light_gray' => '0;37',
        'white' => '1;37',
    ];

    /**
     * Adds colors to string for output to console.
     *
     * @param string $message
     * @param null $color Foreground color name or code
     * @param null $background Background color name or code
     *
     * @return string Colored string
     */
    public function getColoredString($message, $color = null, $background = null)
    {
        $colored = '';

        if (!is_null($color)) {
            if (isset(self::FOREGROUND[$color])) {
                $colored .= "\033[" . self::FOREGROUND[$color] . 'm';
            } else if (preg_match('/[0-9];[0-9]{2}/', $color)) {
                $colored .= "\033[{$color}m";
            }
        }

        if (!is_null($background)) {
            if (isset(self::BACKGROUND[$background])) {
                $colored .= "\033[" . self::BACKGROUND[$background] . 'm';
            } else if (preg_match('/[0-9]{2}/', $color)) {
                $colored .= "\033[{$background}m";
            }
        }

        if (strlen($colored) > 0) {
            $colored .= "$message\033[0m";
        } else {
            $colored = $message;
        }

        return $colored;
    }

    /**
     * Returns names of all foreground colors.
     * @return array
     */
    public function getForegroundColors()
    {
        return array_keys(self::FOREGROUND);
    }

    /**
     * Returns names of all background colors.
     *
     * @return array
     */
    public function getBackgroundColors()
    {
        return array_keys(self::BACKGROUND);
    }

    /**
     * Allows using foreground color as function name for coloring strings.
     *
     * @param $name Name of function (foreground color)
     * @param $arguments Arguments of function call (message, $background = null)
     *
     * @return string
     * @throws Exception
     */
    public function __call($name, $arguments)
    {
        $color = preg_replace_callback('/([A-Z])/', function($match) {
            return '_' . strtolower($match[1]);
        }, $name);

        if (isset(self::FOREGROUND[$color])) {
            if (count($arguments) == 0) {
                return '';
            }

            if (count($arguments) > 1) {
                $background = $arguments[1];
            } else {
                $background = null;
            }

            $message = $arguments[0];

            return $this->getColoredString($message, $color, $background);
        }

        throw new Exception("Unknown function '$name'");
    }
}
