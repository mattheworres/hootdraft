# 03: Client warns before an email change and shows the Email Unverified message

**What to build:** Tell a Commissioner what an email change does before they submit it, and show the new login message for an Email Unverified user clearly. See `../spec.md`.

**Blocked by:** 02

**Status:** completed

- [x] In the profile form (`app/features/authentication/editProfile.component.*`), when the email field differs from the saved email, the form shows a warning before submit. The warning says the Commissioner will 1) be logged out and 2) must make the new email address Email Verified (by following the emailed link) before they can log in again
- [x] The warning is absent when the email is unchanged
- [x] The login form shows the Email Unverified message from the server as it is. It does not replace it with a generic text
- [x] The login form shows the generic failure for a Disabled user, as now
- [x] After a profile save with a new email, the client handles the ended session in the same way as it does now for a password change (no stale logged-in state)
- [x] Change every user-facing string to follow `GLOSSARY.md`: use "Email Verified", "Email Unverified" and "Email Verification Key" in full, and "Reset" for the password flow. Remove bare "verified", "unverified", "verification" and "verify". Check server strings (service and validator error messages, for example "Verification token invalid." and "This account hasn't been verified yet."), the email templates and email subjects (`templates/`, for example `VerifyEmail.html`, `ReverifyEmail.html` and `ResetPassword.html`), and all client text (`app/`). Search for `verif` in each place to find them. Update the tests that assert these strings. One exception: keep the login message "Your account email is still unverified. Please click the link in your email to continue." exactly as it is, with the bare "unverified". This is the product owner's decision
- [x] Move the client onto the renamed API paths from ticket 02. Release of the server and the client will occur together, as both require rewrites/ports.
  - `POST /verify` is now `POST /emailVerification` (body `_email`, `_emailVerificationKey`). `GET /verifyToken` is now `GET /checkResetKey` (query `_email`, `_resetKey`). Change them in `app/features/shared/factories/modelApi.factory.js` and rename the `verify` and `verifyResetToken` API calls and service methods to match.
  - The request fields were already renamed in ticket 02: `_emailVerificationKey` (verify account) and `_resetKey` (`/checkResetKey`, `/resetPassword`, invite setup).
  - The emailed link still points at the client route `/verify/:email/:key` (`LoginUserService::createEmailVerificationLink`, `authentication.routes.js`, `pathHelper.service.js`). If you rename that route, change the server link in the same release. Links already sent keep the old route for up to 48 hours.
  - Login can now also answer "Your account email is still unverified. Your link expired and we could not send a new one. Please try again later." (a resend email failed). Show it as it is, like the other Email Unverified messages.
