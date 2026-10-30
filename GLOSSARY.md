# HootDraft

A service for running live fantasy sports drafts: a commissioner sets up a draft, managers take turns choosing pro players, and the results and statistics are shared with everyone who can view the draft.

## Language

### People

**Commissioner**:
The user who creates a Draft and runs it. Also called the **Commish**.
_Avoid_: Owner, host, admin

**Admin**:
A user with the site-wide admin role. Admins manage users, pro player data and Draft Stats across all Drafts.
_Avoid_: Commissioner (a Commissioner is not necessarily an Admin)

**Manager**:
A participant in one Draft who takes turns making Picks, such as a team in a fantasy league. A Manager belongs to a single Draft.
_Avoid_: Team, owner, drafter

**Pro Player**:
A real-world professional athlete in the searchable player list. The Commissioner searches it while entering a Pick, and the match is only a suggestion.
_Avoid_: Player

### Accounts

**Email Verified / Email Unverified**:
Whether the user followed the emailed link for their current address. A new user and a Commissioner who changed their email are Email Unverified until they follow the link. Always use the full term: "verified", "unverified" and "verification" alone do not say which check is meant. The matching token is the **Email Verification Key**.
_Avoid_: Verified, unverified, verification (without "Email"), confirmed, active

**Enabled / Disabled**:
The Admin's switch. A Disabled user cannot log in. Only an Admin changes it, and it is separate from Email Verified.
_Avoid_: Blocked, active, inactive

**Reset**:
Choosing a new password through an emailed link, after a lost password request. It uses its own token, the **Reset Key**, which is separate from the Email Verification Key. A Reset never makes a user Email Verified.
_Avoid_: Verification, verify

### The draft

**Draft**:
One event in which Managers take turns choosing Pro Players, run by a Commissioner for a single sport.
_Avoid_: League, event

**Setting Up**:
The Draft status before it has begun, while Managers, Round Times and other options are still being configured.
_Avoid_: Undrafted, not started

**In Progress**:
The Draft status while Picks are being selected.
_Avoid_: Started, live, active

**Complete**:
The Draft status after every Pick has been selected. A Draft becomes Complete automatically on its last Pick and cannot be completed or reopened by hand.
_Avoid_: Finished, ended, done

**Draft Style**:
Whether Draft Order repeats the same each Round (**Standard**) or reverses on alternate Rounds (**Serpentine**).

**Draft Order**:
The sequence in which Managers take their turns in a Round.
_Avoid_: Pick order

### Picks and rounds

**Pick**:
A turn on the draft board, belonging to a Manager at a particular position in a Round. A Pick is either a Selected Pick or an Unselected Pick.
_Avoid_: Slot, turn, player (the old code word for a Pick)

**Selected Pick**:
A Pick for which a player has been chosen. Its name, team and position usually come from a Pro Player suggestion, but the Commissioner can edit them (a nickname, say) or enter a player who isn't in the Pro Player list at all.
_Avoid_: Selection, drafted player, made pick

**Unselected Pick**:
A Pick that exists on the draft board but has no Pro Player chosen yet.
_Avoid_: Empty pick, open slot

**Round**:
One row of Picks on the draft board. A Round normally gives each Manager one Pick in Draft Order, but Trades can leave it unbalanced, so some Managers take several Picks in a Round and others none.
_Avoid_: Turn, pass

**Round Time**:
The number of seconds a Manager is allowed per Pick, either the same for every Round or set for each Round separately.
_Avoid_: Time limit, clock

**Pick Duration**:
The seconds that passed between the previous Selected Pick and this one.
_Avoid_: Pick time (that word means the moment of selection)

### Trades and depth charts

**Trade**:
An exchange of Picks between two Managers, which can include Picks that are already Selected.
_Avoid_: Swap, deal

**Traded Pick**:
A Pick that now belongs to a different Manager than the one it started with.

**Depth Chart**:
A Manager's Selected Picks arranged into positions with a fixed number of slots each. A Draft opts in to using Depth Charts.
_Avoid_: Roster, lineup

### Results

**Draft Stats**:
A summary of a Complete Draft: how long it took, which Managers and Rounds were fastest and slowest, and which teams and positions were chosen most and least. An Admin can regenerate them.
_Avoid_: Draft statistics, report, recap

### Source control

**Develop branch**:
The main repository branch that finished work merges into. Today it is named `master`, and the code and docs still say `master`. The team will rename it. Use "develop branch" in new text.
_Avoid_: Master, main
