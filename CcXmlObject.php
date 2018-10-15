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
 * @file      CcXmlObject.php
 * @author    Andreas Dirmeier
 * @par       Language: PHP
 *
 * Description for class CcXmlObject
 */

/**
 * A basic xml object wich will store name attributes and subnodes.
 */
class CcXmlObject
{
  /**
   * Name of this xml object
   * @var string $sTag
   */
  protected $sTag     = "";
  /**
   * Content as string
   * @var string $sContent
   */
  protected $sContent = "";
  /**
   * Array of containing xml nodes
   * @var CcXmlObject[] $aNodes
   */
  protected $aNodes   = array(); //!< ignore warning
  /**
   * Array of attributes stored
   * @var array $aAttributes
   */
  protected $aAttributes = array();
  /**
   * Marker if this XmlObject is short tag.
   * This is a short tag: &lt;tag /&gt;
   * This is not a short tag: &lt;tag&gt;&lt;/tag&gt;
   * @var string $bIsShort
   */
  protected $bIsShort = false;
  
  /**
   * Create a basic xml node by name and fill with data if set.
   * @param string $sTag: Name of new XmlObject
   * @param boolean $bIsShortOpenedTag: true if it is an short tag
   * @param string $sContent: inital content
   */
  public function __construct($sTag, $bIsShortOpenedTag = true, $sContent = null)
  {
    $this->sTag = $sTag;
    $this->bIsShort = $bIsShortOpenedTag;
    if($sContent != null)
    {
      $this->setContent($sContent);
    }
  }
  
  /**
   * Create a subnode if not already exists.
   * If not existing, a new node will be generated and returned.
   * If existing, the found node will be returned.
   * A path like "subnode/subnode/node" is also allowed
   * @param string $sName: name or path to node to search for.
   * @return CcXmlObject|NULL
   */
  public function createIfNotExists($sName)
  {
    $aPath = explode("/", $sName);
    $oCurrentNode = $this;
    for($i=0; $i<count($aPath); $i++)
    {
      if($aPath[$i] != "")
      {
        if($oCurrentNode != null &&
            $oCurrentNode->getNode($aPath[$i]) != null)
        {
          $oCurrentNode = $oCurrentNode->getNode($aPath[$i]);
        }
        else
        {
          $oNewNode = new CcXmlObject($aPath[$i]);
          $oCurrentNode->addNode($oNewNode);
          $oCurrentNode = $oNewNode;
        }
      }
    }
    return $oCurrentNode;
  }
  
  /**
   * Add an Attribute to current xml tag
   * 
   * An empty Attribute is possyible by seeting $sAttribute to null
   * 
   * @param string $sName
   * @param string|null $sAttribute
   */
  public function addAttribute($sName, $sAttribute)
  {
    $this->aAttributes[$sName] = $sAttribute;
  }
  
  /**
   * Select an attribute and change its value.
   * If not existing, create it.
   * @param string $sName
   * @param string $sAttribute
   */
  public function setAttribute($sName, $sAttribute)
  {
    if(isset($this->aAttributes[$sName]))
    {
      $this->aAttributes[$sName] = $sAttribute;
    }
    else 
    {
      $this->addAttribute($sName, $sAttribute);
    }
  }
  
  /**
   * Change name of this xml object
   * @param string $sTag
   */
  public function setTag($sTag)
  {
    $this->sTag = $sTag;
  }
  
  /**
   * Add a node to this object
   * @param CcXmlObject $aNode: new Node to add
   */
  public function addNode($aNode)
  {
    if($aNode != null)
    {
      $this->bIsShort = false;
      $this->aNodes[] = $aNode;
    }
  }
  
  /**
   * Overwrite or set current content of object.
   * @param string $sContent
   */
  public function setContent($sContent)
  {
    if($sContent != null || $sContent != "")
      $this->bIsShort = false;
    $this->sContent = $sContent;
  }
  
  /**
   * Append content to currently stored
   * @param string $sContent
   */
  public function addContent($sContent)
  {
    $this->sContent .= $sContent;
  }
  
  /**
   * Get currently stored content.
   * @return string
   */
  public function getContent()
  {
    return $this->sContent;
  }
  
  /**
   * Get name of this xml object
   * @return string
   */
  public function getTag()
  {
    return $this->sTag;
  }
  
  /**
   * Find an attribute by name.
   * @param string $sName: Name of attribute to search for
   * @return array|NULL Attribute as array or NULL if not found.
   */
  public function getAttribute($sName)
  {
    if(count($this->aAttributes))
    {
      foreach($this->aAttributes as $sAttributeName => $sData)
      {
        if($sName == $sAttributeName)
        {
          return $sData;
        }
      }
    }
    return null;
  }
  
  /**
   * Get Node by name.
   * A path like "subnode/subnode/node" is also allowed
   * @param string $sName: Name or path to search for
   * @return CcXmlObject|NULL Found node or NULL if not
   */
  public function getNode($sName)
  {
    $aPath = explode("/", $sName);
    $oCurrentNode = $this;
    $oFoundNode = null;
    for($i=0; $i<count($aPath); $i++)
    {
      $bFound = false;
      foreach($oCurrentNode->getNodes() as $Key)
      {
        if($Key->sTag == $aPath[$i])
        {
          $bFound = true; 
          $oCurrentNode = $Key;
          if($i == count($aPath)-1)
          {
            $oFoundNode = $Key;
          }
          break;
        }
      }
      if($bFound == false)
        break;
    }
    return $oFoundNode;
  }
  
  /**
   * Get all stored nodes as array
   * @return CcXmlObject[]
   */
  public function getNodes()
  {
    return $this->aNodes;
  }
  
  /**
   * Remove an attribute by name
   * @param string $sName
   */
  public function removeAttribute($sName)
  {
    if(isset($this->aAttributes[$sName]))
    {
      unset($this->aAttributes[$sName]);
    }
  }
  
  /**
   * Check if an attribute by name stored
   * @param string $sName
   * @return boolean
   */
  public function hasAttribute($sName)
  {
    if(count($this->aAttributes))
    {
      foreach($this->aAttributes as $sAttributeName => $sData)
      {
        if($sName == $sAttributeName)
        {
          $sData;
          return true;
        }
      }
    }
    return false;
  }
  
  /**
   * Get the whole content of this xml object as string.
   * This string can be extended to a more readable string.
   * @param boolean $bIntend: true if string should become more readable
   * @param number $iIntendLevel: Currently ignored, no level supported.
   * @return string formated xml string
   */
  public function getXml($bIntend = false, $iIntendLevel = 0)
  {
    $sReturn = "";
    if($this->sTag)
    {
      $sReturn .= "<";
      $sReturn .= $this->sTag;
      if(count($this->aAttributes))
      {
        foreach($this->aAttributes as $sName => $sData)
        {
          $sReturn .= " ";
          $sReturn .= $sName;
          if($sData != null)
          {
            $sReturn .= "=\"";
            $sReturn .= $sData;
            $sReturn .= "\"";
          }
        }
      }
      
      if($this->bIsShort == true)
      {
        $sReturn .= " />";
      }
      else 
      {
        $sReturn .= ">";
      }
    }
    if($bIntend) $sReturn .= "\n";
    
    $sContent = $this->getContent();
    if($sContent != "")
    {
      $sReturn .= $sContent;
      if($bIntend) $sReturn .= "\n";
    }
    
    foreach($this->aNodes as $oNode)
    {
      $sReturn .= $oNode->getXml($bIntend, $iIntendLevel);
    }
    
    if($this->sTag)
    {
      if($this->bIsShort == false)
      {
        $sReturn .= "</";
        $sReturn .= $this->sTag;
        $sReturn .= ">";
        if($bIntend) $sReturn .= "\n";
      }
    }
    return $sReturn;
  }
  
  /**
   * Get a string of this object
   * @return string formated string from getXml()
   */
  public function __toString()
  {
    return $this->getXml();
  }
}

