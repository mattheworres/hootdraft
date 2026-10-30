# 05: Unverified accounts can recover through a password reset

**What to build:** A user whose verification link has expired can get back in by resetting their password, as Known Compromises already promises: "Completing a password reset also enables the account". Today verification keys expire after 48 hours (master had no expiry). `/lostPassword` refuses unverified accounts and `/resetPassword` refuses disabled ones. There is no resend, and the address can't be registered again, so the account is stuck for good. This affects a new user who misses the window, and a Commissioner who changes their email in their profile (which disables the account) and misses the link.

**Blocked by:** 01

**Status:** wontfix

- [ ] `/lostPassword` sends a reset email to an unverified (disabled) account, the same as to an enabled one
- [ ] Completing `/resetPassword` enables the account and logs the user in
- [ ] An invited user's flow (`/inviteSetup` via `/verifyToken` and `/resetPassword`) still works
- [ ] Login still refuses an unverified account with the generic failure message
- [ ] Functional tests cover: register, let the verification key expire, request a reset, complete it, then log in successfully. The same for the "changed email in profile" case

## Comments

- Superseded by `.scratch/email-verified-split/`. Letting a password reset enable an unverified account was the wrong fix. The new design separates `emailVerified` from `enabled`, adds a second token column pair, and resends the verification email when an unverified user logs in with the correct password and the old token has expired. See `.scratch/email-verified-split/spec.md`.
