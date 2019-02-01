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
   * Last state received from proc_get_status
   * @var string[] $aStatus
   */
  private $aStatus;
  
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
    $this->aPipes = array(
        0 => null,  // stdin
        1 => null,  // stdout
        2 => null   // stderr
    );
    $this->sExecutable = $sExecutable;
  }
  
  /**
   * close all handles if required
   */
  public function __destruct()
  {
    $this->close();
  }
  
  /**
   * Execute current setup
   */
  public function start($sArgumentLine = null)
  {
    $sCurrentDir = getcwd();
    $sRun = $this->sExecutable;
    if($sArgumentLine)
    {
      $sRun .= " ".$sArgumentLine;
    }
    $bAllOk = function_exists("proc_open");
    if( $this->getWorkingDir() &&
        is_dir($this->getWorkingDir()))
    {
      $bAllOk = chdir($this->getWorkingDir());
    }
    else
    {
      $bAllOk = false;
    }
    
    if($bAllOk)
    {
      $this->pProcess = proc_open($sRun,
          $this->aDescriptors,
          $this->aPipes);
    }
    if($this->getWorkingDir())
    {
      chdir($sCurrentDir);
    }
    return $bAllOk;
  }
  
  /**
   * close process if not already done
   */
  public function close()
  {
    $iResult = -1;
    for($i=0; $i < count($this->aPipes); $i++)
    {
      if($this->aPipes[$i] != null) fclose($this->aPipes[$i]);
      $this->aPipes[$i] = null;
    }
    if($this->pProcess != null)
    {
      $iResult = $this->getExitCode();
      $i = proc_close($this->pProcess);
      $this->pProcess = null;
    }
    return $iResult;
  }
  
  /**
   * Close the stdin pipe of executable
   */
  public function closeWrite()
  {
    if($this->pProcess != null)
    {
      if($this->aPipes[0])
      {
        fclose($this->aPipes[0]);
        $this->aPipes[0] = null;
      }
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
   * Read form executable stderror pipe
   * @param number $iMaxLength: Maximum length of string to read
   * @return string|false Read string or false on error
   */
  public function readError($iMaxLength = 10240)
  {
    return fread($this->aPipes[2], $iMaxLength);
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
    } while($sRead);
    return $sOutput;
  }
  
  /**
   * Read all data from stderror pipe
   * @return string|false Read string or false on error
   */
  public function readAllError()
  {
    $sOutput = "";
    do
    {
      $sRead = $this->readError(10240);
      if($sRead)
      {
        $sOutput .= $sRead;
      }
    } while($sRead);
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
  
  /**
   * @brief Check if current process is in a running state.
   * @return bool true if running
   */
  public function isRunning()
  {
    if($this->pProcess != null)
    {
      $this->aStatus = proc_get_status($this->pProcess);
    }
    return $this->aStatus["running"];
  }
  
  /**
   * @brief Wait for process until isRunning is false
   */
  public function waitFinished()
  {
    while($this->isRunning());
  }
  
  /**
   * @brief Get Exitcode of closed process.
   * @return number ExitCode as number
   */
  public function getExitCode()
  {
    if($this->pProcess != null)
    {
      $this->aStatus = proc_get_status($this->pProcess);
    }
    $iResult = $this->aStatus["exitcode"];
    return $iResult;
  }
  
  /**
   * @brief Get setted working dir of this prosses.
   * @return string Current Working dir or empty if not yet set.
   */
  public function getWorkingDir()
  {
    return $this->sWorkingDir;
  }
  
  /**
   * Directly exeute a program if possible
   * @param string $sProgram: Name of Program to execute
   * @param string $sWorkingDir: Working directory if required
   * @param string &$sData: Output data if required
   */
  public static function exec($sProgram, $sWorkingDir=null, &$sData=null)
  {
    $oProc = new CcProcess($sProgram);
    if($sWorkingDir) $oProc->setWorkingDir($sWorkingDir);
    $oProc->run();
    if($sData != null)
    {
      $sData = "";
      while($oProc->isRunning()) $sData .= $oProc->readAll();
    }
    else
    {
      $oProc->waitFinished();
    }
    return $oProc->getExitCode();
  }
}
