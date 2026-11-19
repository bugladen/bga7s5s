# Setup crash: firstPlayer addClass on null

## Report
Table won't load. Console:
`Uncaught TypeError: Cannot read properties of null (reading 'className')`
at `dojo.add` ← Setup.js:192 (`dojo.addClass(`${firstPlayer}-first-player`, ...)`)

gamedatas snapshot: day 1, turnPhase 3 (High Drama), firstPlayer set, **homeCards: Array(0)**.

## Cause
Setup gated `createHome` on `gamedatas.homeCards.length > 0` ("past faction choosing"). The `${id}-first-player` node lives only inside `jstpl_home`. With empty homeCards, homes never built, then firstPlayer block still ran and dojo looked up a missing id → null.className.

Score seal first-player (`jstpl_player_board`) always exists; only the home marker was missing. Crash hit home first.

## WHY not just guard addClass
Guarding alone would let setup finish with **no home boards** in High Drama whenever homeCards is empty. Homes are created when a leader is played (`notif_playLeader`), so the right gate is `player.leader`, not "are any cards currently at Player Home." Characters can all be in the city; home board must still exist.

## Fix
- Setup.js: create homes / seals / discard hooks when `player.leader` is set
- Setup.js + Notifications.js notif_firstPlayer: null-guard home + score first-player nodes independently

## Still suspicious
Empty `homeCards` on day-1 High Drama is abnormal — schemes should sit at Player Home. Leaders can still appear in `players[].leader` via `getCardById` DB fallback even when not loaded into Theah at Player Home. If after this fix homes render but schemes are missing, dig card_location vs Location property desync (moveCardInDeck/parkCard class of bug) on that table.

## Prior context
Recent work was Fine Addition host chooser + in-hand announce badge — unrelated to this load path.
