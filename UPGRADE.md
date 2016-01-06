# Upgrading

If you are running PHP Draft version 1.3.0 currently, you can upgrade to version 2.0.0

**NOTE**: Backup all site files and data before proceeding with the upgrade. This has been tested, but not thoroughly, so you will want to protect yourself in case your data is corrupted or your server run version 2. You have been warned.

After following steps 1, 4 through 8 in README.md, open `/api/Domain/Models/Migrations/2.0.0/Up.sql` and ensure line 5 references your database name.

Execute `Up.sql` on your database.

If your installation has any NFL drafts that use extended rosters, refer to the commented out commands on line 36 of `Up.sql`

Additionally, your dates and times will be off as version 2 switched to using UTC dates by default. If you are aware of the timezone you used in 1.3, use the SQL contained in lines 99 - 116.

If you run into any issues, please submit a bug on the issues tab - you may not be the only one to run into that issue!