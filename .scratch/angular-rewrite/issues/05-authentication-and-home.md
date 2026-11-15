# 05: Authentication and home

**What to build:** The authentication screens and the home page in the new client. See `AngularRewrite.md`, Section 3, phase 5.

**Blocked by:** 04

**Status:** ready-for-agent

**Note:** One pull request for each route group of the inventory. Split this ticket by route group when the phase starts.

- [ ] Login, register (with reCAPTCHA at its current version), the email link screen (`/verify`), lost password, Reset, invite setup, profile
- [ ] The messages for an Email Unverified user and for a Disabled user match the API behavior
- [ ] Home page: the Draft table with sort, the filter dropdown and typeahead, the reset button, and the saved table state
- [ ] The HTML layout matches the AngularJS templates, except for Angular-specific details
- [ ] Unit tests for store logic
- [ ] The inventory rows of each route group pass on the new client in four browsers and are marked as migrated
- [ ] Each pull request has the screenshot report
