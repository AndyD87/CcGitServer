# PHP based git server

Target of this git server for HTTP is to keep configuration and requirements as simple as possible.

Some setups for git to publish repositories over HTTP requires a seperate webDAV module for *push* support.  
For example, a git repository with apache requires to setup *DAV* in it's http.conf, wich makes the repositories not very portable.

CcGitServer implements a very simplified DAV Server to communicate with git.

Nevertheless some requirements are necessary:
 - Webserver with rewrite support
   - Apache2: a2enmod rewrite
 - php >= 5.0
 - php-xml
 - git with git-http-backend on server
 
## Setup

This project is designe to run in *root* or in *root/git* directory of an webserver. The support
for other directories is a target but not yet done.

### Example for apache on Ubuntu:

Install apache and php with modules

    sudo apt-get install apache2 php php-xml
    sudo a2enmod redirect
    sudo service apache2 restart

If *.htaccess* is not already enabled

    sudo echo "<Directory /var/www/html>" | sudo tee -a /etc/apache2/sites-available/000-default.conf
    sudo echo "    AllowOverride All" | sudo tee -a /etc/apache2/sites-available/000-default.conf
    sudo echo "</Directory>" | sudo tee -a /etc/apache2/sites-available/000-default.conf

Setup git server 

    cd /var/www/html
    sudo git clone https://github.com/AndyD87/CcGitServer.git git

First git repository

    cd /var/www/html/git
    sudo php git.php create ExampleProject
    sudo chown -R www-data.www-data *

First clone from repository

    cd ~
    git clone http://localhost/git/Example.git

## User control

It can be seen in CcGitServer::checkAuth.

Overload or edit CcGitServer::checkAuth to setup your own user control.

## Next Targets

This system is very rudimental, and mainly designed to run on internal networks.

- Security features
    It is dangerous to run it on internet,
- Avoid dependency from php-xml
- Portable user control instance to load on init
- Centralize configuration for supporting different directories
    ( currently all projects should be in this direcotry or in any subdirectory)

## Contact

If you need help, you can find me on [GitHub](https://github.com/AndyD87) or on my [Website](https://adirmeier.de).

## License

Author CcGitServer: [Andreas Dirmeier](http://adirmeier.de)  
CcGitServer is licensed under LGPL v3. Look at COPYING and COPYING.LESSER for further information.
