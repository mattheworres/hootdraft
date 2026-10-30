# Email verified is separate from enabled

## Problem

The `users.enabled` flag has three meanings: the email is not verified yet, the Commissioner changed their email and must verify again, and an Admin disabled the user. One message ("Account is not enabled.") covers all three. The verification token and the reset token share one column pair, `verificationKey` and `verificationKeyCreated`. Because of this:

- A lost password request cannot be allowed for an unverified account. A new token would overwrite the verification token.
- A user whose verification link expired (48 hours) cannot get a new one. The account is stuck. (Ticket 05 in `phprewrite-review` describes the symptom.)

## Decisions

- **Two switches.** `emailVerified` is set by the user when they follow the emailed link. `enabled` is set only by an Admin.
- **Two token pairs.** `emailVerificationKey` + `emailVerificationKeyCreated` for email verification. `resetKey` + `resetKeyCreated` for password reset. The old `verificationKey` pair is renamed to `resetKey`. `*KeyCreated` is a UTC `datetime`. A token is fresh for 48 hours (`VERIFY_LINK_TTL_SECONDS`).
- **Login.** The system checks the password first. It acts only on a correct password (no account enumeration).
  - `enabled = 0`: generic failure message. This wins over the unverified case.
  - `emailVerified = 0`: the message "Your account is still unverified. Please click the link in your email to continue."
  - If the user is unverified and the email token is expired, the system also issues a new token and sends a new email. If the token is fresh, it sends no email.
- **No rate limiting.** The expired-token rule and the correct-password rule limit the email volume. `symfony/rate-limiter` is not installed, so rate limiting would be scope creep.
- **Lost password.** It still refuses a user with `emailVerified = 0` or `enabled = 0`. A reset email must not stand in for email verification.
- **Email change.** It sets `emailVerified = 0`. It never writes `enabled`. The session ends, because the session check also requires `emailVerified`. The client warns the Commissioner before submit.
- **Invited users** are created with `emailVerified = 1` and `enabled = 1`.
- **Migration.**
  - `enabled = 1` → `emailVerified = 1`.
  - `enabled = 0` with a pending `verificationKey` → `emailVerified = 0`, `enabled = 1`, and the key moves to the email columns.
  - `enabled = 0` without a key → `emailVerified = 1`, `enabled = 0`.
  - An Admin-disabled user who has a pending reset key is misread as mid-verification. We accept this. The user stays blocked until they verify.

## Glossary terms to add

- **Email Verified / Email Unverified**: whether the user followed the emailed link for their current address. _Avoid_: confirmed, active.
- **Enabled / Disabled**: the Admin's switch. A Disabled user cannot log in. _Avoid_: blocked, active, inactive.

## Tickets

1. `01-schema-migration-and-glossary` — columns, rename, migration, glossary
2. `02-login-verification-flow` — server behavior (blocked by 01; ship with 01)
3. `03-client-unverified-messages` — client warning and message (blocked by 02)
