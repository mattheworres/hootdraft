# 02: Login, verification and resend use the new switches

**What to build:** Move the server flows onto `emailVerified`, and let an unverified user with a correct password get a new verification email when the old token has expired. See `../spec.md`.

**Blocked by:** 01

**Status:** ready-for-agent

- [ ] Registration creates the user with `emailVerified = 0`, `enabled = 1`, and an email verification token. The verification email links to that token
- [ ] `IsVerificationValid` and `VerifyUser` use the email verification columns. `VerifyUser` sets `emailVerified = 1`, clears the email token, and never writes `enabled`. A Disabled user who verifies stays Disabled
- [ ] `UpdateUserProfile`: a new email address sets `emailVerified = 0`, issues an email verification token, sends the email, and never writes `enabled`
- [ ] Login checks the password first (`checkPostAuth`, or an equivalent). A wrong password or unknown email gives the generic failure and sends no email
- [ ] Correct password and `enabled = 0` gives the generic failure message, even when `emailVerified = 0` too
- [ ] Correct password, `enabled = 1`, `emailVerified = 0` gives the failure message "Your account is still unverified. Please click the link in your email to continue." (`LoginFailureHandler` currently always returns "Invalid credentials.". Change it for this case only)
- [ ] In that case, if `emailVerificationKeyCreated` is older than `VERIFY_LINK_TTL_SECONDS` (or the key is missing), issue a new token and send a new verification email. The response says a new link was sent. If the token is fresh, send no email
- [ ] `UserProvider` drops the session when the user is no longer enabled or no longer email verified (this ends the session after an email change)
- [ ] `/lostPassword` still refuses `emailVerified = 0` and `enabled = 0` accounts
- [ ] `UsersInvitationService` creates users with `emailVerified = 1` and `enabled = 1`. The invite flow (`/inviteSetup`, `/verifyToken`, `/resetPassword`) still works
- [ ] The Admin user endpoints write `enabled` only. They do not change `emailVerified`
- [ ] No code path other than the Admin endpoints writes `enabled`
- [ ] Functional tests in `tests/Functional/AuthenticationTest.php` cover: register then verify then log in; unverified with a fresh token (message, no email); unverified with an expired token (message, new email, new link verifies); wrong password gets no email and no hint; Disabled and unverified gets the generic message; email change ends the session and blocks login until verified; a Disabled user cannot be enabled by verifying; reset does not touch the email token
- [ ] Update `PHP8Rewrite.md` and the "Known Compromises" text where they describe the old behavior. Tell the reviewer about ticket 05 in `phprewrite-review` (see Comments)

## Comments

- `phprewrite-review/issues/05-unverified-accounts-recover-through-reset.md` asks for the opposite design (reset enables an unverified account). This design replaces it. Ticket 05 is already marked `wontfix`.
