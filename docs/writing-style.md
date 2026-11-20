# Writing style

This page defines how to write the documents in `docs/`, the plans at the root of the repository, and the issue files in `.scratch/`. It applies to the implementation plan, the glossary, and the architecture decision records (ADRs).

## Base style guide

Follow the [Google developer documentation style guide](https://developers.google.com/style). Start with its [highlights page](https://developers.google.com/style/highlights). When a rule on this page conflicts with the Google guide, this page wins.

The Google rules that come up most often in these documents:

- Address the reader as "you". Write steps as instructions: "Add the Draft store", not "We will add the Draft store". See [Person](https://developers.google.com/style/person).
- Use active voice, so the actor is clear. See [Voice](https://developers.google.com/style/voice).
- Use the present tense.
- Put a condition before the instruction it controls: "If the Draft is Complete, stop the polling." See [Sentence structure](https://developers.google.com/style/sentence-structure).
- Use sentence case for titles and headings: "Considered options", not "Considered Options". See [Capitalization](https://developers.google.com/style/capitalization).
- Use the serial comma: "Managers, Picks, and Trades". See [Serial commas](https://developers.google.com/style/commas-serial).
- Use American spelling: "favor", not "favour". See [Spelling](https://developers.google.com/style/spelling).
- Use numbered lists for steps in order. Use bulleted lists for other lists. See [Lists](https://developers.google.com/style/lists).
- Put code, file paths, commands, package names, config keys, and literal values in code font. See [Code in text](https://developers.google.com/style/code-in-text).
- Use descriptive link text. Don't write "click here". See [Cross-references](https://developers.google.com/style/cross-references#descriptive-link-text).
- Write "ID" in prose. Use `id` in code font only when you mean the code field. See the [word list](https://developers.google.com/style/wordlist).
- Write for a global audience. Prefer short sentences and plain words. Write "for example", not "e.g.". See [Writing for a global audience](https://developers.google.com/style/translation).

## Project conventions

These rules add to the Google guide or replace part of it.

### Glossary terms

- Define each domain term in [GLOSSARY.md](../GLOSSARY.md).
- Write a glossary term with the exact capitalization from the glossary: Selected Pick, Draft Order, Round Time. The capital letters mark it as a defined term, but a lowercase term is fine where the meaning is clear: "pick timer" in a test name. Don't add bold or other formatting.
- Don't use a word that the glossary lists under _Avoid_.
- If you need a new domain term, add it to the glossary first.

### Interface elements

- Write the label of a button, header action, menu item, or badge in **bold**, as the Google guide requires: **Save**, **Cancel**. See [UI elements](https://developers.google.com/style/ui-elements).
- A word can be both a glossary term and a label. Bold tells the reader which one you mean: "Select **Trades** to see the Trades of a Draft."
- Put message text and example copy in double quotes: "Unable to load draft".
- Put literal values in code font: `"NFL"`.

### Code names

- Write class, component, store, type, and function names in code font: `PickRepository`, `GetCurrentPick`.

### Cross-references

- Refer to a part of the plan by its number: "Section 2.3", "Phase 6". Add the number of the list item when the item matters: "Section 2.3, item 1".
- After you add, remove, or reorder a section, phase, or list item, check every reference to it.

## Document types

### Implementation plan

- Write each phase as numbered instructions in the imperative.
- Give each phase a "Done when" condition.
- Keep one route group, or one unit of work of similar size, equal to one pull request. Each pull request must leave the app in a working state.
- Record each sidestep under "Known compromises".

### Architecture decision records

- Name the file `NNNN-short-title.md` in `docs/adr/`.

### Glossary

- Give each term a bold name, a one- or two-sentence definition, and an _Avoid_ line with the words not to use.
- Group terms under headings by topic.

## Future work

The Google guide says not to pre-announce features. That rule is for user-facing docs. Planning documents can describe future work, such as stretch goals and deferred options. Label it clearly, for example under "Stretch goals" or with the result "Deferred".
