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
 * @page      CcWebDavResponse
 * @author    Andreas Dirmeier
 * @par       Language: PHP
 *
 * Description for class CcWebDavResponse
 */
require_once 'CcXmlObject.php';

class CcWebDavResponse extends CcXmlObject
{

  function __construct ()
  {
    parent::__construct("D:response");
    $oStatusNode = new CcXmlObject("D:status", false);
    $oStatusNode->setContent("HTTP/1.1 200 OK");
    $this->addNode($oStatusNode);
  }

  function setLink ($sLink)
  {
    $oLinkNode = new CcXmlObject("D:href", false);
    $oLinkNode->setContent($sLink);
    $this->addNode($oLinkNode);
  }
  
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
  
  function addFileProp()
  {
    $this->createIfNotExists("D:propstat/D:prop/lp1:resourcetype");
  }
  
  function setCreated($sCreationDate)
  {
    $oXmlElmenent = $this->createIfNotExists("D:propstat/D:prop/lp1:creationdate");
    $oXmlElmenent->setContent($sCreationDate);
  }
  
  function setLastModified($sLastModiefied)
  {
    $oXmlElmenent = $this->createIfNotExists("D:propstat/D:prop/lp1:getlastmodified");
    $oXmlElmenent->setContent($sLastModiefied);
  }
}
