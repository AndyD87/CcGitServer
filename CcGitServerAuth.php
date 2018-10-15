<?php
/**
 * @copyright  Andreas Dirmeier (C) 2018
 *
 * This file is part of CcGitServerAuth.
 *
 * CcGitServerAuth is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * CcGitServerAuth is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with CcGitServerAuth.  If not, see <http://www.gnu.org/licenses/>.
 **/
/**
 * @file      CcGitServerAuth.php
 * @author    Andreas Dirmeier
 * @par       Language: PHP
 *
 * Description for class CcGitServerAuth
 */
require_once "IGitServerAuth.php";

/**
 * @brief Default User data storage for CcGitServer
 */
class CcGitServerAuthUser
{
  /**
   * true if this user is administrator
   * @var bool $bIsAdmin
   */
  public $bIsAdmin;
  /**
   * Name of this user
   * @var string $sUsername
   */
  private $sUsername;
  /**
   * sha256 hash value of a password for this user.
   * @var string $sPassword
   */
  private $sPassword;
  
  /**
   * Setup all variables on creation time
   * @param string $sUsername: Name of new user
   * @param string $sPassword: Password of new user wich must be already hashed with sha256
   * @param bool $bIsAdmin: set true to creat new admin user
   */
  public function __construct($sUsername, $sPassword, $bIsAdmin)
  {
    $this->sUsername = $sUsername;
    $this->sPassword = $sPassword;
    $this->bIsAdmin = $bIsAdmin;
  }
  
  /**
   * Try to login.
   * Check if parameters are matching with stored
   * @param string $sUsername: Name to compare with this user
   * @param string $sPassword: Password to verify user, wich must be already hashed with sha256
   * @return boolean true if login was succeded
   */
  public function login($sUsername, $sPassword)
  {
    $bRet = false;
    if($sUsername == $this->sUsername &&
        $sPassword == $this->sPassword)
    {
      $bRet = true;
    }
    return $bRet;
  }
}

/**
 * @brief Default Authentication manager for CcGitServer
 */
class CcGitServerAuth implements IGitServerAuth 
{
  /**
   * Current user. It will be set by setupUser().
   * If no valid user was found this value will be set null
   * @var CcGitServerAuthUser $m_oCurrentUser
   */
  private $m_oCurrentUser = null;
  
  /**
   * List of Users
   * @var CcGitServerAuthUser[] $m_aUserList
   */
  private $m_aUserList = null;
  
  /**
   * Default user setup for demonstration purpose
   */
  public function setupDefault()
  {
    $this->m_aUserList[] = new CcGitServerAuthUser
      ("admin", hash('sha256', "admin"), true);
    $this->m_aUserList[] = new CcGitServerAuthUser
      ("user", hash('sha256', "user"), false);
  }
  
  /**
   * {@inheritDoc}
   * @see IGitServerAuth::authAdmin()
   */
  public function authAdmin()
  {
    $bSuccess = false;
    if($this->setupUser())
    {
      if($this->m_oCurrentUser->bIsAdmin)
      {
        $bSuccess = true;
      }
      else
      {
        $this->sendAccessDenied();
      }
    }
    else
    {
      $this->sendAuthRequired();
    }
    return $bSuccess;
  }
  
  /**
   * {@inheritDoc}
   * @see IGitServerAuth::authGet()
   */
  public function authGet()
  {
    // Default setup does not require auth for get
    $bSuccess = true;
    return $bSuccess;
  }
  
  /**
   * {@inheritDoc}
   * @see IGitServerAuth::authDav()
   */
  public function authDav()
  {
    $bSuccess = false;
    if($this->setupUser())
    {
      $bSuccess = true;
    }
    else
    {
      $this->sendAuthRequired();
    }
    return $bSuccess;
  }
  
  private function setupUser()
  {
    $bSuccess = false;
    if(isset($_SERVER['PHP_AUTH_USER']) &&
       isset($_SERVER['PHP_AUTH_PW']))
    {
      $sUsername = $_SERVER['PHP_AUTH_USER'];
      $sPassword = hash('sha256', $_SERVER['PHP_AUTH_PW']);
      foreach($this->m_aUserList as $oUser)
      {
        if ($oUser->login($sUsername, $sPassword))
        {
          $this->m_oCurrentUser = $oUser;
          $bSuccess = true;
          break;
        }
      }
    }
    return $bSuccess;
  }
  
  private function sendAuthRequired()
  {
    header('WWW-Authenticate: Basic realm="CcGitServer"');
    header('HTTP/1.1 401 Authorization Required');
  }
  
  private function sendAccessDenied()
  {
    header('HTTP/1.1 403 Access Denied');
  }
}
