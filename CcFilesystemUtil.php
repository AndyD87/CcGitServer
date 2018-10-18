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
}