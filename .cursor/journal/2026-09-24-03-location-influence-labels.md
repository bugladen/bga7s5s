# Location Influence Labels (UI)

## Ask

Eddie wants Influence totals per player at each city location: top-left label, white bg + rounded corners (Parley Gone Wrong idiom), values in player color with spaces between. Restore on refresh.

Follow-ups:
- Include "influence during pressure" amounts (claim-relevant).
- Spaces between values + tippy "Current Player Influence Totals".
- Refresh on move / engage / engarde / enter play / leave play — **not** turn end.

## WHY this design

- Reuse Parley/Motion-to-Delay overlay shape but **top-left + white**.
- Totals = sum of `getInfluencePressureValue()` — same STAT_INFLUENCE hook as claim pressure.
- Refresh from `Theah::runEvents` **after** hub+cards for matching events — WHY: Location/Engaged/Controller are final then; works whether `runEventHubAfterCards` is true or false. Central list in `eventAffectsLocationInfluenceTotals` so we don't sprinkle notifies through EventHub.
- Enter play: Mustered (card/character), Recruited, CityCardAdded, ApproachCharacterPlayed.
- Leave play: DiscardedFromPlay, RemovedFromPlay, Destroyed, SentToLocker.
- getAllDatas still live-computes for refresh.

## Solomonia aura missing (follow-up)

WHY it was missing: Solomonia's "+1 during Influence pressures at adjacent locations while at Forum" is implemented as `SOLOMONIA_PRESSURE_TYPE` set only on `EventPressureOccuring`. Standing labels only summed `getInfluencePressureValue` — they never saw that flag.

Fix: when Solomonia is controlled at Forum, add +1 to her controller at Forum-adjacent city locs (same adjacency as `_02044`, home excluded). Same board condition pressureLocation would apply on claim.

Still exclude reaction-chosen mid-pressure mods (Loyal, Pack Tactics, Vantage Point).

## Audit: other additives vs standing labels (2026-09-24)

**Covered:** `getInfluencePressureValue` (Claude +1, Aníbal +2, Astrid only when PRESSURING_PLAYER set), continuous ModifiedInfluence auras, Solomonia Forum-adjacent +1.

**Same class as Solomonia (standing board → PRESSURE_TYPE mid-pressure):**
- **Don Constanzo** `_01006`: DONE — if Constanzo's controller has a Thug at the location, +1 on the Influence label (mirrors Influence-only pressure; multi-stat pressures would get +1 per type in pressureLocation).

**Adds to claim total but not "Influence" label:**
- **So It Begins** `_01183`: pressures at its location also add Combat — claim total includes Combat values; our label stays Influence-only.

**Intentionally not standing (optional / action / mid-pressure):** Pack Tactics, Castillian Caper, Trial of Faith, Loyal, Meeting of the Minds, Vantage Point (−), Pull the Strand, Claude reaction filter, Reputation Meritée Mercenary filter. Win-on-tie flags (Tabard, etc.) don't add.

## Don't "fix" later

Don't move setup call before garden/bazaar `data-location`.
Don't revert to ModifiedInfluence.
Don't put `display: flex` back without gap — use inline-block + `&nbsp;`.
Don't put tippy behind `pointer-events: none`.
Don't move refresh back into EventPlayerTurnEnd — Eddie switched to board-change events.
Don't notify inside individual EventHub cases — `runEvents` after-pipeline is the right single place.
Don't drop Solomonia's standing +1 thinking PRESSURE_TYPE-only bonuses are all ephemeral — her aura is board state; Loyal/Pack Tactics are the reaction ones to skip.
Don't drop Constanzo's +1 when a controlled Thug is at the location — same standing gap as Solomonia was.
Don't order viewer-first in PHP notify->all — that would put one player's order on everyone's screen. Reorder in `displayLocationInfluenceTotals` with `this.player_id`.

