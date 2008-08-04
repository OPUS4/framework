<?php
/**
 * Defines the base url view helper. The helper class is passed to each view
 * by Application::setupView() on bootstrapping.
 *
 * This file is part of OPUS. The software OPUS has been developed at the
 * University of Stuttgart with funding from the German Research Net
 * (Deutsches Forschungsnetz), the Federal Department of Higher Education and
 * Research (Bundesministerium fuer Bildung und Forschung) and The Ministry of
 * Science, Research and the Arts of the State of Baden-Wuerttemberg
 * (Ministerium fuer Wissenschaft, Forschung und Kunst des Landes
 * Baden-Wuerttemberg).
 *
 * PHP versions 4 and 5
 *
 * OPUS is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * OPUS is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OPUS; if not, write to the Free Software Foundation,
 * Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @package     Opus_Application_Framework
 * @author      Ralf Claussnitzer <ralf.claussnitzer@slub-dresden.de>
 * @copyright   Universitaetsbibliothek Stuttgart, 1998-2008
 * @license     http://www.gnu.org/licenses/gpl.html
 * @version     $Id$
 */

/**
 * This helper class defines only one method baseUrl() to retrieve the
 * application base url for absolut url's in views.
 *
 * @package     Opus_Application_Framework
 * @subpackage	View_Helper
 */
class Opus_View_Helper_BaseUrl {

	/**
	 * Return the base url of the application.
	 *
	 * @return Base url string.
	 */
	public function baseUrl() {
		$fc = Zend_Controller_Front::getInstance();
		$request=$fc->getRequest();
		return $request->getBaseUrl();
	}

}