##
# This script demonstrates how to use CcGitServer on a blank ubuntu system
# You can ran this script in a live system too.
# Just execute:
#    sh ExampleUbuntuSetup.sh
#
# DO NOT USE THIS SCRIPT IN PRODUCTIVE SYSTEMS
# This script will change settings wich you will not want.
# It's just for demonstration purpose.
##

# Install apache and php with modules
sudo apt-get -y install apache2 php php-xml
sudo a2enmod rewrite
sudo service apache2 restart

# If .htaccess is not already enabled
sudo echo "<Directory /var/www/html>" | sudo tee -a /etc/apache2/sites-available/000-default.conf
sudo echo "    AllowOverride All" | sudo tee -a /etc/apache2/sites-available/000-default.conf
sudo echo "</Directory>" | sudo tee -a /etc/apache2/sites-available/000-default.conf
sudo service apache2 restart

# Setup git server
cd /var/www/html
sudo git clone https://github.com/AndyD87/CcGitServer.git git
sudo mkdir repositories
sudo cp -f git/Tools/ExampleUbuntuIntegration/git.php git/git.php

if [ -e ~/.gitconfig ]
then
    echo "gitconfig found, nothing to setup"
else
    echo "setup git config"
    git config --global user.email "test@test.test"
    git config --global user.name "test@test.test"
fi

# First git repository
cd /var/www/html/git
sudo php git.php create ExampleProject
sudo chown -R www-data.www-data ../*

# First clone from repository
cd ~
git clone http://TestUser:TestPW@localhost/git/repositories/ExampleProject.git
cd ExampleProject
echo " " >> README.md
git commit -am "TestCommit"
git push
