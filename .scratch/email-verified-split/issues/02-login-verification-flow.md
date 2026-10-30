# 02: Login, Email Verification and resend use the new switches

**What to build:** Move the server flows onto `emailVerified`, and let an Email Unverified user with a correct password get a new email when the Email Verification Key has expired. See `../spec.md`.

**Blocked by:** 01

**Status:** ready-for-agent

- [ ] Registration creates the user with `emailVerified = 0`, `enabled = 1`, and an Email Verification Key. The email links to that key
- [ ] `IsVerificationValid` and `VerifyUser` use the email verification columns (the item below renames them). `VerifyUser` sets `emailVerified = 1`, clears the Email Verification Key, and never writes `enabled`. A Disabled user who follows the email link stays Disabled
- [ ] `UpdateUserProfile`: a new email address sets `emailVerified = 0`, issues an Email Verification Key, sends the email, and never writes `enabled`
- [ ] Login checks the password first (`checkPostAuth`, or an equivalent). A wrong password or unknown email gives the generic failure and sends no email
- [ ] Correct password and `enabled = 0` gives the generic failure message, even when `emailVerified = 0` too
- [ ] Correct password, `enabled = 1`, `emailVerified = 0` gives the failure message "Your account email is still unverified. Please click the link in your email to continue." (`LoginFailureHandler` currently always returns "Invalid credentials.". Change it for this case only. The bare "unverified" in this message is the product owner's decision and is an exception to the glossary rule)
- [ ] In that case, if `emailVerificationKeyCreated` is older than `VERIFY_LINK_TTL_SECONDS` (or the key is missing), issue a new Email Verification Key and send a new email. The response says a new link was sent. If the key is fresh, send no email
- [ ] `UserProvider` drops the session when the user is no longer enabled or no longer Email Verified (this ends the session after an email change)
- [ ] `/lostPassword` still refuses `emailVerified = 0` and `enabled = 0` accounts
- [ ] `UsersInvitationService` creates users with `emailVerified = 1` and `enabled = 1`. The invite flow (`/inviteSetup`, `/verifyToken`, `/resetPassword`) still works
- [ ] The Admin user endpoints write `enabled` only. They do not change `emailVerified`
- [ ] No code path other than the Admin endpoints writes `enabled`
- [ ] Functional tests in `tests/Functional/AuthenticationTest.php` cover: register, follow the email link, then log in; Email Unverified with a fresh key (message, no email); Email Unverified with an expired key (message, new email, new link makes the user Email Verified); wrong password gets no email and no hint; Disabled and Email Unverified gets the generic message; email change ends the session and blocks login until the user is Email Verified; a Disabled user stays Disabled after following the email link; a Reset does not touch the Email Verification Key
- [ ] Make every variable, method, property, class, test name and code comment in the server code follow `GLOSSARY.md`. Use "Email Verified", "Email Unverified" and "Email Verification Key" in full, and "Reset" and "Reset Key" for the password flow. Remove bare "verified", "unverified" and "verification". This includes the `LoginUserValidator` locals (`$verificationToken`, `$rawVerificationToken`), `IsVerificationValid`, `CreateUnverifiedNewUser`, the service comments, and the request field `_verificationToken` (change it together with the client if you rename it). Search `src/` and `tests/` for `verif` to find them. User-facing strings are in ticket 03
- [ ] Update `PHP8Rewrite.md` and the "Known Compromises" text where they describe the old behavior. Tell the reviewer about ticket 05 in `phprewrite-review` (see Comments)

## Comments

- `phprewrite-review/issues/05-unverified-accounts-recover-through-reset.md` asks for the opposite design (a Reset enables an Email Unverified account). This design replaces it. Ticket 05 is already marked `wontfix`.
