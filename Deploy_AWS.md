# Installation on AWS Instance

## SSH to Instance

* ssh -vvv -i /path-to-secret-key/ user@ip-address

## Install Nginx

* sudo wget http://nginx.org/keys/nginx_signing.key
* sudo apt-key add nginx_signing.key
* sudo nano /etc/apt/sources.list
* Append these lines at the bottom of the file
    * deb http://nginx.org/packages/ubuntu xenial nginx
    * deb-src http://nginx.org/packages/ubuntu xenial nginx
    * Save and exit
* sudo apt update
* sudo apt install nginx
* sudo nano /etc/nginx/conf.d/default.conf
    * add "try_files $uri $uri/ /index.php?$args;" after "index index.php index.html index.htm;" 
    * Save and exit
* sudo service nginx start
    
    
## Install MySQL

* sudo apt install mysql-server mysql-client
* sudo mysql_secure_installation
* sudo service mysql start


## Install PHP

* sudo apt update
* sudo apt install php php-fpm php-cli php-mysql
* sudo nano /etc/php/7.0/fpm/php.ini
    * uncomment the following line "cgi.fix_pathinfo=1" by remove ; ate the beginning of line
    * and change it to "cgi.fix_pathinfo=0"
    * save and exit
* sudo service php7.0-fpm restart


## Configure Nginx to use FastCGI

* sudo nano /etc/nginx/conf.d/default.conf
    * uncomment followinf codeblock: location ~/.php { -- } and set root parameter to root directory of files
    * save and exit
* sudo nano /etc/php/7.0/fpm/pool.d/www.conf
    * find and replace "listen = /etc/php/7.0/fpm/php-fpm.sock" by "listen = 127.0.0.1:9000"
    * set listen.owner = nginx, listen.group = nginx, listen.mode = 0664, user = nginx & group = nginx (by default this lines are commented)
