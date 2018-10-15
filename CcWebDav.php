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
 * @file      CcWebDav.php
 * @author    Andreas Dirmeier
 * @par       Language: PHP
 *
 * Description for class CcWebDav
 */
require_once "CcStringUtil.php";
require_once "CcXmlParser.php";
require_once "CcXmlObject.php";
require_once "CcWebDavMultistatus.php";
require_once "CcWebDavResponse.php";
require_once "CcWebDavLockResponse.php";
require_once 'CcLinkConverter.php';

const ErrorUnknown = 1;
const ErrorInvalidMethod = 2;
const ErrorParsingInput = 3;
const ErrorMethodNotMatchingInputData = 4;
const ErrorUnknownInputData = 5;

const PropAll = "D:allprop";
const PropSupportedLock = "D:supportedlock";
const PropRessourceType = "lp1:resourcetype";
const PropCreationDate = "lp1:creationdate";
const PropLastModified = "lp1:getlastmodified";


/**
 * @class CcWebDav
 * @brief Distribute a simple webdav server.
 *        Main development purpose was to ship git data with php.
 */
class CcWebDav
{
  /**
   * @var ILinkConverter
   */
  private $oLinkConverter = null;
  
  private $sMethod;
  /**  Default set internal error to -1, it will be cleare on constructor */
  private $iError = -1;
  /**  Depth settings for propfineed, predefined as infinity */
  private $iDepth = -1;

  /**
   * @var CcXmlObject
   */
  private $oRequest = null;
  
  private $sInput = "";
  
  /**
   * @var CcXmlObject
   */
  private $oResponse = null;
  
  public function __construct ()
  {
    $this->iError = 0;
  }
  
  /**
   * Overwrite any existing link converter
   * @param ILinkConverter $oLinkConverter
   */
  public function setLinkConverter($oLinkConverter)
  {
    $this->oLinkConverter = $oLinkConverter;
  }
  
  /**
   * Get Currently stored link converter or create a default.
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
  
  public function setDepth ($iDepth)
  {
    $this->iDepth = $iDepth;
  }

  public function setMethod ($sMethod)
  {
    switch ($sMethod)
    {
      case "PROPFIND":
      case "MKCOL":
      case "LOCK":
      case "UNLOCK":
      case "PUT":
      case "MOVE":
      case "OPTIONS":
        CcGitServer::writeDebugLog("method: $sMethod");
        $this->sMethod = $sMethod;
        break;
      default:
        CcGitServer::writeDebugLog("[ERROR]unknown method: $sMethod");
        $this->iError = 1;
    }
    return $this->iError == 0;
  }
  
  public function setError($iError, $sAddtionalMessage = "")
  {
    CcGitServer::writeDebugLog("Error received: " . $iError);
    if($sAddtionalMessage != "")
      CcGitServer::writeDebugLog("      Messasge: " . $sAddtionalMessage);
    $this->iError = $iError;
  }
  
  public function hasError()
  {
    return $this->iError != 0;
  }
  
  public function isAllOk()
  {
    return !$this->hasError();
  }

  public function exec ($sInputData = "")
  {
    $this->sInput = $sInputData;
    if ($this->iError == 0)
    {
      CcGitServer::writeDebugLog("WebDav Execution");
      CcGitServer::writeDebugLog("Method:  " . $this->sMethod);
      CcGitServer::writeDebugLog("BaseDir: " . $this->getLinkConverter()->getCurrentPath());
      CcGitServer::writeDebugLog("BaseUrl: " . $this->getLinkConverter()->getCurrentLink());
      CcGitServer::writeDebugLog("Depth:   " . $this->iDepth);
      CcGitServer::writeDebugLog("InputData:");
      CcGitServer::writeDebugLog($sInputData);
      CcGitServer::writeDebugLog("");
      $this->execMethod();
      
      if($this->oResponse)
      {
        CcGitServer::writeDebugLog("");
        CcGitServer::writeDebugLog("Repsonse:");
        echo $this->oResponse->getXml();
        CcGitServer::writeDebugLog($this->oResponse->getXml(true));
      }        
    }
    return $this->iError == 0;
  }
  
  private function execMethod()
  {
    switch($this->sMethod)
    {
      case "OPTIONS":
        header('Allows: OPTIONS GET HEAD PROPFIND MKCOL LOCK UNLOCK PUT MOVE');
        break;
      case "PROPFIND":
        $this->execPropfind();
        break;
      case "MKCOL":
        $this->execMkcol();
        break;
      case "LOCK":
        $this->execLock();
        break;
      case "UNLOCK":
        $this->execUnlock();
        break;
      case "PUT":
        $this->execPut();
        break;
      case "MOVE":
        $this->execMove();
        break;
      default:
        header("HTTP/1.1 406 Not Acceptable");
        $this->setError(ErrorInvalidMethod, $this->sMethod);
    }
  }
  
  private function execMove()
  {
    if(isset($_SERVER["HTTP_DESTINATION"]))
    {
      $sTargetPath = $this->getLinkConverter()->convertLinkToPath($_SERVER["HTTP_DESTINATION"]);
      if($sTargetPath)
      {
        if( is_file($this->getLinkConverter()->getCurrentPath()) &&
            (!is_file($sTargetPath) || unlink($sTargetPath)) &&
              (is_dir(dirname($this->getLinkConverter()->getCurrentPath())) ||
              mkdir(dirname($this->getLinkConverter()->getCurrentPath()))))
        {
          if(rename($this->getLinkConverter()->getCurrentPath(),$sTargetPath))
          {
            header("HTTP/1.1 200 Ok");
          }
          else 
          {
            CcGitServer::writeDebugLog("rename failed");
            CcGitServer::writeDebugLog("Move from: ".$this->getLinkConverter()->getCurrentPath());
            CcGitServer::writeDebugLog("       to: ".$sTargetPath);
            header("HTTP/1.1 406 Not Acceptable");
          }
        }
        else
        {
          CcGitServer::writeDebugLog("files not found or already existing");
          header("HTTP/1.1 406 Not Acceptable");
        }
      }
      else
      {
        CcGitServer::writeDebugLog("destination not found");
        header("HTTP/1.1 406 Not Acceptable");
      }
    }
    else 
    {
      CcGitServer::writeDebugLog("destination not found");
      header("HTTP/1.1 406 Not Acceptable");
    }
  }
  
  private function execPut()
  {
    if(is_file($this->getLinkConverter()->getCurrentPath()) &&
      unlink($this->getLinkConverter()->getCurrentPath()) == false)
    {
      CcGitServer::writeDebugLog("[ERROR] is_file");
      header("HTTP/1.1 406 Not Acceptable");
    }
    else if(is_dir(dirname($this->getLinkConverter()->getCurrentPath())) ||
            mkdir(dirname($this->getLinkConverter()->getCurrentPath())))
    {
      $fp = fopen($this->getLinkConverter()->getCurrentPath(), "w");
      if($fp)
      {
        if(fwrite($fp, $this->sInput) !== false)
        {
          fclose($fp);
          header("HTTP/1.1 201 Created");
        }
        else
        {
          CcGitServer::writeDebugLog("[ERROR] fwrite");
          header("HTTP/1.1 406 Not Acceptable");
          fclose($fp);
          unlink($this->getLinkConverter()->getCurrentPath());
        }
      }
      else
      {
        CcGitServer::writeDebugLog("[ERROR] fopen ". $this->getLinkConverter()->getCurrentPath());
        header("HTTP/1.1 406 Not Acceptable");
      }
    }
  }
  
  private function execLock()
  {
    $oParser = new CcXmlParser();
    $this->oRequest = $oParser->parse($this->sInput);
    if($this->oRequest->getTag() == "D:lockinfo")
    {
      if(is_file($this->getLinkConverter()->getCurrentPath().".lock") &&
         !unlink($this->getLinkConverter()->getCurrentPath().".lock"))// @todo remove this line
      {
        header("HTTP/1.1 406 Not Acceptable");
      }
      else
      {
        $sGuid = uniqid();
        $fp = fopen($this->getLinkConverter()->getCurrentPath().".lock", "w");
        if($fp)
        {
          fwrite($fp, $sGuid);
          if(!fclose($fp))
          {
            CcGitServer::writeDebugLog("[ERROR] Closing lock file");
          }
        }
        else 
        {
          CcGitServer::writeDebugLog("[ERROR] Creating lock file");
        }
        if(is_file($this->getLinkConverter()->getCurrentPath().".lock"))
        {
          header("HTTP/1.1 200 Ok");
          $this->oResponse = new CcWebDavLockResponse();
          $this->oResponse->setUuid("opaquelocktoken:9cdd9e0a-ade0-485e-a38f-a70be8fa8ded");
        }
        else 
        {
          header("HTTP/1.1 403 Forbidden");
        }
      }
    }
    else
    {
      header("HTTP/1.1 403 Forbidden");
    }
  }
  
  private function execUnlock()
  {
    $bOk = true;
    if(is_file($this->getLinkConverter()->getCurrentPath().".lock"))
    {
      if(unlink($this->getLinkConverter()->getCurrentPath().".lock"))
      {
        header("HTTP/1.1 200 Ok");
      }
      else
      {
        $bOk = false;
        header("HTTP/1.1 403 Forbidden");
      }
    }
    else
    {
      header("HTTP/1.1 200 Ok");
    }
    if($bOk)
    {
      $this->oResponse = new CcWebDavLockResponse();
    }
  }
  
  private function execMkcol()
  {
    if(is_dir($this->getLinkConverter()->getCurrentPath()) ||
        mkdir($this->getLinkConverter()->getCurrentPath()))
    {
      header("HTTP/1.1 201 Created");
      CcGitServer::writeDebugLog("Directory created");
    }
    else
    {
      header("HTTP/1.1 406 Not Acceptable");
      CcGitServer::writeDebugLog("Failed to create directory: ". $this->getLinkConverter()->getCurrentPath());
    }
  }
  
  private function execPropfind()
  {
    $oParser = new CcXmlParser();
    $this->oRequest = $oParser->parse($this->sInput);
    $this->oResponse = new CcWebDavMultistatus();
    if($this->oRequest->getTag() == "D:propfind")
    {
      header("HTTP/1.1 207 Multi-Status");
      $oPathNode = $this->oRequest->getNode("D:prop");
      $bAllProp = false; // Default set all prop
      $aProperties = array();
      if($oPathNode == null)
      {
        $oPathNode = $this->oRequest->getNode(PropAll);
        if($oPathNode == null)
        {
          $this->setError(ErrorUnknownInputData, "No given properties");
        }
        else
        {
          $aProperties[] = PropAll;
          $bAllProp = true;
        }
      }
      // Receive all required props
      if($this->isAllOk() && $bAllProp == false)
      {
        foreach($oPathNode->getNodes() as $oNode)
        {
          $aProperties[] = $oNode->getTag();          
        }
      }
      // Receive all required props
      if($this->isAllOk())
      {
        $this->execPropfindSearch("", $aProperties, $this->iDepth);
      }
      
    }
    else
    {
      $this->setError(ErrorMethodNotMatchingInputData);
    }
  }
  
  private function execPropfindSearch($sCurrentUrl, $aProperties, $iDepth)
  {
    $sFilePath = $this->getLinkConverter()->getCurrentPath().$sCurrentUrl;
    $oResponse = new CcWebDavResponse();
    $oResponse->setLink($this->getLinkConverter()->getCurrentLink().$sCurrentUrl);
    $this->oResponse->addResponse($oResponse);
    // Search locking
    
    if(array_search(PropSupportedLock, $aProperties) !== false || 
       array_search(PropAll, $aProperties) !== false)
    {
      $oResponse->addLockSupportedExclusiveWrite();
      $oResponse->addLockSupportedSharedWrite();
    }
    
    if(array_search(PropRessourceType, $aProperties) !== false ||
        array_search(PropAll, $aProperties) !== false)
    {
      if(is_dir($sFilePath))
      {
        $oResponse->addCollectionProp();
      }
      else if(is_file($sFilePath))
      {
        $oResponse->addFileProp();
      }
    }
    
    if(array_search(PropLastModified, $aProperties) !== false ||
        array_search(PropAll, $aProperties) !== false)
    {
      if(is_dir($sFilePath) ||
         is_file($sFilePath))
      {
        $oFileModifiedTime = filemtime($sFilePath);
        $oResponse->setLastModified(date("Y-m-dTH:i:sZ",$oFileModifiedTime));
      }
    }
    
    if(array_search(PropCreationDate, $aProperties) !== false ||
        array_search(PropAll, $aProperties) !== false)
    {
      if(is_dir($sFilePath) ||
          is_file($sFilePath))
      {
        $oFileCreatedTime = filectime($sFilePath);
        $oResponse->setCreated(date("Y-m-dTH:i:sZ",$oFileCreatedTime));
      }
    }
    
    if($iDepth != 0 && 
       is_dir($sFilePath))
    {
      $iDepth = ($iDepth > 0) ? $iDepth-1 : $iDepth;
      if ($handle = opendir($sFilePath)) 
      {
        while (false !== ($entry = readdir($handle))) 
        {
          if( $entry != "" && 
              $entry != "." &&
              $entry != "..")
          {
            $this->execPropfindSearch($sCurrentUrl."/".$entry, $aProperties, $iDepth);
          }
        }
      }
    }
  }
}

