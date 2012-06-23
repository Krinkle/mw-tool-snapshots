ts-krinkle-mwSnapshots
======================

## Install:
* Copy `local.php-sample` and rename to `local.php`
* Fill in paths for the needed directories. Keep in mind that
  the PHP script needs to be able to write, read and remove files
  from these directories. In the case of `cacheDir`, it needs to be
  able to create directories as well.
* Make sure the path of `mediawikiCoreRepoDir` points to a mediawiki core checkout:<br>
  `git clone https://gerrit.wikimedia.org/r/p/mediawiki/core.git mediawiki-core`
* Run:<br>
  `php scripts/updateSnaphots.php`
* Symlink `{cacheDir}/snapshots` to `./public_html/snapshots`
* Schedule updateSnaphots.php to run hourly<br>
   `0 * * * * php $HOME/externals/ts-krinkle-mwSnapshots/scripts/updateSnaphots.php > $HOME/externals/ts-krinkle-mwSnapshots/logs/updateSnaphots.log 2>&1`
* Symlink `./public_html` to `~/public_html/mwSnapshots`
