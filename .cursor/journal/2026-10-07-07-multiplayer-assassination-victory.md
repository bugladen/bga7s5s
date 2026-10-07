# Multiplayer assassination victory

## Context
User: "In a multiplayer game, an assassination victory can occur if a player is the only one with a Leader in play."

Rules (Learn to Play / Comprehensive): Assassination = be the only player with a Leader in play. In 3–4p, when your Leader is destroyed, lose half Renown rounded up (game continues unless that leaves a sole Leader-holder).

## Gap
`Leader.php` only ended the game on Leader destroy when `PLAYER_COUNT == 2`. Multiplayer always applied half Renown and never checked for sole remaining Leader-holder — so 3p/4p assassination wins never fired.

## Approach
Unify on the rules condition (not player-count branch for the win):
1. On `EventCharacterDestroyed` for this Leader, count **distinct ControllerIds** of other Leaders still in play (`getCharactersInPlay`, exclude self).
2. WHY by player not by card: Bravos etc. can put multiple Leaders under one controller — win text is "only player with a Leader."
3. WHY `hasTrait("Leader")` not `instanceof Leader`: user correction — trait is authoritative; non-Leader subclasses can carry the trait.
4. **2p always ends** on Leader destroy (`PLAYER_COUNT === 2`) — user confirmation. Do not rely only on trait scan (0 remaining / dying player still holds another Leader must not fall into half-Renown).
5. Multiplayer: if exactly one player remains with a Leader → assassination victory; else half Renown and continue.
6. Winner: sole remaining Leader-holder when trait scan finds one; else in 2p fallback the destroyed Leader's opponent.

## Implemented
- `Leader.php`: win when exactly one other ControllerId still has a `Leader`-trait character in play; else half Renown.
- Non-winner scores set to -1 on assassination (Dominance-style) so prior half-penalty Renown cannot beat the winner on BGA ranking.
- Notify on assassination victory (was silent in 2p before).
- `FakeGame`: `playerScores` + get/setPlayerReknown stubs; `GenericLeader` harness + autoload.
- Suite: `tests/regression/Leader_Assassination_Test.php` (2p win, 3p half, 3p sole-win, dual-Leader same controller).

## Do not regress
- **2p Leader kill ALWAYS ends the game** (explicit `PLAYER_COUNT === 2` branch) — never half-Renown
- Multiplayer with 2+ Leaders left: half Renown only, no endOfGame
- Multiplayer reducing to sole Leader-holder: endOfGame + score wipe for non-winners
- Count by ControllerId not Leader card count (Bravos muster)
- Gate on `hasTrait("Leader")`, not `instanceof Leader`
