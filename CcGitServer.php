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
 * @page      CcGitServer
 * @author    Andreas Dirmeier
 * @par       Language: PHP
 * 
 * Description for class CcGitServer
 */
require_once "CcStringUtil.php";
require_once "CcWebDav.php";

function __CcGitServer_error_handler($errno, $errstr, $errfile, $errline, $errcontext)
{
  return CcGitServer::error_handler($errno, $errstr, $errfile, $errline, $errcontext);
}

/**
 * @brief recursive remove directory
 */
function rrmdir($dir)
{
  $bSuccess = true;
  if (is_dir($dir)) 
  {
    $oItems = scandir($dir);
    if($oItems !== false)
    {
      foreach ($oItems as $oItem) 
      {
        if ($oItem != "." && $oItem != "..") 
        {
          if (filetype($dir."/".$oItem) == "dir")
          {
            $bSuccess &= rrmdir($dir."/".$oItem);
          }
          else 
          {
            $bSuccess &= unlink($dir."/".$oItem);
          }
        }
      }
    }
    $bSuccess &= rmdir($dir);
  }
  return $bSuccess;
}

class CcGitServer
{
  private static $bDebug = false;
  private static $bIsWeb = false;
  
  /**
   * @var string is set by @ref setGitRootDir
   */
  private $sGitRootDir = "";
  /**
   * @var string is set by @ref setWebRootDir 
   */
  private $sWebRootDir = "";
  /**
   * @var string is set by @ref setRelativeDir
   */
  private $sRelativeDir = "";
  
  private $sMethod ="";
  
  public function __construct()
  {
    if( is_array($_SERVER) &&
        isset($_SERVER['REQUEST_METHOD']))
    {
      CcGitServer::$bIsWeb = true;
    }
    if($this->sGitRootDir == "")
    {
      if($this->sWebRootDir == "")
      {
        // Setup default root directory in same directory as this script
        $this->sGitRootDir = dirname(__FILE__);
        // Setup default web root directory in one directory up of this script
        $this->sWebRootDir = dirname(dirname(__FILE__));
      }
      else
      {
        $this->sGitRootDir = $this->sWebRootDir;
      }
    }
    else 
    {
      // Setup default web root directory in one directory up of this script
      if($this->sWebRootDir == "")
      {
        $this->sWebRootDir = $this->sGitRootDir;
      }
    }
    if( $this->sRelativeDir == "" &&
        CcStringUtil::startsWith($this->sGitRootDir, $this->sWebRootDir))
    {
      $this->sRelativeDir = substr($this->sGitRootDir, strlen($this->sWebRootDir));
    }
    set_error_handler ( "__CcGitServer_error_handler", E_ALL);
    
    restore_error_handler();
  }
  
  /**
   * Set server root directory of git content like /var/www/html
   * @param string $sWebRootDir: Directory containing webcontent.
   */
  public function setWebRootDir($sWebRootDir)
  {
    $this->sWebRootDir = $sWebRootDir;
  }
  
  /**
   * Set git root directory of git content like /var/www/html/git
   * If git root dir is same as server root dir, setWebRootDir is enough.
   * @param string $sGitRootDir: Directory containing git projects.
   */
  public function setGitRootDir($sGitRootDir)
  {
    $this->sGitRootDir = $sGitRootDir;
  }
  
  /**
   * @brief This String describs the relative path to git root dir within webserver
   *        For example:
   *          Webserver at: /var/www/html
   *          Gitserver at: /var/www/html/git
   *          This variable: /git
   * @param string $sRelativeDir: Directory containing git projects.
   */
  public function setRelativeDir($sRelativeDir)
  {
    $this->sRelativeDir = $sRelativeDir;
  }
  
  public function exec()
  {
    if(CcGitServer::$bIsWeb)
    {
      if(isset($_SERVER["REQUEST_URI"]))
      {
        $_SERVER["REQUEST_URI"] = CcGitServer::cleanProjectPath($_SERVER["REQUEST_URI"]);
      }
      if(isset($_SERVER["REDIRECT_URL"]))
      {
        $_SERVER["REDIRECT_URL"] = CcGitServer::cleanProjectPath($_SERVER["REDIRECT_URL"]);
      }
      $this->sMethod = $_SERVER['REQUEST_METHOD'];
      CcGitServer::writeDebugLog($this->sMethod);
      switch ($this->sMethod)
      {
        case "HEAD":
        case "GET":
          if($this->checkAuth(true))
          {
            $this->execGet();
          }
          break;
        default:
          if($this->checkAuth(false))
          {
            $this->execWebDav();
            CcGitServer::writeDebugLog("Auth done\n");
          }
          break;
      }
    }
    else
    {
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
  
  public function checkAuth($bIsGet = false)
  {
    $bAuthSuccess = false;
    if($bIsGet)
    {
      // Default allow get request
      $bAuthSuccess = true;
    }
    else 
    {
      if(!isset($_SERVER['PHP_AUTH_USER']) ||
          !isset($_SERVER['PHP_AUTH_PW']))
      {
        header('WWW-Authenticate: Basic realm="Git"');
        header('HTTP/1.1 401 Unauthorized');
        CcGitServer::writeDebugLog("Auth required\n");
        //CcGitServer::writeDebugLog(CcStringUtil::getVarDump($_SERVER));
      }
      else
      {
        //! @todo Replace checkAuth with your own by overloading or
        //!       Change user values here
        $sUsername = "User";
        $sPassword = "user";
        if($_SERVER['PHP_AUTH_USER'] == $sUsername ||
            $_SERVER['PHP_AUTH_PW'] == $sPassword)
        {
          $bAuthSuccess = true;
        }
      }
    }
    return $bAuthSuccess;
  }
  
  public static function execGit($sParam, $sWorkingDir)
  {
    $sCurrentDir = getcwd();
    $aOutput = array();
    $iReturn = -1;
    if( chdir($sWorkingDir))
    {
      exec("git ". $sParam, $aOutput, $iReturn);
    }
    else
    {
      CcGitServer::writeDebugLog("Direcotry not existing");
    }
      
    chdir($sCurrentDir);
    return $iReturn == 0;
  }
  
  
  public function createRepository($sPath)
  {
    $bSuccess = true;
    $sPath = CcStringUtil::removeAllEnding($sPath, ".git");
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
        CcGitServer::writeDebugLog("failed to create project directory");
        $bSuccess = false;
      }
      
      if($bSuccess && CcGitServer::execGit("init --bare", $sPathGit) == false)
      {
        CcGitServer::writeDebugLog("failed to init");
        $bSuccess = false;
      }
      
      if($bSuccess && CcGitServer::execGit("clone \"$sPathGit\"", dirname($sPathGit)) == false)
      {
        CcGitServer::writeDebugLog("failed to clone");
        $bSuccess = false;  
      }
      
      if($bSuccess)
      {
        $oResult = file_put_contents($sPath."/README.md", "Init git", FILE_APPEND);
        if($oResult === false)
        {
          CcGitServer::writeDebugLog("failed to write ".$sPath."/README.md");
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
        if(rrmdir($sPath))
        {
          CcGitServer::writeDebugLog("Repository '$sPath' successfully created");
        }
        else
        {
          CcGitServer::writeDebugLog("Failed to remove '$sPath'");
        }
      }
      else
      {
        if(is_dir($sPath))
        {
          rrmdir($sPath);
        }
        if(is_dir($sPathGit))
        {
          rrmdir($sPathGit);
        }
      }
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
    if(0 == preg_match($sRegEx, $_SERVER["REQUEST_URI"]))
    {
      if(is_file($this->sGitRootDir.$_SERVER["REQUEST_URI"]))
      {
        echo file_get_contents($this->sGitRootDir.$_SERVER["REQUEST_URI"]);
        CcGitServer::writeDebugLog("RegEx not matching: ".$_SERVER["REQUEST_URI"]);
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
      $_SERVER["DOCUMENT_ROOT"] = $this->sGitRootDir;
      $_SERVER["CONTEXT_DOCUMENT_ROOT"] = $this->sGitRootDir;
      // Copy server data to environment
      if (is_array($_SERVER))
      {
        foreach ($_SERVER as $key => $value)
        {
          if ($key == "QUERY_STRING")
          {
            CcGitServer::setEnv("QUERY_STRING", $_SERVER["REQUEST_URI"]);
          }
          else
          {
            CcGitServer::setEnv($key, $value);
          }
        }
      }
      
      // Setup git-http-backend paths
      CcGitServer::setEnv("GIT_PROJECT_ROOT", $this->sGitRootDir);
      CcGitServer::setEnv("GIT_HTTP_EXPORT_ALL", "");
      CcGitServer::setEnv("PATH_INFO", $_SERVER["REDIRECT_URL"]);
      
      // execute git-core/git-http-backend
      $stdout = "";
      if (is_file("/usr/lib/git-core/git-http-backend"))
      {
        $stdout = shell_exec(
            "cd \"$this->sGitRootDir\" & /usr/lib/git-core/git-http-backend");
      }
      else
      {
        $stdout = shell_exec("cd \"$this->sGitRootDir\" & git http-backend");
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
          // Save ressources by checking if log is realy required before generating
          if (CcGitServer::$bDebug)
            CcGitServer::writeDebugLog(substr($stdout, $Offset));
        }
        else
        {
          CcGitServer::writeDebugLog($stdout);
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
    CcGitServer::writeDebugLog($this->sGitRootDir." ".$_SERVER['REQUEST_URI']);
    $oWebDav->setBaseDir($this->sGitRootDir.$_SERVER['REQUEST_URI']);
    $oWebDav->setRootDir($this->sWebRootDir);
    $oWebDav->setMethod($this->sMethod);
    
    // Generate base url from incoming string
    $sBaseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
    $sBaseUrl .= "://".$_SERVER['HTTP_HOST'].$this->sRelativeDir.$_SERVER['REQUEST_URI'];
    $oWebDav->setBaseUrl($sBaseUrl);
    
    if (function_exists('getallheaders'))
    {
      $oHeaderInfo = getallheaders();
      if (isset($oHeaderInfo["Depth"]))
      {
        $oWebDav->setDepth($oHeaderInfo["Depth"]);
      }
    }
    
    $sDataRaw = file_get_contents('php://input');
    
    $oWebDav->exec($sDataRaw);
  }
  
  public static function setEnv ($Key, $Value, $Flag = FILE_APPEND)
  {
    putenv($Key . '=' . $Value . '');
  }
  
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
  
  public static function error_handler ($errno, $errstr, $errfile, $errline, $errcontext)
  {
    CcGitServer::writeDebugLog("ErrNr:   $errno");
    CcGitServer::writeDebugLog("ErrStr:  $errstr");
    CcGitServer::writeDebugLog("ErrFile: $errfile");
    CcGitServer::writeDebugLog("ErrLine: $errline");
    CcGitServer::writeDebugLog("ErrCtx:");
    CcGitServer::writeDebugLog(CcStringUtil::getVarDump($errcontext));
    CcGitServer::writeDebugLog("");
    return null;
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
  
  private function cleanProjectPath ($sProject)
  {
    $sProject = str_replace("//", "/", $sProject);
    CcGitServer::writeDebugLog($sProject." ".$this->sRelativeDir);
    if (CcStringUtil::startsWith($sProject, $this->sRelativeDir))
    {
      $sProject = substr($sProject, strlen($this->sRelativeDir));
    }
    $sProject = CcStringUtil::removeAllEnding($sProject, '/');
    $sProject = CcStringUtil::removeAllLeading($sProject, '/');
    $sProject = "/" . $sProject;
    return $sProject;
  }
}
