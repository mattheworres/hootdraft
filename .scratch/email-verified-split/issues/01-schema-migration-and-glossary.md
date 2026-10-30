# 01: Schema and glossary for email verified

**What to build:** Add the new columns, rename the reset columns, migrate existing rows, and add the glossary terms. The code must still read and write the renamed columns correctly. See `../spec.md`.

**Blocked by:** none

**Status:** ready-for-agent

**Ship note:** Do not release this ticket alone. After the migration, mid-verification users have `emailVerified = 0` and `enabled = 1`. Until ticket 02 lands, nothing blocks them at login. Release 01 and 02 together.

- [ ] A phinx migration adds `users.emailVerified` (boolean, not null), `emailVerificationKey` and `emailVerificationKeyCreated` (nullable `datetime`, same type as the old pair)
- [ ] The same migration renames `verificationKey` to `resetKey` and `verificationKeyCreated` to `resetKeyCreated`
- [ ] The migration fills the new columns by the rules in the spec (three row cases). It has a working `down`
- [ ] `LoginUser`, `LoginUserRepository` (queries, `Create`, `Update`, `VerificationMatches` and the code that strips secrets), `LoginUserValidator` and `LoginUserService` use the `resetKey` names. `LoginSuccessHandler` comments are correct
- [ ] `LoginUser` and the repository read and write `emailVerified`, `emailVerificationKey` and `emailVerificationKeyCreated`. Admin-only user lists do not return the new key fields
- [ ] `GLOSSARY.md` has the terms Email Verified / Email Unverified and Enabled / Disabled, with _Avoid_ lines
- [ ] A migration test or functional test checks each of the three row cases
- [ ] Existing tests pass after the rename
