<?php
/**
 * @copyright  Andreas Dirmeier (C) 2018
 *
 * This file is part of CcGitServer.
 *
 * CcGitServer is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * CcGitServer is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with CcGitServer.  If not, see <http://www.gnu.org/licenses/>.
 **/
/**
 * @file      CcXmlParser.php
 * @author    Andreas Dirmeier
 * @par       Language: PHP
 *
 * Description for class CcXmlParser
 */

require_once "CcXmlObject.php";

/**
 * Object to generate a CcXmlObject from string
 */
class CcXmlParser
{
  /**
   * Object from xml_parser_creates
   * @var resource $oParserObject
   */
  private $oParserObject;
  /**
   * Entry node for parsed string
   * @var CcXmlObject $oRootNode
   */
  private $oRootNode = null;
  /**
   * Current nodes in current path of parser, where at 0 is root node
   * @var CcXmlObject[] $oCurrentNode
   */
  private $oCurrentNode = array(); //!< ignore warning
  /**
   * Error value if something was wrong.
   * @var int $iError
   */
  private $iError = -1;
  
  /**
   * Setup all requirements to parse a xml string
   */
  function __construct()
  {
    $this->iError = 0;
    $this->oRootNode = null;
    $this->oParserObject = xml_parser_create();
    xml_set_object($this->oParserObject, $this);
    xml_parser_set_option($this->oParserObject, XML_OPTION_CASE_FOLDING, 0);
  }
  
  /**
   * Free all ressources
   */
  function __destruct()
  {
    xml_parser_free($this->oParserObject);
  }
  
  /**
   * This method gets called if new xml object was found.
   * This will add object to current node.
   * @param object $parser: in constructor created parser object.
   * @param string $sElementName: name of new xml object
   * @param array $element_attrs: all attributes to add to new xml object.
   */
  function evtNewElement($parser,$sElementName,$element_attrs)
  {
    $oNode = new CcXmlObject($sElementName);
    foreach($element_attrs as $Key =>$value)
    {
      $oNode->addAttribute($Key, $value);
    }
    if(count($this->oCurrentNode) > 0)
    {
      $this->oCurrentNode[count($this->oCurrentNode)-1]->addNode($oNode);
    }
    else 
    {
      $this->oRootNode = $oNode;
    }
    $this->oCurrentNode[] = $oNode;
  }
  
  /**
   * Finalize current xml object and reduce current nodepath by 1
   * @param object $parser: in constructor created parser object.
   * @param string $sElementName: Name of ending object to check for validation, curently ignored
   */
  function evtEndElement($parser,$sElementName)
  {
    array_shift($this->oCurrentNode);
  }
  
  /**
   * Gets called if new content is available for current xml object.
   * @param object $parser: in constructor created parser object.
   * @param string $sData: data to append to current xml node
   */
  function evtAddDataElement($parser,$sData)
  {
    if(count($this->oCurrentNode) > 1)
    {
      $this->oCurrentNode[count($this->oCurrentNode)-1]->addContent($sData);
    }
  }
  
  /**
   * Get current xml error from parser and write error to debug log.
   */
  function writeError()
  {
    $this->iError = xml_get_error_code($this->oParserObject);
    CcGitServer::writeDebugLog("ErrorNr: ".$this->iError);
    CcGitServer::writeDebugLog(xml_error_string(xml_get_error_code($this->oParserObject)));
  }
  
  /**
   * Direct parse a string without creating a parser object.
   * @param string $sInputData: Data to create xml object from
   * @return CcXmlParser|NULL Created root node or null if failed. 
   */
  static function parseXml($sInputData)
  {
    $oParser = new CcXmlParser();
    if($sInputData != null && $sInputData != "")
    {
      $oParser->parse($sInputData);
    }
    return $oParser;
  }
  
  /**
   * Parse a string and generate a root node
   * @param string $sInputData: Data to create xml object from
   * @return CcXmlParser|NULL Created root node or null if failed.
   */
  function parse($sInputData)
  {
    if(xml_set_element_handler($this->oParserObject,"evtNewElement","evtEndElement"))
    {
      if(xml_set_character_data_handler($this->oParserObject,"evtAddDataElement"))
      {
        if(xml_parse($this->oParserObject, $sInputData, true))
        {
          if(xml_parser_free($this->oParserObject))
          {
          }
          else
          {
            $this->writeError();
          }
        }
        else
        {
          $this->writeError();
        }
      }
      else
      {
        $this->writeError();
      }
    }
    else
    {
      $this->writeError();
    }
    return $this->oRootNode;
  }
  
  /**
   * Check if any error occured
   * @return boolean true if error occured
   */
  public function hasError()
  {
    return $this->iError != 0;
  }
  
  /**
   * Get generated root node
   * @return CcXmlObject|null if any error occured
   */
  public function getRootNode()
  {
    return $this->oRootNode;
  }
}
