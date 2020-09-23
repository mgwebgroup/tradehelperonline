# syntax = docker/dockerfile:1.0-experimental
# This file documetns CI/CD pipeline
##################################################################
# This stage deploys code base and settings unique to a client.
##################################################################
FROM mgwebgroup/deb10.2-php72:symfony AS base

ARG INSTANCE_USER=root
ARG APP_ENV
ARG DB_USER=root
ARG DB_PASSWORD=rootpass
ARG DB_HOST=localhost
ARG DB_NAME
ARG DATA_REMOTE
ARG BUCKET_NAME

ENV INSTANCE_USER $INSTANCE_USER
ENV APP_ENV $APP_ENV
ENV DATABASE_CONNECTOR="mysql://${DB_USER}:${DB_PASSWORD}@${DB_HOST}:3306/${DB_NAME}"

USER root:www-data
WORKDIR /var/www/html
COPY . .

RUN ./deploy_app bare


##################################################################
## This stage deploys application assets and data
##################################################################
FROM base as data
ARG DATA_REMOTE
ARG BUCKET_NAME

RUN --mount=type=secret,id=datastore ./deploy_app assets
RUN chown -R ${INSTANCE_USER}:www-data assets

RUN --mount=type=secret,id=datastore ./deploy_app data
RUN chown -R ${INSTANCE_USER}:www-data data
