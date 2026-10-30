# 03: Client warns before an email change and shows the Email Unverified message

**What to build:** Tell a Commissioner what an email change does before they submit it, and show the new login message for an Email Unverified user clearly. See `../spec.md`.

**Blocked by:** 02

**Status:** ready-for-agent

- [ ] In the profile form (`app/features/authentication/editProfile.component.*`), when the email field differs from the saved email, the form shows a warning before submit. The warning says the Commissioner will 1) be logged out and 2) must make the new email address Email Verified (by following the emailed link) before they can log in again
- [ ] The warning is absent when the email is unchanged
- [ ] The login form shows the Email Unverified message from the server as it is. It does not replace it with a generic text
- [ ] The login form shows the generic failure for a Disabled user, as now
- [ ] After a profile save with a new email, the client handles the ended session in the same way as it does now for a password change (no stale logged-in state)
- [ ] Change every user-facing string to follow `GLOSSARY.md`: use "Email Verified", "Email Unverified" and "Email Verification Key" in full, and "Reset" for the password flow. Remove bare "verified", "unverified", "verification" and "verify". Check server strings (service and validator error messages, for example "Verification token invalid." and "This account hasn't been verified yet."), the email templates and email subjects (`templates/`, for example `VerifyEmail.html`, `ReverifyEmail.html` and `ResetPassword.html`), and all client text (`app/`). Search for `verif` in each place to find them. Update the tests that assert these strings. One exception: keep the login message "Your account email is still unverified. Please click the link in your email to continue." exactly as it is, with the bare "unverified". This is the product owner's decision
