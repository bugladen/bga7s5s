# Duel Stat Display in Duel Table

## What changed
Surfaced `Game::CHALLENGE_STAT` to the UI as a stat-icon image inside the
existing `#duel_stats` row of the duel table. Left-justified the `#duel_stats`
cell so the label/icon hug the left edge.

Files:
- `modules/php/Game.php` — `getAllDatas()` now writes `challengeStat` into the
  in-duel payload alongside the existing duel data.
- `modules/php/StatesTrait.php` — `stDuelStart` notif `duelStarted` includes
  `challengeStat` so late-joining clients (and the live duel start path)
  pick up the stat without needing a separate notif.
- `modules/js/Templates.js` — `jstpl_duel_table` renders a stat icon div
  inside `#duel_stat_value` using `_7sfs-card-${challengeStatClass}-image`.
- `modules/js/Utilities.js` — `displayDuelTable(challengeStat)` takes the stat
  and lowercases it for the CSS class.
- `modules/js/Setup.js` — passes `gamedatas.challengeStat` on initial setup.
- `modules/js/Notifications.js` — `notif_duelStarted` passes
  `args.challengeStat`.
- `seventhseacityoffivesails.css` — `#duel_stats` is `text-align: left`. Added
  reset rules under `#duel_stat_value > div` to neutralize `float: right` and
  the side margins baked into the card-stat-image classes, since they were
  designed for use inside the stat-box layouts on card faces — not as a free-
  floating inline icon.

## Why
- **Why reuse `_7sfs-card-*-image` classes:** They already carry the correct
  `boardResources.png` sprite offsets and dimensions. Spinning a new class
  set just for the duel table would be duplicated styling that drifts when
  the sprite sheet changes.
- **Why a CSS reset rather than new selectors:** The existing image classes
  have `float: right` and asymmetric margins because they live next to a
  value column in `_7sfs-card-*-box`. Inside `#duel_stat_value` (an
  `inline-block` span) those floats produce weird placement; the simplest
  fix is to override `float`/`margin` for this specific container instead
  of inventing parallel `*-duel-image` variants.
- **Why ship the stat in `getAllDatas` AND the notif:** `getAllDatas` covers
  the page-reload / spectator-join case where a duel is already underway,
  the notif covers the moment a duel begins live. Both code paths already
  call `displayDuelTable()` so threading the value through both is the
  cheapest fix.
- **Why lowercase in JS instead of PHP:** `Game::STAT_*` are capitalized
  ("Combat"/"Finesse"/"Influence") and used as semantic identifiers across
  the PHP layer. Keeping the canonical casing on the wire and downcasing
  only where CSS needs it avoids surprising future PHP consumers.

## Known gap (deliberate, out of scope)
The display does not currently update when `CHALLENGE_STAT` is flipped
mid-duel (e.g. `Maneuver_02041` "You Cheated", `Reaction_03012` "Subtle").
The user only asked for initial display of the stat. If we later want
live updates, the cleanest hook is to fire a small `duelStatChanged`
notif from the places that mutate the global, and have the JS swap the
class on `#duel_stat_value > div`. Flagging here so a future session
does not assume the missing live update is a bug rather than scope.

## Verification
Static review only — no local test harness for this codebase, deployment
is SFTP to BGA Studio. The UI changes should be eyeballed in a live duel
to confirm icon sizing inside the table cell looks right at typical zoom
levels.
