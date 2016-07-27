# Change Log

## DEV/MASTER

**Implemented enhancements:**

- Added Holiday Providers for all 16 German States. [\#34](https://github.com/azuyalabs/Yasumi/pull/34) ([stelgenhof](https://github.com/stelgenhof))
- Added Holiday Provider for Croatia. [\#32](https://github.com/azuyalabs/Yasumi/pull/32) ([karlomikus](https://github.com/karlomikus))


## 1.4.0 (June 4, 2016)

**Implemented enhancements:**

- Added Holiday Provider for Brazil. [\#21](https://github.com/azuyalabs/Yasumi/pull/21) ([dorianneto](https://github.com/dorianneto))
- Added Holiday Provider for the Czech Republic. [\#26](https://github.com/azuyalabs/Yasumi/pull/26) ([dfridrich](https://github.com/dfridrich))
- Added Holiday Provider for the United Kingdom. [\#23](https://github.com/azuyalabs/Yasumi/pull/23) ([stelgenhof](https://github.com/stelgenhof))
- Add Welsh language (spoken in Wales, UK) translations for the holidays in the United Kingdom [\#25](https://github.com/azuyalabs/Yasumi/pull/25) ([meigwilym](https://github.com/meigwilym))
- All Holiday Provider must now implement a code that will identify it. Typically this is the ISO3166 code 
  corresponding to the respective country or subregion. This can help for purposes such as translations or interfacing 
  with other API's for example.
- To determine a set of holidays between two dates you can now use the aptly named 'between()' method.

**Resolved issues:**

- Fixed an issue with the unit test for the 'getProviders' method failing on Windows. Hardcoded unix-style directory separators have been replaced by DIRECTORY_SEPARATOR. [\#30](https://github.com/azuyalabs/Yasumi/pull/30) ([navarr](https://github.com/navarr))
- Corrected a typo in the English translation for 敬老の日 (Japan) [\#22](https://github.com/azuyalabs/Yasumi/pull/22) ([navarr](https://github.com/navarr))
- Fixed issue that the unit tests in 'YasumiTest' (methods 'next' and 'previous') didn't cover the situations that the limits are exceeded. [\#28](https://github.com/azuyalabs/yasumi/issues/28)


## 1.3.0 (May 2, 2016)

**Implemented enhancements:**

- Added Holiday Provider for Poland. [\#18](https://github.com/azuyalabs/Yasumi/pull/18) ([mpdx](https://github.com/mpdx))
- Added Holiday Provider for New Zealand. [\#13](https://github.com/azuyalabs/Yasumi/pull/13) ([badams](https://github.com/badams))
- Added Holiday Provider for Greece. [\#10](https://github.com/azuyalabs/Yasumi/pull/10) ([sebdesign](https://github.com/sebdesign))
- Added Holiday Provider for Germany. [\#9](https://github.com/azuyalabs/Yasumi/pull/9) ([eaglefsd](https://github.com/eaglefsd))
- Added translations ('fr_FR', 'fr_BE') for Belgium National day [\#864d250](https://github.com/azuyalabs/yasumi/commit/864d25097abbeedbee15bcc37702a34c36a5b696) ([R2c](https://github.com/R2c))
- Sorted all translations in the translation files alphabetically (descending). 
- Added missing English ('en_US') translations for the Christian holidays 'Immaculate Conception', 'Maundy Thursday', 
  'St. Georges Day', 'St. John's Day', 'St. Josephs Day' and 'St. Stephens Day'. 
- Added Test Interface class to ensure the unit tests contain a some minimal assertions.
- Refactoring and cleanup of all unit tests.

**Resolved issues:**

- Fixed issue for Sweden as All Saints Day was always calculated to be on November 1st. However the holiday has always 
  been celebrated on a Saturday (between October 31 and November 6th).
- Fixed the getProviders as it was not able to load Holiday Providers defined in (sub) regions [\#5879133](https://github.com/azuyalabs/yasumi/commit/58791330ccf5c13b1626885921534c32866b7faf) ([R2c](https://github.com/R2c))
- Fixed issue that it was possible for the AbstractProvider class to be loaded as a Holiday Provider [\#9678bc4](https://github.com/azuyalabs/yasumi/commit/9678bc490e34980404ad5dc5b3d45a3c76a3ca0f) ([R2c](https://github.com/R2c))
- Corrected incorrect pathname reference \*BaseTestCase.php files ("Test" -> "test).
- Fixed issue for France as Good Friday and St. Stephens Day were defined as official holidays. These aren't national
  holidays and are only observed in the French departments Moselle, Bas-Rhin and Haut-Rhin. With this fix, these 
  holidays have been removed from the France Holiday providers and new providers for the departments Moselle, Bas-Rhin 
  and Haut-Rhin are added. [\#17](https://github.com/azuyalabs/yasumi/issues/17) ([R2c](https://github.com/R2c))
- Updated locales list based on CLDR version 29. Removed locales of which the region identifier is not specified.
- Fixed issue for Sweden as Midsummer's Day (st. Johns Day) was always calculated to be on June 24th. However the 
  holiday has always been celebrated on a Saturday (between June 20 and June 26).
- Fixed base test for some Spain/LaRioja as some holidays have been established only in a particular year, causing
  false failures in the unit tests.
- Running php-cs-fixer fix . --level=psr2 generated a massive list of changes, and broke unit tests. Added a custom
  .php_cs config file to adhere to the PSR-2 Coding Standards and resolve this issue. In addition the php-cs-fixer 
  command to has been added to composer to run the fixers and on the CI server (Travis), meaning PR’s will need to be 
  PSR2 compliant before they can be merged. If any files do not pass, the build fails. [\#15](https://github.com/azuyalabs/yasumi/issues/15) [\#16](https://github.com/azuyalabs/Yasumi/pull/16) ([badams](https://github.com/badams))
- Accidentally the timezone for Norway was set to "Europe/Copenhagen". This has been corrected to "Europe/Oslo". [\#11](https://github.com/azuyalabs/yasumi/issues/11) [\#12](https://github.com/azuyalabs/Yasumi/pull/12) ([badams](https://github.com/badams))
- Fixed issue for Finland as Midsummer's Day (st. Johns Day) was always calculated to be on June 24th. However since 
  1955, the holiday has always been celebrated on a Saturday (between June 20 and June 26).


## 1.2.0 (Apr 4, 2016)

**Implemented enhancements:**

- Added Holiday Provider for Denmark
- Added Holiday Provider for Norway
- Added Holiday Provider for Sweden
- Added Holiday Provider for Finland
- New function 'isWorkingDay' added that determines whether a date represents a working day or not. A working day is 
  considered a date that is neither a holiday nor falls into the weekend.
- Refactoring and cleanup of unit tests


**Resolved issues:**

- The Vernal Equinox Day and Autumnal Equinox Day in Japan were excluded from having it substituted for another day in
  case these days would fall on the weekend.
- Fixed tests for some holiday providers as some holidays have been established only in a particular year, causing
  false failures in the unit tests.


## 1.1.0 (Mar 10, 2016)

**Implemented enhancements:**

- Added Spain Holiday Provider (including the autonomous communities Andalusia, Aragon, Asturias, Balearic Islands, 
  Basque Country, Canary Islands, Cantabria, Castile and León, Castilla-La Mancha, Ceuta, Community of Madrid, 
  Extremadura, Galicia, La Rioja, Melilla, Navarre, Region of Murcia, Valencian Community)
- Added Corpus Christi, St. Joseph's Day, Maundy Thursday, St. George's Day, St. John's Day to the common Christian 
  Holidays.
- Updated some English, Italian, French and Dutch translations. 
- Created separate tests for holidays that are substituted on different days.
- Moved all other holiday calculations in Netherlands and France to individual methods.
- Allow for name spaced holiday providers.
- Added test for translation of Ash Wednesday and Valentinesday in the Netherlands.
- Added test to check whether all holidays for a Holiday Provider are defined by the respective provider class.
- Removed support for PHP 5.4. Minimum version is now 5.5. PHP 7.0 is known to work however in Travis CI still allowed
  to fail

**Resolved issues:**

- For Japan substituted holidays had same date as the original holidays.

## 1.0.0 (Apr 21, 2015)

- Initial Release