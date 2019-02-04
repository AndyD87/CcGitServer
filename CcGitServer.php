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

namespace NGitServer;

require_once "CcStringUtil.php";
require_once "CcFilesystemUtil.php";
require_once "CcWebDav.php";
require_once "CcLinkConverter.php";
require_once "CcGitServerAuth.php";
require_once "CcGitApp.php";

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
   * Path to git-http-backend executable
   * Please use setHttpBackendExecutable to replace it.
   * @var string $sHttpBackendExe
   */
  private $sHttpBackendExe = "git-http-backend";
  
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
    // execute git-core/git-http-backend
    if (is_file("/usr/lib/git-core/git-http-backend"))
    {
      $this->sHttpBackendExe = "/usr/lib/git-core/git-http-backend";
    }
    else if(is_file("/usr/libexec/git-core/git-http-backend"))
    {
      $this->sHttpBackendExe = "/usr/libexec/git-core/git-http-backend";
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
   * Set external user interface implementation to support an external user interface
   * @param IGitServerAuth $oAuth
   */
  public function setAuth($oAuth)
  {
    $this->oAuth = $oAuth;
  }
  
  /**
   * Replace current sotred git-http-backend executable with
   * an other one
   * @param string $sPath: Path to Executable
   */
  public function setHttpBackendExecutable($sPath)
  {
    $this->sHttpBackendExe = $sPath;
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
   * Check if current User is admin
   */
  public function authAdmin()
  {
    $bRet = $this->getAuth()->authAdmin();
    if($bRet)
    {
      $this->setEnvUser();
    }
    return $bRet;
  }
  
  /**
   * Check if current User is allowed to pull
   */
  public function authPull()
  {
    $bRet = $this->getAuth()->authPull();
    if($bRet)
    {
      $this->setEnvUser();
    }
    return $bRet;
  }
  
  /**
   * Check if current User is allowed to modify and create files
   */
  public function authPush()
  {
    $bRet = $this->getAuth()->authPush();
    if($bRet)
    {
      $this->setEnvUser();
    }
    return $bRet;
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
   * Determine if current environment is a web environment
   * @return bool
   */
  public function isWeb()
  {
    return CcGitServer::$bIsWeb;
  }
  
  /**
   * Extract path to Repository from current path
   * @return string Path to repository or "" if not found.
   */
  public function getRepositoryPath()
  {
    $sReturn = "";
    $sRegEx = "/(\/.*\.git)(?=\/|$)/";
    $oMatches = array();
    if(preg_match($sRegEx, $this->getLinkConverter()->getCurrentPath(), $oMatches))
    {
      $sReturn = $oMatches[1];
    }
    return $sReturn;
  }
  
  /**
   * Path to file or directory relative to repository directory
   * @return string Path to repository or "" if not found.
   */
  public function getFilePathInRepository()
  {
    if(CcStringUtil::startsWith($this->getLinkConverter()->getCurrentPath(), $this->getRepositoryPath()))
    {
      return substr($this->getLinkConverter()->getCurrentPath(), strlen($this->getRepositoryPath()));
    }
    echo null;
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
  public function start()
  {
    if(CcGitServer::$bIsWeb &&
        $this->getLinkConverter()->isValid() == false)
    {
      CcHttp::errorNotAcceptable();
    }
    else if(CcGitServer::$bIsWeb)
    {
      $this->sMethod = $_SERVER['REQUEST_METHOD'];
      CcGitServer::writeDebugLog($this->sMethod);
      switch ($this->sMethod)
      {
        case "HEAD":
        case "GET":
          if(isset($_GET["service"]) && $_GET["service"] == "git-upload-pack")
          {
            CcGitServer::writeDebugLog("upload");
            if($this->authPull())
            {
              CcGitServer::writeDebugLog("Do auth");
              $this->execHttp();
            }
          }
          else if(isset($_GET["service"]) && $_GET["service"] == "git-receive-pack")
          {
            CcGitServer::writeDebugLog("receive");
            if($this->authPush())
            {
              $this->execHttp();
            }
          }
          else if($this->authPull())
          {
            CcGitServer::writeDebugLog("http");
            $this->execHttp();
          }
          break;
        case "POST":
          if($this->authPull())
          {
            CcGitServer::writeDebugLog("post");
            $this->execHttp();
          }
          break;
        default:
          // @todo check for repository create with admin privilegues
          if($this->authPush())
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
    $oGitApp = new CcGitApp($sPath);
    return $oGitApp->createBare();
  }
  
  private function execHttp()
  {
    if($this->isGitHttpBackendAvailable() == false)
    {
      if(CcGitServer::isGitPath($this->getLinkConverter()->getCurrentPath()) &&
         is_file($this->getLinkConverter()->getCurrentPath()))
      {
        echo file_get_contents($this->getLinkConverter()->getCurrentPath());
      }
      else
      {
        CcHttp::errorNotFound();
      }
    }
    else
    {
      $this->setupEnv();
      
      // init values
      $sLine = "";
      
      $oProc = new CcProcess($this->sHttpBackendExe);
      $oProc->setWorkingDir($this->getRepositoryPath());
      $oProc->start();
      if($this->sMethod == "POST")
      {
        $oProc->write(CcWebDav::getInputData());
        $oProc->closeWrite();
      }
      
      while($sLine = $oProc->readLine())
      {
        if("" == trim($sLine))
        {
          // Header end
          break;
        }
        else if (CcStringUtil::startsWith($sLine, "Status: "))
        {
          $sLine = substr($sLine, strlen("Status: "));
          header("HTTP/1.1 $sLine");
          CcGitServer::writeDebugLog("error");
        }
        else if ($sLine == "")
        {
          // Empty Line, end of header, do nothing.
        }
        else
        {
          header($sLine);
          CcGitServer::writeDebugLog(trim($sLine));
        }
      }
      
      $bIsRunning = true;
      while($bIsRunning)
      {
        $sData = $oProc->read(4);
        if(!$sData)
        {
          $bIsRunning = false;
        }
        else
        {
          echo $sData.$oProc->readLine();
        }
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
      $oHeaderInfo = array_change_key_case($oHeaderInfo, CASE_LOWER);
      if (isset($oHeaderInfo["depth"]))
      {
        $oWebDav->setDepth($oHeaderInfo["depth"]);
      }
    }
    
    $oWebDav->start();
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
   * Set Git Server in commandline mode to get debug messages
   * @param bool $bOn true enable cli mode, false to disable it.
   */
  public static function setCli($bOn = true)
  {
    CcGitServer::$bIsWeb = !$bOn;
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
  
  /**
   * Check if a path is targeting a valid git file
   * ReEx adepted from https://git-scm.com/docs/git-http-backend
   * @param string $sPath
   * @return boolean true if matching
   */
  public static function isGitPath($sPath)
  {
    $sRegEx = "/.*\/(HEAD|info\/refs|objects\/(info\/[^\/]+|[0-9a-f]{2}\/[0-9a-f]{38}|pack\/pack-[0-9a-f]{40}\.(pack|idx))|git-(upload|receive)-pack)/";
    if(1 == preg_match($sRegEx, $sPath))
    {
      return true;
    }
    else 
    {
      return false;  
    }
  }
  
  private function isGitHttpBackendAvailable()
  {
    $bRet = false;
    
    if(!function_exists("proc_open"))
    {
      $bRet = false;
    }
    else if (is_file($this->sHttpBackendExe))
    {
      $bRet = true;
    }
    return $bRet;
  }
  
  private function setEnvUser()
  {
    $_SERVER["REMOTE_USER"] = $this->getAuth()->getUsername();
  }
  
  private function setupEnv()
  {
    // Turn off output buffering
    ini_set('output_buffering', 'off');
    // Turn off PHP output compression
    ini_set('zlib.output_compression', false);
    // turn off output buffering
    ob_end_clean();
    
    // Implicitly flush the buffer(s)
    ini_set('implicit_flush', true);
    ob_implicit_flush(true);
    
    // Prepare Path to project by trim slashes
    if(isset($_SERVER["REDIRECT_QUERY_STRING"]))
    {
      $_SERVER["QUERY_STRING"] = $_SERVER["REDIRECT_QUERY_STRING"];
    }
    $_SERVER["DOCUMENT_ROOT"] = $this->getLinkConverter()->getRootPath();
    $_SERVER["CONTEXT_DOCUMENT_ROOT"] = $this->getLinkConverter()->getRootPath();
    // Copy server data to environment
    if (is_array($_SERVER))
    {
      foreach ($_SERVER as $key => $value)
      {
        CcGitServer::setEnv($key, $value);
      }
    }
    
    // Setup git-http-backend paths
    CcGitServer::setEnv("GIT_PROJECT_ROOT", $this->getRepositoryPath());
    CcGitServer::setEnv("GIT_HTTP_EXPORT_ALL", "");
    CcGitServer::setEnv("PATH_INFO", $this->getFilePathInRepository());
    
  }
}
