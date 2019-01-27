CONFIG_SRC=/vagrant/vagrant-config
MYSQL=/etc/mysql/mysql.conf.d
NGINX=/etc/nginx
PHP=/etc/php/7.1

# The output of all these installation steps is noisy. With this utility
# the progress report is nice and concise.
function install {
  echo installing $1
  shift
  apt-get -y install "$@" >/dev/null 2>&1
}

function quietRun {
  "$@" > /dev/null 2>&1
}

echo Remove old NodeJS
apt-get -y purge --auto-remove nodejs  >/dev/null

install CUrl curl

echo Update Repositories
curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | sudo apt-key add - >/dev/null
echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list >/dev/null
curl -sL https://deb.nodesource.com/setup_8.x | sudo -E bash - >/dev/null
apt-add-repository ppa:ondrej/php  >/dev/null
apt-get -y update  >/dev/null

install NodeJS nodejs
install NPM npm
mkdir ~/.npm-global
npm config set prefix '~/.npm-global'
install Yarn yarn
install PHP_7.2 php7.2
install PHP_Modules php7.2-mbstring php7.2-mysql php7.2-xdebug php7.2-xml
install PHP_FPM php7.2-fpm

echo Set User Permissions
sed -i 's/user = www-data/user = vagrant/g' /etc/php/7.2/fpm/pool.d/www.conf
sed -i 's/group = www-data/group = vagrant/g' /etc/php/7.2/fpm/pool.d/www.conf

echo Display PHP errors
sed -i 's/display_errors = Off/display_errors = On/g' /etc/php/7.2/fpm/php.ini

echo Remove apache2
service apache2 stop  >/dev/null
apt-get -y remove apache2  >/dev/null
apt-get -y remove apache2*  >/dev/null
apt-get -y autoremove  >/dev/null

install nginx nginx-full

quietRun curl -Ss https://getcomposer.org/installer -o composer-setup.php || echo "Downlaod Composer install script"
quietRun php composer-setup.php --install-dir=/usr/local/bin --filename=composer || echo "Install Composer globally"

debconf-set-selections <<< 'mysql-server mysql-server/root_password password passw0rd'
debconf-set-selections <<< 'mysql-server mysql-server/root_password_again password passw0rd'
install MySql mysql-server
quietRun mysql -uroot -proot < $CONFIG_SRC/database-config.sql || echo "Run MySQL config"
quietRun cp $MYSQL/mysqld.cnf $MYSQL/mysqld.cnf.save || echo "Backup MySQL cnf"
quietRun cp $CONFIG_SRC/mysqld.cnf $MYSQL/mysqld.cnf || echo "Install new MySQL cnf"

echo Updating Nginx config
quietRun cp $CONFIG_SRC/phpdraft $NGINX/sites-available/phpdraft || echo "Copy Nginx config to sites-available"
quietRun rm $NGINX/sites-enabled/* || echo "Remove existing sites-enabled from Nginx"
quietRun ln -s $NGINX/sites-available/phpdraft $NGINX/sites-enabled/phpdraft || echo "Add Nginx config to sites-enabled"

quietRun cp $CONFIG_SRC/xdebug.ini $PHP/mods-available/xdebug.ini || echo "Copy Xdebug config"

echo Restarting Services
quietRun systemctl restart mysql.service || echo "Restart MySQL"
quietRun systemctl restart php7.1-fpm.service || echo "Restart PHP-FPM"
quietRun systemctl restart nginx.service || echo "Restart Nginx"

echo Creating HootDraft database
#runuser -l vagrant -c 'mysqladmin -u root -ppassw0rd create phpdraft'
quietRun mysqladmin -u root -ppassw0rd create phpdraft

#runuser -l vagrant -c 'sudo chown -R -H $USER: /vagrant'
runuser -l vagrant -c 'sudo npm install -g gulp@3.9.0'
