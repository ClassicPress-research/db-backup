# Simple DB Backup - Target audience and design choices

WORK IN PROGRESS. The features described in present tense in this document are not guaranteed to have been implemented yet. They define the design choices made for developing the plugin. The development follows an MVP approach, i.e. we build the minimum viable product first, adding features as we go.

## Target audience, raison-d'être

Simple DB Backup is meant to provide a very simple database backup and restoration method for ClassicPress and WordPress. This is the kind of backup you'd need in the following simple scenarios:

* Before you run a migration from WordPress to ClassicPress.
* Before you run an upgrade to ClassicPress itself.
* As a simple means to preserve your site's state before making a change which could prove problematic.

The target audience is the less experienced people, running a site on shared / managed hosting, who either do not have access to SSH / phpMyAdmin or do not have the experience using these tools.

The expected use cases and target audience inform the design choices made while building this plugin.

## Architectural choices

### MySQL only

The only supported database servers are those fully compatible with MySQL's variant of the SQL language. At the time of this writing (January 2018) these are:

* MySQL
* MariaDB
* Percona

Note that Amazon AWS does offer managed MySQL under a different name (Amazon RDS for MySQL). This is still MySQL and supported. Same goes for any other hosting, cloud or otherwise, offerring a MySQL, MariaDB or Percona installation.

### Only backs up the database

Simple DB Backups, as the name implies, will only back up and restore your database content. It will not make a full site backup. That requires backing up files as well, not to mention that restoration requires partial or complete reconfiguration of the site. This use case is already covered by third party plugins.

### Not suitable for site transfer

The database backup is meant to be restored on the same site it was taken on. This means the same MySQL version, the same subdomain, the same absolute filesystem and relative web directories. If you move your site to a different MySQL server with a different version, a different domain/subdomain, a different location on the server's hard disk or a different relative web directory the restored site won't function. This is on purpose. Moving a site requires a series of additional steps both during backup and during restoration such as not backing up transients and replacing data (including serialized data) in the database upon restoration. This use case is already covered by third party plugins.

### Only backs up VIEWs and TABLEs

Simple DB Backup will only backup the structure of TABLEs and VIEWs. It will not back up the structure of PROCEDUREs, FUNCTIONs and TRIGGERs. The latter typically require additional privileges to restore them. If you are using this kind of advanced entities you either know how to back up and restore your MySQL database manually (e.g. using mysqldump) or you are using a third party plugin which supports them.

MySQL TABLEs may have different engines. Tables with the following EGNINEs only have their structure, but not their data, backed up:

* [BLACKHOLE](https://dev.mysql.com/doc/refman/5.7/en/blackhole-storage-engine.html) because it throws away all data, i.e. it never stores anything
* [EXAMPLE](https://dev.mysql.com/doc/refman/5.7/en/example-storage-engine.html) because that's a null engine, provided as an example to MySQL developers
* [FEDERATED](https://dev.mysql.com/doc/refman/5.7/en/federated-storage-engine.html) because it's a reference to a table stored in a remote database
* [MEMORY](https://dev.mysql.com/doc/refman/5.7/en/memory-storage-engine.html), HEAP because it's a temporary, in-memory storage which the code _should_ rebuild if empty since it's guarnateed to not survive database server restarts.
* [MERGE](https://dev.mysql.com/doc/refman/5.7/en/merge-storage-engine.html), MRG_MYISAM because they are a collection of identical MyISAM tables.

VIEWs are only backed up as structure, of course, since VIEWs are essentially stored SELECT statements.

### No locking

No read / write locking is applied to the tables when performing the backup. This may sound weird and make you wonder what happens with consistency. 

According to my experience maintaining this kind of software since 2006 consistency is not an issue for the majority of sites. The few sites which would, indeed, face a consistency issue tend to use a MySQL master/slave architecure and a custom WPDB class which reads from slaves and writes to the master. In these cases you can suspend the slaves' sync with the master while the backup is in progress but, frankly, if you run a server like that you're probably already managing your own backups and have no need for Simple DB Backup.

The other reason no locking is applied is that locking has to be lifted or the site will stop functioning properly. According to my experience, failed backups due to the server acting up (e.g. because mod_bw treated the backup as a denial of service) are overwhelmingly far more common than consistency issues. In case of PHP being killed before a backup step is complete the lock release would never run, causing the site to fail.

### No exclusions / inclusions

Unlike third party backup plugins there is no provision for excluding tables or specifying that the data of some tables should not be backed up. This kind of advanced backup is out of scope of this plugin. You can use a third party backup plugin in this case.

Moreover, this plugin will naively back up all tables and views it finds in the database, regardless of their prefix. This is done on purpose. The use cases we cater for assume a simple site with a database holding its tables only. If you have two sites sharing the same database, sorry, we'll be backing up everyting. 

While it's trivial to reject tables whose names don't start with the prefix definied in the configuration file, my experience tells me that it's more harmful than backing up everything. Many times I've seen sites with third party scripts installed on the same database and integrated into the site or plugins (based on third party scripts) not following WordPress/ClassicPress best practices and using table names without a prefix.

### No dependency management and minimal foreign key support

Simple DB Backup does not track table and view inter-dependencies. All tables are backed up alphabetically sorted, followed by the VIEWs which arealso alphabetically sorted.

Table and view dependencies may manifest themselves in two different ways, foreign keys (for tables) and table names in SELECT operations (for views).

Our use cases assume that we are restoring the database backup on top of an existing site. Therefore the tables themselves already exist, we just overwrite them as we go through the restoration. This takes care of referencing other tables in VIEWs and the DDL of tables as far as foreign keys are concerned.

Moreover, the restoration runs with foreign key checks disabled, therefore even if the rows on the foreign table are missing during restoration we can still insert the data on the local table, knowing that the foreign table will be updated further on during the restoration.

The corrollary to that is that we can not restore the backup to an empty database. Doing so would require backing up after doing full dependency tracking to ensure that tables and viewss are restored in an order which precludes referencing a table/view before we can know for sure it's created.

If you have complex sites with table inter-dependencies and absolutely need to back them up in a way which guarantees restoration on an empty database server you must use a third party plugin.

### Pure PHP backup with timeout management

Since we're assuming backing up on a shared or managed hosting environment we have the reasonable expectation that it will probably:

* disable PHP function which allow us to call shell commands / run binaries
* does not guarantee availability of mysqldump or similar external tools
* be of an unknown architecture, i.e. we cannot package a statically linked mysqldump binary ourselves
* have a rather short PHP maximum execution time
* have a rather short timeout for PHP to respond to the web server
* probably be using server side code to limit the amount of resources used by the site such as mod_bw, mod_evasive, resource monitoring etc

These assumptions mean that we need to run the backup in pure PHP, without shelling out to native binaries, and that the backup has to be split in short steps, each one running on its own page load to prevent the server killing the backup process. This is exactly the kind of software I've specialised in.

It comes down to three parameters defining the behavior of each backup step which runs in a spearate page load: the minimum execution time, the maximum execution time and the execution time bias. The default settings (2 / 5 / 75%) are enough for most servers. If you have a really pesky shared hosting you may change that to 5 / 3 / 50. Yes, minimum is less than maximum. This creates a "duty cycle" where the backup runs for 1.5 to 2 seconds and then waits another 3 to 3.5 seconds doing absolutely nothing, thus lowering the CPU usage limit of the PHP process. This code is battle tested, having run several hundreds of millions of times on dozens of millions of sites.

### SQL file splitting when necessary

Another problem with cheap shared / managed hosting is maximum file size limits which only applies when creating a file through PHP. This is done as defense against malicious scripts which allow attackers to upload very big malicious files for hosting them on affected sites. Unfortunately, this also means that you can't create a single, big, SQL backup file. Depending on the host this limit could be as low as 128KB or as high as 30MB. Serveral well-known European hosts have limits in the 2-10MB range for their cheapest offerrings.

Simple DB Backup deals with that in two ways. First, it tries to auto-detect if the last write operation failed, roll it back and start writing to a new file. This is the preferred method and works on most hosts. Some hosts, however, will kill the PHP process if it tries to create a file bigger than that. For these hosts there's a configuration option about the maximum file size.

In case the SQL file needs to be split in multiple files the files are named .sql (first part), .s01 (second part), .s02 (third part) and so on and so forth. If restoring them manually, they have to be restored in this order. If restored through the plugin, restoration takes care of it automatically.

### Maximum packet size and compound SQL INSERT statements

The backup tries to create compound SQL INSERT statements, i.e. a single generated SQL query will try to INSERT multiple rows in the table. This is done for performance reasons. The default maximum SQL query length is 256KB, well below the MySQL maximum packet size. This can be configured.

It is possible to end up in a situation where the backup of a single row exceeds the MySQL maximum packet size. This can happen when there are multiple fields on the table and one of them is close to the maximum packet size. The code that generated it probably never INSERTs an entire row, just updates fields. **There is nothing you can do in this case. The restoration WILL fail.** Detecting or increasing the MySQL packet size requires database root user access which, by definition, we do not have. There is no solution in this case other than asking the host to increase the MySQL maximum packet size in their `my.cnf` file. The same problem will occur no matter what you use to back up the database contents.

### Restoration with timeout management

Simple DB Backup also offers an integrated restoration feature with the same kind of timeout prevention as the backup.

This feature is designed to run outside the ClassicPress index.php loop, directly accessible over AJAX, since we might be updating tables that ClassicPress needs to render a page (like the options table). Simply put, we can't run a restoration through the application being restored.

In order to prevent security issues it is inactive until you choose to perform a restoration. At this point a random, long password is created, stored on disk and communicated to the browser. The password itself is not stored on disk in plaintext, we only store its bcrypt hash (making attack modes other than Man In The Middle utterly impractical). The browser sends that to the restoration script over AJAX. It is advisable to use HTTPS to access your site to avoid MITM (man-in-the-middle) attacks. Moreover, the password file is removed when the restoration is over. This check and deletion also take place when the plugin boots up to prevent accidental security issues.

Since we run outside ClassicPress we need to use our own database driver during restoration. Since the restoration is triggered from inside ClassicPress, we copy the database connection settings (as provided by the relevant constants set in wp-config.php) into the same file which storess our restoration password's bcrypt hash. 

Moreover we need to detect which PHP database connection method to use: mysql, mysqli or pdomysql. We inspect the WPDB object and use the same method it uses to connect.

There are a few caveats regarding restoration:

* If you cannot write directly to files there's no way to create the restoration.php file with the restoration password and the db connection information. Right now this is a fatal error. A future version could ask the user to provide FTP credentials or, better, instruct them to create that file themselves.
* If you use a custom / modified WPDB class which reports it's using the mysql driver when in reality using something else (e.g. MySQL over PDO) the restoration will fail. Right now this is a fatal error. A future version could let the user override the driver technology.

### Unit Testing

The backup and restoration engine powering the plugin is fully Unit Tested. The plugin interface itself is not. We could possibly use Selenium to test it in the future.

### Requires JavaScript

Running the backup and restoration requires JavaScript enabled. A `<NOSCRIPT>` element wraps the interface, providing feedback about missing JavaScript, disabling the interface in this case.