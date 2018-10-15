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
 * @file
 * @brief This file is the main entry point for CcGitServer
 *        It is a copy of the original git.php and featured with a custom
 *        LinkConverter.
 *
 * This file can be called from webser with redirect (look at .htaccess file) or
 * from commandline:
 *
 * Commandline options:
 *  - **create PathToNewProject**
 *    create Project at "PathToNewProject"
 *
 * Example for creating a directory:
 *
 *    php git.php create ExampleProject
 *
 *    The command will generate a project named ExampleProject.git in current directory
 */
require_once 'CcGitServer.php';
require_once 'CcLinkConverter.php';
require_once 'IGitServerAuth.php';

class CustomLinkConverter extends CcLinkConverter
{
  public function __construct()
  {
    $sNewPath = dirname(__FILE__)."/../repositories";
    $this->setRootPath($sNewPath);
    if(isset($_SERVER['HTTPS']) &&
        isset($_SERVER['HTTP_HOST']))
    {
      $sRootLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
      $sRootLink .= "://".$_SERVER['HTTP_HOST']."/git/repositories";
      $this->setRootLink($sRootLink);
    }
    $this->setupDefault();
  }
  
  /**
   * This method will generate a path to server stored file from http link
   * Example:
   *  https://adirmeier.de/index.php -> /var/www/html/index.php
   * @param string $sLink
   * @return string|bool Path or false if invalid
   */
  public function convertLinkToPath($sLink)
  {
    $oParsed = parse_url($sLink);
    if(isset($oParsed['path']))
    {
      $sPath = $oParsed['path'];;
      if(CcStringUtil::startsWith($sPath, "/git/repositories"))
      {
        $sPath = substr($sPath, strlen("/git/repositories"));
      }
      $sPath = $this->getRootPath()."/".$sPath;
      $sPath = CcStringUtil::cleanPath($sPath);
      if($this->isPathValid($sPath))
      {
        return $sPath;
      }
      else
      {
        return false;
      }
    }
    return false;
  }
  
  public function convertPathToLink($sPath)
  {
    $oParsed = parse_url($sPath);
    if(isset($oParsed['path']))
    {
      $sPath = CcStringUtil::cleanPath($oParsed['path']);
      if(CcStringUtil::startsWith($sPath, $this->getRootPath()))
      {
        $sPath = substr($sPath, strlen($this->getRootPath()));
      }
      if(CcStringUtil::startsWith($sPath, "/repositories"))
      {
        $sPath = substr($sPath, strlen("/repositories"));
      }
      $sPath = CcStringUtil::removeAllLeading($sPath, "/");
      return $this->getRootLink()."/".$sPath;
    }
    return false;
  }
}

class CUserAuth implements IGitServerAuth
{
  private function checkUser()
  {
    $bSuccess = false;
    if(isset($_SERVER['PHP_AUTH_USER']) &&
        isset($_SERVER['PHP_AUTH_PW']) &&
        $_SERVER['PHP_AUTH_USER'] == "TestUser" &&
        $_SERVER['PHP_AUTH_PW'] == "TestPW")
    {
      $sUsername = $_SERVER['PHP_AUTH_USER'];
      $sPassword = hash('sha512', $_SERVER['PHP_AUTH_PW']);
      foreach($this->aUserList as $oUser)
      {
        if ($oUser->login($sUsername, $sPassword))
        {
          $this->m_oCurrentUser = $oUser;
          $bSuccess = true;
          break;
        }
      }
    }
    return $bSuccess;
  }
  
  public function authAdmin()
  {
    return $this->checkUser();  
  }
  
  public function authGet()
  {
    return $this->checkUser();
  }
  
  public function authDav()
  {
    return $this->checkUser();
  }
}

$oGitServer = new CcGitServer();
$oGitServer->setLinkConverter(new CustomLinkConverter());
$oGitServer->exec();
