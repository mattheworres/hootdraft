# 03: Client warns before an email change and shows the unverified message

**What to build:** Tell a Commissioner what an email change does before they submit it, and show the new unverified-login message clearly. See `../spec.md`.

**Blocked by:** 02

**Status:** ready-for-agent

- [ ] In the profile form (`app/features/authentication/editProfile.component.*`), when the email field differs from the saved email, the form shows a warning before submit. The warning says the Commissioner will 1) be logged out and 2) must verify the new email address before they can log in again
- [ ] The warning is absent when the email is unchanged
- [ ] The login form shows the unverified message from the server as it is. It does not replace it with a generic text
- [ ] The login form shows the generic failure for a Disabled user, as now
- [ ] After a profile save with a new email, the client handles the ended session in the same way as it does now for a password change (no stale logged-in state)
- [ ] Rebuild `js/` output only if the repo commits it; follow how earlier client tickets did this
