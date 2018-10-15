<?php

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