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
 * @file      CcHttp.php
 * @author    Andreas Dirmeier
 * @par       Language: PHP
 *
 * Description for class CcHttp
 */
namespace NGitServer;

require_once 'CcStringUtil.php';
require_once 'CcFilesystemUtil.php';

/**
 * Utility class for http protocol.
 * For example write status to header 
 */
class CcHttp
{
  /**
   * Write information to header
   * @param string $sName: Name of variable to write to header, oder full string if
   *                       if $sValue not used.
   * @param string $sValue: Data for $sName if required or null
   */
  public static function writeHeader($sName, $sValue=null)
  {
    if(function_exists("header"))
    {
      if($sValue != null)
      {
        header("$sName: $sValue");
      }
      else
      {
        header("$sName");
      }
    }
  }
  
  /**
   * Set contenttype to header
   * @param string $sType
   */
  public static function setContentType($sType)
  {
    CcHttp::writeHeader("Content-Type", $sType);
  }
  
  /**
   * send HTTP 200 message
   */
  public static function ok()
  {
    CcHttp::writeHeader("HTTP/1.1 200 Ok");
  }
  
  /**
   * send HTTP 201 message
   */
  public static function okCreated()
  {
    CcHttp::writeHeader("HTTP/1.1 201 Created");
  }
  
  /**
   * send HTTP 207 message
   */
  public static function okMultistatus()
  {
    CcHttp::writeHeader("HTTP/1.1 201 Multi Status");
  }
  
  /**
   * send HTTP 401 message
   */
  public static function errorAuthRequired()
  {
    header('WWW-Authenticate: Basic realm="CcGitServer"');
    header('HTTP/1.1 401 Authorization Required');
  }
  
  /**
   * send HTTP 403 message
   */
  public static function errorAccessDenied()
  {
    CcHttp::writeHeader("HTTP/1.1 403 Access Denied");
  }
  
  /**
   * send HTTP 404 message
   */
  public static function errorNotFound()
  {
    CcHttp::writeHeader("HTTP/1.1 404 Not Found");
  }
  
  /**
   * send HTTP 406 message
   */
  public static function errorNotAcceptable()
  {
    CcHttp::writeHeader("HTTP/1.1 406 Not Acceptable");
  }
}