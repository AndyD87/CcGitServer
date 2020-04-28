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

require_once 'CcGitServer.php';
require_once 'CcStringUtil.php';
require_once 'CcProcess.php';

/**
 * Object to handle informations returned by executing git commands.
 */
class CcGitApp extends CcProcess
{
  /**
   * Create a git executable and define it's target git directory
   * @param string $sBaseDir: String to directory to execute git in.
   */
  public function __construct($sBaseDir)
  {
    parent::__construct("git");
    $this->setWorkingDir($sBaseDir);
  }
  
  /**
   * @brief Add a file to current branch
   * @param string $sFileToAdd: Relative path to File to add. 
   * @return number ExitCode of git
   */
  public function add($sFileToAdd)
  {
    return $this->run("add \"$sFileToAdd\"");
  }
  
  /**
   * @brief Commit to branch and set username and mail for commit message.
   * @param string $sMessage: Message wich is describing the changes. 
   * @param string $sUser:    Name of User for commit message
   * @param string $sMail:    Mail address of User for questions
   * @return number ExitCode of git
   */
  public function commit($sMessage, $sUser="coolcow.de", $sMail="server@coolcow.de")
  {
    $this->run("config user.name ".escapeshellarg($sUser));
    $this->run("config user.email ".escapeshellarg($sMail));
    return $this->run("commit -am ".escapeshellarg($sMessage));
  }
  
  /**
   * @brief Push branch to a remote repository
   * 
   * @param string $sTarget:        Url or path to push branch to.
   * @param string $sTargetBranch:  Local and remote branch to commit.
   * @param string $sUsername:      Optional Username if required
   * @param string $sPassword:      Optional Password if required
   * @return true if succeeded
   */
  public function push($sTarget, $sTargetBranch, $sUsername = null, $sPassword = null)
  {
    $bSuccess = true;
    $sNewTarget = $sTarget;
    if($sUsername != null)
    {
      $sUserCombo = rawurlencode($sUsername);
      if($sPassword != null && strlen($sPassword) > 0)
      {
        $sUserCombo .= ":".rawurlencode($sPassword);
      }
      if(startsWith($sTarget, "http://"))
      {
        $sNewTarget = substr($sTarget, strlen("http://"));
        $sNewTarget = "http://".$sUserCombo."@".$sNewTarget;
      }
      else if(startsWith($sTarget, "https://"))
      {
        $sNewTarget = substr($sTarget, strlen("https://"));
        $sNewTarget = "https://".$sUserCombo."@".$sNewTarget;
      }
      else if(startsWith($sTarget, "git://"))
      {
        $sNewTarget = substr($sTarget, strlen("git://"));
        $sNewTarget = "git://".$sUserCombo."@".$sNewTarget;
      }
      else
      {
        $bSuccess = false;
      }
    }
    if($bSuccess)
    {
      if($sNewTarget) $sNewTarget = CcStringUtil::addQuotes($sNewTarget);
      if($sTargetBranch) $sTargetBranch = CcStringUtil::addQuotes($sTargetBranch);
      $sCommand = "push $sNewTarget $sTargetBranch";
      $bSuccess = $this->run($sCommand);
    }
    return $bSuccess;
  }
  
  /**
   * @brief Pull branch from a remote repository
   *
   * @param string $sTarget:        Url or path to pull from.
   * @param string $sTargetBranch:  Local and remote branch to commit. (+refs/heads/*:refs/heads/*)
   * @param string $sUsername:      Optional Username if required
   * @param string $sPassword:      Optional Password if required
   * @return true if succeeded
   */
  public function pull($sTarget, $sTargetBranch, $sUsername = null, $sPassword = null)
  {
    $bSuccess = true;
    $sNewTarget = $sTarget;
    if($sUsername != null)
    {
      $sUserCombo = rawurlencode($sUsername);
      if($sPassword != null && strlen($sPassword) > 0)
      {
        $sUserCombo .= ":".rawurlencode($sPassword);
      }
      if(startsWith($sTarget, "http://"))
      {
        $sNewTarget = substr($sTarget, strlen("http://"));
        $sNewTarget = "http://".$sUserCombo."@".$sNewTarget;
      }
      else if(startsWith($sTarget, "https://"))
      {
        $sNewTarget = substr($sTarget, strlen("https://"));
        $sNewTarget = "https://".$sUserCombo."@".$sNewTarget;
      }
      else if(startsWith($sTarget, "git://"))
      {
        $sNewTarget = substr($sTarget, strlen("git://"));
        $sNewTarget = "git://".$sUserCombo."@".$sNewTarget;
      }
      else
      {
        $bSuccess = false;
      }
    }
    if($bSuccess)
    {
      if($sNewTarget) $sNewTarget = CcStringUtil::addQuotes($sNewTarget);
      if($sTargetBranch) $sTargetBranch = CcStringUtil::addQuotes($sTargetBranch);
      $sCommand = "fetch $sNewTarget $sTargetBranch";
      $bSuccess = $this->run($sCommand);
    }
    return $bSuccess;
  }
  /**
   * @brief Create a bare project on current location
   *        It will also create a Readme.md within Repository.
   * @return true if succeede.
   */
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
              if($oTmpRepo->push("origin", "master"))
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
  
  /**
   * @brief Init a bare repositoy by cloning from remote URL.
   * @param string $sUrl: URL to clone from
   * @return true if succeeded
   */
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
      while($this->isRunning());
      $this->sResult = $this->readAll();
      $iReturn = $this->close();
    }
    return $iReturn==0;
  }
}
