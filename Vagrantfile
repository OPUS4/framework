# -*- mode: ruby -*-
# vi: set ft=ruby :

$software = <<SCRIPT
# Downgrade to PHP 7.1
apt-add-repository -y ppa:ondrej/php
apt-get -yq update
apt-get -yq install php7.1

# Install MYSQL
debconf-set-selections <<< "mysql-server mysql-server/root_password password root"
debconf-set-selections <<< "mysql-server mysql-server/root_password_again password root"
apt-get -yq install mysql-server

# Install required PHP packages
apt-get -ya install php7.1-mbstring
apt-get -yq install php7.1-dom
apt-get -yq install php7.1-pdo
apt-get -yq install php7.1-fileinfo
apt-get -yq install php7.1-json
apt-get -yq install php7.1-curl
apt-get -yq install php7.1-mysql
apt-get -yq install php7.1-zip

# Install Ant
apt-get -yq install ant
SCRIPT

$composer = <<SCRIPT
/vagrant/bin/install-composer.sh
SCRIPT

$database = <<SCRIPT
/vagrant/bin/prepare-database.sh
SCRIPT

$prepare_tests = <<SCRIPT
cd /vagrant
ant prepare-workspace
# TODO currently the admin account is always used for tests
if test ! -f tests/config.ini; then
  ant prepare-config -DdbUserName=opus4admin -DdbUserPassword=opusadminpwd -DdbAdminName=opus4admin -DdbAdminPassword=opusadminpwd
fi
bin/composer update
php db/createdb.php
SCRIPT

$environment = <<SCRIPT
if ! grep "cd /vagrant" /home/vagrant/.profile > /dev/null; then
  echo "cd /vagrant" >> /home/vagrant/.profile
fi
if ! grep "PATH=/vagrant/bin" /home/vagrant/.bashrc > /dev/null; then
  echo "export PATH=/vagrant/bin:$PATH" >> /home/vagrant/.bashrc
fi
SCRIPT

$help = <<SCRIPT
echo "Use 'vagrant ssh' to log into VM and 'logout' to leave it."
echo "In VM use:"
echo "'composer test' for running tests"
echo "'composer update' to update dependencies"
echo "'composer cs-check' to check coding style"
echo "'composer cs-fix' to automatically fix basic style problems"
SCRIPT

Vagrant.configure("2") do |config|
  config.vm.box = "bento/ubuntu-20.04"

  config.vm.provision "Install required software...", type: "shell", inline: $software
  config.vm.provision "Install Composer...", type: "shell", privileged: false, inline: $composer
  config.vm.provision "Setup database...", type: "shell", inline: $database
  config.vm.provision "Prepare tests...", type: "shell", privileged: false, inline: $prepare_tests
  config.vm.provision "Setup environment...", type: "shell", inline: $environment
  config.vm.provision "Help", type: "shell", privileged: false, inline: $help
end
