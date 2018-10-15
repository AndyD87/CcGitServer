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

class CcXmlObject
{
  protected $sTag     = "";
  protected $sId      = "";
  protected $sContent = "";
  protected $aNodes   = array();
  protected $aAttributes = array();
  protected $bIsShort = false;
  
  public function __construct($sTag, $bIsShortOpenedTag = true, $sContent = null)
  {
    $this->sTag = $sTag;
    $this->bIsShort = $bIsShortOpenedTag;
    if($sContent != null)
    {
      $this->setContent($sContent);
    }
  }
  
  public function createIfNotExists($sName)
  {
    $aPath = explode("/", $sName);
    /**
     * @var CcXmlObject
     */
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
  
  public function setId($sId)
  {
    $this->sId = $sId;
  }
  
  public function setTag($sTag)
  {
    $this->sTag = $sTag;
  }
  
  public function addNode($aNode)
  {
    if($aNode != null)
    {
      $this->bIsShort = false;
      $this->aNodes[] = $aNode;
    }
  }
  
  public function setContent($sContent)
  {
    if($sContent != null || $sContent != "")
      $this->bIsShort = false;
    $this->sContent = $sContent;
  }
  
  public function addContent($sContent)
  {
    $this->sContent .= $sContent;
  }
  
  public function getContent()
  {
    return $this->sContent;
  }
  
  public function getTag()
  {
    return $this->sTag;
  }
  
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
   * 
   * @return CcXmlObject[]
   */
  public function getNodes()
  {
    return $this->aNodes;
  }
  
  public function removeAttribute($sName)
  {
    if(isset($this->aAttributes[$sName]))
    {
      unset($this->aAttributes[$sName]);
    }
  }
  
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
      if($this->sId  != "")
      {
        $sReturn .= " id=\"".$this->sId."\"";
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
  
  public function __toString()
  {
    return $this->getXml();
  }
}

