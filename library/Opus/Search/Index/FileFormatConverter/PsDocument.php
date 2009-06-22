<?php
/**
 * Converter for PS documents
 *
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
 * @category    Application
 * @package     Opus_Search
 * @author      Oliver Marahrens <o.marahrens@tu-harburg.de>
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

class Opus_Search_Index_FileFormatConverter_PsDocument implements Opus_Search_Index_FileFormatConverter_FileFormatConverterInterface
{
  /**
   * Converts a PDF file to plain text
   *
   * @param string $filepath Path to the file that should be converted to text
   * @return string Fulltext
   */
    public static function toText($filepath)
    {
   		$config = Zend_Registry::get('Zend_Config');

		$ps2asciiPath = $config->searchengine->ps2ascii->path;
        $maxIndexFileSize = $config->searchengine->index->maxFileSize;
        
   		if (false === file_exists($ps2asciiPath . '/ps2ascii'))
   		{
   			throw new Exception('Cannot index document: PS-Converter not found! Please check configuration.');
   		}
        if (false === file_exists($filepath))
        {
            throw new Exception('Cannot index document: Document "' . $filepath . '" not found!');
        }

        exec("$ps2asciiPath/ps2ascii \"".$filepath."\"", $return, $returnval);
        
        $volltext = implode(' ', $return);
        
        if ($maxIndexFileSize > 0) {
            $volltext = mb_substr($volltext, 0, $maxIndexFileSize);
        }
        
        return $volltext;
    }
}