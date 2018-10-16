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
 * @file      CcGitServer.php
 * @author    Andreas Dirmeier
 * @par       Language: PHP
 *
 * Description for class CcGitServer
 */
require_once "CcStringUtil.php";
require_once "CcFilesystemUtil.php";
require_once "CcWebDav.php";
require_once "CcLinkConverter.php";
require_once "CcGitServerAuth.php";

class CcGitServer
{
  /**
   * Enable/disable debug mode for generating debug output to logfile
   * 
   * true for debug output
   * @var bool $bDebug
   */
  private static $bDebug = false;
  
  /**
   * Will be set from constructor and describes if current environment is a webserver.
   * Indicator for this value is $_SERVER['REQUEST_METHOD']
   * 
   * true if we are called from webserver.
   * @var bool $bIsWeb
   */
  private static $bIsWeb = false;
  
  /**
   * Link converter to match http link with local filesystem
   * @var ILinkConverter $oLinkConverter
   */
  private $oLinkConverter = null;
  /**
   * Authentication manager to check user rights for get, dav and admin
   * @var IGitServerAuth $oAuth
   */
  private $oAuth = null;
  
  /**
   * Current running method like GET and PUT
   * @var string $sMethod
   */
  private $sMethod ="";
  
  /**
   * Create a CcGitServerObject
   * It will search for web environment by checking $_SERVER['REQUEST_METHOD']
   * wich is required for any request. 
   */
  public function __construct()
  {
    if( is_array($_SERVER) &&
        isset($_SERVER['REQUEST_METHOD']))
    {
      CcGitServer::$bIsWeb = true;
    }
  }
  
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
  
  /**
   * Set external user interface implementation to support an external user interface
   * @param IGitServerAuth $oAuth
   */
  public function setAuth($oAuth)
  {
    $this->oAuth = $oAuth;
  }
  
  /**
   * Get an external set user interface implemenation wich was previously set by @ref setAuth
   * or create a default one if nothing was set before
   * @return IGitServerAuth
   */
  public function getAuth()
  {
    if($this->oAuth == null)
    {
      $this->oAuth = new CcGitServerAuth();
      $this->oAuth->setupDefault();
    }
    return $this->oAuth;
  }
  
  /**
   * Same check as in @ref isValidRepositoryPath, but it checks if
   * repository is existing on filesystem too.
   * 
   * @param bool $bStrict: if true Path must be a repository not any subdir 
   * @return bool
   */
  public function isRepository($bStrict = false)
  {
    $bRet = false;
    if($this->isValidRepositoryPath($bStrict))
    {
      $oMatches = array();
      $sRegEx = "/(\/.*\.git)(?=\/|$)/";
      if(preg_match($sRegEx, $this->getLinkConverter()->getCurrentPath(), $oMatches))
      {
        $bRet = is_dir($oMatches[1]);
      }
    }
    return $bRet;
  }
  
  /**
   * Check if path is valid repository path.
   *
   * If $bStrict is false, every path within directory is valid.
   * For example:
   *   Project.git/HEAD
   *   Project.git/refs/info
   *
   * If $bStrict is true only repository path is valid
   * For Example:
   *   Project.git
   *   Project.git/
   *
   * @param bool $bStrict: if true Path must be a repository not any subdir
   * @return bool
   */
  public function isValidRepositoryPath($bStrict = false)
  {
    $bRet = false;
    $sPath = $this->getLinkConverter()->getCurrentPath();
    if($sPath)
    {
      $oMatches = array();
      $sRegEx = "/(\/.*\.git)(?=\/|$)/";
      if($bStrict)
      {
        $sRegEx = "/(\/.*\.git)(?=\/|$)$/";
      }
      if(preg_match($sRegEx, $sPath, $oMatches))
      {
        $bRet = true;
      }
    }
    return $bRet;
  }
  
  /**
   * Execute CcGitServer application
   */
  public function exec()
  {
    if(CcGitServer::$bIsWeb &&
        $this->getLinkConverter()->isValid() == false)
    {
      $this->writeDebugLog("Links and Paths are not valid");
      header("HTTP/1.1 406 Not Acceptable");
    }
    else if(CcGitServer::$bIsWeb)
    {
      $this->sMethod = $_SERVER['REQUEST_METHOD'];
      CcGitServer::writeDebugLog($this->sMethod);
      switch ($this->sMethod)
      {
        case "HEAD":
        case "GET":
          if($this->getAuth()->authGet())
          {
            $this->execGet();
          }
          break;
        default:
          // @todo check for repository create with admin privilegues
          if($this->getAuth()->authDav())
          {
            $this->execWebDav();
          }
          break;
      }
    }
    else
    {
      // CLI execution, check arguments
      global $argv, $argc;
      if(is_array($argv))
      {
        if($argc > 2)
        {
          if($argv[1] == "create")
          {
            $this->createRepository($argv[2]);
          }
        }
        else
        {
          echo "ERROR no arguments";
        }
      }
      else
      {
        echo "ERROR not in cli?";
      }
    }
  }
  
  /**
   * Create a repository on current stored path
   */
  public function createCurrentRepository()
  {
    if($this->isPathARepository())
    {
      $this->createRepository($this->getLinkConverter()->getCurrentPath());
    }
  }
  
  /**
   * Create a new repository.
   * Rootpath from LinkConverter will be prepended to sPath.
   * ".git" will be appended to $sPath if not already done.
   * Subdirectories are supporte like "subdir/Project.git"
   * @param string $sPath
   * @return boolean true if creation succeeded
   */
  public function createRepository($sPath)
  {
    $bSuccess = true;
    if($this->getLinkConverter()->getRootPath())
    {
      $sPath = CcStringUtil::cleanPath($sPath);
      if(CcStringUtil::startsWith($sPath, $this->getLinkConverter()->getRootPath()))
      {
        $sPath = substr($sPath, strlen($this->getLinkConverter()->getRootPath()));
      }
      $sPath = CcStringUtil::removeAllEnding($sPath, ".git");
      $sPath = $this->getLinkConverter()->getRootPath()."/".$sPath;
      $sPathGit = $sPath.".git";
      if( is_dir($sPathGit) || is_file($sPathGit) ||
          is_dir($sPath) || is_file($sPath))
      {
        CcGitServer::writeDebugLog("project already exists");
        $bSuccess = false;
      }
      else
      {
        if($bSuccess && mkdir($sPathGit, 0775, true) == false)
        {
          echo "failed to create project directory\n";
          $bSuccess = false;
        }
        
        if($bSuccess && CcGitServer::execGit("init --bare", $sPathGit) == false)
        {
          echo "failed to init\n";
          $bSuccess = false;
        }
        
        if($bSuccess && CcGitServer::execGit("clone \"$sPathGit\"", dirname($sPathGit)) == false)
        {
          echo "failed to clone\n";
          $bSuccess = false;
        }
        
        if($bSuccess)
        {
          $oResult = file_put_contents($sPath."/README.md", "Init git", FILE_APPEND);
          if($oResult === false)
          {
            echo "failed to write ".$sPath."/README.md\n";
            $bSuccess = false;
          }
        }
        
        if($bSuccess)
        {
          if(CcGitServer::execGit("add README.md", $sPath) !== FALSE)
          {
            // No check here, it can work but return value != 0 is possible
          }
        }
        
        if($bSuccess)
        {
          if(CcGitServer::execGit('commit -am "first commit"', $sPath) !== FALSE)
          {
            // No check here, it can work but return value != 0 is possible
          }
        }
        
        if($bSuccess)
        {
          if(CcGitServer::execGit('push origin master', $sPath) !== FALSE)
          {
            // No check here, it can work but return value != 0 is possible
          }
        }
        
        if($bSuccess)
        {
          if(CcFilesystemUtil::RemoveDir($sPath, true))
          {
            echo "Repository '$sPath' successfully created\n";
          }
          else
          {
            echo "Failed to remove '$sPath'\n";
          }
        }
        else
        {
          if(is_dir($sPath))
          {
            CcFilesystemUtil::RemoveDir($sPath, true);
          }
          if(is_dir($sPathGit))
          {
            CcFilesystemUtil::RemoveDir($sPathGit, true);
          }
        }
      }
    }
    else
    {
      $bSuccess = false;
      echo "No root dir found\n";
    }
    return $bSuccess;
  }
  
  private function execGet()
  {
    $sRegEx = "/.*\/(HEAD|".
        "info\/refs|".
        "objects\/(".
        "info\/[^\/]+|".
        "[0-9a-f]{2}\/[0-9a-f]{38}|".
        "pack\/pack-[0-9a-f]{40}\.(pack|idx))".
        "|git-(upload|receive)-pack)/";
    if( CcGitServer::isGitHttpBackendAvailable() == false ||
        0 == preg_match($sRegEx, $this->getLinkConverter()->getCurrentPath()))
    {
      if(is_file($this->getLinkConverter()->getCurrentPath()))
      {
        echo file_get_contents($this->getLinkConverter()->getCurrentPath());
      }
      else
      {
        CcGitServer::writeDebugLog($this->getLinkConverter()->getCurrentPath());
        header('HTTP/1.0 404 Not Found');
      }
    }
    else
    {
      $bWriteContent = true;
      if ($this->sMethod == "HEAD")
      {
        $bWriteContent = false;
      }
      // Prepare Path to project by trim slashes
      $_SERVER["DOCUMENT_ROOT"] = $this->getLinkConverter()->getRootPath();
      $_SERVER["CONTEXT_DOCUMENT_ROOT"] = $this->getLinkConverter()->getRootPath();
      $_SERVER["QUERY_STRING"] = $this->getLinkConverter()->getRelativePath();
      // Copy server data to environment
      if (is_array($_SERVER))
      {
        foreach ($_SERVER as $key => $value)
        {
          CcGitServer::setEnv($key, $value);
        }
      }
      
      // Setup git-http-backend paths
      CcGitServer::setEnv("GIT_PROJECT_ROOT", $this->getLinkConverter()->getRootPath());
      CcGitServer::setEnv("GIT_HTTP_EXPORT_ALL", "");
      CcGitServer::setEnv("PATH_INFO", $this->getLinkConverter()->getRelativePath());
      
      // execute git-core/git-http-backend
      $stdout = "";
      if (is_file("/usr/lib/git-core/git-http-backend"))
      {
        $stdout = shell_exec(
            "cd \"".$this->getLinkConverter()->getRootPath()."\" & /usr/lib/git-core/git-http-backend");
      }
      else
      {
        $stdout = shell_exec("cd \"$this->getLinkConverter()->getRootPath()\" & git http-backend");
      }
      if (is_array($stdout))
      {
        $stdout = implode("\n", $stdout);
      }
      $Offset = 0;
      $lines = CcGitServer::parseHeader($stdout, $Offset);
      if (is_array($lines) && count($lines) > 0)
      {
        $lineCount = count($lines);
        CcGitServer::writeDebugLog("ROOT: " . getenv("GIT_PROJECT_ROOT"));
        CcGitServer::writeDebugLog("PATH: " . getenv("PATH_INFO"));
        CcGitServer::writeDebugLog("Offset: $Offset");
        CcGitServer::writeDebugLog("Length: " . (strlen($stdout) - $Offset));
        CcGitServer::writeDebugLog("Header-Lines: " . $lineCount);
        
        for ($i = 0; $i < $lineCount; $i ++)
        {
          $line = $lines[$i];
          if (CcStringUtil::startsWith($line, "Content-Type: "))
          {
            header($line);
            $line = substr($line, strlen("Content-Type: "));
          }
          else if (CcStringUtil::startsWith($line, "Status: "))
          {
            $line = substr($line, strlen("Status: "));
            header("HTTP/1.0 $line");
          }
          else if ($line == "")
          {
            // Empty Line, end of header, do nothing.
          }
          else
          {
            header($line);
          }
          CcGitServer::writeDebugLog($line);
        }
        if ($bWriteContent && $Offset < strlen($stdout))
        {
          echo substr($stdout, $Offset);
        }
      }
      else
      {
        header('HTTP/1.0 404 Not Found');
      }
    }
  }
  
  private function execWebDav()
  {
    $oWebDav = new CcWebDav();
    $oWebDav->setLinkConverter($this->oLinkConverter);
    $oWebDav->setMethod($this->sMethod);
    
    if (function_exists('getallheaders'))
    {
      $oHeaderInfo = getallheaders();
      if (isset($oHeaderInfo["Depth"]))
      {
        $oWebDav->setDepth($oHeaderInfo["Depth"]);
      }
    }
    
    $oWebDav->exec();
  }
  
  /**
   * Execute local git in a specified Working directory
   * @param string $sParam: Parameters as string
   * @param string $sWorkingDir: Working dir where git gets executed in
   * @return boolean true if git returned with 0
   */
  public static function execGit($sParam, $sWorkingDir)
  {
    $sCurrentDir = getcwd();
    $aOutput = array();
    $iReturn = -1;
    if(!function_exists("exec"))
    {
      echo "exec disabled\n";
    }
    else if(chdir($sWorkingDir))
    {
      exec("git ". $sParam, $aOutput, $iReturn);
    }
    else
    {
      echo "Direcotry not existing\n";
    }
    
    chdir($sCurrentDir);
    return $iReturn == 0;
  }
  
  /**
   * Set a new or overwrite a System variable.
   * This will be valid for this instance only.
   * @param string $Key: Name of variable
   * @param string $Value: Value to set
   */
  public static function setEnv ($Key, $Value)
  {
    putenv($Key . '=' . $Value . '');
  }
  
  /**
   * Write a message to debug log.
   * This will only be done if CcGitServer::$bDebug is true
   * @param string $Message
   */
  public static function writeDebugLog ($Message)
  {
    if (CcGitServer::$bDebug)
    {
      file_put_contents("CcGitServerLog.txt", $Message . "\n", FILE_APPEND);
    }
    if(CcGitServer::$bIsWeb == false)
    {
      echo $Message . "\n";
    }
  }
  
  private static function isGitHttpBackendAvailable()
  {
    $bRet = false;
    
    if(!function_exists("shell_exec"))
    {
      $bRet = false;
    }
    else if (is_file("/usr/lib/git-core/git-http-backend") ||
        is_file("git-core/git-http-backend") ||
        is_file("git-http-backend"))
    {
      $bRet = true;
    }
    return $bRet;
  }
  
  private static function parseHeader ($Input, &$Offset)
  {
    $bOffsetFound = false;
    $uiCurrentOffest = 0;
    $Offset = 0;
    $sLine = "";
    $aLines = array();
    $uiLength = strlen($Input);
    while ($bOffsetFound == false && $uiCurrentOffest < $uiLength)
    {
      if ($Input[$uiCurrentOffest] == "\n")
      {
        $aLines[] = $sLine;
        $sLine = "";
        if ($uiCurrentOffest + 1 < $uiLength && $Input[$uiCurrentOffest + 1] ==
            "\n")
        {
          $bOffsetFound = true;
          $uiCurrentOffest ++;
        }
        else if ($uiCurrentOffest + 2 < $uiLength &&
            $Input[$uiCurrentOffest + 1] == "\r" &&
            $Input[$uiCurrentOffest + 2] == "\n")
        {
          $bOffsetFound = true;
          $uiCurrentOffest ++;
          $uiCurrentOffest ++;
        }
      }
      else
      {
        $sLine .= $Input[$uiCurrentOffest];
      }
      $uiCurrentOffest ++;
    }
    $Offset = $uiCurrentOffest;
    return $aLines;
  }
}
