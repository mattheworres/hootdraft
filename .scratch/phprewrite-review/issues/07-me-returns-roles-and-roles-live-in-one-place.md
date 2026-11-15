# 07: `/me` returns `roles`; role parsing and the Admin check live in one place

**What to build:** `GET /me` returns `name`, `is_admin` and `roles` for an active session, as spec 3.d.7 asks; today `roles` is missing. While making that change, put the knowledge of roles in one place.

- The comma-separated `roles` column is split in two places (the `LoginUser` entity and the user provider)
- "Is this user an Admin" is decided in three (the security `User`, `LoginUserService` and `LoginUserValidator`)

**Blocked by:** 01, 05 (both edit `LoginUserService` and `LoginUserValidator`)

**Status:** awaiting-human-review

- [x] `GET /me` returns `roles` as an array alongside `name` and `is_admin`, and still returns 401 without a session
- [x] Splitting the `roles` column and deciding Admin each have one implementation that the other call sites use
- [x] The Admin user list and update responses keep their current shape
- [x] Functional tests for `/me` assert `roles` for a Commissioner and for an Admin. Existing Admin and auth tests pass unchanged

## Comments

**Agent (implementation):**

- `GET /me` returns `roles`, a list such as `["ROLE_COMMISH","ROLE_ADMIN"]`. Only `/me` adds it. The login, Email Verification Key link and Reset responses do not include `roles` (maintainer decision).
- `/me` returns every granted role, with the `security.yaml` role hierarchy applied. An Admin stored as only `ROLE_ADMIN` also reports `ROLE_COMMISH`, so the client can check `ROLE_COMMISH` for every Commissioner (maintainer decision, to allow a future role below Commissioner).
- `LoginUser::parseRoles` is the only place that splits roles. It trims each role and removes empty entries. `UserProvider` uses it.
- `LoginUser::rolesIncludeAdmin` is the only Admin check. `Security\User::isAdmin`, `LoginUser::isAdmin` and `LoginUserValidator` use it.
- Removed `LoginUserService::IsAdmin`. `Admin\UserController::delete` now calls `$user->isAdmin()`.
- Behaviour change (maintainer accepted): an Admin update now stores the parsed list. `" ROLE_COMMISH, ROLE_ADMIN,"` is accepted and stored as `ROLE_COMMISH,ROLE_ADMIN`. Before, the trailing comma gave "Role invalid.". An empty value is still invalid. A list with an empty entry (`["ROLE_COMMISH",""]`) is still invalid.
- `LoginUser::ROLE_COMMISH` and `LoginUser::ROLE_ADMIN` are the role names (maintainer request to DRY up code and eliminate these magic strings). All code and tests use them. `security.yaml` reads them with `!php/const`. Only doc comments still name the roles as text.
- Not changed: `LoginUserRepository` still joins roles with `implode` in 3 places.
