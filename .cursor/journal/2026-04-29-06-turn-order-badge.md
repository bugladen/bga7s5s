# Turn Order Badge

Added a round badge to each player's `jstpl_player_board` showing `turn_order` from the `player` table.

## Changes
- `Game.php` getAllDatas SQL: added `turn_order` to the player select.
- `Templates.js` jstpl_player_board: new `${id}-score-turn-order` div placed right after `score-seal-first-player`.
- `Setup.js`: passes `turnOrder: player.turn_order ?? ''` into format_block; tooltip "Player Turn Order" added; for games with ≤2 players the badge is hidden via `display:none` (no tooltip attached either).
- CSS: `._7sfs-score-turn-order` styled as a 27px circle with a beige fill so it reads distinctly from the renown badge while sharing dimensions.

## Why
- User asked turn_order be surfaced through the gamedatas structure rather than computed client-side.
- Hiding (rather than omitting) keeps the template uniform across player counts.
- `turn_order` can be NULL pre-assignment; using `?? ''` in JS yields a blank circle rather than "null".

## Not done
- No live update on first-player rotation. Spec only requested surfacing/display, so leaving for a follow-up if wanted.
