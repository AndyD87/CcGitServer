<?php
/**
 * @copyright  Andreas Dirmeier (C) 2018
 *
 * This file is part of CcGitApp.
 *
 * CcGitApp is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * CcGitApp is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with CcGitApp.  If not, see <http://www.gnu.org/licenses/>.
 **/
/**
 * @file      CcGitApp.php
 * @author    Andreas Dirmeier
 * @par       Language: PHP
 *
 * Description for class CcGitApp
 */

namespace NGitServer;
use NWebpage\CcStringUtil;

require_once 'CcGitServer.php';
require_once 'CcStringUtil.php';

require_once 'CcProcess.php';

/**
 * Object to handle informations returned by executing git commands.
 */
class CcGitApp extends CcProcess
{
  /** @var string $sResult */
  private $sResult = "";
  
  /**
   * Create a git executable and define it's target git directory
   * @param string $sBaseDir: String to directory to execute git in.
   */
  public function __construct($sBaseDir)
  {
    parent::__construct("git");
    $this->setWorkingDir($sBaseDir);
  }
  
  public function add($sFileToAdd)
  {
    return $this->run("add \"$sFileToAdd\"");
  }
  
  public function commit($sMessage, $sUser="coolcow.de", $sMail="server@coolcow.de")
  {
    $this->run("config user.name ".escapeshellarg($sUser));
    $this->run("config user.email ".escapeshellarg($sMail));
    return $this->run("commit -am ".escapeshellarg($sMessage));
  }
  
  public function push($sLocalBranch, $sTarget, $sTargetBranch)
  {
    if($sLocalBranch) $sLocalBranch = CcStringUtil::addQuotes($sLocalBranch);
    if($sTarget) $sTarget = CcStringUtil::addQuotes($sTarget);
    if($sTargetBranch) $sTargetBranch = CcStringUtil::addQuotes($sTargetBranch);
    return $this->run("push $sLocalBranch $sTarget $sTargetBranch");
  }
  
  public function createBare()
  {
    $bSuccess = false;
    if($this->run("init --bare"))
    {
      if($this->run("clone ./ tmp"))
      {
        $oResult = file_put_contents($this->getWorkingDir()."/tmp/README.md", "Init git");
        if($oResult === false)
        {
          CcGitServer::writeDebugLog("failed to write tmp/README.md");
        }
        else 
        {
          $oTmpRepo = new CcGitApp($this->getWorkingDir()."/tmp");
          if($oTmpRepo->add("README.md"))
          {
            if($oTmpRepo->commit("Initial commit"))
            {
              if($oTmpRepo->push("", "origin", "master"))
              {
                CcFilesystemUtil::RemoveDir($this->getWorkingDir()."/tmp", true);
                $bSuccess = true;
              }
              else
              {
                CcGitServer::writeDebugLog("Failed to push");
              }
            }
            else
            {
              CcGitServer::writeDebugLog("Failed to commit");
            }
          }
          else
          {
            CcGitServer::writeDebugLog("Failed to add");
          }
        }
      }
      else
      {
        CcGitServer::writeDebugLog("Failed to clone");
      }
    }
    else
    {
      CcGitServer::writeDebugLog("Failed to init");
    }
    return $bSuccess;
  }
  
  public function cloneMirror($sUrl)
  {
    $bSuccess = false;
    if($this->run("clone --mirror ".escapeshellarg($sUrl)." ./"))
    {
      $bSuccess = true;
    }
    return $bSuccess;
  }
  
  /**
   * Get Last commits
   * @todo remove formats and parse it to arrays
   * @param int $iNumber: Limit number of commits or 0 for all.
   * @return string
   */
  public function getLog($iNumber = 0)
  {
    $sCommandLine = "log";
    if($iNumber)
    {
      $sCommandLine .= " -n $iNumber";
    }
    $this->run($sCommandLine);
    $aLines = CcStringUtil::stripLines($this->sResult);
    $sLog = "";
    foreach($aLines as $sLine)
    {
      $sLog .= $sLine."<br />";
    }
    return $sLog;
  } 
  
  /**
   * Getn number of commits
   * @return integer
   */
  public function getLogCount()
  {
    $sCommandLine = "log --pretty='%h'";
    $this->run($sCommandLine);
    $aLines = CcStringUtil::stripLines($this->sResult);
    $iNumber = count($aLines);
    if($iNumber > 0 && $aLines[$iNumber-1]=="") $iNumber--;
    return $iNumber;
  }
  
  /**
   * Get Last commits
   * @todo remove formats and parse it to arrays
   * @return string
   */
  public function getBranchList()
  {
    $sCommandLine = "branch";
    $this->run($sCommandLine);
    $aLines = CcStringUtil::stripLines($this->sResult);
    $sLog = "";
    $bStrip = false;
    if(CcStringUtil::startsWith($this->sResult, "  ") ||
        CcStringUtil::startsWith($this->sResult, "* "))
    {
      $bStrip = true;
    }
    foreach($aLines as $sLine)
    {
      if($bStrip) $sLine = substr($sLine, 2);
      if($sLine != "") $sLog .= $sLine."<br />";
    }
    if(CcStringUtil::endsWith($sLog, "<br />")) $sLog = substr($sLog, 0, strlen($sLog)-6);
    return $sLog;
  }
  
  /**
   * Check if current repository is a valid bare repository
   * @return bool true if it is.
   */
  public function isBare()
  { 
    $bRet = false;
    $this->run("rev-parse --is-bare-repository");
    if(strtolower( trim($this->sResult) == "true"))
    {
      $bRet = true;
    }
    return $bRet;
  }
  
  /**
   * Internal run git command and get it's result stored
   * @param string $sArguments: Command line arguments for git
   * @return int Exit code
   */
  private function run($sArguments)
  {
    $iReturn = -1;
    if($this->start($sArguments))
    {
      $this->sResult = $this->readAll();
      $iReturn =$this->close();
    }
    return $iReturn==0;
  }
}
