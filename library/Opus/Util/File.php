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
 * @copyright   Copyright (c) 2008-2011, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * @category    Framework
 * @package     Opus
 * @author      Jens Schwidder <schwidder@zib.de>
 */

namespace Opus\Util;

use function file_exists;
use function is_dir;
use function mb_strlen;
use function rmdir;
use function rtrim;
use function scandir;
use function unlink;

use const DIRECTORY_SEPARATOR;

/**
 * Utility class for common methods handling files and directories.
 */
class File
{
    /**
     * Remove a directory and its entries recursivly.
     *
     * @param string $dir Directory to delete.
     * @return bool Result of rmdir() call.
     */
    public static function deleteDirectory($dir)
    {
        if (false === file_exists($dir)) {
            return true;
        }
        if (false === is_dir($dir)) {
            return unlink($dir);
        }
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            if (false === self::deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return rmdir($dir);
    }

    /**
     * Adds to a given path a directory separator if not set.
     *
     * @param string $path Path with or without directory separator.
     * @return string Path with directory separator.
     */
    public static function addDirectorySeparator($path)
    {
        if (false === empty($path)) {
            $path = rtrim($path); // remove trailing whitespaces

            $lastIndex = mb_strlen($path) - 1;

            if (DIRECTORY_SEPARATOR !== $path[$lastIndex]) {
                $path .= DIRECTORY_SEPARATOR;
            }
        }

        return $path;
    }
}
