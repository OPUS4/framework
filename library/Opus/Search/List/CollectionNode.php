<?php
/**
 * Collection node
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
 * @package     Module_Search
 * @author      Oliver Marahrens <o.marahrens@tu-harburg.de>
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * class CollectionNode
 * includes a list of documents from this Node
 */
class Opus_Search_List_CollectionNode extends Opus_Search_List_BasicList
{

  /**
   * Number of hits in this list
   * 
   * @var integer Number of documents in this list
   * @access private
   */
  private $numberOfDocuments;

  /**
   * Documents belonging to this node
   * 
   * @var array Array of documents contained in this list
   * @access private
   */
  private $documents;

  /**
   * Name of this node
   * 
   * @var string Name of this node
   * @access private
   */
  private $name;

  /**
   * Role-ID of the CollectionRole of this Node
   * 
   * @var integer ID of the root node of this collection
   * @access private
   */
  private $roleId;

  /**
   * Collection-ID of the CollectionNode
   * 
   * @var integer ID of this node in the collection
   * @access private
   */
  private $collectionId;

  /**
   * Constructor
   * 
   * @param array|integer $coll     (Optional) ID of the root node of this collection or array containing the ID and the name of the root node
   * @param array|integer $collnode (Optional) ID of this node of this collection or array containing the ID and the name of this node
   */
  public function __construct($coll = null, $collnode = null) {
  		$this->documents = array();
  		#$this->name = array();
  		$this->roleId = $coll;
  		$this->collectionId = $collnode;
  		if (is_array($coll) === true) {
			$this->name = $coll['name'];
			$this->roleId = (int) $coll['collections_roles_id'];
  		}
  		if (is_array($collnode) === true) {
			$this->name = $collnode[0]['name'];
			$this->collectionId = (int) $collnode[0]['collections_id'];
  		}
  		$this->getDocuments();
  }

  /**
   * Add a Document to this node
   * 
   * @param Opus_Search_Adapter_DocumentAdapter $doc Document in this node
   * @return void
   */
  public function add($doc) {
    array_push($this->documents, $doc);
  } 

  /**
   * Returns the number of documents in this node
   * 
   * @return integer Number of hits in this list
   */
  public function count() {
  	$this->numberOfDocuments = count($this->documents);
    return $this->numberOfDocuments;
  }

  /**
   * Deletes a Search hit from the list
   * 
   * @param Opus_Search_Adapter_DocumentAdapter|integer $item Element (or index of element) that should be removed from the list
   * @return void
   */
  public function delete($item) {
    
  }

  /**
   * Gets an element from the list by its index
   * 
   * @param integer $index Index number of the element
   * @return Opus_Search_SearchHit Document with the given index number out of this list
   */
  public function get($index) {
    return $this->documents[$index];
  }  

  /**
   * Sorts the list
   * Possible sort criteria are:
   * not defined yet
   * 
   * @param string $criteria Criteria the list should be sorted with
   * @return void
   */
  public function sort($criteria) {
    
  }  

  /**
   * Gets the name of this node by its language
   * 
   * @param string $language (Optional) Desired language of the element, if null or not given the language will be detected using Zend_Locale
   * @return string If the language does not exist, null will be returned
   */
  public function getName($language = null) {
  	#if ($language === null) 
  	#{
     #   $translate = Zend_Registry::get('Zend_Translate');
  		#$lang = $translate->getLocale();
		// get the correct language from the database...
  		#switch($lang)
  		#{
	  	#	case "de_DE":
  		#		$language = "ger";
  		#		break;
  		#	default:
	  	#		$language = "eng";
  		#		break;
  		#}
  	#}
  	#if (array_key_exists($language, $this->name)) return $this->name[$language];
  	return $this->name;
  }  

  /**
   * Gets the ID of the CollectionRole containing this Node
   * 
   * @return integer RoleId
   */
  public function getRoleId() {
  	return $this->roleId;
  }  

  /**
   * Gets the ID of the CollectionNode
   * 
   * @return integer CollectionId
   */
  public function getNodeId() {
  	return $this->collectionId;
  }  

  /**
   * Gets the SubNodes ID of this CollectionNode
   * 
   * @return integer CollectionId
   */
  public function getSubNodes() {
  		$nodeData = Opus_Collection_Information::getSubCollections($this->roleId, $this->collectionId);
  		$doctypeList = new Opus_Search_List_CollectionNodeList();
		foreach ($nodeData as $member) {
			$node = new Opus_Search_List_CollectionNode($this->roleId, $member['content']);
			$doctypeList->add($node);
		}
  	return $doctypeList;
  }  

  /**
   * Builds the CollectionNode-Object mapping the information from Opus_Collection
   * 
   * @return string Complete path to root. If this is root, null will be returned
   */
  public function getCollectionNode() {
  		if ($this->collectionId > 0) {
  			$nodeInfo = Opus_Collection_Information::getPathToRoot($this->roleId, $this->collectionId);
  		} else {
  			$nodeInfo = null;
  		}
		return $nodeInfo;
  }  

  /**
   * Gets the documents from this Node from the database
   * 
   * @param boolean $alsoSubnodes (Optional) Put the Subnodes also in the CollectionNode
   * @return array Documents in this node
   */
  public function getDocuments($alsoSubnodes = false) {
  		$docs = Opus_Collection_Information::getAllCollectionDocuments($this->roleId, $this->collectionId, $alsoSubnodes);
		unset ($this->documents);
		$this->documents = array();
		foreach ($docs as $member) {
			$doc = new Opus_Search_Adapter_DocumentAdapter( (int) $member);
			$this->add($doc);
		}
  		return $this->documents;
  }
}