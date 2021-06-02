##################################################################
# Build copy of prod server
##################################################################
FROM amazonlinux:2.0.20201218.1 AS apache

#ENV INSTANCE_USER $INSTANCE_USER
#ENV APACHE_GROUP $APACHE_GROUP

RUN yum update -y
RUN amazon-linux-extras install -y lamp-mariadb10.2-php7.2 php7.2
RUN amazon-linux-extras install -y epel
# Or install just PHP here (7.4):
#RUN amazon-linux-extras install -y php7.4
RUN yum install -y httpd
RUN yum install -y rsync rclone composer unzip openssh-clients git

# Install nodejs and npm
# Current nodejs distributions: https://github.com/nodesource/distributions
#RUN curl -sL https://rpm.nodesource.com/setup_15.x | bash -
# Only Node 14 is supported by node-sass v4.14.1. currently
# See: https://github.com/sass/node-sass#node-version-support-policy
RUN curl -sL https://rpm.nodesource.com/setup_14.x | bash -
RUN yum install -y nodejs
#RUN wget https://nodejs.org/dist/v14.17.0/node-v14.17.0-linux-x64.tar.xz

RUN useradd -G apache ec2-user
RUN chown -R ec2-user:apache /var/www

# Disable questioning by ssh when running rsync inside the image later
RUN mkdir /root/.ssh && echo '' > /root/.ssh/config && \
sed -i -n -e 'a Host *' -e 'a StrictHostKeyChecking no' /root/.ssh/config


##################################################################
## Deploy code for stage or prod environemnt
##################################################################
FROM tradehelperonline:apache AS app

ARG APACHE_GROUP=apache
ARG DATA_REMOTE
ARG BUCKET_NAME
ARG INSTANCE_USER=ec2-user
ARG APP_ENV=prod
ARG BRANCH=master

WORKDIR /var/www/html
#COPY . .
RUN --mount=type=secret,id=repo eval "$(ssh-agent -s)" ssh-add /run/secrets/repo ; \
git clone --single-branch -b$BRANCH --depth=2 git@github.com:mgwebgroup/tradehelperonline.git /var/www/html


RUN ./deploy_app bare
RUN --mount=type=secret,id=datastore ./deploy_app assets
RUN --mount=type=secret,id=datastore ./deploy_app data



##################################################################
# This stage deploys code base for test environment.
##################################################################
FROM mgwebgroup/deb10.2-php72:symfony AS test

ARG APACHE_GROUP=www-data
ARG DATA_REMOTE
ARG BUCKET_NAME
ARG INSTANCE_USER=tester
ARG APP_ENV=test

WORKDIR /var/www/html
COPY . .

RUN ./deploy_app bare
RUN --mount=type=secret,id=datastore ./deploy_app assets
RUN --mount=type=secret,id=datastore ./deploy_app data

RUN useradd -g www-data -u1001 tester


