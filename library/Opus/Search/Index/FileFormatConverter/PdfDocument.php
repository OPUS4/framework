<?php
/**
 * Converter for PDF documents
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

class Opus_Search_Index_FileFormatConverter_PdfDocument implements Opus_Search_Index_FileFormatConverter_FileFormatConverterInterface
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

		$pdftotextPath = $config->searchengine->pdftotext->path;
		$ocrEnabled = $config->searchengine->ocr->enable;
		$maxIndexFileSize = $config->searchengine->index->maxFileSize;

		if ($ocrEnabled === '1')
		{
			$ocrTimeout = $config->searchengine->ocr->timeout;
			if (empty($ocrTimeout) === true) $ocrTimeout = 60;
			$pdfimages = $config->searchengine->ocr->pdfimages->path . '/pdfimages';
			if (file_exists($pdfimages) === false) echo "Warning: pdfimages not found!\n";
			$ocropus = $config->searchengine->ocr->ocropus->path . '/ocropus';
			if (file_exists($ocropus) === false) echo "Warning: ocropus not found!\n";
		}

   		if (false === file_exists($pdftotextPath . '/pdftotext'))
   		{
   			throw new Exception('Cannot index document: PDF-Converter not found! Please check configuration.');
   		}
        if (false === file_exists($filepath))
        {
            throw new Exception('Cannot index document: Document "' . $filepath . '" not found!');
        }

        // Failure output can be redirected to /dev/null
        exec("$pdftotextPath/pdftotext -enc UTF-8 \"".$filepath."\" - 2> /dev/null", $return, $returnval);
                
        $volltext = implode(' ', $return);
        
        // if fulltext does not include anything but Spaces, try to ocr it (if its enabled)
        if ($ocrEnabled === '1' &&
            strlen(str_replace(' ', '', $volltext)) === 0 &&  
            file_exists($ocropus) === true && 
            file_exists($pdfimages) === true) 
        {
        	echo "Fulltext not extractable, trying to OCR $filepath...\n";
        	$ocrdir = '../workspace/tmp/ocr' . basename($filepath);
            if (file_exists($ocrdir) === false) {
                mkdir($ocrdir);
            }
            // extract images from PDF
            exec("$pdfimages \"$filepath\" $ocrdir/file");
            $ocrdirHandle = opendir($ocrdir);
            $ocrStart = time();
            while (false !== ($file = readdir($ocrdirHandle))) {
            	if ($file !== '.' && $file !== '..') {
            		if (time <= $ocrStart+$ocrTimeout) {
                		// Failure output can be redirected to /dev/null
                	    exec("$ocropus page \"$ocrdir/$file\" 2> /dev/null", $fulltext, $returnvalue);
                	    $volltext .= implode(' ', $fulltext);
            		}
            	    unlink($ocrdir . '/' . $file);
            	}
            }
            rmdir($ocrdir);
        }
        
        if ($maxIndexFileSize > 0) {
            $volltext = mb_substr($volltext, 0, $maxIndexFileSize);
        }
        #echo $volltext;
        return $volltext;
    }
}