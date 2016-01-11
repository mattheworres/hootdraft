# Upgrading

If you are running PHP Draft version 1.3.0 currently, you can upgrade to version 2.0.0

**NOTE**: Backup all site files and data before proceeding with this upgrade. This has been tested, but not thoroughly, so you will want to protect yourself in case your data is corrupted or your server can't run version 2 for some reason. I wrote a "downgrade" script to undo any data changes and help you roll back to a working 1.3 site, but still: **You have been warned**.

 1. Open `/api/Domain/Models/Migrations/2.0.0/Up.sql` and ensure line 5 references your database name.

 1. Execute `Up.sql` on your database.

 1. If your installation has any NFL drafts that use extended rosters, refer to the commented out commands on line 36 of `Up.sql` (drafts that use the extended NFL rosters can now exist along side ones that don't, so I separated them into two distinct "sports" - this SQL will update those drafts to be of type "NFLE" sport)

 1. Your dates and times will be off as version 2 switched to using UTC dates by default. If you are aware of the timezone you used in 1.3 (EST was default), use the SQL contained in lines 99 - 116.

      - According to MySQL docs, you should be able to use the exact text that PHPD 1.3 used to specify your local timezone (line 25 of [phpdraft 1.3 directory]/includes/global_setup.php):
        "The value can be given as a named time zone, such as 'Europe/Helsinki', 'US/Eastern', or 'MET'. Named time zones can be used only if the time zone information tables in the mysql database have been created and populated."
        Source: http://dev.mysql.com/doc/refman/5.7/en/time-zone-support.html
      - Here are all of the v1.3.0 PHP Draft-supplied values and the corresponding text value you can use in the SQL:
        v1.3 Property Used | Timezone value to use in SQL
        -------------              | -------------
        PHPDRAFT::TIMEZONE_GMT    | Europe/London
        **PHPDRAFT::TIMEZONE_EST (default)**             | **America/New_York**
        PHPDRAFT::TIMEZONE_CST             | America/Chicago
        PHPDRAFT::TIMEZONE_MTN             | America/Denver
        PHPDRAFT::TIMEZONE_PCF               | America/Los_Angeles

      - If you specified Greenwich Mean time (`PHPDRAFT::TIMEZONE_GMT`), that is the same as UTC, so you do NOT need to perform this datetime conversion. Hooray!

 1. You can now follow the steps in README.md (skipping steps 2 and 3). Steps 10 and 11 in particular are still needed because user login data from 1.3 will be wiped out, so you will need to create a new account and make it a site administrator.

If you run into any issues, please submit a bug on the issues tab - you may not be the only one to run into that issue!