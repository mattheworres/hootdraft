# 07: `/me` returns `roles`; role parsing and the Admin check live in one place

**What to build:** `GET /me` returns `name`, `is_admin` and `roles` for an active session, as spec 3.d.7 asks; today `roles` is missing. While making that change, put the knowledge of roles in one place.
- The comma-separated `roles` column is split in two places (the `LoginUser` entity and the user provider)
- "Is this user an Admin" is decided in three (the security `User`, `LoginUserService` and `LoginUserValidator`)

**Blocked by:** 01, 05 (both edit `LoginUserService` and `LoginUserValidator`)

**Status:** ready-for-agent

- [ ] `GET /me` returns `roles` as an array alongside `name` and `is_admin`, and still returns 401 without a session
- [ ] Splitting the `roles` column and deciding Admin each have one implementation that the other call sites use
- [ ] The Admin user list and update responses keep their current shape
- [ ] Functional tests for `/me` assert `roles` for a Commissioner and for an Admin. Existing Admin and auth tests pass unchanged
