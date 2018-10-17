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
require_once 'CcStringUtil.php';
require_once 'CcFilesystemUtil.php';

class CcHttp
{
  public static function writeHeader($sName, $sValue=null)
  {
    if(function_exists("header"))
    {
      if($sValue != null)
      {
        header("$sName: $sValue");
        CcGitServer::writeDebugLog("$sName: $sValue");
      }
      else
      {
        header("$sName");
        CcGitServer::writeDebugLog("$sName");
      }
    }
  }
  
  public static function setContentType($sType)
  {
    CcHttp::writeHeader("Content-Type", $sType);
  }
  
  public static function ok()
  {
    CcHttp::writeHeader("HTTP/1.1 200 Ok");
  }
  
  public static function okCreated()
  {
    CcHttp::writeHeader("HTTP/1.1 201 Created");
  }
  
  public static function okMultistatus()
  {
    CcHttp::writeHeader("HTTP/1.1 201 Multi Status");
  }
  
  public static function errorAuthRequired()
  {
    header('WWW-Authenticate: Basic realm="CcGitServer"');
    header('HTTP/1.1 401 Authorization Required');
  }
  
  public static function errorAccessDenied()
  {
    CcHttp::writeHeader("HTTP/1.1 403 Access Denied");
  }
  
  public static function errorNotFound()
  {
    CcHttp::writeHeader("HTTP/1.1 404 Not Found");
  }
  
  public static function errorNotAcceptable()
  {
    CcHttp::writeHeader("HTTP/1.1 406 Not Acceptable");
  }
}