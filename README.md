# PHP based git server

Target of this git server for HTTP is to keep configuration and requirements as simple as possible.

Some setups for git to publish repositories over HTTP requires a seperate webDAV module for *push* support.  
For example, a git repository with apache requires to setup *DAV* in it's http.conf, wich makes the repositories not very portable.

CcGitServer implements a very simplified DAV Server to communicate with git.

At the moment, there is no ui to browse projects.

## Requirements

Nevertheless some requirements are necessary:
 - Webserver with rewrite support
   - Apache2: a2enmod rewrite
 - php >= 5.0
 - php-xml
 
Recommended but not necessary
 - git with git-http-backend on server
  
## Default Settings

Default users for push:
 - admin:admin
 - user:user
 
## Setup

This project is designed to run without configuration in any directory in an webserver. Just the requirements as definied in [Requirements](.#Requirements)

### Example for apache on Ubuntu:

You can run the following example in one script at **Tools/ExampleUbuntuSetup.sh**.  
Be aware to use this script in a productive system, it's just an example.

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

## Integration

Other than in [Setup](.#Setup), the CcGitServer can be configured to run in more complex
envirionments too.  

Some webpages already have an user control or have seperate data folders, they can integrate CcGitServer by confguring thier own interfaces.

You can see an example in /Tools/ExampleUbuntuIntegration/git.php   
For an working example you can use /Tools/ExampleUbuntuIntegrationSetup.sh  
Do not use it in productive systems, it's just for demonstration purpose.

### Directory Setup

The server can be configured to run with different directories too.

By setting an own implementation of **ICcLinkConverter** to **CcGitServer::setLinkConverter()**, it is possible to work with projects in different locations.  
**CcLinkConverter** can be used as an example for a default configuration and can be overloaded to.

### User control Setup

By setting an own implementation of **IGitServerAuth** to **CcGitServer::setAuth()**, it is possible to work with an own 

With **CcGitServerAuth** there is a default implementation of **IGitServerAuth**, wich will be loaded if no other Interfwas was set.
 
## Next Targets

This system is very rudimental, and mainly designed to run on internal networks.

- Security features
    It is dangerous to run it on internet,
- Avoid dependency from php-xml
- test locking, it is just working with .lock files
- Simple html ui for browsing projects

## Contact

If you need help, you can find me on [GitHub](https://github.com/AndyD87) or on my [Website](https://adirmeier.de).

## License

Author CcGitServer: [Andreas Dirmeier](http://adirmeier.de)  
CcGitServer is licensed under LGPL v3. Look at COPYING and COPYING.LESSER for further information.
