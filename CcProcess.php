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
 * @file      CcProcess.php
 * @author    Andreas Dirmeier
 * @par       Language: PHP
 *
 * Description for class CcProcess
 */
namespace NGitServer;

require_once 'CcStringUtil.php';
require_once 'CcFilesystemUtil.php';

/**
 * Execute an local application.
 * This class can read/write to pipes and setup working direcotry.
 */
class CcProcess
{
  /**
   * Path to executable
   * @var string $sExecutable
   */
  private $sExecutable;
  
  /**
   * Process handle, wich get set on exec
   * @var resource $pProcess
   */
  private $pProcess = null;
  
  /**
   * Pipe descriptor list for reading and writing support
   * @var string[][] $aDescriptors
   */
  private $aDescriptors;
  
  /**
   * Generated pipes from exec to read/write from/to.
   * @var resource[] $aPipes
   */
  private $aPipes;
  
  /**
   * Working directory to exectute in. If empty, current dir will be used
   * @var string $sWorkingDir
   */
  private $sWorkingDir;
  
  /**
   * Create a process object with target executable.
   * @param string $sExecutable
   */
  public function __construct($sExecutable)
  {
    $this->aDescriptors = array(
        0 => array("pipe", "r"),  // stdin
        1 => array("pipe", "w"),  // stdout
        2 => array("pipe", "w")   // stderr
    );
    $this->sExecutable = $sExecutable;
  }
  
  /**
   * close all handles if required
   */
  public function __destruct()
  {
    if($this->pProcess != null)
    {
      $this->close();
    }
  }
  
  /**
   * Execute current setup
   */
  public function exec()
  {
    $sCurrentDir = getcwd();
    $bAllOk = function_exists("proc_open");
    if($this->sWorkingDir)
    {
      $bAllOk = chdir($this->sWorkingDir);
    }
    if($bAllOk)
    {
      $this->pProcess = proc_open($this->sExecutable,
          $this->aDescriptors,
          $this->aPipes);
    }
    if($this->sWorkingDir)
    {
      $bAllOk = chdir($sCurrentDir);
    }
  }
  
  /**
   * close process if not already done
   */
  public function close()
  {
    if($this->pProcess != null)
    {
      pclose($this->pProcess);
      $this->pProcess = null;
    }
  }
  
  /**
   * Close the stdin pipe of executable
   */
  public function closeWrite()
  {
    if($this->pProcess != null)
    {
      fclose($this->aPipes[0]);
    }
  }
  
  /**
   * Working directory to exectute in. 
   * If empty, current dir will be used.
   * @param string $sWorkingDir: New working directory
   */
  public function setWorkingDir($sWorkingDir)
  {
    $this->sWorkingDir = $sWorkingDir;
  }
  
  /**
   * Read form executable stdout pipe
   * @param number $iMaxLength: Maximum length of string to read
   * @return string|false Read string or false on error
   */
  public function read($iMaxLength = 10240)
  {
    return fread($this->aPipes[1], $iMaxLength);
  }
  
  /**
   * Read all data from stdout pipe
   * @return string|false Read string or false on error
   */
  public function readAll()
  {
    $sOutput = "";
    do
    {
      $sRead = $this->read(10240);
      if($sRead)
      {
        $sOutput .= $sRead;
      }
    } while($sRead && strlen($sRead) == 10240);
    return $sOutput;
  }
  
  /**
   * 
   * @param number $iMaxLength: Maximum length of line to read
   * @return string
   */
  public function readLine($iMaxLength = 10240)
  {
    return fgets($this->aPipes[1], $iMaxLength);
  }
  
  /**
   * Write data to stdin of executable
   * @param string $sData: Data to write to process
   * @return number Number of bytes written
   */
  public function write($sData)
  {
    $iWritten = fwrite($this->aPipes[0], $sData);
    return $iWritten;
  }
  
}