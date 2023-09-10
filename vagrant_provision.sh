#!/usr/bin/env bash

set -e
set -u

DBNAME=ylilauta
DBPASSWD=vagrant

# Use global APT archives instead of US etc.
sed -i -e 's/\/\/[a-z][a-z]\.archive\./\/\/archive./g' /etc/apt/sources.list

# Install packages
apt update

# Install PHP7, Nginx, MySQL, PNGCrush, JpegOptim, ImageMagick
export DEBIAN_FRONTEND=noninteractive
debconf-set-selections <<< "mysql-server mysql-server/root_password password ${DBPASSWD}"
debconf-set-selections <<< "mysql-server mysql-server/root_password_again password ${DBPASSWD}"

apt install -y nginx libnginx-mod-http-lua lua-nginx-dns mysql-server-8.0 \
    php7.4-fpm php7.4-mbstring php7.4-gd php7.4-curl php7.4-bcmath php7.4-intl php7.4-xml php7.4-mysql php-apcu php-imagick php-xdebug \
    php-rrd ffmpeg webp gifsicle jhead jpegoptim pngcrush imagemagick unzip whois ssmtp sassc

# Nginx config
sed -i -e 's/^user .*;/user vagrant;/g' /etc/nginx/nginx.conf
sed -i -e 's/#\? \?use .*;//g' /etc/nginx/nginx.conf
sed -i -e 's/#\? \?multi_accept .*;/multi_accept on; use epoll;/g' /etc/nginx/nginx.conf
# Sendfile messes up with caches
sed -i -e 's/\t\?sendfile on;/\tsendfile off;/g' /etc/nginx/nginx.conf

cat > /etc/nginx/conf.d/zz-setit.conf << EOM
# Basic
server_tokens off;

fastcgi_buffers 512 4k;
client_max_body_size 200M;

# Gzip
gzip_vary on;
gzip_proxied any;
gzip_comp_level 6;
gzip_types image/svg+xml text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;
gzip_min_length 512;

# Limit req
limit_req_zone \$binary_remote_addr zone=reqs:10m rate=10r/s;

# IP Blacklist for posting
geo \$ip_blacklisted {
    default 0;
    #include snippets/ip_blacklist.conf;
}
EOM

cat > /etc/nginx/snippets/security.conf << EOM
# Security
location ~ /\.ht { return 404; }
EOM
cat > /etc/nginx/snippets/php-upstream.conf << EOM
location ~ \.php\$ {
    limit_req zone=reqs burst=10 nodelay;

    include snippets/fastcgi-php.conf;
    fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
}
EOM

# Nginx vhosts
rm /etc/nginx/sites-available/*
rm /etc/nginx/sites-enabled/*
cat > /etc/nginx/sites-available/default << EOM
server {
    server_name _;
    listen 80 default_server;
    listen [::]:80 default_server;
    root /vagrant/public;

    index index.php;
    try_files \$uri /index.php?\$args;

    set \$googlebot 'false';
    access_by_lua_file /vagrant/nginx/access_control.lua;

    # Security
    location ~ /\.ht { return 404; }

    location ~ ^/phpmyadmin/ {
        root /srv/www;

        location ~ \.php$ {
            include snippets/fastcgi-php.conf;
            fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        }
    }

    # TV-guide
    location /tv-opas/ {
        alias /vagrant/TVGuide/public/;
        try_files \$uri /tv-opas//tv-opas/index.php?\$args;

        location ~ /tv-opas/.+\.php\$ {
            include snippets/fastcgi-php.conf;
            fastcgi_param SCRIPT_FILENAME \$document_root/index.php;
            fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        }
    }

    location /tv-opas/static/ {
        alias /vagrant/TVGuide/static/;
        try_files \$uri =403;

        location ~ \.php\$ { return 404; }
    }

    # Boards and threads
    location / {
        # Board urls need to end with a slash
        rewrite ^/([a-z0-9\_]+)(-([2-9]|[1-9][0-9]+))?$ /\$1\$2/ permanent;

        rewrite ^/([a-z0-9\_]+)(-([2-9]|[1-9][0-9]+))?/$ /board.php?board=\$1&page=\$3;
        rewrite ^/([a-z0-9\_]+)/([1-9][0-9]*)$ /thread.php?board=\$1&thread=\$2;
    }

    location = / { try_files \$uri /index.php?\$query_string; }
    location = /preferences { rewrite ^ /preferences.php; }
    location = /gold { rewrite ^ /gold.php; }
    location = /banned { rewrite ^ /banned.php; }
    location /settings/ { rewrite "^/settings/([a-z0-9]*)$" /settings.php?page=\$1; }
    location ~ /(my|all|replied|hidden|followed)threads {
        rewrite ^/(my|all|replied|hidden|followed)(threads)(-)?([0-9]+)?$ /customboard.php?action=\$1\$2&page=\$4;
    }
    location /mod { rewrite ^/mod/([a-z0-9\_]+)?/?$ /mod/index.php?action=\$1; }
    location = /post { rewrite ^ /scripts/ajax/post.php; }

    # IP blacklist
    location = /scripts/ajax/post.php {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
    }

    location /static/ {
        root /vagrant/;
        try_files \$uri =404;

        rewrite "^/static/(css|js)/([0-9.]{3,}/)?(.+)(\.css|\.js)$" /static/\$1/\$3\$4 break;

        location ~ \.php\$ { return 404; }
    }

    location /fonts/ {
        root /vagrant/static/;
        try_files \$uri =404;

        location ~ \.php\$ { return 404; }
    }

    location /files/ {
        root /vagrant/;
        try_files \$uri =404;

        location ~ \.php\$ { return 404; }

        # Files do not use query strings.
        if (\$query_string != '') { return 404; }

        location ~ "^/files/([0-9a-z])([0-9a-z])([0-9a-z])([0-9a-z]{2,}\.\w+)\$" {
            alias /vagrant/files/\$1/\$2/\$3/\$1\$2\$3\$4;
        }

        location ~ "^/files/thumb/([0-9a-z])([0-9a-z])([0-9a-z])([0-9a-z]{2,}\.\w+)\$" {
            alias /vagrant/files/\$1/\$2/\$3/\$1\$2\$3\$4;
            image_filter_buffer 10M;
            image_filter resize 240 240;
        }

        location ~ "^/files/thumb/([0-9a-z])([0-9a-z])([0-9a-z])([0-9a-z]{2,})-(480|720)(\.\w+)\$" {
            alias /vagrant/files/\$1/\$2/\$3/\$1\$2\$3\$4\$6;
            image_filter_buffer 10M;
            image_filter resize \$5 \$5;
        }
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
    }
}
EOM
ln -s /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

# PHP config
#sed -i -e 's/;\?opcache.enable=.*/opcache.enable=1/g' /etc/php/7.4/fpm/php.ini
sed -i -e 's/upload_max_filesize \?= \?.*/upload_max_filesize = 200M/g' /etc/php/7.4/fpm/php.ini
sed -i -e 's/post_max_size \?= \?.*/post_max_size = 200M/g' /etc/php/7.4/fpm/php.ini
sed -i -e 's/error_reporting \?= \?.*/error_reporting = E_ALL/g' /etc/php/7.4/fpm/php.ini
sed -i -e 's/^user \?= \?.*/user = vagrant/g' /etc/php/7.4/fpm/pool.d/www.conf
sed -i -e 's/^group \?= \?.*/group = vagrant/g' /etc/php/7.4/fpm/pool.d/www.conf
sed -i -e 's/^listen.owner \?= \?.*/listen.owner = vagrant/g' /etc/php/7.4/fpm/pool.d/www.conf
sed -i -e 's/^listen.group \?= \?.*/listen.group = vagrant/g' /etc/php/7.4/fpm/pool.d/www.conf
sed -i -e 's/^;\?rlimit_files \?= \?.*/rlimit_files = 65536/g' /etc/php/7.4/fpm/pool.d/www.conf
sed -i -e 's/display_errors \?= \?.*/display_errors = On/g' /etc/php/7.4/fpm/php.ini
echo "env[APPLICATION_ENVIRONMENT] = development" >> /etc/php/7.4/fpm/pool.d/www.conf

# XDebug settings
cat >> /etc/php/7.4/fpm/conf.d/20-xdebug.ini << EOM
xdebug.profiler_enable_trigger=1
xdebug.profiler_output_dir=/vagrant/profiler
xdebug.idekey=PHPSTORM
xdebug.remote_port=9090
xdebug.remote_enable=1
xdebug.remote_connect_back=1
EOM

# MySQL config
cat > /etc/mysql/mysql.conf.d/zz-user.cnf << EOM
[mysqld]
# No need for this in PHP 7.4
default_authentication_plugin = mysql_native_password

bind_address = 127.0.0.1
skip-name-resolve
skip-log-bin

# Basics
group_concat_max_len = 65536
innodb_file_per_table = 1
innodb_log_file_size = 128M

# Buffer pool should be larger than total data in databases for optimum
# performance. Total size is size * instances.
# Remember, the buffer pools are stored in RAM, do not oversize!
innodb_buffer_pool_size = 1G
innodb_buffer_pool_instances = 2

# Flushing writes only once per 2 secs increases write performance quite a bit.
# only downside is the possible loss of 2 seconds worth of data in case of a crash.
innodb_flush_neighbors = 0
innodb_flush_log_at_trx_commit = 0
innodb_flush_log_at_timeout = 2
#innodb_flush_method = O_DIRECT

# Other InnoDB related
#innodb_log_file_size = 16M # Because of the 2GB disk we have
innodb_open_files = 500
#innodb_read_io_threads = 64
#innodb_write_io_threads = 64

# Slowlog
slow_query_log = 1
slow_query_log_file = /var/log/mysql/mysql-slow.log
long_query_time = 0.03 # Yup. 0.03 seconds is slow for a query.
log_queries_not_using_indexes = 1
EOM
service mysql restart

# Install PHPMyAdmin
cd /tmp
wget https://files.phpmyadmin.net/snapshots/phpMyAdmin-5.0+snapshot-english.tar.gz
tar xfz phpMyAdmin-5.0+snapshot-english.tar.gz
mkdir -p /srv/www/phpmyadmin
mv phpMyAdmin-5.0+snapshot-english/* /srv/www/phpmyadmin/
rm phpMyAdmin-5.0+snapshot-english.tar.gz
rm -r phpMyAdmin-5.0+snapshot-english
cat > /srv/www/phpmyadmin/config.inc.php << EOM
<?php
\$cfg['Servers'][1]['user'] = 'root';
\$cfg['Servers'][1]['password'] = '${DBPASSWD}';
\$cfg['Servers'][1]['auth_type'] = 'config';
\$cfg['blowfish_secret'] = '`head /dev/urandom | tr -dc A-Za-z0-9 | head -c 64 ; echo ''`';
EOM
chown -R vagrant:vagrant /srv/www

mysql -uroot -p${DBPASSWD} -e "ALTER USER root@localhost IDENTIFIED WITH mysql_native_password BY '${DBPASSWD}'"
mysql -uroot -p${DBPASSWD} -e "CREATE DATABASE IF NOT EXISTS ${DBNAME};"
mysql -uroot -p${DBPASSWD} ${DBNAME} < /vagrant/schema.sql
mysql -uroot -p${DBPASSWD} ${DBNAME} < /vagrant/data.sql

# Set locales
update-locale LANG=en_US.UTF-8 LANGUAGE=en_US.UTF-8 LC_ALL=en_US.UTF-8
sed -i -e 's/# fi_FI.UTF-8 UTF-8/fi_FI.UTF-8 UTF-8/g' /etc/locale.gen
sed -i -e 's/# de_DE.UTF-8 UTF-8/de_DE.UTF-8 UTF-8/g' /etc/locale.gen
sed -i -e 's/# sv_SE.UTF-8 UTF-8/sv_SE.UTF-8 UTF-8/g' /etc/locale.gen
locale-gen

# Install composer
cd /tmp
curl -s https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

# Install dependencies
cd /vagrant
composer install

# Restart configured services
service nginx restart
service php7.4-fpm restart
service mysql restart

# Load IP blacklists
#FIXME
#sh /vagrant/cli/update-blacklist.sh

# Cronjobs
cat > /etc/cron.d/ylilauta << EOM
* * * * * vagrant /usr/bin/php /vagrant/cron/cron_minutely.php > /dev/null
2 * * * * vagrant /usr/bin/php /vagrant/cron/cron_hourly.php > /dev/null
2 1 * * * vagrant /usr/bin/php /vagrant/cron/cron_daily.php > /dev/null
4 1 1 * * vagrant /usr/bin/php /srv/www/ylilauta/cron/cron_monthly.php > /dev/null
EOM

# Email
cat > /etc/ssmtp/ssmtp.conf << EOM
root=postmaster
mailhub=smtp.example.com
authuser=example@example.com
authpass=example
usestarttls=yes
hostname=ylilauta.org
fromlineoverride=no
EOM
cat >> /etc/ssmtp/revaliases << EOM
root:example@example.com
www-data:example@example.com
vagrant:example@example.com
EOM

php /vagrant/cron/cron_minutely.php
php /vagrant/cron/cron_hourly.php
php /vagrant/cron/cron_daily.php