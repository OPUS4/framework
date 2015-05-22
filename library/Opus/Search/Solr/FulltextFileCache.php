<?php

/**
 * This file is part of OPUS. The software OPUS has been originally developed
 * at the University of Stuttgart with funding from the German Research Net,
 * the Federal Department of Higher Education and Research and the Ministry
 * of Science, Research and the Arts of the State of Baden-Wuerttemberg.
 *
 * OPUS 4 is a complete rewrite of the original OPUS software and was developed
 * by the Stuttgart University Library, the Library Service Center
 * Baden-Wuerttemberg, the North Rhine-Westphalian Library Service Center,
 * the Cooperative Library Network Berlin-Brandenburg, the Saarland University
 * and State Library, the Saxon State Library - Dresden State and University
 * Library, the Bielefeld University Library and the University Library of
 * Hamburg University of Technology with funding from the German Research
 * Foundation and the European Regional Development Fund.
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
 * @category    Application
 * @author      Thomas Urban <thomas.urban@cepharum.de>
 * @copyright   Copyright (c) 2009-2015, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

class Opus_Search_Solr_FulltextFileCache {

	const MAX_FILE_SIZE = 16777216; // 16 MiByte


	protected static function getCacheFileName( Opus_File $file ) {
		$name = null;

		try {
			$hash = $file->getRealHash('md5') . '-' . $file->getRealHash('sha256');
			$name = realpath( Opus_Config::get()->workspacePath . "/cache/solr_cache---$hash.txt" );
		}
		catch (Exception $e) {
			Opus_Log::get()->err(__CLASS__ . '::' . __METHOD__ . ' : could not compute hash values for ' . $file->getPath() . " : $e");
		}

		return $name;
	}

	/**
	 * Tries readng cached fulltext data linked with given Opus file from cache.
	 *
	 * @param Opus_File $file
	 * @return false|string found fulltext data, false on missing data in cache
	 */
	public static function readOnFile( Opus_File $file ) {
		$fileName = static::getCacheFileName( $file );
		if ( $fileName && is_readable( $fileName ) ) {
			// TODO: Why keeping huge files in cache for not actually using here but trying to fetch extraction from remote Solr service over and over again?
			if ( filesize( $fileName ) > self::MAX_FILE_SIZE ) {
				Opus_Log::get()->info( 'Skipped reading fulltext HUGE cache file ' . $fileName );
			} else {
				// try reading cached content
				$fileContent = file_get_contents( $fileName );
				if ( $fileContent !== false ) {
					return trim( $fileContent );
				}

				Opus_Log::get()->info( 'Failed reading fulltext cache file ' . $fileName );
			}
		}

		return false;
	}

	/**
	 * Tries writing fulltext data to local cache linked with given Opus file.
	 *
	 * @note Writing file might fail without notice. Succeeding tests for cached
	 *       record are going to fail then, too.
	 *
	 * @param Opus_File $file
	 * @param string $fulltext
	 */
	public static function writeOnFile( Opus_File $file, $fulltext ) {
		if ( is_string( $fulltext ) ) {
			// try deriving cache file's name first
			$cache_file = static::getCacheFileName( $file );
			if ( $cache_file ) {
				// use intermediate temporary file with random name for writing
				// to prevent race conditions on writing cache file
				$tmp_path = realpath( Opus_Config::get()->workspacePath . '/tmp/' );
				$tmp_file = tempnam( $tmp_path, 'solr_tmp---' );

				if ( !file_put_contents( $tmp_file, trim( $fulltext ) ) ) {
					Opus_Log::get()->info( 'Failed writing fulltext temp file ' . $tmp_file );
				} else {
					// writing temporary file succeeded
					// -> rename to final cache file (single-step-operation)
					if ( !rename( $tmp_file, $cache_file ) ) {
						// failed renaming
						Opus_Log::get()->info( 'Failed renaming temp file to fulltext cache file ' . $cache_file );

						// don't keep temporary file
						unlink( $tmp_file );
					}
				}
			}
		}
	}
}
