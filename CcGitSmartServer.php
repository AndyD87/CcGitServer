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
 * @file      CcGitSmartServer.php
 * @author    Andreas Dirmeier
 * @par       Language: PHP
 *
 * Description for class CcGitSmartServer
 */
require_once 'CcStringUtil.php';
require_once 'CcFilesystemUtil.php';
require_once 'CcHttp.php';
require_once 'CcGitServer.php';
require_once 'CcProcess.php';

class CcGitSmartServer
{
  private $oLinkConverter = null;
  
  /**
   * Set an external LinkConverter to support various directory structures.
   * @param ILinkConverter $oLinkConverter
   */
  public function setLinkConverter($oLinkConverter)
  {
    $this->oLinkConverter = $oLinkConverter;
  }
  
  /**
   * Get currently set LinkConverter.
   * It will be used to get external set LinkConverter or to create
   * the default LinkConverter if nothing was set.
   * @return ILinkConverter
   */
  public function getLinkConverter()
  {
    if($this->oLinkConverter == null)
    {
      $this->oLinkConverter = new CcLinkConverter();
      $this->oLinkConverter->setupDefault();
    }
    return $this->oLinkConverter;
  }
  
  public function exec()
  {
    
    if(isset($_GET['service']))
    {
      CcGitServer::writeDebugLog("CcGitSmartService");
      switch($_GET['service'])
      {
        case "git-upload-pack":
          $this->execUploadPack();
          break;
        default:
          CcGitServer::writeDebugLog($_GET['service']);
          CcHttp::errorNotFound();
      }
    }
    else
    {
      CcHttp::errorNotFound();
    }
  }
  
  public function execUploadPack()
  {
    CcHttp::setContentType("application/x-git-upload-pack-advertisement");
    CcHttp::writeHeader("Expires: Fri, 01 Jan 1980 00:00:00 GMT");
    CcHttp::writeHeader("Pragma: no-cache");
    CcHttp::writeHeader("Cache-Control: no-cache, max-age=0, must-revalidate");
    
    $oProc = new CcProcess("git-upload-pack \"".$this->getRepositoryPath()."\"");
    $oProc->exec();
    CcGitServer::writeDebugLog($oProc->readAll());
    
    CcGitServer::writeDebugLog("");
    
    $stdout = shell_exec("git-upload-pack \"".$this->getRepositoryPath()."\"");
    $sOutput = "001e# service=git-upload-pack\n";
    $sOutput .= $stdout;
    CcGitServer::writeDebugLog("git-upload-pack \"".$this->getRepositoryPath()."\"");
    CcGitServer::writeDebugLog($sOutput);
    echo $sOutput;
  }
  
  /**
   * Get data from input stream
   * @param bool $bChunked: if true a maximum of 100k will be read at once
   * @return string
   */
  private function getInputData()
  {
    return file_get_contents('php://input');
  }
  
  public function getRepositoryPath($sPath="")
  {
    $sReturn = "";
    $sRegEx = "/(\/.*\.git)(?=\/|$)/";
    $oMatches = array();
    if($sPath == "")
    {
      $sPath = $this->getLinkConverter()->getCurrentPath();
    }
    if(preg_match($sRegEx, $sPath, $oMatches))
    {
      $sReturn = $oMatches[1];
    }
    return $sReturn;
  }
  
}