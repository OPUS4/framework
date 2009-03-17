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
 * @package     Opus_Search
 * @author      Oliver Marahrens <o.marahrens@tu-harburg.de>
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

class Opus_Search_Index_Document extends Zend_Search_Lucene_Document
{

    /* Index structure
     *   title  Titel  UnStored
     *   author  Autor als Nachname, Vorname  UnStored 
     *   persons  sonstige beteiligte Personen als Nachname, Vorname  UnStored
     *   contents  Aus der bzw. den Volltextdateien indizierter Volltext  UnStored 
     *   abstract  Kurzfassung  UnStored
     *   subject  Alle Klassen und Schlagworte als leerzeichenseparierte Liste  UnStored
     *   doctype  Dokumenttyp (auch über Browsing zugänglich, sollte aber auch als Eingrenzungskriterium suchbar sein)  UnStored 
     *   year  Erscheinungsjahr  Keyword 
     *   institute  Alle mit dem Dokument assoziierten Institutionen als leerzeichenseparierte Liste  Text 
     *   docid  dient zur Verknüpfung mit dem DBMS, muss nicht durchsucht werden  UnIndexed
     *   source  Dateiname des Dokuments, das den zu diesem Datensatz indizierten Volltext enthält, muss nicht durchsucht werden, sondern nur angezeigt  UnIndexed
     */

    /**
     * Holds encoding value.
     *
     * @var string
     */
    private $__encoding = 'UTF-8';

    /**
     * Constructor
     *
     * @param array &$documentdata Document to index
     */
    public function __construct(array &$documentdata)
    {
        $this->addField(Zend_Search_Lucene_Field::UnIndexed('source', $documentdata['source'], $this->__encoding));
        $this->addField(Zend_Search_Lucene_Field::UnIndexed('docid', $documentdata['docid'], $this->__encoding));
        $this->addField(Zend_Search_Lucene_Field::Keyword('year', $documentdata['year'], $this->__encoding));
        $this->addField(Zend_Search_Lucene_Field::Keyword('urn', $documentdata['urn'], $this->__encoding));
        $this->addField(Zend_Search_Lucene_Field::Keyword('isbn', $documentdata['isbn'], $this->__encoding));
        $this->addField(Zend_Search_Lucene_Field::Text('abstract', $documentdata['abstract'], $this->__encoding));
        $this->addField(Zend_Search_Lucene_Field::Text('title', $documentdata['title'], $this->__encoding));
        $this->addField(Zend_Search_Lucene_Field::Text('author', $documentdata['author'], $this->__encoding));
        $this->addField(Zend_Search_Lucene_Field::UnStored('fulltext', strtolower($documentdata['content'])));
        #$this->addField(Zend_Search_Lucene_Field::UnStored('contents', "This is just a test, every document should get it as fulltext.", $this->__encoding));
        $this->addField(Zend_Search_Lucene_Field::UnStored('persons', $documentdata['persons'], $this->__encoding));
        $this->addField(Zend_Search_Lucene_Field::UnStored('subject', $documentdata['subject'], $this->__encoding));
        $this->addField(Zend_Search_Lucene_Field::UnStored('doctype', $documentdata['doctype'], $this->__encoding));
        $this->addField(Zend_Search_Lucene_Field::UnStored('institute', $documentdata['institute'], $this->__encoding));
    }
}
