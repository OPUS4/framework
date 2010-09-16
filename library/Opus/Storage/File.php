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
 * @package     Opus_Model
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @author      Henning Gerhardt <henning.gerhardt@slub-dresden.de>
 * @copyright   Copyright (c) 2008-2010, OPUS 4 development team
 * @copyright   Copyright (c) 2010 Saechsische Landesbibliothek - Staats- und Universitaetsbibliothek Dresden (SLUB)
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * 
 */
class Opus_Storage_File {

    /**
     * Working directory.  All files will be modified relative to this path.
     *
     * @var string
     */
    private $workingDirectory = null;

    /**
     * Construct storage object.  The first parameter $directory states the
     * working directory, in which all file modifications will take place.
     *
     * @param string $directory 
     */
    public function __construct($directory) {
        if (!is_dir($directory)) {
            throw new Opus_Storage_Exception("Storage directory '$directory' does not exist. (cwd: " . getcwd() . ")");
        }

        if (!is_executable($directory)) {
            throw new Opus_Storage_Exception("Storage directory '$directory' is not executable. (cwd: " . getcwd() . ")");
        }

        $this->workingDirectory = $this->addDirectorySeparator($directory);

    }

    /**
     * Adds to a given path a directory separator if not set.
     *
     * @param string $path Path with or without directory separator.
     * @return string Path with directory separator.
     */
    private function addDirectorySeparator($path) {
        if (false === empty($path)) {
            if (DIRECTORY_SEPARATOR !== $path[mb_strlen($path) - 1]) {
                $path .= DIRECTORY_SEPARATOR;
            }
        }

        return $path;

    }

    /**
     * Creates subdirectory "$this->workingDirectory/$subdirectory"
     *
     * @param string $subdirectory Subdirectory of working dir to create.
     * @throws Opus_Storage_Exception
     * @return boolean
     */
    public function createSubdirectory($subdirectory) {
        $subFullPath = $this->workingDirectory . DIRECTORY_SEPARATOR . $subdirectory;

        if (is_dir($subFullPath)) {
            return true;
        }

        if (true === @mkdir($subFullPath, 0777, true)) {
            return true;
        }

        throw new Opus_Storage_Exception('Could not create directory "' . $subFullPath . '"!');

    }

    /**
     * Copy a file from source to destination
     *
     * @param string $sourceFile Absolute path.
     * @param string $destintationFile Path relative to workingDirectory.
     * @throws Opus_Storage_Exception
     * @return boolean
     */
    public function copyFile($sourceFile, $destinationFile) {
        $fullDestinationPath = $this->workingDirectory . $destinationFile;
        if (true === @copy($sourceFile, $fullDestinationPath)) {
            return true;
        }

        throw new Opus_Storage_Exception('Could not copy file from "' . $sourceFile . '" to "' . $fullDestinationPath . '"!');

    }

    /**
     * Copy a file from source to destination
     *
     * @param string $sourceFile Absolute path.
     * @param string $destintationFile Path relative to workingDirectory.
     * @throws Opus_Storage_Exception
     * @return boolean
     */
    public function renameFile($sourceFile, $destinationFile) {
        $fullSourcePath = $this->workingDirectory . $sourceFile;
        $fullDestinationPath = $this->workingDirectory . $destinationFile;

        if (false === file_exists($fullSourcePath)) {
            throw new Opus_Storage_Exception('File to rename "' . $fullSourcePath . '" does not exist!');
        }

        if (true === @rename($fullSourcePath, $fullDestinationPath)) {
            return true;
        }

        throw new Opus_Storage_Exception('Could not rename file from "' . $fullSourcePath . '" to "' . $fullDestinationPath . '"!');

    }

    /**
     * Deletes a given file.
     *
     * @param string $file
     * @throws Opus_Storage_Exception
     * @return boolean
     */
    public function deleteFile($file) {
        $fullFile = $this->workingDirectory . $file;
        if (false === file_exists($fullFile)) {
            throw new Opus_Storage_Exception('File to delete "' . $fullFile . '" does not exist!');
        }

        if (true === @unlink($fullFile)) {
            return true;
        }

        throw new Opus_Storage_Exception('File "' . $fullFile . '" could not be deleted!');

    }

    /**
     * Determine mime encoding information for a given file.
     * If mime encoding could not be determinated 'application/octet-stream'
     * is returned.
     *
     * @param string $file
     * @return string
     */
    public function getFileMimeEncoding($file) {
        $fullFile = $this->workingDirectory . $file;
        $mimeEncoding = 'application/octet-stream';

        // for PHP >= 5.30 or PECL fileinfo >= 0.1.0
        if (true === class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
            if (false !== $finfo) {
                $mimeEncoding = $finfo->file($fullFile);
                $finfo = null;
            }
        }
        else if (function_exists('mime_content_type')) {
            // use mime_content_type for PHP < 5.3.0
            $mimeEncoding = @mime_content_type($fullFile);
        }
        else {
            $message = "Opus_Storage_File: Neither PECL fileinfo, nor mime_content_type could be found.";
            $logger = Zend_Registry::get('Zend_Log');
            $logger->err($message);
        }

        return $mimeEncoding;

    }

    /**
     * Determine size of a given file.
     *
     * @param string $file
     * return integer
     */
    public function getFileSize($file) {
        $fullFile = $this->workingDirectory . $file;

        $fileSize = 0;
        if (true === file_exists($fullFile)) {
            // Common workaround for php limitation (2 / 4 GB file size)
            // look at http://php.net/manual/en/function.filesize.php
            // more information
            $fileSize = sprintf('%u', @filesize($fullFile));
        }

        return $fileSize;

    }

}

