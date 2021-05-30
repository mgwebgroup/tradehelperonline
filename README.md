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
You will need to generate access token on Github first.
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
https://github.com/mgwebgroup/tradehelperonline.git
```

The above commands will copy application code, will run composer install, copy all assets from AWS S3 storage and install them using __npm run build__ command and will copy application data from AWS S3 storage.
Production database must already exist, with data import used in building test images.

2. Run the prod image, and sync all files to the prod:
```bash
docker run --name apache --privileged --rm -it \
--mount type=bind,src=/home/alex/.ssh/tradehelper-prod.pem,dst=/root/tradehelper-prod.pem \
-w /var/www/html/ tradehelperonline:prod \
sh -c 'eval "$(ssh-agent -s)"; ssh-add /root/tradehelper-prod.pem; rsync -plogrv  --delete-before --chown=ec2-user:apache /var/www/html/ ec2-user@54.70.88.233:/var/www/html/'
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
At the time of development, the package worked primarily with SPDR funds (XLE, XLB, XLU, etc.) and instruments associated with them. The package can work with any stock instrument, however analytical tools developed as studies to this package, relied heavily on the SPDR funds. To differentiate between sets of instruments utilized for different purposes, idea of an Instrument Universe was introduced. Instrument Universe contains all of instruments collected for some analytical purpose. This universe is further split into watch lists. In this package the term **y_universe** refers to instruments collected under 11 SPDR funds:
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


### Maintenance.

#### Instruments Universe for Market Survey Study.
Some studies work with a set of instruments (stocks) collected from external sources. For example, Market Survey study evaluates instruments from the sectors maintained by [Spider ETFs](https://www.sectorspdr.com). ETF's frequently have instruments removed and added, and it's a good idea to update subject universe of a study at least once a quarter.

Below is the procedure on how to update a universe of instruments for the Market Survey study. All actions are performed inside data/ project directory.
1. Download constituents of each ETF.

1.1 To download manually:
Go to https://www.ssga.com/us/en/intermediary/etfs/fund-finder. Enter fund name into search box (example: XLC). On the results page select tab Holdings, then look for link "Download All Holdings: Daily". Save file on disk.

1.2 Use **curl** command from the Free Software Foundation (usually supplied in GNU/Linux distributions). This example will download holdings for each SPDR ETF and will save results in files named after each ETF. For example: XLU.xlsx, XLE.xlsx, etc. Do not execute these commands in one call. The ssga.com API will block fast queries. Space them out at least a minute apart.

```bash
curl -o XLC.xlsx -GL https://www.ssga.com/us/en/individual/etfs/library-content/products/fund-data/etfs/us/holdings-daily-us-en-xlc.xlsx
curl -o XLY.xlsx -GL https://www.ssga.com/us/en/individual/etfs/library-content/products/fund-data/etfs/us/holdings-daily-us-en-xly.xlsx
curl -o XLP.xlsx -GL https://www.ssga.com/us/en/individual/etfs/library-content/products/fund-data/etfs/us/holdings-daily-us-en-xlp.xlsx
curl -o XLE.xlsx -GL https://www.ssga.com/us/en/individual/etfs/library-content/products/fund-data/etfs/us/holdings-daily-us-en-xle.xlsx
curl -o XLF.xlsx -GL https://www.ssga.com/us/en/individual/etfs/library-content/products/fund-data/etfs/us/holdings-daily-us-en-xlf.xlsx
curl -o XLV.xlsx -GL https://www.ssga.com/us/en/individual/etfs/library-content/products/fund-data/etfs/us/holdings-daily-us-en-xlv.xlsx
curl -o XLI.xlsx -GL https://www.ssga.com/us/en/individual/etfs/library-content/products/fund-data/etfs/us/holdings-daily-us-en-xli.xlsx
curl -o XLB.xlsx -GL https://www.ssga.com/us/en/individual/etfs/library-content/products/fund-data/etfs/us/holdings-daily-us-en-xlb.xlsx
curl -o XLK.xlsx -GL https://www.ssga.com/us/en/individual/etfs/library-content/products/fund-data/etfs/us/holdings-daily-us-en-xlk.xlsx
curl -o XLU.xlsx -GL https://www.ssga.com/us/en/individual/etfs/library-content/products/fund-data/etfs/us/holdings-daily-us-en-xlu.xlsx
curl -o XRT.xlsx -GL https://www.ssga.com/us/en/individual/etfs/library-content/products/fund-data/etfs/us/holdings-daily-us-en-xrt.xlsx
curl -o XHB.xlsx -GL https://www.ssga.com/us/en/individual/etfs/library-content/products/fund-data/etfs/us/holdings-daily-us-en-xhb.xlsx
```

2. Produce comma separated files for each ETF.
In this example ETF instruments will be arranged as needed by the Market Survey study for its file *y_universe.csv*.
Open each downloaded Excel file and create a new csv file with the following columns: Symbol,Name,Industry,SPDR Fund. Cut and paste data from Excel to csv manually. Ignore lines that do not contain stock symbols. Save results as a comma separated file named after each ETF in lower case. For example: xhb.csv, xrt.csv, etc.

3. Combine all sector files into one file titled *y_universe.csv*.
Use heading from each sector ETF files. See previous item.
Note how many lines you have after you done. In BASH:
```bash
wc y_universe.csv
```

4. Screen for illegal symbols and remove duplicate instruments.
Sometimes *y_universe* file that you produce by combining indvidual ETF listings may contain illegal data in the Symbol field. This may happen during manual handling of the downloaded files, when extraneous rows supplied by SPDR Funds were not removed. Examples are 'CASH_USD' and any other ticker name that contains numbers.

To make sure you only have stock symbols in the y_universe file, run this command:
```bash
sed  -i.bak -e '/^[A-Z]*[0-9_]\+[0-9A-Z]*,/d' y_universe.csv
```
This command will also create a backup copy *y_universe.csv.bak*.

Some ETF's share same stocks between each other. However, we only want one occurrence of each instrument in our *y_universe* file. The easiest way to accomplish this is run the following commands in BASH:
```bash
tail -n +2 y_universe.csv | sort -fdu -t , -k 1,1 | sed -e '1i Symbol,Name,Industry,SPDR Fund' > y_universe-uniq-sorted.csv
mv y_universe-uniq-sorted.csv y_universe.csv
```

Some symbols may contain characters incompatible with price provider. For example Brown-Forman Corporation Class B comes as symbol *BF.B* from SPDR's, but is *BF-B* in Yahoo Finance. This functionality must be built in into the price provider API, but is not yet, so symbols like these must be resolved manually.
```bash
sed -i -e 's/^\([A-Z]\+\)\.\+\([A-Z]*\),/\1-\2,/' y_universe.csv
```

Note what new symbols have been added and removed and how many lines you have after you done. In BASH:
```bash
diff -y --suppress-common-lines y_universe.csv source | tee symbol_diff.txt
wc y_universe.csv
```
Results are displayed on screen and saved to file *symbol_diff.txt*. It will be used in later steps to prune the *y_universe* watchlist.

- Optional: Update existing Market Survey table, which contains visual scores.
Open the Market_Survey file in an spreadsheet application. Export the sorted instruments with their scores into a csv file (Market_Survey.csv). Include header (Symbol,Name,Industry,SPDR Fund).
Run:
```bash
# Make sure you only have unique and sorted values in the Market Survey file:
tail -n +2 Market_Survey.csv | sort -fdu -t , -k 1,1 | sed -e '1i Symbol,Name,Industry,SPDR Fund' > market-survey-uniq-sorted.csv
#add new symbols from y_universe
join -t , -j 1 --header -a1 \
-o1.1,1.2,1.3,1.4,2.5,2.6,2.7,2.8,2.9,2.10,2.11,2.12,2.13,2.14,2.15,2.16,2.17,2.18,2.19,2.20,2.21,2.22,2.23,2.24,2.25,2.26,2.27,2.28,2.29 \
y_universe.csv market-survey-uniq-sorted.csv > temp.csv
# make sure the resulting line count matches y_universe.csv
wc temp.csv
```

Paste all the lines (except header) from *temp.csv* back into your Market Survey file which is open in spreadsheet application and save.


5. Update Exchange Listings files.
Each instrument is listed on either NASDAQ or other exchanges (NYSE, AMEX, BATS, etc.). NASDAQ has this information available. In this system, the files are titled *nasdaqlisted.csv* and *otherlisted.csv*. They are used during import of instruments into the system.

List of current company listings can be downloaded from NASDAQ website: [company list](https://www.nasdaq.com/screening/company-list.aspx)

It is also possible to download traded symbols for each exchange. These instructions come from [this post](https://quant.stackexchange.com/questions/1640/where-to-download-list-of-all-common-stocks-traded-on-nyse-nasdaq-and-amex).

Login to NASDAQ's FTP server:
```bash
lftp ftp.nasdaqtrader.com
cd SymbolDirectory
get nasdaqlisted.txt
get otherlisted.txt
```

The following will replace pipe ("|") separator to comma (",") and will replace dots in some symbols into dashes.
```bash
sed -si -e 's%|%,%g' -e '/^File/d' nasdaqlisted.txt otherlisted.txt
sed -si -e 's/^\([A-Z]\+\)\.\+\([A-Z]*\)/\1-\2/' nasdaqlisted.txt otherlisted.txt
mv nasdaqlisted.txt nasdaqlisted.csv
mv otherlisted.txt otherlisted.csv
```

Legend for the columns in both files can be found [here](http://www.nasdaqtrader.com/trader.aspx?id=symboldirdefs). But it is quoted here as well:
nasdaqlisted.txt:
Market Category:
* Q = NASDAQ Global Select MarketSM
* G = NASDAQ Global MarketSM
* S =  NASDAQ Capital Market

otherlisted.txt:
Exchange:
* A = NYSE MKT
* N = New York Stock Exchange (NYSE)
* P = NYSE ARCA
* Z = BATS Global Markets (BATS)
* V = Investors' Exchange, LLC (IEXG)


6. Upload the newly produced files to S3 bucket and update them in the data/source directory.
```bash
rclone copy y_universe.csv aws-mgwebgroup:tradehelperonline/data/source \
rclone copy nasdaqlisted.csv aws-mgwebgroup:tradehelperonline/data/source \
rclone copy otherlisted.csv aws-mgwebgroup:tradehelperonline/data/source
# Clean up
mv nasdaqlisted.csv source/ \
mv otherlisted.csv source/ \
mv y_universe.csv source/ \
rm *.bak *.csv *.txt
```


7. Import new instruments into the instruments table
Change into the main project directory and run:
```bash
bin/console th:instruments:import -v
```
Instruments that are already imported will be skipped. This command will print its output to *std_out*.
Do not delete instruments before import! If you delete them, all of your watch lists and price data will loose references. Only delete instruments when you know exactly what you are doing.


8. Remove old instruments from the y_universe watchlist and add new ones



