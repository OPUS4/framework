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
 * @version     $Id $
 *
 * based on the WordPress Plugin Search Unleashed <http://wordpress.org/extend/plugins/search-unleashed/>
 * @author John Godley
 * @copyright Copyright (C) John Godley
 **/

/**
 * Text highlighting
 *
 * @package default
 **/
class Opus_Search_Highlighter {
	var $first_match = -1;
	var $text        = '';
	var $words       = array ();

	/**
	 * Constructor.
	 *
	 * @param string $text Original text
	 * @param array $words Array of words (Zend_Search_Lucene_Index_Term) to highlight
	 * @param boolean $strip Optional removal of HTML
	 * @return void
	 **/
	function __construct( $text, $words, $strip = false ) {
	    if ( $strip )
			$this->text = $this->strip( $text );
		else
			$this->text = $text;

		$this->matches     = 0;
		$this->first_match = strlen( $text );

		// Find the first matched term
		foreach ( (array)$words AS $index => $wordObject ) {
		    $word = $wordObject->text;
		    if ( preg_match( '/('.$word.')(?!=")/i', $this->text, $matches, PREG_OFFSET_CAPTURE ) > 0 ) {
				$this->first_match = min( $this->first_match, $matches[0][1] );
		    }
            # Remove doublettes
			if (in_array($word, $this->words) === false) {
			    $this->words[] = $word;
			}
		}

		if ( $this->first_match >= strlen( $this->text ) )
			$this->first_match = -1;
	}

	/**
	 * Remove all HTML
	 *
	 * @param string $text HTML text
	 * @return string Plain text
	 **/
	function strip( $text )	{
		$text = preg_replace( preg_encoding( '/<script(.*?)<\/script>/s' ), '', $text );
		$text = preg_replace( preg_encoding( '/<!--(.*?)-->/s' ), '', $text );

		$text = str_replace( '>', '> ', $text );   // Makes the strip function look better
		$text = wp_filter_nohtml_kses( $text );
		$text = stripslashes( $text );
		$text = preg_replace( preg_encoding( '/<!--(.*?)-->/s' ), '', $text );
		$text = strip_html( $text );    // Remove all HTML

		return $text;
	}

	/**
	 * Zooms to a particular portion of text that represents the first highlighted term
	 * If no highlighted term is found the zoomed portion is set to the start
	 *
	 * @param integer $before Number of characters to display before the first matched term
	 * @param integer $after Number of characters to display after the first matched term
	 * @return string Zoomed text
	 **/
	function zoom( $before = 100, $after = 400 ) {
		$text = $this->text;

		// Now zoom
		if ( $this->first_match != -1 ) {
			$start = max( 0, $this->first_match - $before );
			$end   = min( $this->first_match + $after, strlen( $text ) );

			$new = substr( $text, $start, $end - $start );

			if ( $start != 0 )
				$new = preg_replace( '/^[^ ]*/', '', $new );

			if ( $end != strlen( $text ) )
				$new = preg_replace( '/[^ ]*$/', '', $new );

			$new = str_replace( ' ,', ',', $new );
			$new = str_replace( ' .', '.', $new );

			$new = trim( $new );
			$text = ( $start > 0 ? '&hellip; ' : '' ).$new.( $end < strlen( $text ) ? ' &hellip;' : '' );
		}
		elseif ( $this->first_match == -1 ) {
			$text = substr( $text, 0, $after );
			$text = preg_replace( '/[^ ]*$/', '', $text );
			$text .= '&hellip;';
		}

		$this->text = $text;
	}

	/**
	 * Does this instance have any matched terms?
	 *
	 * @param string
	 * @return void
	 **/
	function has_matches() {
		return $this->first_match != -1;
	}

	/**
	 * Get highlighted text
	 *
	 * @return string Text
	 **/
	function get() {
		return $this->text;
	}

	/**
	 * Highlight individual words
	 *
	 * @param object $links Not sure
	 * @return string Highlighted text
	 **/
	function mark_words( $links = false ) {
		$text = $this->text;
		$html = strpos( $text, '<' ) === false ? false : true;

		$this->mark_links = $links;
		foreach ( $this->words AS $pos => $word ) {
			if ( $pos > 5 )
				$pos = 1;

			$this->word_count = 0;
			$this->word_pos   = $pos;

			if ( $html )
				$text = @preg_replace_callback( preg_encoding( '/(?<=>)([^<]+)?('.$word.')(?!=")/i' ), array( &$this, 'highlight_html_word' ), $text );
			else
				$text = preg_replace_callback( '/('.$word.')(?!=")/iu', array( &$this, 'highlight_plain_word' ), $text );
		}

		$this->text = $text;
        return $text;
	}

	/**
	 * Highlight plain text word
	 *
	 * @return void
	 **/
	function highlight_plain_word( $words ) {
		$id = '';
		if ( $this->word_count == 0 && $this->mark_links )
			$id = 'id="high_'.( $this->word_pos + 1 ).'"';

		$this->word_count++;
		return '<span '.$id.' class="searchterm'.( $this->word_pos + 1 ).'">'.$words[1].'</span>';
	}

	/**
	 * Highlight HTML word
	 *
	 * @return void
	 **/
	function highlight_html_word( $words ) {
		$id = '';
		if ( $this->word_count == 0 && $this->mark_links )
			$id = 'id="high_'.( $this->word_pos + 1 ).'"';

		$this->word_count++;
		return $words[1].'<span '.$id.' class="searchterm'.( $this->word_pos + 1 ).'">'.$words[2].'</span>';
	}
}
