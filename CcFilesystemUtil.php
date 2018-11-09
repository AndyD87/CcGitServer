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
 * @file      CcFilesystemUtil.php
 * @author    Andreas Dirmeier
 * @par       Language: PHP
 *
 * Description for class CcFilesystemUtil
 */
namespace NGitServer;

require_once 'CcStringUtil.php';

/**
 * @brief Varios static methods to manipulate the local filesystem
 */
class CcFilesystemUtil
{
  /**
   * Remove a directory from local filesystem.
   * @param string $sDirPath: Path to directory to delete
   * @param bool   $bRecurse: Remove any parts inside directory too
   * @return boolean true if delete succeeded
   */
  public static function RemoveDir($sDirPath, $bRecurse)
  {
    $bSuccess = true;
    if (is_dir($sDirPath))
    {
      if($bRecurse)
      {
        // Recursive remove all items in directory
        $oItems = scandir($sDirPath);
        if($oItems !== false)
        {
          foreach ($oItems as $oItem)
          {
            if ($oItem != "." && $oItem != "..")
            {
              if (filetype($sDirPath."/".$oItem) == "dir")
              {
                $bSuccess &= self::RemoveDir($sDirPath."/".$oItem, $bRecurse);
              }
              else
              {
                $bSuccess &= unlink($sDirPath."/".$oItem);
              }
            }
          }
        }
      }
      // Finaly remove dir itself
      $bSuccess &= rmdir($sDirPath);
    }
    else
    {
      $bSuccess = false;
    }
    return $bSuccess;
  }
  
  /**
   * Get mimetype of a file.
   * @param string $sFilePath
   * @return string
   */
  public static function getMimeType($sFilePath)
  {
    if(CcStringUtil::endsWith($sFilePath, ".css"))
    {
      return "text/css";
    }
    else if(function_exists('mime_content_type')) 
    {
      return mime_content_type($sFilePath);
    }
    else
    {
      //@todo
      return "";
    }   
  }
  
  /**
   * Print a file to stdout in 10k chunks.
   * @param string $sPath: path to file
   * @return boolean true if printing was done successfully
   */
  public static function printFile($sPath)
  {
    $bRet = false;
    $oFile = fopen($sPath, "r");
    if($oFile)
    {
      while($oData = fread($oFile, 10240))
      {
        echo $oData;
      }
      fclose($oFile);
      $bRet = true;
    }
    return $bRet;
  }
  
  /**
   * Get lastmodified date of a file in date() format.
   * @param string $sPath: Path to file
   * @param string $sFormat: Format for date as defined in @ref date
   * @return string Formated output string or "" if any error occured
   */
  public static function getLastModifiedString($sPath, $sFormat)
  {
    $sReturn = "";
    if(is_dir($sPath) ||
        is_file($sPath))
    {
      $oFileModifiedTime = filemtime($sPath);
      $sReturn = date($sFormat,$oFileModifiedTime);
    }
    return $sReturn;
  }
  
  /**
   * Get Size of file.
   * On 64bit Systems, nothing special to do, return is same as @ref filesize.
   * On 32bit systems, file larger than 4GB are not supported, so we have to query OS for correct value.
   * @param string $sPath: Path to file
   * @return number
   */
  public static function getFileSize($sPath)
  {
    if(PHP_INT_SIZE == 4)
    {
      if(substr(PHP_OS, 0, 3) == "WIN")
      {
        exec('for %I in ("'.$file.'") do @echo %~zI', $output);
        $return = $output[0];
      }
      else
      {
        $return = trim(`stat -c%s $file`);
      }
    }
    else 
    {
      $return = filesize($sPath);
    }
    return $return;
  }
}