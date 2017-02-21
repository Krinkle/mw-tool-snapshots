[![Build Status](https://travis-ci.org/Krinkle/mw-tool-snapshots.svg?branch=master)](https://travis-ci.org/Krinkle/mw-tool-snapshots)
# Snapshots

## Install:
* Copy `sample-config.php` and rename to `config.php`
* Edit `config.php` and change the directory paths if needed (and ensure they exist).
  Keep in mind that the PHP script needs to be able to read, write, and remove files
  from these directories.
  In the case of `cacheDir`, it also needs to be able to create subdirectories.
* Make sure the path of `mediawikiCoreRepoDir` points to a mediawiki core checkout:<br>
  `git clone https://gerrit.wikimedia.org/r/p/mediawiki/core.git mediawiki-core`
* Run:<br>
  `php scripts/updateSnaphots.php`
* Symlink `{cacheDir}/snapshots` to `./public_html/builds`
* Schedule updateSnaphots.php to run hourly<br>
   `0 * * * * php /path/to/mw-tool-snapshots/scripts/updateSnaphots.php > {logsDir}/updateSnaphots.log 2>&1`
* Symlink `./public_html` to be, or inside of, `/path/to/your/public_html`
