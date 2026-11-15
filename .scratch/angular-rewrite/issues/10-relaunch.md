# 10: Relaunch

**What to build:** The checklist that ends with the public HootDraft instance live again. See `AngularRewrite.md`, Section 3, phase 10.

**Blocked by:** 09

**Status:** ready-for-human

- [ ] nginx sends a Content Security Policy and the other security headers. The E2E suite passes with them
- [ ] CI runs `composer audit` and `npm audit` and fails on a known vulnerability
- [ ] A deploy to a staging host with Deployer. The PHP rewrite did not test the deploy on a real server
- [ ] Manual check on real Safari (macOS and iOS)
- [ ] Manual check of registration with the real reCAPTCHA keys
- [ ] Manual check of real outgoing email (Email Verification Key and Reset Key)
- [ ] Production deploy
