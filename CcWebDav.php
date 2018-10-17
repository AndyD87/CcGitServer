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
require_once 'CcHttp.php';
require_once "CcStringUtil.php";
require_once "CcXmlParser.php";
require_once "CcXmlObject.php";
require_once "CcWebDavMultistatus.php";
require_once "CcWebDavResponse.php";
require_once "CcWebDavLockResponse.php";
require_once 'CcLinkConverter.php';

const ErrorUnknown = 1;       //!< Unknown Error
const ErrorInvalidMethod = 2; //!< Invalid Method
const ErrorParsingInput = 3;  //!< Error on parsing input
const ErrorMethodNotMatchingInputData = 4; //!< Input method not matching with input data
const ErrorUnknownInputData = 5;  //!< Error input data are not correct

const PropAll = "D:allprop";                    //!< xml tag for all properties in webdav
const PropSupportedLock = "D:supportedlock";    //!< xml tag for lock support setting in webdav
const PropRessourceType = "lp1:resourcetype";   //!< xml tag for ressource type in webdav
const PropCreationDate = "lp1:creationdate";    //!< xml tag for creation date in webdav
const PropLastModified = "lp1:getlastmodified"; //!< xml tag for last modified date in webdav


/**
 * @class CcWebDav
 * @brief Distribute a simple webdav server.
 *        Main development purpose was to ship git data with php.
 */
class CcWebDav
{
  /**
   * @var ILinkConverter $oLinkConverter
   */
  private $oLinkConverter = null;
  
  /**
   * Currently running method from http request
   * @var string $sMethod
   */
  private $sMethod;
  
  /**  
   * Default set internal error to -1, it will be cleare on constructor
   * @var int $iError
   */
  private $iError = -1;
  /**  
   * Depth settings for propfineed, predefined as infinity
   * @var int $iDepth
   */
  private $iDepth = -1;

  /**
   * Parsed incoming request data, it will be set to null if parsing failed or
   * no data is given
   * @var CcXmlObject $oRequest
   */
  private $oRequest = null;
  
  /**
   * @var CcXmlObject $oResponse
   */
  private $oResponse = null;
  
  /**
   * Input offset to handle large inputs too
   * @var integer $iInputOffset
   */
  private static $iInputOffset = 0;
  
  /**
   * Nothing to set on construct
   */
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
  
  /**
   * Set depth for all prop
   * @param int $iDepth
   */
  public function setDepth ($iDepth)
  {
    $this->iDepth = $iDepth;
  }

  /**
   * Set next method to execute
   * @param string $sMethod
   * @return boolean false if method not supported
   */
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
        $this->sMethod = $sMethod;
        break;
      default:
        $this->iError = 1;
    }
    return $this->iError == 0;
  }
  
  /**
   * Set CcWebDav in an error state
   * @param int $iError: error number to set
   * @param string $sAddtionalMessage: additional message to output
   */
  public function setError($iError, $sAddtionalMessage = "")
  {
    if($sAddtionalMessage != "")
      CcGitServer::writeDebugLog("      Messasge: " . $sAddtionalMessage);
    $this->iError = $iError;
  }
  
  /**
   * Check if CcWebDav is in an error state
   * @return boolean
   */
  public function hasError()
  {
    return $this->iError != 0;
  }
  
  /**
   * Check if CcWebDav is not in an error state
   * @return boolean
   */
  public function isAllOk()
  {
    return !$this->hasError();
  }

  /**
   * Execute current setup
   * @return boolean true if all succeeded and false if any error occured.
   * 
   */
  public function exec()
  {
    if ($this->iError == 0)
    {
      $this->execMethod();
      
      if($this->oResponse)
      {
        echo $this->oResponse->getXml();
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
        CcHttp::errorNotAcceptable();
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
            CcHttp::ok();
          }
          else 
          {
            CcGitServer::writeDebugLog("rename failed");
            CcGitServer::writeDebugLog("Move from: ".$this->getLinkConverter()->getCurrentPath());
            CcGitServer::writeDebugLog("       to: ".$sTargetPath);
            CcHttp::errorNotAcceptable();
          }
        }
        else
        {
          CcGitServer::writeDebugLog("files not found or already existing");
          CcHttp::errorNotAcceptable();
        }
      }
      else
      {
        CcGitServer::writeDebugLog("destination not found");
        CcHttp::errorNotAcceptable();
      }
    }
    else 
    {
      CcGitServer::writeDebugLog("destination not found");
      CcHttp::errorNotAcceptable();
    }
  }
  
  private function execPut()
  {
    if(is_file($this->getLinkConverter()->getCurrentPath()) &&
      unlink($this->getLinkConverter()->getCurrentPath()) == false)
    {
      CcGitServer::writeDebugLog("[ERROR] is_file");
      CcHttp::errorNotAcceptable();
    }
    else if(is_dir(dirname($this->getLinkConverter()->getCurrentPath())) ||
            mkdir(dirname($this->getLinkConverter()->getCurrentPath())))
    {
      $fp = fopen($this->getLinkConverter()->getCurrentPath(), "w");
      if($fp)
      {
        $bSuccess = true;
        $sData = CcWebDav::getInputData(false);
        if($sData && strlen($sData) > 0)
        {
          if(fwrite($fp, $sData)=== false)
          {
            $bSuccess = false;
          }
        }
        if($bSuccess)
        {
          fclose($fp);
          CcHttp::okCreated();
        }
        else
        {
          CcGitServer::writeDebugLog("[ERROR] fwrite");
          CcHttp::errorNotAcceptable();
          fclose($fp);
          unlink($this->getLinkConverter()->getCurrentPath());
        }
      }
      else
      {
        CcGitServer::writeDebugLog("[ERROR] fopen ". $this->getLinkConverter()->getCurrentPath());
        CcHttp::errorNotAcceptable();
      }
    }
  }
  
  private function execLock()
  {
    $oParser = new CcXmlParser();
    $this->oRequest = $oParser->parse(CcWebDav::getInputData());
    if($this->oRequest->getTag() == "D:lockinfo")
    {
      if(is_file($this->getLinkConverter()->getCurrentPath().".lock") &&
         !unlink($this->getLinkConverter()->getCurrentPath().".lock"))// @todo remove this line
      {
        CcHttp::errorNotAcceptable();
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
          CcHttp::ok();
          $this->oResponse = new CcWebDavLockResponse();
          $this->oResponse->setUuid("opaquelocktoken:9cdd9e0a-ade0-485e-a38f-a70be8fa8ded");
        }
        else 
        {
          CcHttp::errorAccessDenied();
        }
      }
    }
    else
    {
      CcHttp::errorAccessDenied();
    }
  }
  
  private function execUnlock()
  {
    $bOk = true;
    if(is_file($this->getLinkConverter()->getCurrentPath().".lock"))
    {
      if(unlink($this->getLinkConverter()->getCurrentPath().".lock"))
      {
        CcHttp::ok();
      }
      else
      {
        $bOk = false;
        CcHttp::errorAccessDenied();
      }
    }
    else
    {
      CcHttp::ok();
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
      CcHttp::okCreated();
      CcGitServer::writeDebugLog("Directory created");
    }
    else
    {
      CcHttp::errorNotAcceptable();
      CcGitServer::writeDebugLog("Failed to create directory: ". $this->getLinkConverter()->getCurrentPath());
    }
  }
  
  private function execPropfind()
  {
    $oParser = new CcXmlParser();
    $sInputData = CcWebDav::getInputData();
    if($sInputData)
    {
      $this->oRequest = $oParser->parse($sInputData);
      $this->oResponse = new CcWebDavMultistatus();
      if($this->oRequest->getTag() == "D:propfind")
      {
        CcHttp::okMultistatus();
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
  
  /**
   * Get all data from input stream
   * @return string
   */
  public static function getInputData()
  {
    return file_get_contents('php://input');
  }
}

