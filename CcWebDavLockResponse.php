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
 * @file      CcWebDavLockResponse.php
 * @author    Andreas Dirmeier
 * @par       Language: PHP
 *
 * Description for class CcWebDavLockResponse
 */
require_once 'CcXmlObject.php';

class CcWebDavLockResponse extends CcXmlObject
{

  function __construct ()
  {
    parent::__construct("D:prop");
    $this->addAttribute("xmlns:D", "DAV:");
    
    $oActiveLock = $this->createIfNotExists("D:lockdiscovery/D:activelock");
    
    $oLockType = new CcXmlObject("D:locktype");
    $oActiveLock->addNode($oLockType);
    $oLockTypeWrite = new CcXmlObject("D:write");
    $oLockType->addNode($oLockTypeWrite);
    
    $oLockScope = new CcXmlObject("D:lockscope");
    $oActiveLock->addNode($oLockScope);
    $oLockScopeSub = new CcXmlObject("D:exclusive");
    $oLockScope->addNode($oLockScopeSub);
    
    $oDepth = new CcXmlObject("D:depth");
    $oActiveLock->addNode($oDepth);
    $oDepth->setContent("infinity");
    
    $oTimeout = new CcXmlObject("D:timeout");
    $oActiveLock->addNode($oTimeout);
    $oTimeout->setContent("Second-600");
  }

  function setUuid ($sUuid)
  {
    $oActiveLock = $this->createIfNotExists("D:lockdiscovery/D:activelock");
    $oLockToken = new CcXmlObject("D:locktoken");
    $oActiveLock->addNode($oLockToken);
    $oHref = new CcXmlObject("D:href");
    $oLockToken->addNode($oHref);
    $oHref->setContent($sUuid);
  }
}
