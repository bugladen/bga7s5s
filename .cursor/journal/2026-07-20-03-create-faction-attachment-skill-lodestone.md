# create-faction-attachment skill update (post-Lodestone)

## Why
Eddie asked to fold Lodestone (_03065) + related Harpoon-era learnings into the skill so the next while-equipped restriction / sink City Action does not rediscover:
- condition-as-source-of-truth for while-equipped (not just remainder-of-duel)
- opponent detection via EventCardMoving.sourceId ControllerId (initiatingPlayerId is a trap)
- Dame-of-Swords sink chain for "Sink this card" on equipped attachments
- immediate-resolve City Action when there is no picker

## Files touched
- `SKILL.md` — Neutral Lodestone exemplar; shape-table B'' + C sink/immediate rows; compose note
- `pattern-b.md` — new Pattern B'' subsection (while-equipped condition + opponent detection + activate-time)
- `pattern-c.md` — immediate-resolve City Action; Sink this card chain
- `pattern-e.md` — B'' vs remainder-of-duel distinction; Maneuver_01033 Lodestone note
- `helpers.md` — conditions list, sourceId opponent note, sink event factories
- `references.md` — `_03065` + `Technique_02055` rows
- `checklist.md` — 4b while-equipped; 16b sink/immediate; 17 cross-ref B''
- `wiring.md` — while-equipped + sink memory notes
- `compose.md` — condition + Action coexistence on `_03065`

## Deliberately not put in skill
- Full audit of every HD action that move-Homes without activate-time Lodestone check (soft-fail via queueEvent still exists; chased Maneuver_01033 only)
- Whether Lodestone should respect `unstoppable` — shipped without that exempt; left as card-specific unless Rules say otherwise
