# No server-side rendering

nginx serves the Angular client as static files. HootDraft must stay installable on a host that has only nginx, PHP, and a database, and almost every screen depends on live Draft data or a login.

## Considered options

- **Static files only.** Chosen. It needs no new server software.
- **Angular server-side rendering (SSR).** Rejected. It needs a Node.js process on the server, which breaks the install condition.
- **Prerender at build time.** Rejected. It needs no Node.js on the server, but it gives almost no benefit to screens that depend on live data or a login.

## Consequences

- The fast first load comes from a small zoneless bundle and lazy-loaded routes.
- Search engines get no rendered content from Draft pages.
- Open this decision again only if the install condition changes, or if public pages need search engine indexing.
