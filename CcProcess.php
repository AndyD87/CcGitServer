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
require_once 'CcStringUtil.php';
require_once 'CcFilesystemUtil.php';

class CcProcess
{
  private $sExecutable;
  private $sOutput;
  private $sOutputError;
  private $pProcess = null;
  private $aDescriptors;
  private $aPipes;
  
  public function __construct($sExecutable)
  {
    $this->aDescriptors = array(
        0 => array("pipe", "r"),  // stdin
        1 => array("pipe", "w"),  // stdout
        2 => array("pipe", "w")   // stderr
    );
    $this->sExecutable = $sExecutable;
  }
  
  public function __destruct()
  {
    if($this->pProcess != null)
    {
      $this->close();
    }
  }
  
  public function exec()
  {   
    $this->pProcess = proc_open($this->sExecutable,
        $this->aDescriptors, 
        $this->aPipes);
  }
  
  public function close()
  {
    if($this->pProcess != null)
    {
      pclose($this->pProcess);
      $this->pProcess = null;
    }
  }
  
  public function read($iMaxLength = 10240)
  {
    return fread($this->aPipes[1], $iMaxLength);
  }
  
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
}