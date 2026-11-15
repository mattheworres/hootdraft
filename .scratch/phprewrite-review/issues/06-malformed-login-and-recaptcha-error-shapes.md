# 06: Bad login requests and a missing reCAPTCHA secret get the old error shapes

**What to build:** The login and registration forms always get a response the AngularJS client can show (its `parseValidationErrorsFromResponse` reads `errors`).

- A `/login` body with a missing or non-string `_email` / `_password` currently gets Symfony's `json_login` 400 `{"error":"The key \"_email\" must be provided."}`. The develop branch returned `{"success":false,"errors":["Invalid credentials."]}`
- A `/login` POST that isn't JSON reaches the controller's placeholder action and becomes a 500
- An empty `RECAPTCHA_SECRET` outside debug makes the reCAPTCHA library throw ("No secret provided"), so register and lost password answer 500 "An unexpected error occurred." The code comment says it "just fails every verification"

**Blocked by:** 01

**Status:** completed

- [x] A login with a missing field, an empty field or a non-JSON body answers like a wrong password: same status and `{"success":false,"errors":[…]}` shape as the existing failure handler
- [x] No `/login` request returns 500
- [x] With an empty `RECAPTCHA_SECRET` and `APP_DEBUG=0`, register and lost password fail the captcha check with the normal validation error and log the misconfiguration
- [x] The debug-mode skip (empty secret and `APP_DEBUG=1`) is unchanged
- [x] Functional tests cover each case

## Comments

**Agent (implementation):**

- A `/login` request with a bad JSON body now answers 400 `{"success":false,"errors":["Invalid credentials."]}`. A bad JSON body has a missing, null or non-string field, or is a list, invalid JSON or empty.
- `JsonRequestListener` or `json_login` throws a `BadRequestHttpException` for a bad JSON body.
- A request with a non-JSON content type goes to `AuthenticationController::login`. The controller also throws a `BadRequestHttpException`.
- On the login path, `MalformedLoginListener` catches each `BadRequestHttpException` and gives the login failure response. This listener is the only place that makes this decision.
- An empty `RECAPTCHA_SECRET` with debug off returns `recaptcha-failed`. Each check also logs a critical message. The debug skip did not change.
- `tests/bootstrap.php` deletes `var/cache/test` at the start of each run. The non-debug test container is cached, and Symfony does not check if it is current.
- `config/packages/monolog.yaml` adds an in-memory `test` handler. The tests read the log records from it.
- Not changed: `GET /login` (405), `POST /login/` (404) and a request without `X-Requested-With` (403) still get `{"error": …}`. These are not 500s, and the client never sends these requests.
- `phpunit.xml` forces an empty `RECAPTCHA_SECRET`, so a secret in the CI environment cannot change the reCAPTCHA tests.
