# 06: Bad login requests and a missing reCAPTCHA secret get the old error shapes

**What to build:** The login and registration forms always get a response the AngularJS client can show (its `parseValidationErrorsFromResponse` reads `errors`).
- A `/login` body with a missing or non-string `_email` / `_password` currently gets Symfony's `json_login` 400 `{"error":"The key \"_email\" must be provided."}`. Master returned `{"success":false,"errors":["Invalid credentials."]}`
- A `/login` POST that isn't JSON reaches the controller's placeholder action and becomes a 500
- An empty `RECAPTCHA_SECRET` outside debug makes the reCAPTCHA library throw ("No secret provided"), so register and lost password answer 500 "An unexpected error occurred." The code comment says it "just fails every verification"

**Blocked by:** 01

**Status:** ready-for-agent

- [ ] A login with a missing field, an empty field or a non-JSON body answers like a wrong password: same status and `{"success":false,"errors":[…]}` shape as the existing failure handler
- [ ] No `/login` request returns 500
- [ ] With an empty `RECAPTCHA_SECRET` and `APP_DEBUG=0`, register and lost password fail the captcha check with the normal validation error and log the misconfiguration
- [ ] The debug-mode skip (empty secret and `APP_DEBUG=1`) is unchanged
- [ ] Functional tests cover each case
