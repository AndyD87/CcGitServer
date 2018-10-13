# Install apache and php with modules
sudo apt-get install apache2 php php-xml
sudo a2enmod redirect
sudo service apache2 restart

# If .htaccess is not already enabled
sudo echo "<Directory /var/www/html>" | sudo tee -a /etc/apache2/sites-available/000-default.conf
sudo echo "    AllowOverride All" | sudo tee -a /etc/apache2/sites-available/000-default.conf
sudo echo "</Directory>" | sudo tee -a /etc/apache2/sites-available/000-default.conf

# Setup git server
cd /var/www/html
sudo git clone https://github.com/AndyD87/CcGitServer.git git

# First git repository
cd /var/www/html/git
sudo php git.php create ExampleProject
sudo chown -R www-data.www-data *

# First clone from repository
cd ~
git clone http://localhost/git/Example.git