FROM eboraas/apache

RUN apt-get update && apt-get install -y php5 php5-cli php5-gd php5-curl php5-mcrypt php5-intl php5-dev php5-common libav-tools && apt-get clean && rm -rf /var/lib/apt/lists/*
RUN apt-get update && apt-get install -y imagemagick libmagickwand-dev pkg-config && apt-get clean && rm -rf /var/lib/apt/lists/*
RUN printf "\n" | pecl install mongo
RUN printf "\n" | pecl install imagick

COPY ./vhosts/back-openorchestra.conf /etc/apache2/sites-available/back-openorchestra.conf
COPY ./vhosts/front-openorchestra.conf /etc/apache2/sites-available/front-openorchestra.conf
COPY ./vhosts/media-openorchestra.conf /etc/apache2/sites-available/media-openorchestra.conf

COPY ./ini/mongo.ini /etc/php5/mods-available/
COPY ./ini/imagick.ini /etc/php5/mods-available/
COPY ./ini/symfony.ini /etc/php5/mods-available/

COPY ./cron/openorchestra /etc/cron.d/

RUN php5enmod mongo
RUN php5enmod imagick
RUN php5enmod symfony
RUN printf "\n"

RUN a2ensite back-openorchestra.conf
RUN a2ensite front-openorchestra.conf
RUN a2ensite media-openorchestra.conf

RUN usermod -u 1000 www-data

EXPOSE 80

CMD /usr/sbin/apache2ctl -D FOREGROUND
