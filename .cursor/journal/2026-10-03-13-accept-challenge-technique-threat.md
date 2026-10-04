# Accept Challenge — include Technique threat in preview?

User asked: at `highDramaChallengeActionAcceptChallenge`, can we include threat from Techniques?

## Context inherited
Today: Rosa/Soline chooseNext priority, Blood in Water UL cancel, Amour UL nod, Mourad challenge symbols, home deck count icon.

## Answer (feasibility)

**Yes for most techniques**, with care. Technique is already chosen/resolved before Accept:

`TECHNIQUE_AVAILABLE` → `RESOLVE_TECHNIQUE` → `CHECK_CANCELLED` → **ACCEPT** → `GENERATE_THREAT`

Current args (`argsHighDramaChallengeActionAcceptChallenge`) only sum challenge-stat into `defenderThreat`. UI string literally says "excluding Technique" — because real calc lives in `EventGenerateChallengeThreat` (post-Accept).

## Why not just fire GenerateChallengeThreat in args

Handlers are not pure threat math. Side effects on that event:
- `Technique_DestroyPlusOneThrust` / 01157 — queue destroy/unequip (+ ranged event)
- 02026a/b, 04017 — queue attachment choosers when `CHALLENGE_ACCEPTED`
- 01063Swap — mutates `CHOSEN_PERFORMER` + event actorId
- EventHub after-handler — writes globals + notifies all players

Naïve dispatch would mutate game state / spam log during args.

## Clean approaches

1. **Preview flag on event** (`$event->preview = true`) — handlers add threat but skip queueEvent/mutations; skip EventHub after. Best reuse of existing math (01011 Red Hand count, 01196 Combat/Influence gate, 04033 UseThrust).
2. **Technique API** `getChallengeThreatPreview(...)` — explicit but every technique must implement; easy to miss.

Also: scheme/risk challenge modifiers (02061 +1/+1, 03030 actor+1, 04045 actor+1) ride the same event — if goal is "total threat shown", include those too, not just techniques. Lethal (GainLethal) is a flag, not a number.

## Edge cases if we ship this
- 04033 Parry choice → +0 technique threat (UseThrust false) — preview should reflect that
- Bastien swap adjusts actor at GenerateThreat — Accept still shows pre-swap challenger (already true for base stat today)
- Intervene after Accept changes CHOSEN_TARGET — preview is for current challenged character (correct for Accept decision)

## Implemented 2026-10-04
See `2026-10-04-01-accept-challenge-technique-threat.md` — preview flag approach shipped.
