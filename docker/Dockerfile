##################################################################
# This stage deploys code base for test environment.
##################################################################
FROM mgwebgroup/deb10.2-php72:symfony AS test

ARG APP_ENV=test
ARG INSTANCE_USER=tester
ARG APACHE_GROUP=www-data
ARG DATA_REMOTE
ARG BUCKET_NAME

ENV APP_ENV $APP_ENV
ENV INSTANCE_USER $INSTANCE_USER
ENV APACHE_GROUP $APACHE_GROUP
#ENV DATABASE_CONNECTOR="mysql://${DB_USER}:${DB_PASSWORD}@${DB_HOST}:3306/${DB_NAME}"

RUN useradd -g www-data -u1001 tester

WORKDIR /var/www/html
COPY . .

RUN ./deploy_app bare
RUN --mount=type=secret,id=datastore ./deploy_app assets
RUN --mount=type=secret,id=datastore ./deploy_app data


##################################################################
## This stage deploys code base for prod or staging environment
##################################################################
FROM amazonlinux:2.0.20201218.1 AS base

ARG APP_ENV=prod
ARG INSTANCE_USER=ec2-user
ARG APACHE_GROUP=apache
ARG DATA_REMOTE
ARG BUCKET_NAME

ENV APP_ENV $APP_ENV
ENV INSTANCE_USER $INSTANCE_USER
ENV APACHE_GROUP $APACHE_GROUP
#ENV DATABASE_CONNECTOR="mysql://${DB_USER}:${DB_PASSWORD}@${DB_HOST}:3306/${DB_NAME}"

RUN yum update -y
RUN amazon-linux-extras install -y lamp-mariadb10.2-php7.2 php7.2
RUN amazon-linux-extras install -y epel
# Or install just PHP here (7.4):
#RUN amazon-linux-extras install -y php7.4
RUN yum install -y httpd
RUN yum install -y rsync rclone composer unzip openssh-clients

# Install nodejs and npm
# Current nodejs distributions: https://github.com/nodesource/distributions
#RUN curl -sL https://rpm.nodesource.com/setup_15.x | bash -
# Only Node 14 is supported by node-sass v4.14.1. currently
# See: https://github.com/sass/node-sass#node-version-support-policy
RUN curl -sL https://rpm.nodesource.com/setup_14.x | bash -
RUN yum install -y nodejs

RUN useradd -G $APACHE_GROUP $INSTANCE_USER
RUN chown -R $INSTANCE_USER:$APACHE_GROUP /var/www

# Disable questioning by ssh when running rsync inside the image later
RUN mkdir /root/.ssh && echo '' > /root/.ssh/config && \
sed -i -n -e 'a Host *' -e 'a StrictHostKeyChecking no' /root/.ssh/config

WORKDIR /var/www/html
COPY . .

RUN ./deploy_app bare
RUN --mount=type=secret,id=datastore ./deploy_app assets
RUN --mount=type=secret,id=datastore ./deploy_app data
