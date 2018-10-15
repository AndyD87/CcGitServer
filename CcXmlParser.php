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

class CcXmlParser
{
  private $oParserObject;
  /**
   * @var CcXmlObject
   */
  private $oRootNode = null;
  private $oCurrentNode = array();
  private $iError = -1;
  
  function __construct()
  {
    $this->iError = 0;
    $this->oRootNode = null;
    $this->oParserObject = xml_parser_create();
    xml_set_object($this->oParserObject, $this);
    xml_parser_set_option($this->oParserObject, XML_OPTION_CASE_FOLDING, 0);
  }
  
  function __destruct()
  {
    
  }
  
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
  
  function evtEndElement($parser,$sElementName)
  {
    array_shift($this->oCurrentNode);
  }
  
  function evtAddDataElement($parser,$sData)
  {
    if(count($this->oCurrentNode) > 1)
    {
      $this->oCurrentNode[count($this->oCurrentNode)-1]->addContent($sData);
    }
  }
  
  function writeError()
  {
    $this->iError = xml_get_error_code($this->oParserObject);
    CcGitServer::writeDebugLog("ErrorNr: ".$this->iError);
    CcGitServer::writeDebugLog(xml_error_string(xml_get_error_code($this->oParserObject)));
  }
  
  static function parseXml($sInputData)
  {
    $oParser = new CcXmlParser();
    if($sInputData != null && $sInputData != "")
    {
      $oParser->parse($sInputData);
    }
    return $oParser;
  }
  
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
  
  public function hasError()
  {
    return $this->iError != 0;
  }
  
  public function getRootNode()
  {
    return $this->oRootNode;
  }
}
