FROM ubuntu:latest

MAINTAINER Alex Moore version: 0.1

RUN apt-get update && apt-get install -y apache2 php libapache2-mod-php && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN a2enmod php7.0

RUN sed -i "s/short_open_tag = Off/short_open_tag = On/" /etc/php/7.0/apache2/php.ini
RUN sed -i "s/error_reporting = .*$/error_reporting = E_ERROR | E_WARNING | E_PARSE/" /etc/php/7.0/apache2/php.ini

ENV APACHE_RUN_USER www-data
ENV APACHE_RUN_GROUP www-data
ENV APACHE_LOG_DIR /var/log/apache2
ENV APACHE_LOCK_DIR /var/lock/apache2
ENV APACHE_PID_FILE /var/run/apache2.pid

EXPOSE 80

ADD content /var/www/site

ADD apache-config.conf /etc/apache2/sites-enabled/000-default.conf

CMD ["/usr/sbin/apache2", "-D", "FOREGROUND"]
