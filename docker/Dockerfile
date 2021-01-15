# syntax=docker/dockerfile:experimental
##################################################################
# This stage deploys code base for test environment.
##################################################################
FROM mgwebgroup/deb10.2-php72:symfony AS test

ARG INSTANCE_USER=root
ARG APP_ENV=test
ARG DB_USER=root
ARG DB_PASSWORD=rootpass
ARG DB_HOST=localhost
ARG DB_NAME=TRADEHELPERONLINE_TEST
ARG DATA_REMOTE
ARG BUCKET_NAME
ARG APACHE_GROUP=www-data

ENV INSTANCE_USER $INSTANCE_USER
ENV APP_ENV $APP_ENV
ENV DATABASE_CONNECTOR="mysql://${DB_USER}:${DB_PASSWORD}@${DB_HOST}:3306/${DB_NAME}"
ENV APACHE_RUN_GROUP $APACHE_GROUP

USER $INSTANCE_USER:$APACHE_GROUP
WORKDIR /var/www/html
COPY . .

RUN ./deploy_app bare

RUN --mount=type=secret,id=datastore ./deploy_app assets
RUN chown -R $INSTANCE_USER:$APACHE_GROUP assets

RUN --mount=type=secret,id=datastore ./deploy_app data
RUN chown -R $INSTANCE_USER:$APACHE_GROUP data
#RUN ./deploy_app import-data
#RUN ./deploy_app audit-data

##################################################################
## This stage deploys application assets and data
##################################################################
FROM amazonlinux:2.0.20201218.1 AS prod

ARG INSTANCE_USER=ec2-user
ARG APP_ENV=prod
ARG DB_USER
ARG DB_PASSWORD
ARG DB_HOST=localhost
ARG DB_NAME
ARG DATA_REMOTE
ARG BUCKET_NAME
ARG APACHE_GROUP=apache

ENV INSTANCE_USER $INSTANCE_USER
ENV APP_ENV $APP_ENV
ENV DATABASE_CONNECTOR="mysql://${DB_USER}:${DB_PASSWORD}@${DB_HOST}:3306/${DB_NAME}"
ENV APACHE_RUN_GROUP $APACHE_GROUP

RUN yum update -y
RUN amazon-linux-extras install -y lamp-mariadb10.2-php7.2 php7.2
RUN amazon-linux-extras install -y epel
# Or install just PHP here (7.4):
# RUN amazon-linux-extras install -y php7.4
RUN yum install -y httpd
RUN useradd -G $APACHE_GROUP $INSTANCE_USER
RUN chown -R $INSTANCE_USER:$APACHE_GROUP /var/www
RUN yum install -y rsync rclone composer unzip openssh-clients

# Install nodejs and npm
# Current nodejs distributions: https://github.com/nodesource/distributions
#RUN curl -sL https://rpm.nodesource.com/setup_15.x | bash -
# Only Node 14 is supported by node-sass v4.14.1. currently see: https://github.com/sass/node-sass#node-version-support-policy
RUN curl -sL https://rpm.nodesource.com/setup_14.x | bash -
RUN yum install -y nodejs

#USER $INSTANCE_USER:$APACHE_GROUP

WORKDIR /var/www/html
COPY . .

RUN ./deploy_app bare

RUN --mount=type=secret,id=datastore ./deploy_app assets
RUN chown -R $INSTANCE_USER:$APACHE_GROUP assets

RUN --mount=type=secret,id=datastore ./deploy_app data
RUN chown -R $INSTANCE_USER:$APACHE_GROUP data
