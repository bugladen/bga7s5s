# create-faction-attachment skill update (post-Harpoon)

## Why
Eddie asked to fold Harpoon (_03064) learnings into the skill so the next Neutral Weapon / remainder-of-duel Technique does not rediscover:
- condition-as-source-of-truth vs Technique `$Active`
- swap must gate in `swapParticipantsInDuel` (pre-mutate)
- activate-time checks on deferred movers/swappers
- Neutral `initializeFaction` is valid for FactionAttachment

## Files touched
- `SKILL.md` — Neutral intro fix; phrase-table row; compose note
- `pattern-e.md` — Engage-this-card cost; full remainder-of-duel subsection (apply/clear/move/swap/JS/footguns)
- `references.md` — `_03064` row + sibling activate-time file list
- `checklist.md` — item 17 remainder-of-duel; renumbered lint/journal; double-CR fix note
- `helpers.md` — conditions, updateCardObjectInDb, duel helpers, FinesseModifed factory typo
- `wiring.md` — condition JS + swap/activate-time memory note

## Not put in skill
- Bastien 01063 Back button UX detail (too card-specific; mentioned once under swap activate-time as optional)
- Full list of every maneuver that got a Harpoon check — pattern + exemplars enough
