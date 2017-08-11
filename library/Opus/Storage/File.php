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
    private $filesDirectory = null;
    private $subDirectory = null;

    /**
     * Construct storage object.  The first parameter $directory states the
     * working directory, in which all file modifications will take place.
     *
     * @param string $directory
     * @throws Opus_Storage_Exception
     */
    public function __construct($directory = null, $subdirectory = null) {
        if (!is_dir($directory)) {
            throw new Opus_Storage_Exception("Storage directory '$directory' does not exist. (cwd: " . getcwd() . ")");
        }

        if (!is_executable($directory)) {
            throw new Opus_Storage_Exception("Storage directory '$directory' is not executable. (cwd: " . getcwd() . ")");
        }

        $this->filesDirectory = Opus_Util_File::addDirectorySeparator($directory);
        $this->subDirectory = Opus_Util_File::addDirectorySeparator($subdirectory);
    }

    public function getWorkingDirectory() {
        return $this->filesDirectory . $this->subDirectory;
    }

    /**
     * Creates subdirectory "$this->workingDirectory/$subdirectory".
     *
     * @param string $subdirectory Subdirectory of working dir to create.
     * @throws Opus_Storage_Exception
     * @return boolean
     */
    public function createSubdirectory() {
        $subFullPath = $this->getWorkingDirectory();

        if (is_dir($subFullPath)) {
            if (!is_readable($this->filesDirectory)) {
                throw new Opus_Storage_Exception("Storage directory '$subFullPath' is not readable. (cwd: " . getcwd() . ")");
            }

            if (!is_writable($this->filesDirectory)) {
                throw new Opus_Storage_Exception("Storage directory '$subFullPath' is not writable. (cwd: " . getcwd() . ")");
            }

            return true;
        }

        if (true === @mkdir($subFullPath, 0777, true)) {
            return true;
        }

        throw new Opus_Storage_Exception('Could not create directory "' . $subFullPath . '"!');

    }

    /**
     * Move a file from source to destination.  The first parameter must be an
     * absolute path to a file outside the working directory.  The second
     * parameter is relative to the working directory.
     *
     * @param string $sourceFile Absolute path.
     * @param string $destintationFile Path relative to workingDirectory.
     * @throws Opus_Storage_Exception
     * @return boolean
     */
    public function copyExternalFile($sourceFile, $destinationFile) {

        $fullDestinationPath = $this->getWorkingDirectory() . $destinationFile;

        if (file_exists($fullDestinationPath)) {
            throw new Opus_Storage_Exception('Destination file already exists: "' . $fullDestinationPath . '"!');
        }

        if (true !== @copy($sourceFile, $fullDestinationPath)) {
            throw new Opus_Storage_Exception('Could not copy file from "' . $sourceFile . '" to "' . $fullDestinationPath . '"!');
        }

        return true;
    }

    /**
     * Copy a file from source to destination
     *
     * @param string $sourceFile Absolute path.
     * @param string $destintationFile Path relative to workingDirectory.
     * @throws Opus_Storage_Exception
     * @throws Opus_Storage_FileNotFoundException if file does not exist
     * @throws Opus_Storage_FileAccessException if renaming of file failed
     * @return boolean
     */
    public function renameFile($sourceFile, $destinationFile) {
        $fullSourcePath = $this->getWorkingDirectory() . $sourceFile;
        $fullDestinationPath = $this->getWorkingDirectory() . $destinationFile;

        if (false === file_exists($fullSourcePath)) {
            throw new Opus_Storage_FileNotFoundException($fullSourcePath, 'File to rename "' . $fullSourcePath . '" does not exist!');
        }

        if (false === is_file($fullSourcePath)) {
            throw new Opus_Storage_Exception('Tried to rename non-file "' . $fullSourcePath . '; abort"!');
        }

        if (true === @rename($fullSourcePath, $fullDestinationPath)) {
            return true;
        }

        throw new Opus_Storage_FileAccessException('Could not rename file from "' . $fullSourcePath . '" to "' . $fullDestinationPath . '"!');

    }

    /**
     * Deletes a given file inside the working directory.
     *
     * @param string $file
     * @throws Opus_Storage_Exception
     * @throws Opus_Storage_FileNotFoundException if file does not exist
     * @throws Opus_Storage_FileAccessException if deleting file failed
     * @return void
     */
    public function deleteFile($file) {
        $fullFile = $this->getWorkingDirectory() . $file;
        if (false === file_exists($fullFile)) {
            throw new Opus_Storage_FileNotFoundException($fullFile, 'File to delete "' . $fullFile . '" does not exist!');
        }

        if (false === is_file($fullFile)) {
            throw new Opus_Storage_Exception('Tried to delete non-file "' . $fullFile . '; abort"!');
        }

        if (false === @unlink($fullFile)) {
            throw new Opus_Storage_FileAccessException('File "' . $fullFile . '" could not be deleted!');
        }

        return;
    }

    /**
     * Deletes current working directory if empty.
     *
     * @throws Opus_Storage_Exception If directory is empty but deleting failed.
     * @return boolean true on success, false if not found or not empty
     */
    public function removeEmptyDirectory() {
        $directory = $this->getWorkingDirectory();

        if (!is_dir($directory)) {
            return false;
        }

        $is_empty = (count(glob($directory . "/*")) === 0);
        if (!$is_empty) {
            return false;
        }

        if (false === @rmdir($directory)) {
            throw new Opus_Storage_Exception('Empty directory "$directory" could not be deleted!');
        }

        return true;
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
        $fullFile = $this->getWorkingDirectory() . $file;

        // TODO basically this class should exist - why check? We don't for other classes.
        if (true === class_exists('finfo')) {
            // for PHP >= 5.3.0 or PECL fileinfo >= 0.1.0
            $finfo = new finfo(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
            if (false !== $finfo) {
                return $finfo->file($fullFile);
            }
        }
        else if (function_exists('mime_content_type')) {
            // use mime_content_type for PHP < 5.3.0
            return @mime_content_type($fullFile);
        }
        else {
            $message = "Opus_Storage_File: Neither PECL fileinfo, nor mime_content_type could be found.";
            $logger = Zend_Registry::get('Zend_Log');
            $logger->err($message);

            return $this->getFileMimeTypeFromExtension($file);
        }
    }

    /**
     * Guesses mime type of file based on its file name.
     *
     * @param string $file Name of file, to guess mimetype for.
     * @return string
     *
     * TODO make mapping configurable in file?
     */
    public function getFileMimeTypeFromExtension($file) {
        $mimeEncoding = 'application/octet-stream';

        if (preg_match('/\.pdf$/', $file) > 0) {
            $mimeEncoding = "application/pdf";
        }
        else if (preg_match('/\.ps$/', $file) > 0) {
            $mimeEncoding = "application/postscript";
        }
        else if (preg_match('/\.txt$/', $file) > 0) {
            $mimeEncoding = "text/plain";
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
        $fullFile = $this->getWorkingDirectory() . $file;
        if (false === file_exists($fullFile)) {
            throw new Exception("File does not exist.");
        }

        // Common workaround for php limitation (2 / 4 GB file size)
        // look at http://php.net/manual/en/function.filesize.php
        // more information
        return sprintf('%u', @filesize($fullFile));

    }

}

