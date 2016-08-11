FROM eboraas/apache

MAINTAINER eboraas <https://bitbucket.org/EdBoraas/apache-docker>

RUN apt-get update && apt-get install -y php5 php5-cli php5-gd php5-curl php5-mcrypt php5-intl php5-imagick php5-dev php5-common libav-tools && apt-get clean && rm -rf /var/lib/apt/lists/*
RUN printf "\n" | pecl install mongo

COPY ./vhosts/back-openorchestra.conf /etc/apache2/sites-available/back-openorchestra.conf
COPY ./vhosts/front-openorchestra.conf /etc/apache2/sites-available/front-openorchestra.conf
COPY ./vhosts/media-openorchestra.conf /etc/apache2/sites-available/media-openorchestra.conf

COPY mongo.ini /etc/php5/mods-available/
COPY symfony.ini /etc/php5/mods-available/

RUN php5enmod mongo
RUN php5enmod symfony

RUN a2ensite back-openorchestra.conf
RUN a2ensite front-openorchestra.conf
RUN a2ensite media-openorchestra.conf

RUN usermod -u 1000 www-data

RUN mkdir /var/uploaded-files
RUN chmod a+w /var/uploaded-files

EXPOSE 80

CMD /usr/sbin/apache2ctl -D FOREGROUND
