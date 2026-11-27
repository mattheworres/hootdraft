# 02w: CI speed and runner minutes

**What to build:** Changes to `.github/workflows/ci.yml` that make a CI run shorter and use fewer runner minutes. The repository is public, so GitHub-hosted runners cost nothing, but the project uses them with care.

**Blocked by:** 02a

**Status:** ready-for-agent

## Where the time goes

Measured on run 45857262402 (02j pull request, four browsers, all passed). Read a run with `gh run view <id> --json jobs`.

- A pull request run takes about 11 minutes: `composer check` (163s), then the slowest browser job (Firefox, 492s). It uses about 32 runner minutes.
- Each browser job: build the client 13–20s, install the browser 15–49s, start the E2E stack 67–85s, run the suite 259–359s.
- Inside "Start the E2E stack": the PHP image build takes about 54s. Its `apt-get` and PHP-extension step takes 44s of that. The database takes about 11s to become healthy. `composer install` takes about 3s.
- `composer check` spends 82s on "Start the developer box", which builds the same PHP image.

The 02L pull request added three changes: one client build for all browser jobs, a `vendor/` cache, and the yarn and npm caches. They save about 25s per browser job. Take the "after" numbers from the second run of that branch, because the first run fills the caches.

## Work

- [ ] Record the timings of the second run after the 02L pull request, in the same form as above
- [ ] Cache the layers of the PHP image in the GitHub Actions cache, in `composer check` and in each browser job. The two Compose files pass a different `APP_UID`, so only the layers before the `usermod` step are shared. Check that the `apt-get` and PHP-extension layer comes from the cache on a second run
- [ ] Start the E2E stack in the background while the browser installs, and wait for it before the suite runs. The two steps do not depend on each other
- [ ] Ask the maintainer whether to shard the slowest browser jobs. A shard adds its own setup time, about 2 minutes today, but no test runs twice. Do it only if the maintainer agrees
- [ ] Record the new timings and runner minutes of a pull request run here, next to the baseline

## Not in scope

- Caching the Playwright browsers: most of the install time goes to system packages, which the cache does not hold, and Edge installs as a system package.
- Skipping CI for docs-only changes: a required check that never runs blocks the merge.
