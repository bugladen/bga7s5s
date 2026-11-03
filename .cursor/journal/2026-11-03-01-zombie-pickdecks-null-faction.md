# Zombie pickDecks → null faction on buildDecks

## Report
Sentry production (table 939315286, release 261102-0035):
`Attempt to read property "faction" on null` in DeckTrait.php during zombie cron.
Stack: zombieTurn → doZombieTurn → stBuildDecks → buildDecks → `$deck->faction`.

## Root cause
`ZombieTrait` pickDecks case had comment "Pick first available deck" but only called
`setPlayerNonMultiactive(..., 'deckPicked')` — never wrote `player.deck_source`.

When the zombie was the last multiactive (or became so), framework transitioned to
BUILD_TABLE → `stBuildDecks` → `buildDecks`. `json_decode(null/empty)` → null →
`$deck->faction` fatal.

WHY the stack nests stBuildDecks inside doZombieTurn: setPlayerNonMultiactive on the
last player runs the transition synchronously in the same zombie request.

Cannot fix by calling `actPickDeck` from zombie — it uses `getCurrentPlayerId()`,
which fails on the zombie/cron path ("Not logged"). Inline assign using the
`$playerId` argument instead.

## Fix
- pickDecks zombie: assign a **random** Core starter (`array_rand`, same pool as
  `argAvailableDecks` / random-core DeckAssignment), write deck_source,
  tournament-lock if applicable, then release.
- buildDecks: throw BgaSystemException if deck_source doesn't decode — clearer than
  property-on-null if another path ever leaves it empty.

## Tests
ZombieTrait_Test: writes a Core starter + releases; tournament also storePlayerGameData.
Harness: ZombieTraitGame (StatesTraitGame + FrameworkActionsTrait + ZombieTrait).

Follow-up: user asked for random instead of always first (SDCastille).

## Opinion
Classic "comment lies about the code" bug. The intent was right; the implementation
was half-done. Surprising it took this long to hit production — manual decks + a
quit during pick is a narrow window, but once it hits, the whole table dies on
build.
