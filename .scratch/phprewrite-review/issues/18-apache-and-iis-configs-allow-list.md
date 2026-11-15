# 18: Remove Apache and IIS configs, keep nginx up to date

**What to build:** Previously, Apache and IIS webservers were supported with example configuration files that users could go with.

Rather than attempt to continue supporting them (which would require more effort to validate them), let's just drop them until we have a need for them. IIS in particular would be difficult to support.

We'll keep nginx up to date because that's what I use.

**Blocked by:** None (can start immediately).

**Status:** completed

- [x] `.htaccess` and `web.config` are removed
- [x] `EXAMPLE_nginx` matches the Docker nginx allow list
