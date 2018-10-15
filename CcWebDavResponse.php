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
 * @file      CcWebDavResponse.php
 * @author    Andreas Dirmeier
 * @par       Language: PHP
 *
 * Description for class CcWebDavResponse
 */
require_once 'CcXmlObject.php';

/**
 * Webdav response xml object for inserting to multistatus messages
 * @author tsep
 *
 */
class CcWebDavResponse extends CcXmlObject
{
  /**
   * Setup defualt response structure with http ok
   */
  function __construct ()
  {
    parent::__construct("D:response");
    $oStatusNode = new CcXmlObject("D:status", false);
    $oStatusNode->setContent("HTTP/1.1 200 OK");
    $this->addNode($oStatusNode);
  }

  /**
   * Set href link of Response
   * @param string $sLink
   */
  function setLink ($sLink)
  {
    $oLinkNode = $this->createIfNotExists("D:href");
    $oLinkNode->setContent($sLink);
    $this->addNode($oLinkNode);
  }
  
  /**
   * Add exclusive write support to property 
   */
  function addLockSupportedExclusiveWrite()
  {
    $oXmlElmenent = $this->createIfNotExists("D:propstat/D:prop/D:supportedlock");
    if($oXmlElmenent != null)
    {
      $oLockEntry = new CcXmlObject("D:lockentry");
      $oXmlElmenent->addNode($oLockEntry);
      $oLockScope = new CcXmlObject("D:lockscope");
      $oLockEntry->addNode($oLockScope);
      $oLockExclusive = new CcXmlObject("D:exclusive");
      $oLockScope->addNode($oLockExclusive);
      
      $oLockType = new CcXmlObject("D:locktype");
      $oLockEntry->addNode($oLockType);
      $oLockWrite = new CcXmlObject("D:write");
      $oLockType->addNode($oLockWrite);
    }
  }
  
  /**
   * Add shared write support to property
   */
  function addLockSupportedSharedWrite()
  {
    $oXmlElmenent = $this->createIfNotExists("D:propstat/D:prop/D:supportedlock");
    if($oXmlElmenent != null)
    {
      $oLockEntry = new CcXmlObject("D:lockentry");
      $oXmlElmenent->addNode($oLockEntry);
      $oLockScope = new CcXmlObject("D:lockscope");
      $oLockEntry->addNode($oLockScope);
      $oLockShared = new CcXmlObject("D:shared");
      $oLockScope->addNode($oLockShared);
      
      $oLockType = new CcXmlObject("D:locktype");
      $oLockEntry->addNode($oLockType);
      $oLockWrite = new CcXmlObject("D:write");
      $oLockType->addNode($oLockWrite);
    }
  }
  
  /**
   * Mark property as collection(directory)
   */
  function addCollectionProp()
  {
    $oXmlElmenent = $this->createIfNotExists("D:propstat/D:prop/lp1:resourcetype");
    if($oXmlElmenent != null)
    {
      $oCollectionElement = new CcXmlObject("D:collection");
      $oXmlElmenent->addNode($oCollectionElement);
    }
    $oXmlElmenent2 = $this->createIfNotExists("D:propstat/D:prop/D:getcontenttype");
    if($oXmlElmenent2 != null)
    {
      $oXmlElmenent2->setContent("httpd/unix-directory");
    }
  }
  
  /**
   * Mark property as file
   */
  function addFileProp()
  {
    $this->createIfNotExists("D:propstat/D:prop/lp1:resourcetype");
  }
  
  /**
   * Set created time from file or directory
   * @param string $sCreationDate date to set
   */
  function setCreated($sCreationDate)
  {
    $oXmlElmenent = $this->createIfNotExists("D:propstat/D:prop/lp1:creationdate");
    $oXmlElmenent->setContent($sCreationDate);
  }
  
  /**
   * Set last modified time from file or directory
   * @param string $sLastModiefied date to set
   */
  function setLastModified($sLastModiefied)
  {
    $oXmlElmenent = $this->createIfNotExists("D:propstat/D:prop/lp1:getlastmodified");
    $oXmlElmenent->setContent($sLastModiefied);
  }
}
