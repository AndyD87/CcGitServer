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
 * @file
 * @brief This file is the main entry point for CcGitServer
 * 
 * This file can be called from webser with redirect (look at .htaccess file) or
 * from commandline:
 * 
 * Commandline options:
 *  - **create PathToNewProject**
 *    create Project at "PathToNewProject"
 * 
 * Example for creating a directory:
 * 
 *    php git.php create ExampleProject
 *    
 *    The command will generate a project named ExampleProject.git in current directory
 */
require_once 'CcHttp.php';
require_once 'CcGitServer.php';

/**
 * Create common git server
 * @var CcGitServer $oGitServer
 */
$oGitServer = new CcGitServer();
// Check if path is a valid repository
if($oGitServer->isRepository())
{
  // start server
  $oGitServer->exec();
}
else
{
  CcHttp::errorNotFound();
  echo "Repository not found";
}
