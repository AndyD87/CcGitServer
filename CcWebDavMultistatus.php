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
 * @file      CcWebDavMultistatus.php
 * @author    Andreas Dirmeier
 * @par       Language: PHP
 *
 * Description for class CcWebDavMultistatus
 */
require_once 'CcXmlObject.php';

/**
 * Webdav response xml object for multistatus messages
 * @author tsep
 *
 */
class CcWebDavMultistatus extends CcXmlObject
{
  /**
   * Default create multistatus structure wich 
   * will later filled with responses
   */
  function __construct ()
  {
    parent::__construct("D:multistatus");
  }

  /**
   * Add new response to multistatus
   * @param CcWebDavResponse $oResponse
   */
  function addResponse ($oResponse)
  {
    $this->addNode($oResponse);
  }
}

