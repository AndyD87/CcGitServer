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
 * @file      CcLinkConverter.php
 * @author    Andreas Dirmeier
 * @par       Language: PHP
 *
 * Description for class CcLinkConverter
 */
require_once 'ILinkConverter.php';
require_once 'CcStringUtil.php';

/**
 * Default link converter implementation.
 * This implementation will setup directies and links to work
 * direct match url path and local path without redirection.
 */
class CcLinkConverter implements ILinkConverter
{
  private $sRootLink = false;
  private $sRootPath = false;
  private $sCurrentPath = false;
  private $sCurrentLink = false;
  
  /**
   * Overwrite or set current stored root link
   * @param string $sLink
   */
  function setRootLink($sLink)
  {
    $this->sRootLink = $sLink;
  }
  
  /**
   * Overwrite or set current stored root path
   * @param string $sPath
   */
  function setRootPath($sPath)
  {
    $this->sRootPath = CcStringUtil::cleanPath($sPath);
  }
  
  /**
   * Overwrite or set current stored active path
   * @param string $sPath
   */
  function setCurrentPath($sPath)
  {
    $this->sCurrentPath = CcStringUtil::cleanPath($sPath);
  }
  
  /**
   * Overwrite or set current stored active link
   * @param string $sLink
   */
  function setCurrentLink($sLink)
  {
    $this->sCurrentLink = $sLink;
  }
  
  /**
   * {@inheritDoc}
   * @see ILinkConverter::getRootLink()
   */
  function getRootLink()
  {
    return $this->sRootLink;
  }
  
  /**
   * {@inheritDoc}
   * @see ILinkConverter::getRootPath()
   */
  function getRootPath()
  {
    return $this->sRootPath;
  }
  
  /**
   * {@inheritDoc}
   * @see ILinkConverter::getCurrentPath()
   */
  function getCurrentPath()
  {
    return $this->sCurrentPath;
  }
  
  /**
   * {@inheritDoc}
   * @see ILinkConverter::getCurrentLink()
   */
  function getCurrentLink()
  {
    return $this->sCurrentLink;
  }
  
  /**
   * {@inheritDoc}
   * @see ILinkConverter::getRelativePath()
   */
  function getRelativePath()
  {
    $sPath = $this->getCurrentPath();
    if(CcStringUtil::startsWith($sPath, $this->getRootPath()))
    {
      $sPath = substr($sPath, strlen($this->getRootPath()));
    }
    return $sPath;
  }
  
  /**
   * @brief This will setup values wich are currently not set
   */
  function setupDefault()
  {
    if($this->getRootPath() == false)
    {
      if(isset($_SERVER["DOCUMENT_ROOT"])
          && $_SERVER["DOCUMENT_ROOT"] != "")
      {
        $sRootPath = $_SERVER["DOCUMENT_ROOT"];
        $this->setRootPath($sRootPath);
      }
      else
      {
        // fallback
        $this->setRootPath(dirname(__FILE__));
      }
    }
    
    if($this->getRootLink() == false)
    {
      if(isset($_SERVER['HTTP_HOST']))
      {
        $sRootLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
        $sRootLink .= "://".$_SERVER['HTTP_HOST'];
        $this->setRootLink($sRootLink);
      }
      else
      {
        $this->setRootLink("http://localhost");
      }
    }
    
    if($this->getCurrentPath() == false)
    {
      if(isset($_SERVER['REQUEST_URI']))
      {
        $this->setCurrentPath($this->convertLinkToPath($_SERVER["REQUEST_URI"]));
      }
      else
      {
        $this->setCurrentPath($this->sRootPath);
      }
    }
    
    if($this->getCurrentLink() == false)
    {
      if(isset($_SERVER['REQUEST_URI']))
      {
        $this->setCurrentLink($this->convertPathToLink($_SERVER["REQUEST_URI"]));
      }
      else
      {
        $this->setCurrentLink($this->sRootLink);
      }
    }
    
    return $this->isValid();
  }
  
  /**
   * {@inheritDoc}
   * @see ILinkConverter::isValid()
   */
  public function isValid()
  {
    $bRet = false;
    if($this->sCurrentLink &&
        $this->sCurrentPath &&
        $this->sRootLink &&
        $this->sRootPath)
    {
      $bRet = true;
    }
    return $bRet;
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
      $sPath = $this->getRootPath()."/".$oParsed['path'];
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
  
  /**
   * This method will generate a http link to file from stored server like
   * Example:
   *  /var/www/html/index.php -> https://adirmeier.de/index.php
   * @param string $sPath
   * @return string|bool Path or false if invalid
   */
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
      $sPath = CcStringUtil::removeAllLeading($sPath, "/");
      return $this->getRootLink()."/".$sPath;
    }
    return false;
  }
  
  /**
   * Check if a path is valid and do not point to an not allowed
   * path
   * @param string $sPath: string to check
   */
  public function isPathValid($sPath)
  {
    $bRet = false;
    $sPath = CcStringUtil::cleanPath($sPath);
    $sValidRegEx = "/^".preg_quote($this->getRootPath(),"/")."[\/.*]*.*\.git[\/.*]*/";
    if(preg_match($sValidRegEx, $sPath))
    {
      $bRet = true;
    }
    else
    {
      CcGitServer::writeDebugLog("invalid path: ". $sPath);
      CcGitServer::writeDebugLog($sValidRegEx);
    }
    return $bRet;
  }
}
