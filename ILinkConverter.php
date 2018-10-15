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
 * @file      ILinkConverter.php
 * @author    Andreas Dirmeier
 * @par       Language: PHP
 *
 * Description for class ILinkConverter
 */

/**
 * Interface to an object to manage paths and links in CcGitServer
 * @author Andreas Dirmeier
 */
interface ILinkConverter
{
  /**
   * Check if at least one path is set with false.
   * If paths are not valid, a security breach can occur.
   * @return boolean true if all paths ok, false if at least one path fails
   */
  public function isValid();
  
  /**
   * Get web root path like https://adirmeier.de/
   * @return string|bool Link or false if invalid 
   */
  public function getRootLink();
  
  /**
   * Get get server root path like /var/www/html
   * @return string|bool Path or false if invalid
   */
  public function getRootPath();
  
  /**
   * Get get current executed web link like https://adirmeier.de/subdir/index.php
   * @return string|bool Link or false if invalid
   */
  public function getCurrentLink();
  
  /**
   * Get get current executed server path like /var/www/html/subdir/index.php
   * @return string|bool Path or false if invalid
   */
  public function getCurrentPath();
  
  /**
   * This method returns the difference between RootPath and CurrentPath
   */
  public function getRelativePath();
  
  /**
   * This method will generate a path to server stored file from http link
   * Example:
   *  https://adirmeier.de/index.php -> /var/www/html/index.php 
   * @param string $sLink
   * @return string|bool Path or false if invalid
   */
  public function convertLinkToPath($sLink);
  
  /**
   * This method will generate a http link to file from stored server like
   * Example:
   *  /var/www/html/index.php -> https://adirmeier.de/index.php
   * @param string $sPath
   * @return string|bool Path or false if invalid
   */
  public function convertPathToLink($sPath);
  
  /**
   * Check if $sPath is a valid path and does not link to files wich are denied
   * 
   * For Eample with regex:
   * 
   *      ^\/var\/www\/html\/[.*\/.*]*.*\.git\/[.*\/.*]*
   * 
   *  This validates that path is in root dir and at least one *.git project is given
   * @param string $sPath
   * @return bool true if $sPath is ok, otherwise false;
   */
  public function isPathValid($sPath);
  
}
