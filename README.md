[![Build Status](https://travis-ci.org/Krinkle/mw-tool-snapshots.svg?branch=master)](https://travis-ci.org/Krinkle/mw-tool-snapshots)
# Snapshots

## Install

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

## Git memory

The `updateSnaphots.php` uses Git for many of its tasks. By default, Git
will periodically run `git-gc` as part of an otherwise unrelated Git command.
This sub process can take up a substantial amount of memory, and if you
run the update script in an environment with limited memory, it may end up
crashing as a result.

Further reading:
* [git-gc](https://git-scm.com/docs/git-gc), [git-repack](https://git-scm.com/docs/git-repack), and [git-pack-objects](https://git-scm.com/docs/git-pack-objects)
* StackOverflow [q/3095737](https://stackoverflow.com/q/3095737/319266), [q/8214321](https://stackoverflow.com/q/8214321/319266), and [q/42175296](https://stackoverflow.com/q/42175296/319266).

In short:
* Set `pack.threads = 1` to restrict git-gc to one `git-pack-objects` thread.
* Set `pack.windowMemory` to `256m` (or other value, as needed) to restrict
  how much memory may be at the same time. Other internal values are dynamically
  derived from this.

To set these configurations for the the `mediawikiCoreRepoDir` directory only, use:

```
cd ~/src/snapshots/remotes/mediawiki/
git config --local --add pack.threads 1
git config --local --add pack.windowMemory 256m
# Confirm
git config --local -l
```

Or, without changing directories:

```
GIT_DIR=~/src/snapshots/remotes/mediawiki/.git git config --local --add pack.threads 1
GIT_DIR=~/src/snapshots/remotes/mediawiki/.git git config --local --add pack.windowMemory 256m
# Confirm
GIT_DIR=~/src/snapshots/remotes/mediawiki/.git git config --local -l
```
