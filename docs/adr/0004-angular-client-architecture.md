# The Angular client is a clean rewrite: zoneless, signals in components, SignalStore

The AngularJS 1.8 client is end of life. A new Angular app in `client/` replaces it. The app is small (about 7,400 lines) and already component-shaped, and the public instance was down during the rewrite, so no live traffic needed protection.

The new client follows these rules:

- The app is zoneless. It has no zone.js. This is where the performance gain comes from.
- A component reads signals and calls store methods. It never subscribes to an Observable.
- State is in NgRx SignalStore. RxJS stays inside the stores, for HTTP calls and for streams.
- The draft store gets Draft updates from one "draft updates" source. Polling is the only implementation.
- The client uses the `GLOSSARY.md` terms. One API layer mirrors the API field names, such as `draft_sport`, and maps them. The API was frozen during the rewrite, so the old names stay there.

## Considered options

- **Clean rewrite in a new project.** Chosen. The app is small, and nothing was live.
- **Hybrid app with `ngUpgrade`.** Rejected. A hybrid needs zone.js and two build systems, which conflicts with the zoneless rule.
- **NgRx SignalStore.** Chosen. It is signal-native, and it uses RxJS only where a stream is the correct tool.
- **Classic NgRx Store (actions, reducers, effects).** Rejected. Its core is RxJS, which conflicts with the signals-only rule for components.
- **Mercure (server-sent events) in place of polling.** Deferred. The "draft updates" source lets a later refactor add it with no change to components.
- **API field renames to the glossary terms.** Deferred. The API was frozen for the rewrite.

## Consequences

- Don't add a subscription to a component, and don't add zone.js to fix a change detection problem. Fix the signal.
- You can't use a widget library that needs zone.js. Widgets are hand-written with the Angular CDK and output the Bootstrap 3 markup.
- An API field rename touches only the API layer.
