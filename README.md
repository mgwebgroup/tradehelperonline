### Deployment
All deployment operations are container-based, with Docker as the container application and one docker file. Deployment into a container depends on environment as each environment handles database service differently. Currently three environments are recognized:
* dev - database is a mix of production and test data as developer sees fit
* test - database is on the same instance as the application server and only contains test fixtures.
* prod - database is on a separate instance and contains only production data.

Environment variables that control the deployment process are:
* APP_ENV
* DB_USER=root
* DB_PASSWORD=mypassword
* DB_HOST=localhost
* DB_NAME
* DATA_REMOTE
* BUCKET_NAME

Entire application code and data consists of the following components:
* Code Base - (Github);
* Application Settings - feature toggles and parameters (AWS S3 bucket);
* Application Assets - particular to each app instance, graphical files, scripts and css (AWS S3 bucket);
* Application Data - data that needs to be stored in database (AWS S3 bucket);
* Test Fixtures - (Github)

*Dockerfile* is designed to work with script *deploy_app*. It has all necessary functionality for various stages of the deployment. Both files are committed to the repository. Parts of the *deploy_app* script are invoked for each deployment environment within the *Dockerfile*. In this way, you should only be able to configure deployment variables and build the application using one *Dockerfile*.

When building app image, all secret info is passed through the *--secret* flag to the docker builder. This flag contains reference to the rclone configuration, which has access credentials to AWS S3 storage.

File *rclone.conf* must contain bucket access configuration for AWS S3 bucket like so:
```text
[some-aws-remote]
type = s3
provider = AWS
env_auth = false
access_key_id = <access key for authorized AWS user>
secret_access_key = <Password for authorized AWS user>
region = <region>
location_constraint = <region>
acl = bucket-owner-full-control
```

Parent image in *Dockerfile* does not contain database, which is necessary for operation of entire application. In this way, whole application is deployed using two containers or *services*:
* MariaDB service
* Apache service

There are also several *docker-compose* files, which will launch application services configured for each environment:
* docker-compose.yml - Local developoment;
* docker-compose.test.yml - Testing environment
* docker-compose.prod.yml - Production environment

Separation into several docker-compose files is necessary for convenience of storage of all app data on dedicated volumes within the docker framework.  


#### Deployment to test environment
1. Create test app image using current files in the project root:
```shell script
DOCKER_BUILDKIT=1 \
docker build \
-f docker/Dockerfile \
--target=test \
--progress=plain \
--build-arg=DATA_REMOTE=aws-mgwebgroup \
--build-arg=BUCKET_NAME=tradehelperonline \
--build-arg=DB_USER=user \
--build-arg=DB_PASSWORD=mypassword \
--build-arg=DB_HOST=172.24.1.3 \
-t tradehelperonline:test \
--secret id=datastore,src=$HOME/.config/rclone/rclone.conf \
.
```

2. Create container cluster:
```shell script
docker-compose -f docker/docker-compose.test.yml up -d
docker-compose -f docker/docker-compose.test.yml exec apache /var/www/html/deploy_app migrations
docker-compose -f docker/docker-compose.test.yml exec apache /var/www/html/deploy_app fixtures
docker-compose -f docker/docker-compose.test.yml exec apache /var/www/html/deploy_app tests
```

#### Deployment to prod environment
Production database must be set up separately.

1. Create prod app image.
1.1 Using current files in the project root:
```shell script
DOCKER_BUILDKIT=1 \
docker build \
-f docker/Dockerfile \
--target=prod \
--progress=plain \
--build-arg=DATA_REMOTE=aws-mgwebgroup \
--build-arg=BUCKET_NAME=tradehelperonline \
--build-arg=DB_NAME=TRADEHELPERONLINE_PROD \
--build-arg=DB_USER=user \
--build-arg=DB_PASSWORD=mypassword \
--build-arg=DB_HOST=172.24.1.3 \
-t tradehelperonline:prod \
--secret id=datastore,src=$HOME/.config/rclone/rclone.conf \
.
```

1.2 Using files in git repository's master branch:
```shell script
DOCKER_BUILDKIT=1 \
docker build \
-f docker/Dockerfile \
--target=prod \
--progress=plain \
--build-arg=DATA_REMOTE=aws-mgwebgroup \
--build-arg=BUCKET_NAME=tradehelperonline \
--build-arg=DB_NAME=TRADEHELPERONLINE_PROD \
--build-arg=DB_USER=user \
--build-arg=DB_PASSWORD=mypassword \
--build-arg=DB_HOST=172.24.1.3 \
-t tradehelperonline:prod \
--secret id=datastore,src=$HOME/.config/rclone/rclone.conf \
git@github.com:mgwebgroup/tradehelperonline.git#master
```

The above commands will copy application code, will run composer install, copy all assets from AWS S3 storage and install them using __npm run build__ command and will copy application data from AWS S3 storage.
Production database must already exist, with data import used in building test images.

2. Run the prod image, and sync all files to the prod:
```bash
docker run --name apache --privileged --rm -it \
--mount type=bind,src=/home/alex/.ssh/tradehelper-prod.pem,dst=/root/tradehelper-prod.pem \
-w /var/www/html/ tradehelperonline:prod \
sh -c 'eval "$(ssh-agent -s)"; ssh-add /root/tradehelper-prod.pem; rsync -v /var/www/html/ ec2-user@54.70.88.233:/var/www/html/'
```

OR:

```bash
docker run --name apache --privileged --rm -it \
--mount type=bind,src=/home/alex/.ssh/tradehelper-prod.pem,dst=/root/tradehelper-prod.pem \
-w /var/www/html/ tradehelperonline:prod bash

docker exec -it -w /var/www/html apache sh -c 'eval "$(ssh-agent -s)"; ssh-add /root/tradehelper-prod.pem; rsync -v /var/www/html/public/index.php ec2-user@54.70.88.233:/var/www/html/public'
```

The following step is optional:
3. Import production database and create container cluster:
This script will map copy of your production database saved as *backups/TO_BE_PROD_DB.sql* to the *apache* service. Run it and use symfony's __doctrine:database:import__ command to import copy of the production database. After that you can bring up all containers normally.
```shell script
docker-compose -f docker/docker-compose.prod.yml run --rm -v $(pwd)/backups:/var/www/html/akay -w /var/www/html apache dockerize -wait tcp4://mariadb:3306 bin/console doctrine:database:import backups/TO_BE_PROD_DB.sql 
docker-compose -f docker/docker-compose.prod.yml up -d
```


### Definitions.

#### Instrument Universe.
At the time of development, the package worked primarily with SPDR funds (XLE, XLB, XLU, etc.) and instruments associated with them. The package can work with any stock instrument, however analytical tools developed as studies to this package, relied heavily on the SPDR funds. To differentiate between sets of instruments utilized for different purposes, idea of an Instrument Universe was introduced. Instrument Universe contains all of instruments collected for some analytical purpose. This universe is further split into watchlists. In this package the term **y_universe** refers to instruments collected under 11 SPDR funds:
* XLC, Communication Services
* XLY, Consumer Discretionary
* XLP, Consumer Staples
* XLE, Energy,1,Energy
* XLF, Financials
* XLV, Health Care
* XLI, Industrials
* XLB, Materials
* XLRE, Real Estate
* XLK, Technology
* XLU, Utilities

The term **x_universe** is a super set of instruments that includes instruments beyond the SPDR's.
