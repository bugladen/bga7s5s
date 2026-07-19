# Armed and Marshaled (01044) Audit

## Card Text
- **Scheme Effect**: Add a Renown to [The City Docks] and [The Grand Bazaar]. Put target attachment from your discard pile into your hand.
- **City Action**: Engage your performer's attachment and target an opposing character with equal or fewer attachments • Engage them or move them Home.

## Scheme Effect — Renown + Discard Recovery
Renown addition to Docks and Bazaar is clean — two `createRenownAddedToLocationEvent` calls with the correct location constants.

The discard-to-hand transition uses `argsEmpty` + client-side JS filtering (`card.type === 'Attachment'`). The pass handler correctly checks `instanceof Attachment` to prevent passing when valid targets exist.

## Bugs Found & Fixed

### 1. Missing `instanceof Attachment` check in `actFromCardWithId` (_01044.php)
Card says "put target **attachment**" but the server action accepted any card from the discard pile. Client-side JS filtered correctly, but server validation was missing. The pass handler already had the check — this was just the success path that skipped it.

**Fix**: Added `instanceof Attachment` check after the null check, before the discard pile location check.

### 2. Missing engagement validation on attachment selection (Action_01044.php)
In the City Action flow, when the player selects which attachment to engage (state `HIGH_DRAMA_PLAYER_TURN_01044`), `getArgsFromAction` filtered to unengaged attachments for the UI, but `actFromActionWithId` didn't validate on the server side.

**Fix**: Added `$attachment->Engaged` check that throws if the attachment is already engaged.

### 3. Missing `eventCheck` in engage path (Action_01044.php, option 1)
The engage path (id == 1) queued engagement events for both the attachment and the target character without calling `eventCheck` first. The move-home path (id == 2) correctly called `eventCheck`. Pattern from `Action_01152a` confirms `eventCheck` should always precede `queueEvent`.

**Fix**: Added `$game->theah->eventCheck($event)` before each `queueEvent` in option 1.

### 4. Missing `actionResolvedEvent` in move-home path (Action_01044.php, option 2)
Option 1 queued `createActionResolvedEvent` but option 2 didn't. Both branches represent the action fully resolving. Comparison with `Action_01152a` confirms the event should be queued.

**Fix**: Added `createActionResolvedEvent` queue in option 2.

### 5. Missing `resetPlayerPassCount` in move-home path (Action_01044.php, option 2)
Option 1 called both `setUsed` and `resetPlayerPassCount`, but option 2 only called `setUsed`. These should be paired — the action resolved, pass counters should reset.

**Fix**: Added `resetPlayerPassCount` in option 2.

## WHY: eventCheck matters
`eventCheck` runs the event through the reaction/interrupt system before it's queued. Without it, cards that react to engagement (e.g., reactions that trigger on "a card becomes engaged") would never fire for the engage path. The move-home path had this right but the engage path silently skipped it — likely a copy-paste oversight where the engage path was written first without the pattern, then the move-home path was written with it.

## WHY: Inconsistent validation pattern across scheme-resolve states
This is a broader codebase pattern — `argsEmpty` + client-side JS filtering means the server doesn't tell the client what's selectable; the client figures it out from gamedatas. But the server-side action handlers often skip re-validating what the client already filtered. This is a defense-in-depth gap. Fixed it here for 01044; 01045 has the same gap for Mercenary trait checking (not fixed in this session — different card).

## Files Changed
- `modules/php/cards/_7s5s/_01044.php` — bug 1
- `modules/php/cards/_7s5s/actions/Action_01044.php` — bugs 2-5
