HotCRP with Revision Model and Anonymous Voting
=================================

This is the source code of the submission website for
[MICRO-50](https://www.microarch.org/micro50/). As the submission co-chair,
we'd like to share this code to future PC and/or submission chair interested in
using the [revision model](https://www.microarch.org/micro50/Revision/) of
[MICRO-50](https://www.microarch.org/micro50/).

FAQ
-------
Please install HotCRP v2.100 and play with it before reading this section. The
code is based on v2.100.

* How's the code modified to enable revision?
    We leverage the option field in the submission form to achieve this. We
    were able to show only the option field to authors of some papers and let
    them modify the option of their papers even the website is not open for
    submissions.

* How do I, as an admin/PC chair, let only some authors submit revisions?
    1.  Under Settings->Decisions->Revisions, enter a tag (e.g., revision) you
        want to use for papers that are allowed to submit revisions. Add this tag
        to Settings->Tags & tracks->Chair-only tags. Save changes.
    2.  Tag papers with the tag you defined based on your own criteria.
    3.  **When the submission period has passed and you have closed the website**
        Under Settings->Submission form->Submission options,
        add an option (e.g. Revision), and enter your description.
    4.  Select "PDF" for Type, "Visibility to PC and reviewers" for Visibility,
        "1st" for Form order, " Near submission" for Display. Save changes.
    5.  **When the conference is ready to accept revisions**, under
        Settings->Decisions->Revisions, click "Open site for revisions."
    6.  **When the period for revision period has ended**, under
        Settings->Decisions->Revisions, unclick "Open site for revisions."

    Note that you have to make sure that the 3rd step happens *after* your
    close the website.  Otherwise, any author could submit a revision because
    that is just an option of the paper.

* Can opening/closing the website for revisions be automatic (i.e. I enter some
        time stamp and it will work)?
    No. The code does not support that. Neither does it has any coordination
    with the original author response feature. Treat this as an extra feature
    that lets you control the submission form.

* How do I, as an admin/PC chair, use the modified, anonymous voting system?
    1.  Under Settings->Tags & tracks->Approval voting tags,
        enter exactly these two tags "_accept" & "_reject" (we hardcoded them in the source code).
    2.  Under Settings->Tags & tracks->Chair-only tags,
        add these two tags "open" & "closed" (we also hardcoded them in the source code).
    3.  **When a paper is about to enter the voting session, use the tag "open" to tag it.**
        As an admin/PC chair, you should be able to see the voting result to decide when
        to finish the voting session (refresh when needed), while other people see only their vote.
    4.  **When a paper has finished its voting session, use the tag "closed" to tag it and remove the tag "open" from it.**
        Now if people refresh the paper page, they should be able to see the voting result.
        If they don't refresh and change their votes, the "closed" tag prevents this by showing
        a red cross to their changes.

    Note that "_accept", "_reject", "open", "closed" are all hardcoded in the source code.
    Therefore, be careful and type them right when using those tags.

Authors
-------
Po-An Tsai (poantsai@csail.mit.edu) and Mark C. Jeffrey (mcj@csail.mit.edu)


(Below is the original README.md in the original HotCRP github repo.)
-------

HotCRP Conference Review Software [![Build Status](https://travis-ci.org/kohler/hotcrp.svg?branch=master)](https://travis-ci.org/kohler/hotcrp)
=================================

HotCRP is the best available software for managing the conference
review process, including paper submission, review and comment
management, rebuttals, and the PC meeting. Its main strengths are
flexibility and ease of use in the review process, especially through
smart paper search and an extensive tagging facility. It is widely
used in computer science conferences and for internal review processes
at several large companies.

Multitrack conferences with per-track deadlines should use other software.

HotCRP is the open-source version of the software running on
[hotcrp.com](https://hotcrp.com). If you want to run HotCRP without setting
up your own server, use hotcrp.com.

Prerequisites
-------------

HotCRP runs on Unix, including Mac OS X. It requires the following
software:

* Apache, http://apache.org/ or Nginx, http://nginx.org/
  (You may be able to use another web server that works with PHP.)
* PHP version 5.4 or higher, http://php.net/
  - Including MySQL and GD support
* MySQL version 5 or higher, http://mysql.org/
* The zip compressor, http://www.info-zip.org/
* Poppler’s version of pdftohtml, http://poppler.freedesktop.org/ (Only
  required for format checking. Recent pdftohtml versions are suitable for
  HotCRP; most versions released before 2013 aren’t.)

Apache is preloaded on most Linux distributions. You may need to install
additional packages for PHP and MySQL, such as:

* Fedora Linux: php-mysql, php-gd, zip, (poppler-utils)
* Debian Linux: php5-common, php5-gd, php5-mysql,
  libapache2-mod-php5 (or libapache-mod-php5 for Apache 1.x),
  zip, (poppler-utils)
* Ubuntu Linux: php5-common, php5-gd, php5-mysql,
  libapache2-mod-php5 (or libapache-mod-php5 for Apache 1.x),
  zip, (poppler-utils), and a package for SMTP support, such
  as sendmail or postfix

You may need to restart the Apache web server after installing these
packages (`sudo apachectl graceful` or `sudo apache2ctl graceful`). If
using nginx, you will need the php-fpm package.

Installation
------------

1. Run `lib/createdb.sh` to create the database. Use
`lib/createdb.sh OPTIONS` to pass options to MySQL, such as `--user`
and `--password`. Many MySQL installations require privilege to create
tables, so you may need `sudo lib/createdb.sh OPTIONS`. Run
`lib/createdb.sh --help` for more information. You will need to
decide on a name for your database (no spaces allowed).

    The username and password information for the conference database is
stored in `conf/options.php`, which HotCRP marks as world-unreadable. You must
ensure that your PHP can read this file.

2. Edit `conf/options.php`, which is annotated to guide you.
(`lib/createdb.sh` creates this file based on `src/distoptions.php`.)

3. Configure your web server to access HotCRP. The right way to do this
depends on which server you’re running.

    **Nginx**: Configure Nginx to access `php-fpm` for anything under
the HotCRP URL path. All accesses should be redirected to `index.php`.
This example, which would go in a `server` block, makes `/testconf`
point at a HotCRP installation in /home/kohler/hotcrp (assuming that
the running `php-fpm` is listening on port 9000):

        location /testconf/ {
            fastcgi_pass 127.0.0.1:9000;
            fastcgi_split_path_info ^(/testconf)(/.*)$;
            fastcgi_param PATH_INFO $fastcgi_path_info;
            fastcgi_param SCRIPT_FILENAME /home/kohler/hotcrp/index.php;
            include fastcgi_params;
        }

    You may also set up separate `location` blocks so that Nginx
serves files under `images/`, `scripts/`, and `stylesheets/` directly.

    **Apache**: Generally you must add a `<Directory>` to `httpd.conf`
(or one of its inclusions) for the HotCRP directory, and an `Alias`
redirecting your preferred URL path to that directory. This example
makes `/testconf` point at a HotCRP installation in
/home/kohler/hotcrp:

        # Apache 2.2 and earlier:
        <Directory "/home/kohler/hotcrp">
            Options Indexes Includes FollowSymLinks
            AllowOverride all
            Order allow,deny
            Allow from all
        </Directory>
        Alias /testconf /home/kohler/hotcrp
        
        # Apache 2.4 and later:
        <Directory "/home/kohler/hotcrp">
            Options Indexes Includes FollowSymLinks
            AllowOverride all
            Require all granted
        </Directory>
        Alias /testconf /home/kohler/hotcrp

    Note that the first argument to Alias should NOT end in a slash.
The `AllowOverride all` directive is required.

    Everything under HotCRP’s URL path (here, `/testconf`) should be
served by HotCRP. This normally happens automatically. However, if
the URL path is `/`, you may need to turn off your server’s default
handlers for subdirectories such as `/doc`.

4. Update the systemwide setting for PHP’s `session.gc_maxlifetime`
configuration variable. This provides an upper bound on HotCRP session
lifetimes (the amount of idle time before a user is logged out
automatically). On Unix machines, systemwide PHP settings are often
stored in `/etc/php.ini`. The suggested value for this setting is
86400, e.g., 24 hours:

        session.gc_maxlifetime = 86400

    If you want sessions to expire sooner, we recommend you set
`session.gc_maxlifetime` to 86400 anyway, then edit `conf/options.php`
to set `$Opt["sessionLifetime"]` to the correct session timeout.

5. Edit MySQL’s my.cnf (typical location: `/etc/mysql/my.cnf`) to ensure
that MySQL can handle paper-sized objects.  It should contain something
like this:

        [mysqld]
        max_allowed_packet=32M

    max_allowed_packet must be at least as large as the largest paper
you are willing to accept. It defaults to 1M on some systems, which is
not nearly large enough. HotCRP will warn you if it is too small. Some
MySQL setups, such as on Mac OS X, may not have a my.cnf by default;
just create one. If you edit my.cnf, also restart the mysqld server.
On Linux try something like `sudo /etc/init.d/mysql restart`.

6. Sign in to the site to create an account. The first account created
automatically receives system administrator privilege.

You can set up everything else through the web site itself.

* Configuration notes

  - Uploaded papers and reviews are limited in size by several PHP
    configuration variables, set by default to 15 megabytes in the HotCRP
    directory’s `.htaccess` (or `.user.ini` if you are using php-fpm).

  - HotCRP PHP scripts can take a lot of memory, particularly if they're
    doing things like generating MIME-encoded mail messages.  By default
    HotCRP sets the PHP memory limit to 128MB.

  - HotCRP benefits from Apache’s `mod_expires` and `mod_rewrite`
    modules; consider enabling them.

  - Most HotCRP settings are assigned in the conference database’s
    Settings table. The Settings table can also override values in
    `conf/options.php`: a Settings record with name "opt.XXX" takes
    precedence over option $Opt["XXX"].

Database access
---------------

Run `lib/backupdb.sh` at the shell prompt to back up the database.
This will write the database’s current structure and comments to the
standard output. As typically configured, HotCRP stores all paper
submissions in the database, so the backup file may be quite large.

Run `lib/restoredb.sh BACKUPFILE` at the shell prompt to restore the
database from a backup stored in `BACKUPFILE`.

Run `lib/runsql.sh` at the shell prompt to get a MySQL command prompt
for the conference database.

Updates
-------

HotCRP code can be updated at any time without bringing down the site.
If you obtained the code from git, use `git pull`. if you obtained
the code from a tarball, copy the new version over your old code,
preserving `conf/options.php`. For instance, using GNU tar:

    % cd HOTCRPINSTALLATION
    % tar --strip=1 -xf ~/hotcrp-NEWVERSION.tar.gz

Multiconference support
-----------------------

HotCRP can run multiple conferences from a single installation. The
last directory component of the URL will define the conference ID.
For instance:

    http://read.seas.harvard.edu/conferences/testconf/doc/testconf-paper1.pdf
                                             ^^^^^^^^
                                           conference ID

The conference ID can only contain characters in `[-_.A-Za-z0-9]`, and
it must not start with a period. HotCRP will check for funny
conference IDs and replace them with `__invalid__`.

To turn on multiconference support:

1. Set your Web server to use the HotCRP install directory for all relevant
   URLs. For Apache, this may require an `Alias` directive per conference.

2. Set `$Opt["multiconference"]` to true in `conf/options.php`. This will set
   the conference ID to the last directory component as described above.
   Alternately, set `$Opt["multiconferenceAnalyzer"]` to a regular expression,
   a space, and a replacement pattern. HotCRP matches the full input URL to
   the regex, then uses the replacement pattern as the conference ID. For
   example, this setting will use "conf_CONFNAME" as the conference ID for a
   URL like "http://CONFNAME.crap.com/":

        $Opt["multiconferenceAnalyzer"] = '\w+://([^./]+)\.crap\.com\.?/ conf_$1';

3. Set HotCRP options to locate the options relevant for each conference. The
   best mechanism is to use `$Opt["include"]` to include a conference-specific
   options file. For example (note the single quotes):

        $Opt["include"] = 'conf/options-${confid}.php';

    The `${confid}` substring is replaced with the conference ID. HotCRP will
refuse to proceed if the conference-specific options file doesn’t exist. To
ignore nonexistent options files, use wildcards:

        $Opt["include"] = 'conf/[o]ptions-${confid}.php';

    `${confid}` replacement is also performed on these $Opt settings: dbName,
dbUser, dbPassword, sessionName, downloadPrefix, conferenceSite, paperSite,
defaultPaperSite, contactName, contactEmail, emailFrom, emailSender, emailCc,
emailReplyTo, and docstore.

4. Each conference needs its own database. Create one using the
   `lib/createdb.sh` script (the `-c CONFIGFILE` option will be useful).

License
-------

HotCRP is available under the Click license, a BSD-like license. See the
LICENSE file for full license terms.

Authors
-------

Eddie Kohler, Harvard/UCLA

* HotCRP is based on CRP, which was written by Dirk Grunwald,
  University of Colorado
* [banal by Geoff Voelker, UCSD](http://www.sysnet.ucsd.edu/sigops/banal/)
