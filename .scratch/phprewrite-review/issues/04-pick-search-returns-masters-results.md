# 04: Pick search returns the same Pro Players as master

**What to build:** Searching for a Pro Player while entering a Pick returns the same results as master. Master ran the strict full-text search and then always ran the loose search, which replaced the strict results. The split-name search ran only if both found nothing. The port runs the loose search only when the strict one finds nothing. Users therefore get only whole-word matches, least relevant first: "Tom" no longer finds "Tomlinson", and the ASC/DESC toggle mostly has no effect. Nobody decided this, and it isn't recorded under the 4.4 decisions.

**Blocked by:** 01

**Status:** ready-for-agent

- [ ] The public pick search returns what master's sequence would return for the same keywords, team, position and sort
- [ ] The split-name fallback still runs only when no results were found
- [ ] Functional tests cover: a partial name that only the loose search finds, a full name that both searches find (loose results and ordering win), the sort toggle changing the order, and a "First Last" search reaching the split-name fallback
- [ ] If the strict search is now dead code, keep it or remove it, and say which in the handoff note
