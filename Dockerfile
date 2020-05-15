# Default Dockerfile

# NO MIRROR
FROM karthus/framework:7.2-alpine-v3.11.6-cli
LABEL maintainer="Karthus Developers <php@blued.com>" version="1.0" license="MIT"

##
# ---------- env settings ----------
##
# --build-arg timezone=Asia/Shanghai
ARG timezone

ENV TIMEZONE=${timezone:-"Asia/Shanghai"} \
    COMPOSER_VERSION=1.10.6

# update
RUN set -ex \
    && apk update \
    && cd /tmp \
    # install composer
    && wget -o https://mirrors.aliyun.com/composer/composer.phar \
    && chmow +x ./composer.phar \
    && mv composer.phar /usr/local/bin/composer \
    # setting composer
    && composer config -g repo.packagist composer https://mirrors.aliyun.com/composer/ \
    # show php version && php extensions
    && php -v \
    && php -m \
    # setting php.ini
    # TODO
    # - config timezone
    && ln -sf /usr/share/zoneinfo/${TIMEZONE} /etc/localtime \
    && echo "${TIMEZONE}" > /etc/timezone \
    && echo -e "\033[42;37m Build Completed :).\033[0m\n"

WORKDIR /opt/www


# Composer Cache
# COPY ./composer.* /opt/www/
# RUN composer install --no-dev --no-scripts

COPY . /opt/www
RUN composer install --no-dev -o

RUN cd /opt/www \
    && chmow +x ./karthus \

EXPOSE 8000

ENTRYPOINT ["/opt/www/karthus", "start", "d"]
