# 02u: Screenshot report tool

**What to build:** The tool that compares the appearance of the old client and the new client. See `AngularRewrite.md`, Section 2.3, item 11. It is a review aid, not a gate.

**Blocked by:** 02a

**Status:** ready-for-agent

**Note:** The new client does not exist before ticket 04. Prove the tool with the AngularJS client on each side: the difference is 0%. Then change one style on one side and check that the report shows it.

- [ ] Input: a list of screens, each with a route and the setup that the screen needs, and the URL of each client
- [ ] For each screen, capture the old client and the new client in Chromium at one desktop viewport
- [ ] Output: one static HTML page with old, new, difference overlay, and the percentage of changed pixels, largest difference first
- [ ] The setup of each screen uses the API setup helpers of ticket 02a
- [ ] The GitHub Actions workflow uploads the report as a zip file and links it from the run summary
- [ ] `e2e/README.md` states how to add a screen and how to run the tool
