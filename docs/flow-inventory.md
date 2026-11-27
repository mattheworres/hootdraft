# Flow inventory

This page defines "100% of flows" for the Angular rewrite. See `AngularRewrite.md`, Section 2.3, item 1. Each row is one flow of the AngularJS client in `app/`. Phase 2 writes one E2E test for each row.

## How to read this page

### Source

Each row comes from the code in `app/features/`: the route files, components, directives, templates, and shared services. Where a result depends on the API, the API code in `src/` was read too. No row comes from a running client. Phase 2 confirms each row when it writes the test. A result that the code does not settle has the note "(to confirm)".

### Columns

| Column | Content |
| --- | --- |
| ID | Route group prefix and a number. An ID never changes after approval. |
| Route | The client URL. "All" means each route. |
| Viewer | Who uses the client. See the next section. |
| Starting state | What exists before the steps. Test setup creates it through the API. |
| Steps | What the viewer does. |
| Expected result | What the AngularJS client does today. "See W-nn" points to an item in the list at the end of this page. |
| Test file | Empty. Phase 2 fills it. |
| Passes on AngularJS | Empty. Phase 2 fills it. |
| Migrated | Empty. Phases 5 to 8 fill it. |
| Known defect | Empty. Filled when the maintainer approves an item of the list at the end of this page for the row. |

### Viewers

- **anonymous**: no login.
- **Commissioner**: logged in. On a Draft route, the Commissioner of that Draft.
- **another Commissioner**: logged in, but not the Commissioner of the Draft.
- **Admin**: logged in with the Admin role. The API gives an Admin the Commissioner rights on each Draft, so each Commissioner row also holds for an Admin. An Admin row exists only where the screen differs or where the Admin acts on the Draft of a different user.
- **any**: the screen is the same for each viewer.
- **two viewers**: a multi-viewer flow. The first viewer acts. The second viewer has the screen open and does not reload.

A row exists for a viewer or a Draft status only where the screen changes.

### Conventions

- A "toast" is a toast message. A title is given only where the client sets one.
- The "working modal" is the "Working . . ." dialog with a progress bar.
- A "confirm prompt" is the dialog with **Cancel** and one action button.
- "Previous route" means the result of `pathHelperService.sendToPreviousPath()`. Today this is `/home` in most cases. See W-06.
- "API error" means a response that the flow does not expect, such as a 500 or a lost connection. Tests create it with a request intercept.
- Each text input has `maxlength="255"`, so the "... is too long" messages in the templates cannot appear. They have no rows.
- The auth forms have no `novalidate` attribute. The browser can show its own message when the viewer selects the submit button. Tests check the messages of the client, which appear after the field loses focus.
- Polling: the client asks the API for the Draft each 2.75 seconds, and only while the Draft is In Progress and not locked by a Draft Password. A change on a second screen appears after the next poll.

### Route groups

Each group is one pull request of phases 5 to 8.

| Phase | Group | Prefixes | Routes |
| --- | --- | --- | --- |
| 5 | 5A Login, session, and navigation | LOGIN, NAV, INV | `/login`, all |
| 5 | 5B Register and email link | REG, VERIFY | `/register`, `/verify/:email?/:token?` |
| 5 | 5C Lost password, Reset, and invite setup | LOST, RESET, INVITE | `/lostPassword`, `/resetPassword/:email?/:token?`, `/inviteSetup/:email?/:token?` |
| 5 | 5D Profile | PROFILE | `/profile` |
| 5 | 5E Home | HOME | `/`, `/home` |
| 6 | 6A Draft loading, Draft Password, and Draft navigation | DRAFT, PW | each route with `:draft_id` |
| 6 | 6B Draft index | INDEX | `/draft/:draft_id` |
| 6 | 6C Draft Board | BOARD | `/draft/:draft_id/board` |
| 6 | 6D Picks by Round, Picks by Manager, and search | ROUND, MANAGER, SEARCH | `/draft/:draft_id/picks/round`, `/draft/:draft_id/picks/manager`, `/draft/:draft_id/search` |
| 6 | 6E Trades and Draft Stats | TRADES, STATS | `/draft/:draft_id/trades`, `/draft/:draft_id/stats` |
| 6 | 6F Depth Chart | DEPTH | `/draft/:draft_id/depth_chart` |
| 7 | 7A Create and edit a Draft | CREATE, EDIT | `/commish/draft/create`, `/commish/draft/:draft_id/edit` |
| 7 | 7B Managers and Draft status changes | MGR, STATUS | `/draft/:draft_id` (Commissioner parts) |
| 7 | 7C Round Times | TIMES | `/commish/draft/:draft_id/timers` |
| 7 | 7D Add and edit a Pick | ADDPICK, EDITPICK | `/commish/draft/:draft_id/picks/add`, `/commish/draft/:draft_id/picks/edit` |
| 7 | 7E Add a Trade | ADDTRADE | `/commish/draft/:draft_id/trades/add` |
| 8 | 8A Users | USERS | `/admin/users` |
| 8 | 8B Pro Player upload | PRO | `/admin/proplayers` |
| 8 | 8C Regenerate Draft Stats | ADMSTATS | `/admin/stats` |

The 26 client URLs are all in this table.

### Cross-cutting rows

These rows are inside their route groups. This index lists them by kind.

- **Multi-viewer flows:** PW-11, INDEX-12 to INDEX-17, BOARD-13 to BOARD-18, ROUND-06, MANAGER-06, SEARCH-10, TRADES-05, DEPTH-10, NAV-06, USERS-11.
- **Guards, a Commissioner route without a login:** PROFILE-09, CREATE-12, EDIT-09, TIMES-11, ADDPICK-13, EDITPICK-09, ADDTRADE-09.
- **Guards, a Commissioner route of a Draft of another Commissioner:** EDIT-10, TIMES-12, ADDPICK-14, EDITPICK-10, ADDTRADE-10.
- **Guards, an Admin route:** USERS-12, USERS-13, PRO-07, PRO-08, ADMSTATS-07, ADMSTATS-08.
- **Redirects for a logged-in viewer on an auth screen:** LOGIN-13, REG-09, VERIFY-04, LOST-05, RESET-06, INVITE-06.
- **Redirects for a Draft status:** BOARD-01, ROUND-01, MANAGER-01, SEARCH-01, TRADES-01, STATS-01, STATS-02, DEPTH-01, TIMES-06, ADDPICK-09, ADDPICK-10, EDITPICK-05, ADDTRADE-06, ADDTRADE-07.
- **Session expiry (401):** NAV-06, NAV-07, EDITPICK-07, ADDTRADE-08.
- **Wrong Draft Password:** PW-03.
- **Draft not found (404):** DRAFT-11.
- **Forbidden (403), a Commissioner on an Admin route:** USERS-13, PRO-08, ADMSTATS-08.

## Phase 5: authentication and home

### 5A Login, session, and navigation

| ID | Route | Viewer | Starting state | Steps | Expected result | Test file | Passes on AngularJS | Migrated | Known defect |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| LOGIN-01 | `/login` | anonymous | None | Open the route. | Heading "Commissioner Login". Fields **Email** and **Password**, the **Show** switch, and **Login**. Link **Lost password?** goes to `/lostPassword`. Link **Create a new account!** goes to `/register`. See W-35. | `e2e/tests/5a-login.spec.ts` | No |  | W-35 |
| LOGIN-02 | `/login` | anonymous | An Enabled, Email Verified user. The viewer opened `/login` directly. | Enter the email and password. Select **Login**. | The working modal shows and closes. Toast "Welcome back, (name)!" with the title "Logged In". The route is `/home`. The navigation shows the name and **Log Out**. | `e2e/tests/5a-login.spec.ts` | Yes |  |  |
| LOGIN-03 | `/login` | anonymous | The viewer opened a Draft screen, then selected **Commissioner Login**. | Log in. | The client goes to the last route that is not an auth route: the Draft screen. Today the client goes to `/home` unless the viewer opened four or more routes first. See W-06. | `e2e/tests/5a-login.spec.ts` | No |  | W-06 |
| LOGIN-04 | `/login` | Commissioner | The viewer has a Draft. The viewer opened two screens of that Draft without a login (the Draft Board, then the Draft index), then selected **Commissioner Login**. | Log in. | The client returns to the Draft index. The Draft loads again, and the Commissioner actions show. Today the client goes to `/home`. See W-06. | `e2e/tests/5a-login.spec.ts` | No |  | W-06 |
| LOGIN-05 | `/login` | anonymous | A user exists. | Enter the email and a wrong password. Select **Login**. | Toast "Invalid credentials." with the title "Unable to Login". The route stays `/login`. | `e2e/tests/5a-login.spec.ts` | Yes |  |  |
| LOGIN-06 | `/login` | anonymous | A Disabled user. | Enter the correct email and password. Select **Login**. | The same toast as LOGIN-05. | `e2e/tests/5a-login.spec.ts` | Yes |  |  |
| LOGIN-07 | `/login` | anonymous | An Email Unverified user. The Email Verification Key is still valid. | Enter the correct email and password. Select **Login**. | Toast "Your account email is still unverified. Please click the link in your email to continue." with the title "Unable to Login". | `e2e/tests/5a-login.spec.ts` | Yes |  |  |
| LOGIN-08 | `/login` | anonymous | An Email Unverified user. The Email Verification Key is expired. | Enter the correct email and password. Select **Login**. | Toast "Your account email is still unverified. Your link expired, so we sent a new one. Please click the link in your email to continue." A new email arrives. | `e2e/tests/5a-login.spec.ts` | Yes |  |  |
| LOGIN-09 | `/login` | anonymous | None | Leave **Email** empty and move the focus away. Then enter "abc" and move the focus away. | "Email is missing", then "Email is invalid". | `e2e/tests/5a-login.spec.ts` | Yes |  |  |
| LOGIN-10 | `/login` | anonymous | None | Leave **Password** empty and move the focus away. Then enter 7 characters and move the focus away. | "Password is missing", then "Password is too short - 8 characters minimum". | `e2e/tests/5a-login.spec.ts` | Yes |  |  |
| LOGIN-11 | `/login` | anonymous | None | Enter a password. Turn on **Show**. | The password shows as plain text. Turn the switch off and the password is hidden. | `e2e/tests/5a-login.spec.ts` | Yes |  |  |
| LOGIN-12 | `/login` | anonymous | None | Select **Login** with an invalid form. | No request is sent. | `e2e/tests/5a-login.spec.ts` | Yes |  |  |
| LOGIN-13 | `/login` | Commissioner | Logged in. The viewer opened a Draft screen, then `/login`. | Open `/login`. | Toast "Already logged in as (name)." with the title "Logged In". The client goes to the last route that is not an auth route: the Draft screen. Today the client goes to `/home`. See W-06. | `e2e/tests/5a-login.spec.ts` | No |  | W-06 |
| LOGIN-14 | `/login` | anonymous | The login request gives an API error with status 500. | Log in. | Toast "Whoops! We hit a snag - looks like it's on our end (500)" with the title "Unable to Login". | `e2e/tests/5a-login.spec.ts` | Yes |  |  |
| NAV-01 | All | anonymous | None | Open `/home`. | The navigation shows the logo, which links to `/home`, and **Commissioner Login**, which links to `/login`. It does not show **New League**, **Invite A Friend**, or **Admin Stuff**. | `e2e/tests/5a-login.spec.ts` | Yes |  |  |
| NAV-02 | All | Commissioner | Logged in. | Open `/home`. | The navigation shows **New League** (to `/commish/draft/create`), **Invite A Friend**, the name as a link to `/profile` with the text "logged in", and **Log Out**. It does not show **Admin Stuff**. | `e2e/tests/5a-login.spec.ts` | Yes |  |  |
| NAV-03 | All | Admin | Logged in. | Open the **Admin Stuff** menu. | The menu shows **Users** (to `/admin/users`), **Player Data** (to `/admin/proplayers`), and **Draft Stats** (to `/admin/stats`). | `e2e/tests/5a-login.spec.ts` | Yes |  |  |
| NAV-04 | All | Commissioner | Logged in. | Select **Log Out**. | Toast "Logged Out". The route is `/home`. The navigation is the same as NAV-01. After a reload, the viewer is still logged out. | `e2e/tests/5a-login.spec.ts` | Yes |  |  |
| NAV-05 | All | Commissioner | Logged in in one tab. | Open a route in a new tab. | The new tab shows the logged-in navigation. | `e2e/tests/5a-login.spec.ts` | Yes |  |  |
| NAV-06 | All | two viewers | A Commissioner is logged in on a screen. | An Admin makes the user Disabled. The Commissioner does something that calls the API, for example opens `/profile`. | The Commissioner sees the toast "Your login has expired. You must log back in if you wish to continue." The route is `/login`. The navigation is the same as NAV-01. | `e2e/tests/5a-login.spec.ts` | Yes |  |  |
| NAV-07 | All | Commissioner | Logged in. The session is removed on the server. | Reload the page. | The same toast and route as NAV-06. | `e2e/tests/5a-login.spec.ts` | Yes |  |  |
| NAV-08 | All | any | None | Open `/home`, then a Draft Board, then a Depth Chart. | The footer shows the link "HootDraft v2.4.0" ("HootDraft v3.0.0" on the new client), the link "GNU GPL3 License", and "2010 – (current year) by Matthew Orres". The footer is hidden on the Draft Board and the Depth Chart. See W-29. | `e2e/tests/5a-login.spec.ts` | Yes |  |  |
| NAV-09 | All | any | A phone-width window. A Commissioner is logged in. | Select the menu button. Select a link: **New League**, the name, the logo, or **Log Out**. | The menu opens. After the route change, the menu is closed for each link. Today the menu stays open after the name, the logo, and **Log Out**. See W-36. | `e2e/tests/5a-login.spec.ts` | No |  | W-36 |
| NAV-10 | Unknown URL | any | None | Open `/nothing-here`. | The client goes to `/home`. Today the navigation and footer show with an empty screen between them. See W-05. | `e2e/tests/5a-login.spec.ts` | No |  | W-05 |
| INV-01 | All | Commissioner | Logged in. | Select **Invite A Friend**. | The dialog "Invite New User" opens with **Email**, **Name**, and **Message (optional)**. **Invite** is disabled. | `e2e/tests/5a-login.spec.ts` | Yes |  |  |
| INV-02 | All | Commissioner | The dialog is open. | Enter a new email and a name. Select **Invite**. | Toast "(name) should get an email shortly inviting them to join. Good job!". The dialog closes. An email arrives with a link to `/inviteSetup`. | `e2e/tests/5a-login.spec.ts` | Yes |  |  |
| INV-03 | All | Commissioner | The dialog is open. | Leave **Email** empty and move the focus away. Enter "abc". Leave **Name** empty and move the focus away. | "Email is missing", then "Email is invalid". "Name is missing". **Invite** stays disabled. | `e2e/tests/5a-login.spec.ts` | Yes |  |  |
| INV-04 | All | Commissioner | The dialog is open. | Enter the email of an existing user and a name. Select **Invite**. | Toast "Unable to invite user: (API messages)". The dialog stays open. | `e2e/tests/5a-login.spec.ts` | Yes |  |  |
| INV-05 | All | Commissioner | The dialog is open. | Select **Cancel**. | The dialog closes. No request is sent. | `e2e/tests/5a-login.spec.ts` | Yes |  |  |

### 5B Register and email link

| ID | Route | Viewer | Starting state | Steps | Expected result | Test file | Passes on AngularJS | Migrated | Known defect |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| REG-01 | `/register` | anonymous | None | Open the route. | Heading "Commissioner Registration". Fields **Email**, **Name**, **Password**, and **Confirm**, the **Show** switch, the reCAPTCHA box with the label "Human?", and **Register**. **Register** is disabled. | `e2e/tests/5b-register.spec.ts` | Yes |  |  |
| REG-02 | `/register` | anonymous | None | Fill in each field. Complete the reCAPTCHA. Select **Register**. | The working modal shows and closes. The form is replaced by "Registration Almost Complete!" with the name and email. Toast "We sent you an email to verify your address". An email arrives with a link to `/verify/(email)/(Email Verification Key)`. | `e2e/tests/5b-register.spec.ts` | Yes |  |  |
| REG-03 | `/register` | anonymous | None | Leave **Email** empty and move the focus away. Then enter "abc". | "Email is missing", then "Email is invalid". | `e2e/tests/5b-register.spec.ts` | Yes |  |  |
| REG-04 | `/register` | anonymous | None | Leave **Name** empty and move the focus away. | "Name is missing". | `e2e/tests/5b-register.spec.ts` | Yes |  |  |
| REG-05 | `/register` | anonymous | None | Leave **Password** empty and move the focus away. Then enter 7 characters. | "Password is missing", then "Password is too short - 8 characters minimum". | `e2e/tests/5b-register.spec.ts` | Yes |  |  |
| REG-06 | `/register` | anonymous | None | Leave **Confirm** empty and move the focus away. Then enter a value that differs from **Password**. | "You must confirm your password", then "Passwords do not match." | `e2e/tests/5b-register.spec.ts` | Yes |  |  |
| REG-07 | `/register` | anonymous | A user with the same email or name exists. | Fill in the form and select **Register**. | Toast with the API messages and the title "Unable to register". The reCAPTCHA box resets. The form stays. **Register** is disabled until the reCAPTCHA is complete again. | `e2e/tests/5b-register.spec.ts` | Yes |  |  |
| REG-08 | `/register` | anonymous | The register request gives an API error with status 500. | Fill in the form and select **Register**. | Toast "Whoops! We hit a snag - looks like it's on our end (500)" with the title "Unable to register". | `e2e/tests/5b-register.spec.ts` | Yes |  |  |
| REG-09 | `/register` | Commissioner | Logged in. The viewer opened a Draft screen. | Open the route. | The same toast as LOGIN-13. The client goes to the last route that is not an auth route: the Draft screen. Today the client goes to `/home`. See W-06. | `e2e/tests/5b-register.spec.ts` | No |  | W-06 |
| VERIFY-01 | `/verify/:email/:token` | anonymous | An Email Unverified user with a valid Email Verification Key. | Open the link from the email. | The working modal shows for about 3.5 seconds. Then the route is `/home`, the viewer is logged in, and the toast is "Your email is now verified, and you are now logged in. Welcome!". | `e2e/tests/5b-register.spec.ts` | Yes |  |  |
| VERIFY-02 | `/verify/:email/:token` | anonymous | The Email Verification Key is wrong or was used before. | Open the link. | The panel "Unable to Verify Your Email" shows. A toast shows with the title "Unable to verify your email". | `e2e/tests/5b-register.spec.ts` | Yes |  |  |
| VERIFY-03 | `/verify` | anonymous | None | Open the route with no email and no key. | The same panel and toast title as VERIFY-02. | `e2e/tests/5b-register.spec.ts` | Yes |  |  |
| VERIFY-04 | `/verify/:email/:token` | Commissioner | Logged in. The viewer opened a Draft screen. | Open the route. | The same toast as LOGIN-13. The client goes to the last route that is not an auth route: the Draft screen. Today the client goes to `/home`. No request to the email link API is sent. See W-06. | `e2e/tests/5b-register.spec.ts` | No |  | W-06 |

### 5C Lost password, Reset, and invite setup

| ID | Route | Viewer | Starting state | Steps | Expected result | Test file | Passes on AngularJS | Migrated | Known defect |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| LOST-01 | `/lostPassword` | anonymous | None | Open the route. | Heading "Lost Password". Field **Email**, the reCAPTCHA box, and **Reset Password**. | `e2e/tests/5c-lost-reset-invite.spec.ts` | Yes |  |  |
| LOST-02 | `/lostPassword` | anonymous | A user exists. | Enter the email. Complete the reCAPTCHA. Select **Reset Password**. | The form is replaced by "Reset Password Email Sent" with the email. Toast "Reset password email sent". An email arrives with a link to `/resetPassword/(email)/(Reset Key)`. | `e2e/tests/5c-lost-reset-invite.spec.ts` | Yes |  |  |
| LOST-03 | `/lostPassword` | anonymous | None | Leave **Email** empty and move the focus away. Then enter "abc". | "Email is missing", then "Email is invalid". | `e2e/tests/5c-lost-reset-invite.spec.ts` | Yes |  |  |
| LOST-04 | `/lostPassword` | anonymous | None | Enter an email. Do not complete the reCAPTCHA. | **Reset Password** is disabled, so no request is sent. See W-18. | `e2e/tests/5c-lost-reset-invite.spec.ts` | No |  | W-18 |
| LOST-05 | `/lostPassword` | Commissioner | Logged in. The viewer opened a Draft screen. | Open the route. | The same toast as LOGIN-13. The client goes to the last route that is not an auth route: the Draft screen. Today the client goes to `/home`. See W-06. | `e2e/tests/5c-lost-reset-invite.spec.ts` | No |  | W-06 |
| RESET-01 | `/resetPassword/:email/:token` | anonymous | A user with a valid Reset Key. | Open the link from the email. | "Checking your Reset Key, one sec..." shows first. Then the heading "Reset Password", the email as text, fields **Password** and **Confirm**, the **Show** switch, and **Reset Password**. | `e2e/tests/5c-lost-reset-invite.spec.ts` | Yes |  |  |
| RESET-02 | `/resetPassword/:email/:token` | anonymous | The form of RESET-01. | Enter a new password two times. Select **Reset Password**. | The viewer is logged in. The route is `/home`. Toast "Your password has been set and you've been logged in. Welcome!". The new password works at the next login. | `e2e/tests/5c-lost-reset-invite.spec.ts` | Yes |  |  |
| RESET-03 | `/resetPassword/:email/:token` | anonymous | The Reset Key is wrong or was used before. | Open the link. | The panel "Bad Reset Key" shows with a link to `/lostPassword`. The text says "Sauron was a bad guy. You don't want Sauron to steal your account, do you? We didn't think so." No form shows. See W-32. | `e2e/tests/5c-lost-reset-invite.spec.ts` | No |  | W-32 |
| RESET-04 | `/resetPassword/:email/:token` | anonymous | The form of RESET-01. | Leave **Password** empty and move the focus away. Enter 7 characters. Leave **Confirm** empty and move the focus away. Enter a different value. | "Password is missing", "Password is too short - 8 characters minimum", "You must confirm your password", "Passwords do not match." With an invalid form, **Reset Password** is disabled, so no request is sent. See W-18. | `e2e/tests/5c-lost-reset-invite.spec.ts` | No |  | W-18 |
| RESET-05 | `/resetPassword/:email/:token` | anonymous | The Reset request gives an API error. | Submit the form. | Toast with the title "Unable to Reset Password". The form stays. | `e2e/tests/5c-lost-reset-invite.spec.ts` | Yes |  |  |
| RESET-06 | `/resetPassword/:email/:token` | Commissioner | Logged in. The viewer opened a Draft screen. | Open the route. | The same toast as LOGIN-13. The client goes to the last route that is not an auth route: the Draft screen. Today the client goes to `/home`. See W-06. | `e2e/tests/5c-lost-reset-invite.spec.ts` | No |  | W-06 |
| INVITE-01 | `/inviteSetup/:email/:token` | anonymous | An invited user with a valid Reset Key. | Open the link from the invite email. | The heading "Set Your Password", the email as text, fields **Password** and **Confirm**, the **Show** switch, and **Set Your Password**. | `e2e/tests/5c-lost-reset-invite.spec.ts` | Yes |  |  |
| INVITE-02 | `/inviteSetup/:email/:token` | anonymous | The form of INVITE-01. | Enter a password two times. Select **Set Your Password**. | The viewer is logged in. The route is `/home`. Toast "Your password has been set and you've been logged in. Welcome!". | `e2e/tests/5c-lost-reset-invite.spec.ts` | Yes |  |  |
| INVITE-03 | `/inviteSetup/:email/:token` | anonymous | The Reset Key is wrong or was used before. | Open the link. | The panel "Bad Reset Key" shows with a link to `/lostPassword`, the email, the support contact links, and the text "Sauron was a bad guy. You don't want Sauron to steal your account, do you? We didn't think so." See W-32. | `e2e/tests/5c-lost-reset-invite.spec.ts` | No |  | W-32 |
| INVITE-04 | `/inviteSetup/:email/:token` | anonymous | The form of INVITE-01. | The same steps as RESET-04. | The same four messages as RESET-04. With an invalid form, **Set Your Password** is disabled, so no request is sent. See W-18. | `e2e/tests/5c-lost-reset-invite.spec.ts` | No |  | W-18 |
| INVITE-05 | `/inviteSetup/:email/:token` | anonymous | The request gives an API error. | Submit the form. | Toast with the title "Unable to Set Password". | `e2e/tests/5c-lost-reset-invite.spec.ts` | Yes |  |  |
| INVITE-06 | `/inviteSetup/:email/:token` | Commissioner | Logged in. The viewer opened a Draft screen. | Open the route. | The same toast as LOGIN-13. The client goes to the last route that is not an auth route: the Draft screen. Today the client goes to `/home`. See W-06. | `e2e/tests/5c-lost-reset-invite.spec.ts` | No |  | W-06 |

### 5D Profile

| ID | Route | Viewer | Starting state | Steps | Expected result | Test file | Passes on AngularJS | Migrated | Known defect |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| PROFILE-01 | `/profile` | Commissioner | Logged in. | Select the name in the navigation. | Heading "Update User Profile". **Email** and **Name** hold the current values. Fields **Current Password**, **New Password**, and **Confirm New Password** are empty. **Update User Profile** is disabled. | `e2e/tests/5d-profile.spec.ts` | Yes |  |  |
| PROFILE-02 | `/profile` | Commissioner | The form of PROFILE-01. | Change **Name**. Enter the current password. Select **Update User Profile**. | Toast "User profile information updated!". The navigation shows the new name. The client goes to the last route that is not an auth route. Today the client goes to `/home` unless the viewer opened four or more routes first. See W-06. | `e2e/tests/5d-profile.spec.ts` | No |  | W-06 |
| PROFILE-03 | `/profile` | Commissioner | The form of PROFILE-01. | Enter the current password and a new password two times. Select **Update User Profile**. | The viewer is logged out. Toast "Since you changed your password, we had to log you out and log you back in again. But no biggie - just enter your new password now to login again." The route is `/login`. The new password works. | `e2e/tests/5d-profile.spec.ts` | Yes |  |  |
| PROFILE-04 | `/profile` | Commissioner | The form of PROFILE-01. | Change **Email** to a new address. | The warning "Heads up: if you change to a new email address, you will be logged out. ..." shows. A change of letter case only does not show it. | `e2e/tests/5d-profile.spec.ts` | Yes |  |  |
| PROFILE-05 | `/profile` | Commissioner | The warning of PROFILE-04 shows. | Enter the current password. Select **Update User Profile**. | The viewer is logged out. Toast "Your email was updated, so we logged you out. To log in again, click the link we emailed to your new address to verify it. See you soon!". The route is `/home`. An email with an Email Verification Key link arrives at the new address. A login before the link is used gives the result of LOGIN-07. | `e2e/tests/5d-profile.spec.ts` | Yes |  |  |
| PROFILE-06 | `/profile` | Commissioner | The form of PROFILE-01. | Clear **Email**, then enter "abc". Clear **Name**. Leave **Current Password** empty, then enter 7 characters. Enter 7 characters in **New Password**. Enter a different value in **Confirm New Password**. Move the focus away each time. | "Email is missing", "Email is invalid", "Name is missing", "Password is missing", "Password is too short - 8 characters minimum", "New password is too short - 8 characters minimum", "New passwords do not match." **Update User Profile** is disabled. | `e2e/tests/5d-profile.spec.ts` | Yes |  |  |
| PROFILE-07 | `/profile` | Commissioner | The form of PROFILE-01. | Change **Name**. Enter a wrong current password. Select **Update User Profile**. | Toast with the API messages and the title "Unable to update your user profile". The form stays. | `e2e/tests/5d-profile.spec.ts` | Yes |  |  |
| PROFILE-08 | `/profile` | Commissioner | The profile request gives an API error. | Open the route. | Toast "Unable to load user profile". The form shows empty. | `e2e/tests/5d-profile.spec.ts` | Yes |  |  |
| PROFILE-09 | `/profile` | anonymous | None | Open the route. | The client redirects to `/login`. Today there is no redirect: the toast "Unable to load user profile" shows and the form shows empty. See W-01. | `e2e/tests/5d-profile.spec.ts` | No |  | W-01 |
| PROFILE-10 | `/profile` | Admin | Logged in. | Open the route. | The same as PROFILE-01. | `e2e/tests/5d-profile.spec.ts` | Yes |  |  |

### 5E Home

| ID | Route | Viewer | Starting state | Steps | Expected result | Test file | Passes on AngularJS | Migrated | Known defect |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| HOME-01 | `/`, `/home` | any | Drafts in each status. One Draft has a Draft Password. | Open `/`, then `/home`. | The two routes show the same screen: the heading "Welcome to Hoot Draft!" and the Draft table. Columns: **League Name**, **Sport**, **Commissioner**, **Created**, **Status**. A Draft with a Draft Password has a lock icon. Each name links to `/draft/(ID)`. | `e2e/tests/5e-home.spec.ts` | Yes |  |  |
| HOME-02 | `/home` | any | The same as HOME-01. | Enter part of a Draft name in the search box "Search Leagues...". | Only rows whose Draft name contains the text stay. The search looks at the Draft name only. Today the search looks at each column. See W-25. | `e2e/tests/5e-home.spec.ts` | No |  | W-25 |
| HOME-03 | `/home` | any | Drafts of two Leagues. | Open the **Sport** filter. Select a League. Then select "remove". | Only Drafts of that League stay, and the filter button shows the League label. After "remove", each Draft shows again. | `e2e/tests/5e-home.spec.ts` | Yes |  |  |
| HOME-04 | `/home` | any | Drafts in each status. | Open the **Status** filter. Select a status. Then select "remove". | Only Drafts with that status stay. After "remove", each Draft shows again. | `e2e/tests/5e-home.spec.ts` | Yes |  |  |
| HOME-05 | `/home` | any | Drafts of two Commissioners. | Enter part of a Commissioner name in "Search Commissioners...". Select a suggestion. Then select the label. | At most 10 suggestions show. After the choice, only Drafts of that Commissioner stay, and the label "Commish: (name)" replaces the input. A click on the label removes the filter. | `e2e/tests/5e-home.spec.ts` | Yes |  |  |
| HOME-06 | `/home` | any | Several Drafts. | Select the headers **League Name**, **Sport**, **Commissioner**, and **Status**, one at a time, three times each. | Each header sorts the rows ascending, then descending, then not at all. **Status** sorts in the order Setting Up, In Progress, Complete. | `e2e/tests/5e-home.spec.ts` | Yes |  |  |
| HOME-07 | `/home` | any | Several Drafts. | Select the header **Created**. | The table opens with the newest Draft first. The header **Created** sorts ascending, then descending, then not at all, as the other headers do. Today the header does nothing. See W-25. | `e2e/tests/5e-home.spec.ts` | No |  | W-25 |
| HOME-08 | `/home` | any | More than 15 Drafts. | Open the route. Select page 2. | 15 rows show on each page. The pager shows below the table. **First** and **Last** show only with more than 7 pages. | `e2e/tests/5e-home.spec.ts` | Yes |  |  |
| HOME-09 | `/home` | anonymous | A filter that matches no Draft. | Apply the filter. | "No drafts here, chief." shows with **Reset All Filters**. No link to create a Draft shows. | `e2e/tests/5e-home.spec.ts` | Yes |  |  |
| HOME-10 | `/home` | Commissioner | A filter that matches no Draft. | Apply the filter. | The same as HOME-09, plus the link "Create your own draft!" to `/commish/draft/create`. | `e2e/tests/5e-home.spec.ts` | Yes |  |  |
| HOME-11 | `/home` | any | The empty table of HOME-09. | Select **Reset All Filters**. | Each Draft shows again. The search box is empty. Today the search box keeps its old text. See W-25. | `e2e/tests/5e-home.spec.ts` | No |  | W-25 |
| HOME-12 | `/home` | any | Several Drafts. | Set a filter and a sort. Open a Draft. Go back to `/home`. | The filter, the sort, and the page are the same as before, for the life of the tab. | `e2e/tests/5e-home.spec.ts` | Yes |  |  |
| HOME-13 | `/home` | any | The Draft list request gives an API error. | Open the route. | Toast "Unable to load drafts". The table shows "No drafts here, chief." | `e2e/tests/5e-home.spec.ts` | Yes |  |  |
| HOME-14 | `/home` | any | The Draft options request gives an API error. | Open the route. | Toast "Cannot load draft options" with the title "Error". The filters have no entries. | `e2e/tests/5e-home.spec.ts` | Yes |  |  |
| HOME-15 | `/home` | any | The Commissioner search request gives an API error. | Enter text in "Search Commissioners...". | Toast "Unable to search commissioners". | `e2e/tests/5e-home.spec.ts` | Yes |  |  |
| HOME-16 | `/home` | any | A slow Draft list request. | Open the route. | A loading indicator shows until the Draft list arrives. Today no loading indicator shows. See W-24. | `e2e/tests/5e-home.spec.ts` | No |  | W-24 |

## Phase 6: Draft store and public Draft screens

### 6A Draft loading, Draft Password, and Draft navigation

The Draft navigation bar is the second bar, below the top navigation. It shows on each route with a Draft ID.

| ID | Route | Viewer | Starting state | Steps | Expected result | Test file | Passes on AngularJS | Migrated | Known defect |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| DRAFT-01 | `/draft/:draft_id` | anonymous | A Setting Up Draft. | Open the route. | The Draft navigation bar shows the Draft name as a link to the Draft index, and nothing else. | `e2e/tests/6a-draft-loading-password-navigation.spec.ts` | Yes |  |  |
| DRAFT-02 | `/draft/:draft_id` | anonymous | An In Progress Draft with Depth Charts. | Open the route. Open the **Picks** menu. | The bar shows **Draft Board**, **Depth Charts**, and the **Picks** menu with **by Manager**, **by Round**, **Trades**, and **Search**. It does not show **Stats**. Each link opens its route. | `e2e/tests/6a-draft-loading-password-navigation.spec.ts` | Yes |  |  |
| DRAFT-03 | `/draft/:draft_id` | anonymous | An In Progress Draft without Depth Charts. | Open the route. | The same as DRAFT-02 without **Depth Charts**. | `e2e/tests/6a-draft-loading-password-navigation.spec.ts` | Yes |  |  |
| DRAFT-04 | `/draft/:draft_id` | anonymous | A Complete Draft. | Open the route. | The same links as DRAFT-02, plus **Stats**. | `e2e/tests/6a-draft-loading-password-navigation.spec.ts` | Yes |  |  |
| DRAFT-05 | `/draft/:draft_id` | Commissioner | A Setting Up Draft. | Open the **Commissioner Toolbox** menu. | **Start Draft!**, **Setup Timers**, **Edit Draft**, **Delete Draft**. | `e2e/tests/6a-draft-loading-password-navigation.spec.ts` | Yes |  |  |
| DRAFT-06 | `/draft/:draft_id` | Commissioner | An In Progress Draft. | Open the **Commissioner Toolbox** menu. | **Enter Picks**, **Edit a Pick**, **Enter a Trade**, **Setup Timers**, **Reset Draft**, **Delete Draft**. The first four links open their routes. **Reset Draft** and **Delete Draft** open the confirm prompts "Reset draft?" and "Delete the draft?". | `e2e/tests/6a-draft-loading-password-navigation.spec.ts` | Yes |  |  |
| DRAFT-07 | `/draft/:draft_id` | Commissioner | A Complete Draft. | Open the **Commissioner Toolbox** menu. | **Delete Draft** only. | `e2e/tests/6a-draft-loading-password-navigation.spec.ts` | Yes |  |  |
| DRAFT-08 | `/draft/:draft_id` | Admin | A Draft of a different user. | Open the route. | **Commissioner Toolbox** shows, with the entries for the Draft status. | `e2e/tests/6a-draft-loading-password-navigation.spec.ts` | Yes |  |  |
| DRAFT-09 | `/draft/:draft_id` | another Commissioner | A Draft of a different user. | Open the route. | **Commissioner Toolbox** does not show. The screen is the same as for an anonymous viewer. | `e2e/tests/6a-draft-loading-password-navigation.spec.ts` | Yes |  |  |
| DRAFT-10 | `/draft/:draft_id` | any | A Draft is open. | Select the logo. | The Draft navigation bar is hidden on `/home`. | `e2e/tests/6a-draft-loading-password-navigation.spec.ts` | Yes |  |  |
| DRAFT-11 | `/draft/:draft_id` | any | No Draft has this ID. | Open the route. | The panel "Unable to load draft" shows, and the warning toast "We seem to be having trouble loading this draft, possibly due to network connectivity issues". See W-07. | `e2e/tests/6a-draft-loading-password-navigation.spec.ts` | No |  | W-07 |
| DRAFT-12 | Each route with a Draft ID | any | An In Progress Draft is open. | Requests for the Draft start to fail. | After each failed poll, the old toasts close and the warning toast of DRAFT-11 shows. After five failures, the panel "Connection Issues" shows, the Draft content is hidden, and polling stops. | `e2e/tests/6a-draft-loading-password-navigation.spec.ts` | Yes |  |  |
| DRAFT-13 | Each route with a Draft ID | any | Drafts in each status. | Open a public screen of each Draft. Wait. Then open `/home`. | The client polls the In Progress Draft only. Polling stops after the viewer leaves the Draft routes. | `e2e/tests/6a-draft-loading-password-navigation.spec.ts` | Yes |  |  |
| PW-01 | `/draft/:draft_id` | anonymous | A Draft with a Draft Password. | Open the route. | The panel "Draft Locked" shows. The dialog "Draft is password protected" opens with the field **Draft Password**. No Draft content shows. The Draft navigation bar shows the Draft name and nothing else. The Commissioner name shows in the panel, two times. See W-08. | `e2e/tests/6a-draft-loading-password-navigation.spec.ts` | No |  | W-08 |
| PW-02 | `/draft/:draft_id` | anonymous | The dialog of PW-01. | Enter the Draft Password. Select **Enter password**. | The page reloads. The Draft shows. Other routes of the Draft open with no dialog, for the life of the tab. | `e2e/tests/6a-draft-loading-password-navigation.spec.ts` | Yes |  |  |
| PW-03 | `/draft/:draft_id` | anonymous | The dialog of PW-01. | Enter a wrong Draft Password. Select **Enter password**. | The page reloads. The panel and the dialog show again. The field holds the wrong value. | `e2e/tests/6a-draft-loading-password-navigation.spec.ts` | Yes |  |  |
| PW-04 | `/draft/:draft_id` | anonymous | The dialog of PW-01. | Select **Cancel**. Then select the link "provide a password" in the panel. | The dialog closes and the panel stays. The link opens the dialog again. | `e2e/tests/6a-draft-loading-password-navigation.spec.ts` | Yes |  |  |
| PW-05 | `/draft/:draft_id` | anonymous | The dialog of PW-01. | Select the link "login to HootDraft". | The dialog closes. The route is `/login`. | `e2e/tests/6a-draft-loading-password-navigation.spec.ts` | Yes |  |  |
| PW-06 | `/draft/:draft_id` | another Commissioner | A Draft with a Draft Password. | Open the route. | The same as PW-01. The dialog has no login link. | `e2e/tests/6a-draft-loading-password-navigation.spec.ts` | Yes |  |  |
| PW-07 | `/draft/:draft_id` | Commissioner | A Draft with a Draft Password. | Open the route. | No panel and no dialog. The Draft shows. | `e2e/tests/6a-draft-loading-password-navigation.spec.ts` | Yes |  |  |
| PW-08 | `/draft/:draft_id` | Admin | A Draft of a different user with a Draft Password. | Open the route. | No panel and no dialog. The Draft shows. | `e2e/tests/6a-draft-loading-password-navigation.spec.ts` | Yes |  |  |
| PW-09 | The Draft Board, Picks, search, Trades, Draft Stats, and Depth Chart routes | anonymous | An In Progress or Complete Draft with a Draft Password. | Open each route directly. | The panel and the dialog show on each route, and no Draft content shows. See W-09. | `e2e/tests/6a-draft-loading-password-navigation.spec.ts` | No |  | W-09 |
| PW-10 | `/draft/:draft_id` | anonymous | The dialog of PW-01. | Select the close button (×) of the dialog. | The dialog closes and the panel stays, the same as **Cancel**. See W-10. | `e2e/tests/6a-draft-loading-password-navigation.spec.ts` | No |  | W-10 |
| PW-11 | `/draft/:draft_id/board` | two viewers | An In Progress Draft with a Draft Password. | The second viewer opens the Draft Board and enters the Draft Password. The Commissioner enters a Pick. | The second viewer sees the new Selected Pick on the Draft Board after the next poll. | `e2e/tests/6a-draft-loading-password-navigation.spec.ts` | Yes |  |  |
| PW-12 | `/draft/:draft_id` | anonymous | An In Progress Draft with a Draft Password. The panel shows. | Wait. | The client does not poll. | `e2e/tests/6a-draft-loading-password-navigation.spec.ts` | Yes |  |  |

### 6B Draft index

| ID | Route | Viewer | Starting state | Steps | Expected result | Test file | Passes on AngularJS | Migrated | Known defect |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| INDEX-01 | `/draft/:draft_id` | anonymous | A Setting Up Draft with Managers and no Depth Charts. | Open the route. | The panel "League Details: (name)" shows the name, the Commissioner, the League label, the Draft status label, the Draft Style, **Positions** "Off", and the number of Rounds. The panel "Draft Order" lists each Manager with an ordinal ("1st"). No "Commish Actions" panel shows. | `e2e/tests/6b-draft-index.spec.ts` | Yes |  |  |
| INDEX-02 | `/draft/:draft_id` | anonymous | A Setting Up Draft with no Managers. | Open the route. | "Draft Order" shows "(Commissioner name) has not added any managers yet." | `e2e/tests/6b-draft-index.spec.ts` | Yes |  |  |
| INDEX-03 | `/draft/:draft_id` | anonymous | A Draft with Depth Charts. | Open the route. | **Positions** lists each Depth Chart position with its number of slots, for example "2× RB". | `e2e/tests/6b-draft-index.spec.ts` | Yes |  |  |
| INDEX-04 | `/draft/:draft_id` | anonymous | An In Progress Draft with no Selected Pick. | Open the route. | "League Details", "Upcoming Picks" with the next five Picks, and "Last 5 Picks" with "No picks have been made yet...". The first upcoming Pick shows "On the clock". | `e2e/tests/6b-draft-index.spec.ts` | Yes |  |  |
| INDEX-05 | `/draft/:draft_id` | anonymous | An In Progress Draft with more than five Selected Picks. | Open the route. | "Last 5 Picks" shows the five newest Selected Picks, each with Pick number, Round, name, position, team, and Manager. | `e2e/tests/6b-draft-index.spec.ts` | Yes |  |  |
| INDEX-06 | `/draft/:draft_id` | anonymous | A Complete Draft. | Open the route. Select the next Round in the pager. | The donation message shows. "League Details" also shows **Started** and **Ended**. "Round 1 Picks" lists the Selected Picks of Round 1 with a pager. The pager loads the Picks of the Round and changes the heading. | `e2e/tests/6b-draft-index.spec.ts` | Yes |  |  |
| INDEX-07 | `/draft/:draft_id` | any | A Complete Draft. | Select **Hide this message**. Reload. Then set the browser time 15 days ahead and reload. | The donation message is hidden, and stays hidden after the reload. After 14 days it shows again. | `e2e/tests/6b-draft-index.spec.ts` | Yes |  |  |
| INDEX-08 | `/draft/:draft_id` | Commissioner | A Setting Up Draft. | Open the route. | "Commish Actions" shows **Start!**, **Timers**, **Edit**, and **Delete**. "League Details" has an **Edit** icon link to the edit route. "Draft Order" is the Manager editor of group 7B. | `e2e/tests/6b-draft-index.spec.ts` | Yes |  |  |
| INDEX-09 | `/draft/:draft_id` | Commissioner | An In Progress Draft. | Open the route. | "Commish Actions" shows **Enter Pick**, **Change Pick**, **Enter Trade**, **Reset**, **Timers**, and **Delete**. "League Details" shows "Can't edit active draft – Reset draft if you need to change something". The link buttons open their routes. | `e2e/tests/6b-draft-index.spec.ts` | Yes |  |  |
| INDEX-10 | `/draft/:draft_id` | Commissioner | A Complete Draft. | Open the route. | The same as INDEX-06. No "Commish Actions" panel shows. | `e2e/tests/6b-draft-index.spec.ts` | Yes |  |  |
| INDEX-11 | `/draft/:draft_id` | any | A Setting Up Draft, an In Progress Draft, and a Complete Draft. The requests for the Managers, the last Picks, and the Round Picks give an API error. | Open the route of each Draft. | For Managers (Setting Up): toast "Unable to load managers". For the last Picks: the error text "Unable to load last 5 picks" shows in the panel. For the Round Picks of a Complete Draft: toast "Unable to load picks", and the error text "Unable to load round 1 picks" shows in the panel. Today no error text shows. See W-24. | `e2e/tests/6b-draft-index.spec.ts` | No |  | W-24 |
| INDEX-12 | `/draft/:draft_id` | two viewers | An In Progress Draft. The second viewer has the Draft index open. | The Commissioner enters a Pick. | After the next poll, the second viewer sees the Pick first in "Last 5 Picks", and "Upcoming Picks" moves on by one Pick. | `e2e/tests/6b-draft-index.spec.ts` | Yes |  |  |
| INDEX-13 | `/draft/:draft_id` | two viewers | An In Progress Draft with a Selected Pick in "Last 5 Picks". | The Commissioner edits that Pick. | After the next poll, the second viewer sees the new name. | `e2e/tests/6b-draft-index.spec.ts` | Yes |  |  |
| INDEX-14 | `/draft/:draft_id` | two viewers | An In Progress Draft. | The Commissioner enters a Trade of an upcoming Pick. | After the next poll, the Traded Pick in "Upcoming Picks" shows the new Manager. | `e2e/tests/6b-draft-index.spec.ts` | Yes |  |  |
| INDEX-15 | `/draft/:draft_id` | two viewers | An In Progress Draft with one Unselected Pick. | The Commissioner enters the last Pick. | After the next poll, the second viewer sees the Complete view of INDEX-06. Polling stops. | `e2e/tests/6b-draft-index.spec.ts` | Yes |  |  |
| INDEX-16 | `/draft/:draft_id` | two viewers | An In Progress Draft. | The Commissioner resets the Draft. | After the next poll, the second viewer sees the Setting Up view of INDEX-01. | `e2e/tests/6b-draft-index.spec.ts` | No |  | W-39 |
| INDEX-17 | `/draft/:draft_id` | two viewers | A Setting Up Draft. | The Commissioner changes the Draft to In Progress. | The second viewer sees no change until a reload, because a Setting Up Draft is not polled. See W-11. | `e2e/tests/6b-draft-index.spec.ts` | Yes |  |  |

### 6C Draft Board

| ID | Route | Viewer | Starting state | Steps | Expected result | Test file | Passes on AngularJS | Migrated | Known defect |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| BOARD-01 | `/draft/:draft_id/board` | any | A Setting Up Draft. | Open the route. | Warning toast "Draft is still setting up". The client goes to the last route that is not an auth route, and to `/home` when none exists. Today the client goes to `/home` unless the viewer opened four or more routes first. See W-06. | `e2e/tests/6c-draft-board.spec.ts` | No |  | W-06 |
| BOARD-02 | `/draft/:draft_id/board` | any | An In Progress Draft with Selected Picks. | Open the route. | "Loading the board, one sec." shows first. Then one row for each Round with the Round number, and one cell for each Pick. A Selected Pick shows the Pick number, first name, last name, Manager, position, and team, with the color of its position. An Unselected Pick shows the Pick number as an ordinal and the Manager. The current Pick also shows "On the clock". The footer is hidden. | `e2e/tests/6c-draft-board.spec.ts` | Yes |  |  |
| BOARD-03 | `/draft/:draft_id/board` | any | Two In Progress Drafts with the same Managers: one Standard, one Serpentine. | Open each Draft Board. | Standard: each Round has the Managers in Draft Order, and the Pick numbers rise from left to right. Serpentine: each Manager keeps a column, and in Round 2 the Pick numbers reverse: the last Manager picks first. | `e2e/tests/6c-draft-board.spec.ts` | Yes |  |  |
| BOARD-04 | `/draft/:draft_id/board` | any | An In Progress Draft with a Traded Pick. | Open the route. | The cell of the Traded Pick shows the new Manager. | `e2e/tests/6c-draft-board.spec.ts` | Yes |  |  |
| BOARD-05 | `/draft/:draft_id/board` | any | An In Progress Draft with a Selected Pick. A window wider than a phone. | Open the route. | The header above the Draft Board shows "Current Pick" with the Pick, the pick timer area, and "Last Pick" with the Pick. With no Selected Pick, "Last Pick" does not show. On a phone-width window, the header is hidden. | `e2e/tests/6c-draft-board.spec.ts` | Yes |  |  |
| BOARD-06 | `/draft/:draft_id/board` | any | An In Progress Draft with no Round Times. | Open the route. | The pick timer area shows "Pick timers disabled". | `e2e/tests/6c-draft-board.spec.ts` | Yes |  |  |
| BOARD-07 | `/draft/:draft_id/board` | any | An In Progress Draft with Round Times. Time is left for the current Pick. | Open the route. | The flip clock shows the remaining minutes and seconds and counts down. | `e2e/tests/6c-draft-board.spec.ts` | Yes |  |  |
| BOARD-08 | `/draft/:draft_id/board` | any | The state of BOARD-07. | Wait until the pick timer reaches zero. | The flip clock is replaced by "Time's up!" and "Everyone boo (Manager name)!". | `e2e/tests/6c-draft-board.spec.ts` | Yes |  |  |
| BOARD-09 | `/draft/:draft_id/board` | any | An In Progress Draft with Round Times. The time for the current Pick is over. | Open the route. | "Time's up!" shows at once. | `e2e/tests/6c-draft-board.spec.ts` | Yes |  |  |
| BOARD-10 | `/draft/:draft_id/board` | any | A Complete Draft. | Open the route. | The Draft Board shows each Selected Pick. No header shows. The client does not poll. | `e2e/tests/6c-draft-board.spec.ts` | Yes |  |  |
| BOARD-11 | `/draft/:draft_id/board` | any | An In Progress Draft. The Picks request gives an API error. | Open the route. | Toast "Unable to load draft board". | `e2e/tests/6c-draft-board.spec.ts` | Yes |  |  |
| BOARD-12 | `/draft/:draft_id/board` | any | An In Progress Draft. One of these requests gives an API error: current and last Pick, updated Picks, remaining time. | Open the route, or wait for a Draft change. | Toast "Unable to get next and/or current picks", "Unable to get up to date draft picks", or "Unable to load remaining pick time". | `e2e/tests/6c-draft-board.spec.ts` | Yes |  |  |
| BOARD-13 | `/draft/:draft_id/board` | two viewers | An In Progress Draft. The second viewer has the Draft Board open. | The Commissioner enters a Pick. | After the next poll, the cell shows the Selected Pick, "On the clock" moves to the next Pick, and the header shows the new "Current Pick" and "Last Pick". | `e2e/tests/6c-draft-board.spec.ts` | Yes |  |  |
| BOARD-14 | `/draft/:draft_id/board` | two viewers | An In Progress Draft with a Selected Pick. | The Commissioner edits the Pick. | After the next poll, the cell shows the new name, team, and position. | `e2e/tests/6c-draft-board.spec.ts` | Yes |  |  |
| BOARD-15 | `/draft/:draft_id/board` | two viewers | An In Progress Draft. | The Commissioner enters a Trade of two Unselected Picks. | After the next poll, the two cells show the new Managers. | `e2e/tests/6c-draft-board.spec.ts` | No |  | W-40 |
| BOARD-16 | `/draft/:draft_id/board` | two viewers | An In Progress Draft with Round Times. Each viewer has the Draft Board open. | Compare the two flip clocks. Then the Commissioner enters a Pick. | The two flip clocks show the same time, within two seconds. After the Pick, each flip clock starts again with the Round Time of the new current Pick. | `e2e/tests/6c-draft-board.spec.ts` | Yes |  |  |
| BOARD-17 | `/draft/:draft_id/board` | two viewers | An In Progress Draft with one Unselected Pick. | The Commissioner enters the last Pick. | After the next poll, the last cell shows the Selected Pick, the header is hidden, and polling stops. | `e2e/tests/6c-draft-board.spec.ts` | Yes |  |  |
| BOARD-18 | `/draft/:draft_id/board` | two viewers | An In Progress Draft. | The Commissioner resets the Draft. | After the next poll, the second viewer gets the result of BOARD-01. | `e2e/tests/6c-draft-board.spec.ts` | No |  | W-39 |

### 6D Picks by Round, Picks by Manager, and search

| ID | Route | Viewer | Starting state | Steps | Expected result | Test file | Passes on AngularJS | Migrated | Known defect |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| ROUND-01 | `/draft/:draft_id/picks/round` | any | A Setting Up Draft. | Open the route. | The same as BOARD-01. | `e2e/tests/6d-picks-by-round-manager-and-search.spec.ts` | No |  | W-06 |
| ROUND-02 | `/draft/:draft_id/picks/round` | any | An In Progress Draft in Round 2. | Open the route. | "Round 2 Picks" lists each Pick of the current Round, Selected and Unselected, with a pager. "Upcoming Picks" shows beside it. | `e2e/tests/6d-picks-by-round-manager-and-search.spec.ts` | Yes |  |  |
| ROUND-03 | `/draft/:draft_id/picks/round` | any | The state of ROUND-02. | Select the previous Round and the next Round in the pager. | The heading and the Picks change to the chosen Round. The pager stops at Round 1 and at the last Round. | `e2e/tests/6d-picks-by-round-manager-and-search.spec.ts` | Yes |  |  |
| ROUND-04 | `/draft/:draft_id/picks/round` | any | A Complete Draft. | Open the route. | "Round 1 Picks" shows. "League Details" shows beside it in place of "Upcoming Picks". | `e2e/tests/6d-picks-by-round-manager-and-search.spec.ts` | Yes |  |  |
| ROUND-05 | `/draft/:draft_id/picks/round` | any | The Round Picks request gives an API error. | Open the route. | Toast "Unable to load picks", and the error text "Unable to load round 1 picks" shows in the panel. Today no error text shows. See W-24. The toast shows once. Today it shows twice. See W-43. | `e2e/tests/6d-picks-by-round-manager-and-search.spec.ts` | No |  | W-24, W-43 |
| ROUND-06 | `/draft/:draft_id/picks/round` | two viewers | An In Progress Draft. The second viewer has the current Round open. | The Commissioner enters a Pick. | After the next poll, the list of the second viewer shows the Selected Pick, and "Upcoming Picks" moves on. Today the list never changes until a reload. See W-12. | `e2e/tests/6d-picks-by-round-manager-and-search.spec.ts` | No |  | W-12 |
| MANAGER-01 | `/draft/:draft_id/picks/manager` | any | A Setting Up Draft. | Open the route. | The same as BOARD-01. | `e2e/tests/6d-picks-by-round-manager-and-search.spec.ts` | No |  | W-06 |
| MANAGER-02 | `/draft/:draft_id/picks/manager` | any | An In Progress Draft. | Open the route. | The Manager list holds each Manager, and the first Manager is chosen. "(Manager name)'s Picks" lists each Pick of that Manager, Selected and Unselected. "Upcoming Picks" shows beside it. Each row shows the name of the Manager. Today no row shows it. See W-42. | `e2e/tests/6d-picks-by-round-manager-and-search.spec.ts` | No |  | W-42 |
| MANAGER-03 | `/draft/:draft_id/picks/manager` | any | The state of MANAGER-02. | Choose a different Manager. | The heading and the Picks change to that Manager. Each row shows the name of the Manager. Today no row shows it. See W-42. | `e2e/tests/6d-picks-by-round-manager-and-search.spec.ts` | No |  | W-42 |
| MANAGER-04 | `/draft/:draft_id/picks/manager` | any | A Complete Draft. | Open the route. | The same as MANAGER-02, with "League Details" in place of "Upcoming Picks". Each row shows the name of the Manager. Today no row shows it. See W-42. | `e2e/tests/6d-picks-by-round-manager-and-search.spec.ts` | No |  | W-42 |
| MANAGER-05 | `/draft/:draft_id/picks/manager` | any | The Managers request or the Picks request gives an API error. | Open the route. | Toast "Unable to grab managers" or "Unable to load picks". One toast shows for each request. Today the Picks request is sent twice, so its toast shows twice. See W-43. | `e2e/tests/6d-picks-by-round-manager-and-search.spec.ts` | No |  | W-43 |
| MANAGER-06 | `/draft/:draft_id/picks/manager` | two viewers | An In Progress Draft. The second viewer has the Manager of the current Pick open. | The Commissioner enters the Pick. | After the next poll, the list of the second viewer shows the Selected Pick, and "Upcoming Picks" moves on. Today the list never changes until a reload. See W-12. | `e2e/tests/6d-picks-by-round-manager-and-search.spec.ts` | No |  | W-12 |
| SEARCH-01 | `/draft/:draft_id/search` | any | A Setting Up Draft. | Open the route. | The same as BOARD-01. | `e2e/tests/6d-picks-by-round-manager-and-search.spec.ts` | No |  | W-06 |
| SEARCH-02 | `/draft/:draft_id/search` | any | An In Progress or Complete Draft with Selected Picks. | Open the route. | "Search Selected Picks" shows **Keywords**, **Position** "(all positions)", **Team** "(all teams)", the sort button "Most recent picks first", and **Clear**. "Matching Picks" shows "Enter a few player name keywords, select a team or a position to search for picks in the search box to find picks." Today both lists show a blank option. See W-41. | `e2e/tests/6d-picks-by-round-manager-and-search.spec.ts` | No |  | W-41 |
| SEARCH-03 | `/draft/:draft_id/search` | any | The state of SEARCH-02. | Enter part of a name in **Keywords**. | After half a second, "Matching Picks" lists the Selected Picks that match. | `e2e/tests/6d-picks-by-round-manager-and-search.spec.ts` | Yes |  |  |
| SEARCH-04 | `/draft/:draft_id/search` | any | The state of SEARCH-02. | Choose a position. | Only Selected Picks with that position show. | `e2e/tests/6d-picks-by-round-manager-and-search.spec.ts` | Yes |  |  |
| SEARCH-05 | `/draft/:draft_id/search` | any | The state of SEARCH-02. | Choose a team. | Only Selected Picks with that team show. | `e2e/tests/6d-picks-by-round-manager-and-search.spec.ts` | Yes |  |  |
| SEARCH-06 | `/draft/:draft_id/search` | any | Results show. | Select the sort button. | The label changes to "Highest picks first", and the results start with the lowest Pick number. A second click changes them back. | `e2e/tests/6d-picks-by-round-manager-and-search.spec.ts` | Yes |  |  |
| SEARCH-07 | `/draft/:draft_id/search` | any | The state of SEARCH-02. | Enter keywords that match nothing. | "No picks were found matching that criteria." | `e2e/tests/6d-picks-by-round-manager-and-search.spec.ts` | Yes |  |  |
| SEARCH-08 | `/draft/:draft_id/search` | any | Results show. | Select **Clear**. | The three fields are empty and the hint of SEARCH-02 shows. No old results show. Today the old results stay below the hint. See W-21. | `e2e/tests/6d-picks-by-round-manager-and-search.spec.ts` | No |  | W-21, W-41 |
| SEARCH-09 | `/draft/:draft_id/search` | any | The search request gives an API error. | Enter keywords. | Toast "Unable to search picks" and the text "Unable to load search picks". | `e2e/tests/6d-picks-by-round-manager-and-search.spec.ts` | Yes |  |  |
| SEARCH-10 | `/draft/:draft_id/search` | two viewers | An In Progress Draft. The second viewer has a search open with keywords that match no Pick yet. | The Commissioner enters a Pick with a Pro Player that matches the keywords. | After the next poll, the results of the second viewer include the new Pick. Today the results never change until the search changes. See W-12. | `e2e/tests/6d-picks-by-round-manager-and-search.spec.ts` | No |  | W-12 |

### 6E Trades and Draft Stats

| ID | Route | Viewer | Starting state | Steps | Expected result | Test file | Passes on AngularJS | Migrated | Known defect |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| TRADES-01 | `/draft/:draft_id/trades` | any | A Setting Up Draft. | Open the route. | The same as BOARD-01. | `e2e/tests/6e-trades-and-draft-stats.spec.ts` | No |  | W-06 |
| TRADES-02 | `/draft/:draft_id/trades` | any | An In Progress Draft with no Trade. | Open the route. | The panel "Trades" shows "No trades have been made." | `e2e/tests/6e-trades-and-draft-stats.spec.ts` | Yes |  |  |
| TRADES-03 | `/draft/:draft_id/trades` | any | An In Progress Draft with a Trade of one Unselected Pick and one Selected Pick. | Open the route. | "Trade between (Manager 1) and (Manager 2):" with the time and "(during the (ordinal) round)". Below "(Manager) gets:", each Trade Asset shows. A Trade Asset that was an Unselected Pick shows "(ordinal) Round Pick ((ordinal) overall)". A Trade Asset that was a Selected Pick shows the Pick with name, position, and team. | `e2e/tests/6e-trades-and-draft-stats.spec.ts` | Yes |  |  |
| TRADES-04 | `/draft/:draft_id/trades` | any | The Trade of TRADES-03. The Unselected Pick is a Selected Pick now. | Open the route. | The Trade Asset also shows "became (name), (position) – (team)". The same screen shows for a Complete Draft. | `e2e/tests/6e-trades-and-draft-stats.spec.ts` | Yes |  |  |
| TRADES-05 | `/draft/:draft_id/trades` | two viewers | An In Progress Draft. The second viewer has the Trades screen open. | The Commissioner enters a Trade. | After the next poll, the second viewer sees the new Trade. | `e2e/tests/6e-trades-and-draft-stats.spec.ts` | Yes |  |  |
| STATS-01 | `/draft/:draft_id/stats` | any | A Setting Up Draft. | Open the route. | The same as BOARD-01. | `e2e/tests/6e-trades-and-draft-stats.spec.ts` | No |  | W-06 |
| STATS-02 | `/draft/:draft_id/stats` | any | An In Progress Draft. | Open the route. | Warning toast "Draft is not complete yet". Today the toast says "Draft is still setting up". See W-14. The client goes to the last route that is not an auth route, and to `/home` when none exists. Today the client goes to `/home` unless the viewer opened four or more routes first. See W-06. | `e2e/tests/6e-trades-and-draft-stats.spec.ts` | No |  | W-06, W-14 |
| STATS-03 | `/draft/:draft_id/stats` | any | A Complete Draft with Draft Stats. | Open the route. | The panel "Draft Statistics" shows: Time Spent Drafting, Longest Average Pick, Shortest Average Pick, Longest Single Pick, Shortest Single Pick (each with a Manager name), Average Pick, Longest Round, Shortest Round, Most Drafted Team, Least Drafted Team, Most Drafted Position, Least Drafted Position. Each Pick Duration shows in words, for example "2 minutes, 5 seconds", "1 minute" for 60 seconds, and "0 seconds" for zero seconds. Today it shows "60 seconds" and "0 second". See W-34. "League Details" shows beside it. | `e2e/tests/6e-trades-and-draft-stats.spec.ts` | No |  | W-34 |
| STATS-04 | `/draft/:draft_id/stats` | any | A Complete Draft with no Draft Stats. | Open the route. | "Statistics have not been generated for this draft." | `e2e/tests/6e-trades-and-draft-stats.spec.ts` | Yes |  |  |

### 6F Depth Chart

| ID | Route | Viewer | Starting state | Steps | Expected result | Test file | Passes on AngularJS | Migrated | Known defect |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| DEPTH-01 | `/draft/:draft_id/depth_chart` | any | A Setting Up Draft with Depth Charts. | Open the route. | The same as BOARD-01. | `e2e/tests/6f-depth-chart.spec.ts` | No |  | W-06 |
| DEPTH-02 | `/draft/:draft_id/depth_chart` | any | An In Progress Draft with Depth Charts and Selected Picks. | Open the route. | The list **Team:** holds each Manager, and the first Manager is chosen. One box shows for each Depth Chart position with its name and "(Picks)/(slots)". The box "Unassigned" holds the Selected Picks of the Manager that have no position. The box is hidden when it is empty. The footer is hidden. | `e2e/tests/6f-depth-chart.spec.ts` | Yes |  |  |
| DEPTH-03 | `/draft/:draft_id/depth_chart` | any | The state of DEPTH-02. | Choose a different Manager. Open the Draft index. Open the Depth Chart again. | The Depth Chart of the chosen Manager shows, and the same Manager is chosen after the return. | `e2e/tests/6f-depth-chart.spec.ts` | Yes |  |  |
| DEPTH-04 | `/draft/:draft_id/depth_chart` | anonymous | The state of DEPTH-02. | Drag a Pick from "Unassigned" to a position box. Reload. | The Pick is in the position box, and the count of the box goes up by one. The Pick is still there after the reload. No login is necessary. | `e2e/tests/6f-depth-chart.spec.ts` | Yes |  |  |
| DEPTH-05 | `/draft/:draft_id/depth_chart` | any | A position box with one slot holds one Pick. | Drag a second Pick into the box. | The box takes the Pick and shows "2/1" with the over-full style. | `e2e/tests/6f-depth-chart.spec.ts` | Yes |  |  |
| DEPTH-06 | `/draft/:draft_id/depth_chart` | any | A Draft that became Complete less than 10 minutes ago. | Drag a Pick to a different box. | The move works, as in DEPTH-04. | `e2e/tests/6f-depth-chart.spec.ts` | Yes |  |  |
| DEPTH-07 | `/draft/:draft_id/depth_chart` | any | A Draft that became Complete more than 10 minutes ago. | Try to drag a Pick. | The Pick does not move. No request is sent. | `e2e/tests/6f-depth-chart.spec.ts` | Yes |  |  |
| DEPTH-08 | `/draft/:draft_id/depth_chart` | any | One of these requests gives an API error: Managers, Depth Chart, move. | Open the route, or drag a Pick. | Toast "Unable to load managers", "Unable to load depth chart.", or "Unable to update depth chart." | `e2e/tests/6f-depth-chart.spec.ts` | Yes |  |  |
| DEPTH-09 | `/draft/:draft_id/depth_chart` | any | An In Progress Draft without Depth Charts. | Open the route directly. | The Draft navigation bar has no link to this route. The client goes to the Draft index. Today the route opens: the list **Team:** shows, and the toast "Unable to load depth chart." shows. See W-30. | `e2e/tests/6f-depth-chart.spec.ts` | No |  | W-30 |
| DEPTH-10 | `/draft/:draft_id/depth_chart` | two viewers | An In Progress Draft with Depth Charts. The second viewer has the Manager of the current Pick open. | The Commissioner enters the Pick. | After the next poll, the new Selected Pick shows in "Unassigned". | `e2e/tests/6f-depth-chart.spec.ts` | Yes |  |  |
| DEPTH-11 | `/draft/:draft_id/depth_chart` | any | An In Progress Draft with Depth Charts. A slow Managers request. | Open the route. | A loading indicator shows until the request ends. The list **Team:** is not on the screen until the request ends. Today no loading indicator shows. See W-24. | `e2e/tests/6f-depth-chart.spec.ts` | No |  | W-24 |

## Phase 7: Commissioner screens

### 7A Create and edit a Draft

| ID | Route | Viewer | Starting state | Steps | Expected result | Test file | Passes on AngularJS | Migrated | Known defect |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| CREATE-01 | `/commish/draft/create` | Commissioner | Logged in. | Select **New League**. | Heading "Create a New League". Fields **League Name**, **Style**, **Sport**, the **Depth Charts** switch, **# of Rounds**, and **Password**. Buttons **Create League** and **Cancel**. **Create League** is disabled until the form is valid. | `e2e/tests/7a-create-and-edit-a-draft.spec.ts` | Yes |  |  |
| CREATE-02 | `/commish/draft/create` | Commissioner | The form of CREATE-01. | Enter a name. Choose a Draft Style and a League. Enter the number of Rounds. Select **Create League**. | The working modal shows and closes. Toast "(name) created!". The route is the Draft index of the new Draft, which is Setting Up. | `e2e/tests/7a-create-and-edit-a-draft.spec.ts` | Yes |  |  |
| CREATE-03 | `/commish/draft/create` | Commissioner | The form of CREATE-01. | Create a Draft with a value in **Password**. | The Draft has a Draft Password: the home page shows the lock icon, and an anonymous viewer gets the result of PW-01. | `e2e/tests/7a-create-and-edit-a-draft.spec.ts` | Yes |  |  |
| CREATE-04 | `/commish/draft/create` | Commissioner | The form of CREATE-01 with a League chosen. | Turn on **Depth Charts**. | "Each team will need to draft:" shows with one row for each default position of the League, plus "BN". Each row has a number of slots and a position name. **# of Rounds** becomes text and equals the sum of the slots. | `e2e/tests/7a-create-and-edit-a-draft.spec.ts` | Yes |  |  |
| CREATE-05 | `/commish/draft/create` | Commissioner | The state of CREATE-04. | Change a number of slots. Select **Add Roster Spot** and fill in the row. Select the remove icon of a row. | **# of Rounds** follows the sum of the slots after each change. | `e2e/tests/7a-create-and-edit-a-draft.spec.ts` | Yes |  |  |
| CREATE-06 | `/commish/draft/create` | Commissioner | The state of CREATE-04. | Give two rows the same position name. | "One or more of the depth chart positions has a duplicate position." shows. **Create League** is disabled. | `e2e/tests/7a-create-and-edit-a-draft.spec.ts` | Yes |  |  |
| CREATE-07 | `/commish/draft/create` | Commissioner | The state of CREATE-04. | Enter "IR" as a position name. | The note "IR, DL and NA positions generally don't count as active roster slots in most leagues, so you dont need to list them here." shows for "IR" and for "DL", unless the League is NFLE. Today the note does not show for "DL". See W-23. | `e2e/tests/7a-create-and-edit-a-draft.spec.ts` | No |  | W-23 |
| CREATE-08 | `/commish/draft/create` | Commissioner | The state of CREATE-04. | Turn off **Depth Charts**. Create the Draft. | **# of Rounds** is an input again. The Draft index shows **Positions** "Off". | `e2e/tests/7a-create-and-edit-a-draft.spec.ts` | Yes |  |  |
| CREATE-09 | `/commish/draft/create` | Commissioner | The form of CREATE-01. | Clear **# of Rounds** and move the focus away. Enter 0. Enter 31. | "Number of rounds is missing", "Your draft must have at least 1 round", "Too many rounds - max number of rounds is 30". | `e2e/tests/7a-create-and-edit-a-draft.spec.ts` | Yes |  |  |
| CREATE-10 | `/commish/draft/create` | Commissioner | The form of CREATE-01. | Leave **League Name**, **Style**, or **Sport** empty. | **Create League** stays disabled. After the field loses focus, the message "League name is missing", "Style is missing", or "Sport is missing" shows. Today no message shows for these three fields. See W-17. | `e2e/tests/7a-create-and-edit-a-draft.spec.ts` | No |  | W-17 |
| CREATE-11 | `/commish/draft/create` | Commissioner | A Draft with the same name exists. | Fill in the form and select **Create League**. | Toast "Draft name is already taken, please choose a different name." with the title "Unable to create draft". The form stays. | `e2e/tests/7a-create-and-edit-a-draft.spec.ts` | Yes |  |  |
| CREATE-12 | `/commish/draft/create` | anonymous | None | Open the route. | The client redirects to `/login`. Today there is no redirect: the toast "Unable to load draft defaults" and the text "Unable to load draft defaults" show, and no form shows. See W-01. | `e2e/tests/7a-create-and-edit-a-draft.spec.ts` | No |  | W-01 |
| CREATE-13 | `/commish/draft/create` | Commissioner | The form of CREATE-01. | Select **Cancel**. | The browser goes back one step in its history. | `e2e/tests/7a-create-and-edit-a-draft.spec.ts` | Yes |  |  |
| CREATE-14 | `/commish/draft/create` | Commissioner | The request for the positions of a League gives an API error. | Turn on **Depth Charts**. | Toast "Unable to load positions for the given draft sport". | `e2e/tests/7a-create-and-edit-a-draft.spec.ts` | Yes |  |  |
| CREATE-15 | `/commish/draft/create` | Admin | Logged in. | Open the route. | The same as CREATE-01. | `e2e/tests/7a-create-and-edit-a-draft.spec.ts` | Yes |  |  |
| EDIT-01 | `/commish/draft/:draft_id/edit` | Commissioner | A Setting Up Draft. | Select **Edit** on the Draft index. | Heading "Editing (name)". The form of CREATE-01 holds the values of the Draft, with the Draft Password and the Depth Chart positions. Buttons **Save League** and **Cancel**. | `e2e/tests/7a-create-and-edit-a-draft.spec.ts` | Yes |  |  |
| EDIT-02 | `/commish/draft/:draft_id/edit` | Commissioner | The form of EDIT-01. | Change the name, the Draft Style, and the number of Rounds. Select **Save League**. | Toast "(name) edited!". The route is the Draft index, which shows the new values. | `e2e/tests/7a-create-and-edit-a-draft.spec.ts` | Yes |  |  |
| EDIT-03 | `/commish/draft/:draft_id/edit` | Commissioner | The form of EDIT-01. | Turn on **Depth Charts**, change the rows, and save. | The rules of CREATE-04 to CREATE-06 hold. The Draft index lists the positions. | `e2e/tests/7a-create-and-edit-a-draft.spec.ts` | Yes |  |  |
| EDIT-04 | `/commish/draft/:draft_id/edit` | Commissioner | The form of EDIT-01. | Clear **League Name**. Clear **# of Rounds**. Enter 31. | **Save League** is disabled. After each field loses focus, its message shows: "League name is missing", "Number of rounds is missing", "Too many rounds - max number of rounds is 30". Today no message shows for any field. See W-17. | `e2e/tests/7a-create-and-edit-a-draft.spec.ts` | No |  | W-17 |
| EDIT-05 | `/commish/draft/:draft_id/edit` | Commissioner | A second Draft has the name "Taken". | Change the name to "Taken" and save. | Toast with the API message and the title "Unable to edit draft". | `e2e/tests/7a-create-and-edit-a-draft.spec.ts` | Yes |  |  |
| EDIT-06 | `/commish/draft/:draft_id/edit` | Commissioner | The form of EDIT-01. | Select **Cancel**. | The browser goes back one step in its history. | `e2e/tests/7a-create-and-edit-a-draft.spec.ts` | Yes |  |  |
| EDIT-07 | `/commish/draft/:draft_id/edit` | Commissioner | An In Progress or Complete Draft. | Open the route directly. | The Draft index has no link to this route in these statuses. The client shows a warning toast that starts with "Unable to edit draft" and goes to the Draft index. Today the screen has no check of the Draft status, so the form shows. **Save League** of an In Progress Draft saves, and the API refuses it for a Complete Draft with the toast "Unable to edit draft" and the text "Unknown 400 error" (see W-04). See W-15. | `e2e/tests/7a-create-and-edit-a-draft.spec.ts` | No |  | W-15 |
| EDIT-08 | `/commish/draft/:draft_id/edit` | Admin | A Setting Up Draft of a different user. | Open the route. Change the name and save. | The same as EDIT-01 and EDIT-02. | `e2e/tests/7a-create-and-edit-a-draft.spec.ts` | Yes |  |  |
| EDIT-09 | `/commish/draft/:draft_id/edit` | anonymous | A Setting Up Draft. | Open the route. | The client redirects to `/login`. Today there is no redirect: the toast "Unable to load draft" and the text "Unable to load draft for editing" show, and no form shows. See W-01. | `e2e/tests/7a-create-and-edit-a-draft.spec.ts` | No |  | W-01 |
| EDIT-10 | `/commish/draft/:draft_id/edit` | another Commissioner | A Setting Up Draft of a different user. | Open the route. | The client goes to the Draft index. Today the result is the same as EDIT-09. See W-02. | `e2e/tests/7a-create-and-edit-a-draft.spec.ts` | No |  | W-02 |

### 7B Managers and Draft status changes

| ID | Route | Viewer | Starting state | Steps | Expected result | Test file | Passes on AngularJS | Migrated | Known defect |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| MGR-01 | `/draft/:draft_id` | Commissioner | A Setting Up Draft with no Managers. | Open the route. | "Draft Order" shows "There are no managers in your league right now." and the link "Go ahead and add some.", which opens the dialog "Add Managers". |  |  |  |  |
| MGR-02 | `/draft/:draft_id` | Commissioner | The dialog "Add Managers" is open. | Enter a name. Select **Add** two times and enter two more names. Select **Save**. | Toast "Managers added". The dialog closes. "Draft Order" lists the three Managers as "1st", "2nd", and "3rd", with the buttons **Randomize Order** and **Add Managers**. |  |  |  |  |
| MGR-03 | `/draft/:draft_id` | Commissioner | The dialog is open with three rows. | Select the remove icon of one row. Leave one row empty. Select **Save**. | Only the row with a name is saved. With no name at all, the dialog closes and no request is sent. |  |  |  |  |
| MGR-04 | `/draft/:draft_id` | Commissioner | The dialog is open. | Select **Cancel**. | The dialog closes. No request is sent. |  |  |  |  |
| MGR-05 | `/draft/:draft_id` | Commissioner | The add request gives an API error. | Save one name. | Toast "Unable to add managers". The dialog stays open. |  |  |  |  |
| MGR-06 | `/draft/:draft_id` | Commissioner | A Setting Up Draft with Managers. | Select the edit icon of a Manager. Change the name. Press Enter, or select the save icon. | The name becomes an input. After the save: toast "(name) updated!", and the name shows as text again. |  |  |  |  |
| MGR-07 | `/draft/:draft_id` | Commissioner | A Manager name is in edit mode. | Change the name. Select the cancel icon. | The old name shows. No request is sent. While one Manager is in edit mode, the edit and delete icons of the other Managers do nothing. |  |  |  |  |
| MGR-08 | `/draft/:draft_id` | Commissioner | The update request gives an API error. | Save a changed name. | Toast "Unable to update manager". The input stays. |  |  |  |  |
| MGR-09 | `/draft/:draft_id` | Commissioner | A Setting Up Draft with three Managers. | Select the delete icon of the second Manager. | The Manager is removed at once, with no confirm prompt. The other Managers show "1st" and "2nd". |  |  |  |  |
| MGR-10 | `/draft/:draft_id` | Commissioner | The delete request gives an API error. | Select the delete icon of a Manager. | Toast "Unable to delete manager". The Manager shows again at its place. |  |  |  |  |
| MGR-11 | `/draft/:draft_id` | Commissioner | A Setting Up Draft with three Managers. | Drag the third Manager to the first place. Reload. | "Saving draft order..." shows during the request. The ordinals follow the new Draft Order. The Draft Order is the same after the reload. |  |  |  |  |
| MGR-12 | `/draft/:draft_id` | Commissioner | The reorder request gives an API error. | Drag a Manager. | Toast "Unable to reorder managers". |  |  |  |  |
| MGR-13 | `/draft/:draft_id` | Commissioner | A Setting Up Draft with Managers. | Select **Randomize Order**. Select **Yes, Randomize this draft!**. | The confirm prompt "Randomize the draft order?" shows. After the confirm, the list is hidden, a countdown "3…", "2…", "1…" shows, and then the Managers appear one at a time in the new Draft Order. The buttons show again at the end. The Draft Order is the same after a reload. |  |  |  |  |
| MGR-14 | `/draft/:draft_id` | Commissioner | The confirm prompt of MGR-13 is open. | Select **Cancel**. | The prompt closes. The Draft Order does not change. |  |  |  |  |
| MGR-15 | `/draft/:draft_id` | Commissioner | The reorder request gives an API error. | Confirm **Randomize Order**. | Toast "Unable to reorder managers" and toast "Unable to randomize draft order - error while saving managers." |  |  |  |  |
| MGR-16 | `/draft/:draft_id` | Commissioner | A slow Managers request. | Open the route. | No loading indicator shows in "Draft Order". See W-24. |  |  |  |  |
| STATUS-01 | `/draft/:draft_id` | Commissioner | A Setting Up Draft with two or more Managers. | Select **Start!**. Select **Yep! Let's do this!**. | The confirm prompt "Start draft?" shows. After the confirm: toast "Draft started", and the In Progress view of INDEX-09 shows. |  |  |  |  |
| STATUS-02 | `/draft/:draft_id` | Commissioner | A Setting Up Draft with fewer than two Managers. | Select **Start!** and confirm. | Toast "Unable to start draft" followed by "A draft must have at least 2 managers before it can begin." The Draft stays Setting Up. |  |  |  |  |
| STATUS-03 | Each route with a Draft ID | Commissioner | A Setting Up Draft with two or more Managers. | Open **Commissioner Toolbox**. Select **Start Draft!** and confirm. | The same as STATUS-01. |  |  |  |  |
| STATUS-04 | `/draft/:draft_id` | Commissioner | An In Progress Draft with Selected Picks and a Trade. | Select **Reset**. Select **Yes, reset my draft**. | The confirm prompt "Reset draft?" shows. After the confirm: toast "Draft reset", and the Setting Up view shows. After the Draft is In Progress again, it has no Selected Pick and no Trade. |  |  |  |  |
| STATUS-05 | Each route with a Draft ID | Commissioner | An In Progress Draft. A Commissioner screen other than the Draft index is open. | Open **Commissioner Toolbox**. Select **Reset Draft** and confirm. | Toast "Draft reset". The route is the Draft index. |  |  |  |  |
| STATUS-06 | `/draft/:draft_id` | Commissioner | A Setting Up or In Progress Draft. | Select **Delete**. Select **Yes, Delete the draft**. | The confirm prompt "Delete the draft?" shows. After the confirm: toast "Draft deleted", and the route is `/home`. The Draft is not in the table. |  |  |  |  |
| STATUS-07 | `/draft/:draft_id` | Commissioner | A Complete Draft. | Open **Commissioner Toolbox**. Select **Delete Draft** and confirm. | The same result as STATUS-06. |  |  |  |  |
| STATUS-08 | `/draft/:draft_id` | Commissioner | One of the three confirm prompts is open. | Select **Cancel**. Open the prompt again and select the close button (×). | **Cancel** closes the prompt, and the Draft does not change. The close button does nothing. See W-10. |  |  |  |  |
| STATUS-09 | `/draft/:draft_id` | Commissioner | The status request or the delete request gives an API error. | Confirm **Reset** or **Delete**. | Toast "Unable to reset draft" or "Unable to delete draft". |  |  |  |  |
| STATUS-10 | `/draft/:draft_id` | Admin | A Setting Up Draft of a different user with two Managers. | Select **Start!** and confirm. | The same as STATUS-01. |  |  |  |  |

### 7C Round Times

| ID | Route | Viewer | Starting state | Steps | Expected result | Test file | Passes on AngularJS | Migrated | Known defect |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| TIMES-01 | `/commish/draft/:draft_id/timers` | Commissioner | A Setting Up Draft with no Round Times. | Select **Timers** on the Draft index. | Heading "Pick Timers". The **Use Pick Timers** switch is off. Buttons **Save Timers** and **Cancel**. |  |  |  |  |
| TIMES-02 | `/commish/draft/:draft_id/timers` | Commissioner | The state of TIMES-01. | Turn on **Use Pick Timers**. | The **One Timer for All Rounds** switch, "Max Draft Length (estimated):", and one row for each Round show. Each row has minutes and seconds with a suggested value: Round 1 has 4 minutes, Rounds 2 and 3 have 3 minutes 30 seconds, and later Rounds have less. |  |  |  |  |
| TIMES-03 | `/commish/draft/:draft_id/timers` | Commissioner | The state of TIMES-02. | Change the values of two Rounds. Select **Save Timers**. Open the route again. | Toast "Round timers saved!". The client goes to the previous route. The screen shows the saved values with **Use Pick Timers** on. |  |  |  |  |
| TIMES-04 | `/commish/draft/:draft_id/timers` | Commissioner | The state of TIMES-02. | Turn on **One Timer for All Rounds**. Set 1 minute 30 seconds. Save. Open the route again. | One row "(all)" shows in place of the Round rows. After the save, the switch is on and the row holds the value. The Draft Board uses 90 seconds for each Pick. |  |  |  |  |
| TIMES-05 | `/commish/draft/:draft_id/timers` | Commissioner | A Draft with Round Times. | Turn off **Use Pick Timers**. Save. | The Draft Board shows "Pick timers disabled" (BOARD-06). |  |  |  |  |
| TIMES-06 | `/commish/draft/:draft_id/timers` | Commissioner | A Complete Draft. | Open the route directly. | Warning toast "Draft is still setting up". The client goes to the previous route. See W-14. |  |  |  |  |
| TIMES-07 | `/commish/draft/:draft_id/timers` | Commissioner | An In Progress Draft. | Open the route. Change a value and save. | The same as TIMES-03. |  |  |  |  |
| TIMES-08 | `/commish/draft/:draft_id/timers` | Commissioner | A Draft with 4 Managers and 2 Rounds. The state of TIMES-02. | Set each Round to 1 minute. Then turn on **One Timer for All Rounds**. | "Max Draft Length (estimated):" shows "8 minutes, 0 second" for the Round rows. See W-34. With **One Timer for All Rounds**, it shows a value that is too high, because the hidden Round rows are added too. See W-20. |  |  |  |  |
| TIMES-09 | `/commish/draft/:draft_id/timers` | Commissioner | The state of TIMES-02. | Enter 16 in a minutes field or 60 in a seconds field. Select **Save Timers**. | No request is sent. No message shows. **Save Timers** is not disabled. See W-18. |  |  |  |  |
| TIMES-10 | `/commish/draft/:draft_id/timers` | Commissioner | The save request gives an API error. | Select **Save Timers**. | Toast with the title "Unable to save timers". The screen stays. **Cancel** goes back one step in the browser history. |  |  |  |  |
| TIMES-11 | `/commish/draft/:draft_id/timers` | anonymous | A Setting Up Draft. | Open the route. | No redirect. The form shows with **Use Pick Timers** off, below "Loading your timers..." with no end. See W-01. |  |  |  |  |
| TIMES-12 | `/commish/draft/:draft_id/timers` | another Commissioner | A Setting Up Draft of a different user. | Open the route. | The same as TIMES-11. See W-02. |  |  |  |  |
| TIMES-13 | `/commish/draft/:draft_id/timers` | Commissioner | A Setting Up Draft. The Round Times request gives an API error. | Open the route. | The same as TIMES-11. No toast and no error text show. See W-24. |  |  |  |  |

### 7D Add and edit a Pick

| ID | Route | Viewer | Starting state | Steps | Expected result | Test file | Passes on AngularJS | Migrated | Known defect |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| ADDPICK-01 | `/commish/draft/:draft_id/picks/add` | Commissioner | An In Progress Draft. | Select **Enter Pick** on the Draft index. | The panel "Enter Picks" shows **Search Players** with the focus, the **Manual Entry** switch, the current Pick as an Unselected Pick with its Manager, **Enter pick** (disabled), and **Undo**. The panels "Upcoming Picks" and "Previous Picks" show below. With no Selected Pick: "No picks have been made yet." |  |  |  |  |
| ADDPICK-02 | `/commish/draft/:draft_id/picks/add` | Commissioner | The state of ADDPICK-01. The Pro Player list of the League is loaded. | Enter part of a Pro Player name. Select a suggestion. Select **Enter pick**. | At most 10 suggestions show, each with name, position, and team. After the choice, the Pick shows the name, position, and team, and **Enter pick** is enabled. After the save: the working modal, the toast "(first name) (last name) drafted", the next Pick as the current Pick, and the saved Pick first in "Previous Picks". The search box has the focus again. |  |  |  |  |
| ADDPICK-03 | `/commish/draft/:draft_id/picks/add` | Commissioner | A Pro Player is a Selected Pick of the Draft. | Search for the same Pro Player and select the suggestion. | The dialog "Player was already picked" lists the Selected Pick that matches. |  |  |  |  |
| ADDPICK-04 | `/commish/draft/:draft_id/picks/add` | Commissioner | The dialog of ADDPICK-03. | Select **I'm sure – this is NOT a mistake.** | The dialog closes. The Pick keeps the name. **Enter pick** saves it. |  |  |  |  |
| ADDPICK-05 | `/commish/draft/:draft_id/picks/add` | Commissioner | The dialog of ADDPICK-03. | Select **Whoops, its a mistake – lets undo it.** | The dialog closes. The Pick is an Unselected Pick again. Toast "Pick was reset - go ahead and enter another player." |  |  |  |  |
| ADDPICK-06 | `/commish/draft/:draft_id/picks/add` | Commissioner | The state of ADDPICK-01. | Turn on **Manual Entry**. Enter a first name and a last name that are not in the Pro Player list. Choose a team and a position. Select **Enter pick**. | The search box is hidden, and the four fields show. The Pick display follows the fields. **Enter pick** is enabled only with the four fields filled in. The Pick is saved with the toast of ADDPICK-02. **Manual Entry** is off for the next Pick. |  |  |  |  |
| ADDPICK-07 | `/commish/draft/:draft_id/picks/add` | Commissioner | A name is a Selected Pick of the Draft. | Enter the same name with **Manual Entry**. Select **Enter pick**. | The dialog of ADDPICK-03 shows before the save. The first button saves the Pick. The second button resets the Pick with the toast of ADDPICK-05. |  |  |  |  |
| ADDPICK-08 | `/commish/draft/:draft_id/picks/add` | Commissioner | A Pro Player is chosen but not saved. | Select **Undo**. | The Pick is an Unselected Pick again, and **Manual Entry** is off. |  |  |  |  |
| ADDPICK-09 | `/commish/draft/:draft_id/picks/add` | Commissioner | A Setting Up Draft. | Open the route directly. | Warning toast "Unable to add picks for draft: draft has not been started yet." The client goes to the previous route. A second toast "Unable to get current pick" also shows. See W-16. |  |  |  |  |
| ADDPICK-10 | `/commish/draft/:draft_id/picks/add` | Commissioner | A Complete Draft. | Open the route directly. | Warning toast "Unable to add picks for draft: draft is already completed". The rest is the same as ADDPICK-09. See W-16. |  |  |  |  |
| ADDPICK-11 | `/commish/draft/:draft_id/picks/add` | Commissioner | An In Progress Draft with one Unselected Pick. | Enter the last Pick. | Toast "(name) drafted". The working modal shows for 1.5 seconds. Then the route is the Draft index with the Complete view, and the toast is "Congrats! Your draft has been completed." with the title "That's a Wrap!". |  |  |  |  |
| ADDPICK-12 | `/commish/draft/:draft_id/picks/add` | Commissioner | One of these requests gives an API error: current Pick, Pro Player search, save, duplicate check. | Open the route, search, or save. | Current Pick: toast "Unable to get current pick". Search: toast "Unable to search pro players". Save: toast with the title "Unable to enter pick". Duplicate check: no toast, and with **Manual Entry** the button **Enter pick** stays disabled. See W-19. |  |  |  |  |
| ADDPICK-13 | `/commish/draft/:draft_id/picks/add` | anonymous | An In Progress Draft. | Open the route. | No redirect. Toast "Unable to get current pick". The panel shows with an empty Pick. See W-01. |  |  |  |  |
| ADDPICK-14 | `/commish/draft/:draft_id/picks/add` | another Commissioner | An In Progress Draft of a different user. | Open the route. | The same as ADDPICK-13. See W-02. |  |  |  |  |
| ADDPICK-15 | `/commish/draft/:draft_id/picks/add` | Admin | An In Progress Draft of a different user. | Enter a Pick. | The same as ADDPICK-02. |  |  |  |  |
| EDITPICK-01 | `/commish/draft/:draft_id/picks/edit` | Commissioner | An In Progress Draft in Round 2 with Selected Picks in Rounds 1 and 2. | Select **Change Pick** on the Draft index. | The panel "Edit Pick – Round 2 Picks" shows a pager and the Selected Picks of the current Round, each with an edit button. |  |  |  |  |
| EDITPICK-02 | `/commish/draft/:draft_id/picks/edit` | Commissioner | The state of EDITPICK-01. | Select Round 1 in the pager. Then select a Round with no Selected Pick. | The heading and the list follow the Round. For the empty Round: "Round (number) currently doesn't have any Picks." |  |  |  |  |
| EDITPICK-03 | `/commish/draft/:draft_id/picks/edit` | Commissioner | The state of EDITPICK-01. | Select the edit button of a Pick. Search for a different Pro Player and select it. Select **Update pick**. | The list is replaced by the Pick form of ADDPICK-01 with the button **Update pick**. After the save: toast "(first name) (last name) updated", and the client goes to the previous route. The Draft Board shows the new name. |  |  |  |  |
| EDITPICK-04 | `/commish/draft/:draft_id/picks/edit` | Commissioner | The Pick form of EDITPICK-03. | Turn on **Manual Entry**. Change the first name to a nickname. Select **Update pick**. | The four fields hold the values of the Pick. The save shows no duplicate dialog. The toast and route are the same as EDITPICK-03. |  |  |  |  |
| EDITPICK-05 | `/commish/draft/:draft_id/picks/edit` | Commissioner | A Setting Up or Complete Draft. | Open the route directly. | Warning toast "Unable to edit picks for draft: draft has not been started yet." or "Unable to edit picks for draft: draft is already completed". No redirect. The screen stays empty. See W-15. |  |  |  |  |
| EDITPICK-06 | `/commish/draft/:draft_id/picks/edit` | Commissioner | The Pick form of EDITPICK-03, with a different Pro Player chosen. | Select **Undo**. | The form shows the saved values of the Pick again. No button returns to the list of Picks. See W-31. |  |  |  |  |
| EDITPICK-07 | `/commish/draft/:draft_id/picks/edit` | Commissioner | The Pick form is open. The session is removed on the server. | Select **Update pick**. | The viewer is logged out. The route is `/login`. Two toasts show: "Your login has expired. You must log back in if you wish to continue." and "Unauthorized: please log in." See W-33. |  |  |  |  |
| EDITPICK-08 | `/commish/draft/:draft_id/picks/edit` | Commissioner | The Round Picks request or the save request gives an API error. | Open the route, or save. | Toast "Unable to load picks", or a toast with the title "Unable to edit pick". |  |  |  |  |
| EDITPICK-09 | `/commish/draft/:draft_id/picks/edit` | anonymous | An In Progress Draft with Selected Picks. | Open the route. Select the edit button of a Pick. Change it and select **Update pick**. | No redirect at first. The list and the form show. The save gives the toast "Unauthorized: please log in.", and the route is `/login`. See W-01. |  |  |  |  |
| EDITPICK-10 | `/commish/draft/:draft_id/picks/edit` | another Commissioner | An In Progress Draft of a different user with Selected Picks. | The same steps as EDITPICK-09. | The list and the form show. The save gives the toast "Unknown 400 error" with the title "Unable to edit pick". See W-02 and W-04. |  |  |  |  |

### 7E Add a Trade

| ID | Route | Viewer | Starting state | Steps | Expected result | Test file | Passes on AngularJS | Migrated | Known defect |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| ADDTRADE-01 | `/commish/draft/:draft_id/trades/add` | Commissioner | An In Progress Draft. | Select **Enter Trade** on the Draft index. | The panel "Enter a new Trade" shows "Choose two managers to enter a trade for:", the menus "Choose one manager" and "Choose another manager", and **Enter New Trade** (disabled). |  |  |  |  |
| ADDTRADE-02 | `/commish/draft/:draft_id/trades/add` | Commissioner | The state of ADDTRADE-01. | Choose a Manager in the first menu. Open the second menu. | The Picks of the Manager show below the menu, each with a round button. In the second menu, the same Manager is disabled, and "GAAHH NO!" shows beside the name. See W-22. |  |  |  |  |
| ADDTRADE-03 | `/commish/draft/:draft_id/trades/add` | Commissioner | The state of ADDTRADE-02. | Choose a different Manager in the second menu. | The Picks of the second Manager show. The heading changes to "Choose at least one asset to trade:". |  |  |  |  |
| ADDTRADE-04 | `/commish/draft/:draft_id/trades/add` | Commissioner | Two Managers are chosen. | Select one Pick of the first Manager only. Then select one Pick of the second Manager. | With Picks of one Manager only, **Enter New Trade** stays disabled. With one or more Picks of each Manager, it is enabled. A second click on a Pick removes the choice. |  |  |  |  |
| ADDTRADE-05 | `/commish/draft/:draft_id/trades/add` | Commissioner | The state of ADDTRADE-04 with a Pick of each Manager chosen. | Select **Enter New Trade**. | Toast "Trade entered". The route is the Trades screen, which shows the Trade (TRADES-03). Each Traded Pick has the new Manager on the Draft Board. |  |  |  |  |
| ADDTRADE-06 | `/commish/draft/:draft_id/trades/add` | Commissioner | A Setting Up Draft. | Open the route directly. | Warning toast "Unable to add trade for draft: draft has not been started yet." The client goes to the previous route. |  |  |  |  |
| ADDTRADE-07 | `/commish/draft/:draft_id/trades/add` | Commissioner | A Complete Draft. | Open the route directly. | Warning toast "Unable to add trade for draft: draft is already completed". The client goes to the previous route. |  |  |  |  |
| ADDTRADE-08 | `/commish/draft/:draft_id/trades/add` | Commissioner | One of these requests gives an API error: Managers, Picks of a Manager, save. Or the session is removed on the server before the save. | Open the route, choose a Manager, or save. | Managers: toast "Unable to load managers for trade". Picks: toast "Unable to load assets for manager". Save: toast with the title "Unable to enter trade". No session: the same as EDITPICK-07. See W-33. |  |  |  |  |
| ADDTRADE-09 | `/commish/draft/:draft_id/trades/add` | anonymous | An In Progress Draft. | Open the route. Choose a Manager. | No redirect. The panel and the menus show. Toast "Unable to load assets for manager". See W-01. |  |  |  |  |
| ADDTRADE-10 | `/commish/draft/:draft_id/trades/add` | another Commissioner | An In Progress Draft of a different user. | The same steps as ADDTRADE-09. | The same as ADDTRADE-09. See W-02. |  |  |  |  |

## Phase 8: Admin screens

### 8A Users

| ID | Route | Viewer | Starting state | Steps | Expected result | Test file | Passes on AngularJS | Migrated | Known defect |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| USERS-01 | `/admin/users` | Admin | Several users. One is Disabled. | Open **Admin Stuff**, then **Users**. | Heading "Manage Users". The table has the columns **Email**, **Name**, and **Enabled?** ("Yes" or "No"), and the buttons **Edit** and **Delete** in each row. |  |  |  |  |
| USERS-02 | `/admin/users` | Admin | The state of USERS-01. | Enter text in "Search emails...". Enter text in "Search names...". Choose "No" in the **Enabled?** filter, then "remove". | Each input filters its column. The filter shows only Disabled users. "remove" shows each user again. |  |  |  |  |
| USERS-03 | `/admin/users` | Admin | More than 15 users. | Select the headers **Email**, **Name**, and **Enabled?**. Select page 2. | Each header sorts the rows. 15 rows show on each page. |  |  |  |  |
| USERS-04 | `/admin/users` | Admin | The state of USERS-01. | Select **Edit** in a row. | The dialog "Edit User" shows **Email**, **Name**, the **Enable** switch, and the checkboxes **Full Site Administrator** and **Commissioner**, with the values of the user. |  |  |  |  |
| USERS-05 | `/admin/users` | Admin | The dialog of USERS-04. | Change the name. Select **Save**. | The dialog closes. The table loads again and shows the new name. |  |  |  |  |
| USERS-06 | `/admin/users` | Admin | The dialog of USERS-04 for an Enabled user. | Turn off **Enable**. Select **Save**. Then try to log in as that user. | The row shows "No". The login gives the result of LOGIN-06. After **Enable** is on again, the login works. |  |  |  |  |
| USERS-07 | `/admin/users` | Admin | The dialog of USERS-04 for a Commissioner. | Select **Full Site Administrator**. Select **Save**. Log in as that user. | The user sees **Admin Stuff** in the navigation. |  |  |  |  |
| USERS-08 | `/admin/users` | Admin | The dialog of USERS-04. | Clear **Email** or **Name**. Select **Save**. | No request is sent. The dialog stays open. No message shows. See W-17. |  |  |  |  |
| USERS-09 | `/admin/users` | Admin | The dialog of USERS-04. | Change the name. Select **Cancel**. | The dialog closes. No request is sent. The row shows the changed name until the table loads again. See W-26. |  |  |  |  |
| USERS-10 | `/admin/users` | Admin | The state of USERS-01. | Select **Delete** in a row. Select **Yes, Delete User**. | The confirm prompt "Delete (name)?" shows. After the confirm: the working modal, the toast "User deleted", and one row less. With a sorted or filtered table, the row that leaves the table can be the wrong one. See W-26. **Cancel** closes the prompt with no change. |  |  |  |  |
| USERS-11 | `/admin/users` | two viewers | A Commissioner is logged in. | The Admin makes the user Disabled. | The Commissioner gets the result of NAV-06 at the next request. |  |  |  |  |
| USERS-12 | `/admin/users` | anonymous | None | Open the route. | No redirect. The heading and an empty table show. Toast "Unable to load users". See W-03. |  |  |  |  |
| USERS-13 | `/admin/users` | Commissioner | Logged in, not an Admin. | Open the route. | The same as USERS-12. See W-03. |  |  |  |  |
| USERS-14 | `/admin/users` | Admin | One of these requests gives an API error: users, update, delete. The delete of an Admin user is such a case. | Open the route, save, or delete. | Toast "Unable to load users", "Unable to update user" (the dialog closes), or "Unable to delete user". |  |  |  |  |

### 8B Pro Player upload

| ID | Route | Viewer | Starting state | Steps | Expected result | Test file | Passes on AngularJS | Migrated | Known defect |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| PRO-01 | `/admin/proplayers` | Admin | Logged in. | Open **Admin Stuff**, then **Player Data**. | Heading "Manage Autocomplete Data". The **League** menu shows "Pick a league, any league" and lists each League. The file input **CSV Player Data File** takes `.csv` files. Button **Upload Player Data**. |  |  |  |  |
| PRO-02 | `/admin/proplayers` | Admin | The state of PRO-01. A valid CSV file for a League. | Choose the League. Choose the file. Select **Upload Player Data**. | "Uploading. . ." with a progress bar replaces the button. Then toast "(League) player data updated!", and the **League** menu shows "Pick a league, any league" again. The Pro Player search of ADDPICK-02 finds a name from the file. |  |  |  |  |
| PRO-03 | `/admin/proplayers` | Admin | The state of PRO-01. | Select **Upload Player Data** with no League, and then with a League but no file. | Warning toast "You must select a league AND a CSV file first!". No request is sent. The message "League is missing" never shows. See W-17. |  |  |  |  |
| PRO-04 | `/admin/proplayers` | Admin | A CSV file larger than 768 KB. | Choose a League and the file. Select **Upload Player Data**. | No request is sent. The warning toast of PRO-03 shows. The text "File too large" shows below the input (to confirm). |  |  |  |  |
| PRO-05 | `/admin/proplayers` | Admin | A CSV file that the API refuses. | Choose a League and the file. Select **Upload Player Data**. | Toast "Unable to upload player data: (API messages)". The button shows again. |  |  |  |  |
| PRO-06 | `/admin/proplayers` | Admin | The Leagues request gives an API error. | Open the route. | Toast "Unable to load league data, check dev tools and report findings to the open source project (please!)". |  |  |  |  |
| PRO-07 | `/admin/proplayers` | anonymous | None | Open the route. | No redirect. The form shows with an empty **League** menu. The toast of PRO-06 shows. See W-03. |  |  |  |  |
| PRO-08 | `/admin/proplayers` | Commissioner | Logged in, not an Admin. | Open the route. | The same as PRO-07. See W-03. |  |  |  |  |

### 8C Regenerate Draft Stats

| ID | Route | Viewer | Starting state | Steps | Expected result | Test file | Passes on AngularJS | Migrated | Known defect |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| ADMSTATS-01 | `/admin/stats` | Admin | Complete Drafts with and without Draft Stats, and Drafts in the other statuses. | Open **Admin Stuff**, then **Draft Stats**. | The working modal shows during the load. Heading "Manage Draft Stats". The table lists the Complete Drafts only, with the columns **League Name** (a link to the Draft index), **Sport**, **Commissioner**, and **When Generated**, and the button **Generate Stats** in each row. **When Generated** shows a date and the link "view" to the Draft Stats screen, or "Never". |  |  |  |  |
| ADMSTATS-02 | `/admin/stats` | Admin | A Complete Draft with "Never". | Select **Generate Stats** in the row. | The working modal shows. Toast "Stats regenerated!". The table loads again, and the row shows the date of today and "view". The Draft Stats screen shows values (STATS-03). |  |  |  |  |
| ADMSTATS-03 | `/admin/stats` | Admin | A Complete Draft with Draft Stats. | Select **Generate Stats** in the row. | The same toast as ADMSTATS-02. The Draft Stats are made again. |  |  |  |  |
| ADMSTATS-04 | `/admin/stats` | Admin | More than 15 Complete Drafts. | Enter text in "Search Leagues...". Select each sort header. Select page 2. | The input filters by Draft name. Each header sorts the rows. 15 rows show on each page. |  |  |  |  |
| ADMSTATS-05 | `/admin/stats` | Admin | Complete Drafts of two Commissioners. | Enter a name in "Search Commissioners..." and select a suggestion. | The filter is not bound to a column. The result is not settled by the client code (to confirm). See W-27. |  |  |  |  |
| ADMSTATS-06 | `/admin/stats` | Admin | The Drafts request or the generate request gives an API error. | Open the route, or select **Generate Stats**. | Toast "Unable to load drafts, check dev tools and report findings to the open source project (please!)", or "Unable to regenerate stats: (API message)". With no Draft in the table: "No drafts here, chief." |  |  |  |  |
| ADMSTATS-07 | `/admin/stats` | anonymous | None | Open the route. | No redirect. The heading and "No drafts here, chief." show. The first toast of ADMSTATS-06 shows. See W-03. |  |  |  |  |
| ADMSTATS-08 | `/admin/stats` | Commissioner | Logged in, not an Admin. | Open the route. | The same as ADMSTATS-07. See W-03. |  |  |  |  |

## Behavior that looks wrong in the AngularJS client

This list is for the maintainer's decision. The AngularJS code is not fixed (Section 2.3, item 9). For each item, choose one:

- **Fix in the new client.** The rows change to the correct result, the test is a known failure on AngularJS, and the "Known defect" column gets the item number.
- **Keep.** The rows stay, and the new client copies the behavior.

Each item has a proposal. No row changes before the maintainer decides.

### Access and routes

| Item | What happens today | Rows | Proposal | Decision |
| --- | --- | --- | --- | --- |
| W-01 | The client has no route guards. An anonymous viewer on `/profile` or on a `/commish` route sees the screen with error toasts. Issue 04 already plans the guards. | PROFILE-09, CREATE-12, EDIT-09, TIMES-11, ADDPICK-13, EDITPICK-09, ADDTRADE-09 | Fix: redirect to `/login`. | yes |
| W-02 | A Commissioner on a `/commish` route of a Draft of a different user sees the screen with error toasts. | EDIT-10, TIMES-12, ADDPICK-14, EDITPICK-10, ADDTRADE-10 | Fix: redirect to the Draft index. | yes |
| W-03 | The Admin routes set `adminOnly: true`, but no code reads it. Each viewer sees the Admin screens with error toasts. The API refuses the data, so nothing leaks. | USERS-12, USERS-13, PRO-07, PRO-08, ADMSTATS-07, ADMSTATS-08 | Fix: an anonymous viewer goes to `/login`, and a Commissioner goes to `/home`. | yes |
| W-04 | When an API route check fails (not the Draft's Commissioner, wrong Draft status, Draft Password), the API puts the error object inside a one-element array. The client expects an object and shows "Unknown 400 error". | EDITPICK-10, and each save that the API refuses for these reasons | Fix in the client API layer: read the messages from the array. The API does not change. | yes |
| W-05 | No fallback route exists. An unknown URL shows an empty screen. | NAV-10 | Fix: redirect to `/home`. | yes |
| W-06 | `sendToPreviousPath()` goes to `/home` unless the viewer opened four or more routes in the tab. It also removes two entries from the stored history on each call. Its list of auth routes holds `/forgotPassword`, which is not a route, and lacks `/lostPassword` and `/inviteSetup`. | LOGIN-03, LOGIN-04, LOGIN-13, BOARD-01, PROFILE-02, TIMES-03, EDITPICK-03, and each "previous route" result | Fix: go to the last route that is not an auth route, and to `/home` when none exists. | yes |

### Draft loading and the Draft Password

| Item | What happens today | Rows | Proposal | Decision |
| --- | --- | --- | --- | --- |
| W-07 | The panel "Unable to load draft" exists, but no code turns it on. A missing Draft gives two toasts and an empty screen. | DRAFT-11 | Fix: show the panel, and one toast. | yes |
| W-08 | The "Draft Locked" panel reads `commishName` from the wrong scope, so the Commissioner name is empty two times in the text. | PW-01 | Fix: show the name. | yes |
| W-09 | On a locked Draft, the Draft Board shows its loading text with no end, and Picks by Manager and the Depth Chart ask for Managers and show an error toast. | PW-09 | Fix: show only the panel and the dialog. | yes |
| W-10 | The close button (×) of the Draft Password dialog, the confirm prompt, and the duplicate Pick dialog calls `cancelClicked()`, which does not exist. | PW-10, STATUS-08 | Fix: the button closes the dialog, the same as **Cancel**. | yes |
| W-11 | A Setting Up Draft is not polled. A second viewer does not see the change to In Progress without a reload. Issue 06 keeps this rule ("Polling runs only while the Draft is In Progress"). | INDEX-17 | Keep, as issue 06 says. Confirm. | yes |

### Multi-viewer updates

| Item | What happens today | Rows | Proposal | Decision |
| --- | --- | --- | --- | --- |
| W-12 | Picks by Round, Picks by Manager, and search use `this.lodash` in the update handler but do not inject it. The handler throws, so the lists never refresh for a second viewer. Picks by Manager also passes the wrong arguments to its reload. | ROUND-06, MANAGER-06, SEARCH-10 | Fix: refresh after the next poll, the same as the Draft index. | yes |
| W-13 | The Draft index adds one more listener for Draft changes on each change. The requests for the last and next Picks double with each Pick. No row shows this. | None | Do not copy. The draft store has one subscription. | yes |
| W-39 | A reset does not change the Draft counter, and the client tells the screens about a change only when the counter changes. After the next poll, the Draft index of a second viewer shows the Setting Up view, but "Draft Order" is empty: no Managers and no "has not added any managers yet" text. The client also goes on polling the Setting Up Draft. Found by the test of INDEX-16. | INDEX-16, BOARD-18 | Fix: after a status change, the second viewer gets the Setting Up view of INDEX-01 with the Draft Order, and polling stops. The draft store reacts to a change of the Draft status, not only of the counter. | yes |
| W-40 | A poll that finds changed Picks, with no Selected Pick yet, throws on the Draft Board: the API sends no last Pick, and the client reads its round without a check. The client updates the current Pick and no other changed Pick. A Trade of two Unselected Picks before the first Selected Pick changes only the first cell until the viewer reloads. Found by the test of BOARD-15. | BOARD-15 | Fix: skip a missing last Pick, and update each changed Pick. | yes |

### Draft status rules

| Item | What happens today | Rows | Proposal | Decision |
| --- | --- | --- | --- | --- |
| W-14 | Two redirects use the text "Draft is still setting up" for a different status: Draft Stats for an In Progress Draft, and Round Times for a Complete Draft. | STATS-02, TIMES-06 | Fix with new text. Suggested: "Draft is not complete yet" and "Draft is already completed". | yes |
| W-15 | The edit Pick screen shows a warning for a Setting Up or Complete Draft but does not redirect, and the screen stays empty. The edit Draft screen has no check of the Draft status at all. | EDITPICK-05, EDIT-07 | Fix: warn and go to the Draft index, the same as add Pick and add Trade. | yes |
| W-16 | The add Pick screen redirects for a Setting Up or Complete Draft, but still asks the API for the current Pick. A second toast "Unable to get current pick" shows. | ADDPICK-09, ADDPICK-10 | Fix: one warning toast. | yes |

### Forms

| Item | What happens today | Rows | Proposal | Decision |
| --- | --- | --- | --- | --- |
| W-17 | Some validation messages can never show. Edit Draft and the Admin "Edit User" dialog read the error state from the model, not the form. On create Draft, **Style** and **Sport** have no field name, and the **League Name** messages wait for a submit that a disabled button prevents. On the Pro Player upload, "League is missing" reads a field that does not exist. | CREATE-10, EDIT-04, USERS-08, PRO-03 | Fix: show each message after the field loses focus, the same as the auth forms. | yes |
| W-18 | Some submit buttons are never disabled, because the template calls a function name that does not exist: Reset and invite setup (`registerFormIsInvalid`), Round Times (`timerFormIsInvalid`). Lost password never checks the reCAPTCHA state. | RESET-04, INVITE-04, TIMES-09, LOST-04 | Fix: disable each button while its form is invalid or the reCAPTCHA is not complete. | yes |
| W-19 | `pickService` uses `messageService` in its error handler but does not inject it. When the duplicate check fails, the handler throws: no toast shows, and with **Manual Entry** the button stays disabled. | ADDPICK-12 | Fix: toast "Unable to search for player already drafted", and enable the button. | yes |
| W-20 | With **One Timer for All Rounds**, "Max Draft Length" adds the one Round Time for each Round and then adds the hidden Round rows too. | TIMES-08 | Fix: count the one Round Time only. | yes |
| W-21 | **Clear** on the search screen empties the fields but keeps the old results below the hint. | SEARCH-08 | Fix: remove the results. | yes |
| W-22 | The second Manager menu of add Trade shows the debug text "GAAHH NO!" beside the Manager chosen in the first menu. | ADDTRADE-02 | Fix: remove the text. | yes |
| W-23 | The note about IR, DL, and NA positions never shows for "DL". The code compares the League with `!'NFLE'`, which is always false. | CREATE-07 | Fix: show the note for "DL" unless the League is NFLE. Confirm the intent. | yes |

### Lists and indicators

| Item | What happens today | Rows | Proposal | Decision |
| --- | --- | --- | --- | --- |
| W-24 | Four templates use `<section-loading>` or `<section-error>` without the `phpd` prefix. These are not component names, so nothing shows: the home page, the Manager editor, the Depth Chart, and Round Times. Round Times also binds the two tags to `draftLoading` and `draftError`, but the controller sets `pickTimerDataLoading` and `pickTimerDataError`. Three more places use the correct tag with a name that is never set: the Round Picks of a Complete Draft index, the "Last 5 Picks" error, and the Picks by Round error. | HOME-16, MGR-16, DEPTH-11, TIMES-13, INDEX-11, ROUND-05 | Fix: show the loading and error indicators. | yes |
| W-25 | The home table: the **Created** header looks like a sort header but has no sort, and the default sort that the template asks for does not apply. The search box "Search Leagues..." looks at each column. **Reset All Filters** leaves the old text in the search box (a known issue in a code comment). | HOME-02, HOME-07, HOME-11 | Fix: sort by **Created**, newest first by default. Search the Draft name only. Clear the box. | yes |
| W-26 | Admin users: after a delete, the client removes a row by its place in the visible table, which is the wrong row when the table is sorted, filtered, or on a later page. The API deletes the correct user. **Cancel** in "Edit User" keeps the unsaved edits in the row until the next load. | USERS-09, USERS-10 | Fix: remove the correct row, and edit a copy. | yes |
| W-27 | The Commissioner filter on the Admin Draft Stats screen has no column binding. It probably filters out each row or no row. Phase 2 confirms. | ADMSTATS-05 | Fix: filter by Commissioner, the same as the home page. | yes |
| W-28 | `messageService` has default toast titles ("Success!", "Error", "Information", "Warning"), but the check uses `null` and the callers pass nothing. Most toasts have no title. | Each toast row | Keep: no title unless the row names one. | yes |
| W-41 | On the search screen, the **Position** and **Team** lists show a blank option at load and after **Clear**, not "(all positions)" and "(all teams)". The empty model is `''`, and the empty option matches only `null`. | SEARCH-02, SEARCH-08 | Fix: start both models empty in a way that selects the option, and set them the same way on **Clear**. | yes |
| W-42 | The Picks request of a Manager gives no Manager name for a Pick, so no row in Picks by Manager shows a Manager name, Selected or not. Picks by Round shows the name. | MANAGER-02, MANAGER-03, MANAGER-04 | Fix: show the name of the chosen Manager in each row. | yes |
| W-43 | The client asks for the Round Picks twice on load, and for the Picks of a Manager twice on load. An API error gives the toast twice. | ROUND-05, MANAGER-05 | Fix: one request, one toast. | yes |

### Text and open questions

| Item | What happens today | Rows | Proposal | Decision |
| --- | --- | --- | --- | --- |
| W-29 | The footer shows the fixed text "HootDraft v2.4.0". | NAV-08 | Decide the text for the relaunch. | relaunch will have "HootDraft v3.0.0" |
| W-30 | The Depth Chart route has no check that the Draft uses Depth Charts. The Draft navigation bar hides the link, but the URL opens. | DEPTH-09 | Fix: redirect to the Draft index. Phase 2 confirms what shows today. | yes |
| W-31 | The edit Pick form has no way back to the list of Picks. **Undo** only resets the fields. | EDITPICK-06 | Decide: add **Cancel**, or keep. | add Cancel |
| W-32 | The "Bad Reset Key" panels on Reset and invite setup hold a joke about Hitler. | RESET-03, INVITE-03 | Decide if the text stays for the relaunch. | replace "Hitler" with "Sauron" |
| W-33 | Two paths handle a 401 on the save of edit Pick and add Trade: the shared session-expiry handler ("Your login has expired. ...") and a local handler ("Unauthorized: please log in."). A logged-in viewer gets two toasts. | EDITPICK-07, ADDTRADE-08 | Fix: one handler, the shared one. | yes |
| W-34 | The filter that writes a duration in words gives "0 second" for zero seconds, and "60 seconds" for one minute exactly (it uses "more than", not "or more"). | TIMES-08, STATS-03 | Fix: "0 seconds" and "1 minute". | yes |
| W-35 | On `/login`, the **Email** field has the focus. When the viewer presses the mouse button on **Lost password?** or **Create a new account!**, the field loses the focus and the message "Email is missing" shows. The message pushes the link down before the mouse button comes up, so the click does nothing. | LOGIN-01 | Fix: the links keep their place. For example, reserve the space for the messages. | yes |
| W-36 | At phone width, the menu closes after a route change only for the links with their own handler: **New League**, **Invite A Friend**, and the links of the **Admin Stuff** menu. After **Commissioner Login**, **Log Out**, the logo, and the name link, the menu stays open. These links have no handler for it. The Draft navigation closes on each route change. | NAV-09 | Fix: close the menu on each route change. | yes |
| W-37 | The Commissioner filter on the home table throws a `TypeError` (`actual.toLowerCase is not a function`) when the viewer selects a suggestion, so no Draft is filtered out, although the label shows. The filter sends the Commissioner ID as a number, and `customSmartTableFilter` calls `toLowerCase()` on it. Found by the test of HOME-05. The cause is that the API now sends numeric IDs (the PHP 8 rewrite), and the filter assumed strings. W-27 has a different cause: that screen has no column binding. | HOME-05 | Fixed in AngularJS with a string comparison. The new client uses typed IDs and `===`. | fixed in AngularJS |
| W-38 | The home table saves the page number in the tab, but it shows page 1 again after the viewer opens a Draft and goes back. The filter and the sort come back. Found by the test of HOME-12. The cause: smart-table sets the page to 1 each time the Draft array is replaced, and the Drafts arrive after the saved state is loaded. | HOME-12 | Fixed in AngularJS: the saved page is set again once the Drafts load. The new client keeps the page. | fixed in AngularJS |
