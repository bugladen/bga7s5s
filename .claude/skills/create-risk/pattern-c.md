> Part of **create-risk**. Open from [SKILL.md](SKILL.md) only when the shape table routes here — keep WHYs intact; do not summarize away regression traps.

## Pattern C — Maneuver

A Maneuver is a Risk-specific ability that activates when the Risk is used as a combat card in a duel round.

```php
namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_NNNNN extends Maneuver
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("...");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah)) return false;
        // ... gating predicates (trait, gambled, stat comparison, etc.)
        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // EventManeuverCanceled handler not needed

        if ($event instanceof EventDuelCalculateManeuverValues && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $event->riposte += 1;
            $event->explanations[] = sprintf($event->theah->game->translate("%s adds 1 Riposte."), $owner->getInjectCode());
        }

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            // one-shot side effects (draw a card, wound, transition into a sub-state)
        }
    }
}
```

### Pre-commit hook: EventManeuverCanceled

Every Maneuver subclass must include either an `EventManeuverCanceled` handler OR a literal `EventManeuverCanceled handler not needed` comment. Add the comment when the maneuver has no state to undo (pure additive Riposte/Parry/Thrust + queued draw/etc., framework rolls those back on cancel).

When the maneuver carries state on the Maneuver object (e.g., `Maneuver_01084::IncreaseAdversaryThrust`), include a real handler that clears the flag on cancel.

### "Duelist Maneuver" / "Scoundrel Maneuver" / "Gambling Maneuver" — trait-prefixed gates

These are **mechanical performer-trait gates**, not Sorcerer abilities. Add an `isAvailable` predicate:

```php
// Duelist / Scoundrel / Pirate / … Maneuver:
$actor = $theah->getDuelRoundActor();
if (! $actor || ! $actor->hasTrait('Duelist')) return false;

// Gambling Maneuver:
if (! $theah->game->globals->get(Game::DUEL_GAMBLED, false)) return false;
```

`Game::DUEL_GAMBLED` is set true in `FrameworkActionsTrait::actChooseGambleCard` when the gambled combat card is locked in, and cleared in `stDoneRound`. See `Technique_03002` (Aja) for the same gate on the Technique side.

### "If your participant has more / equal or greater <Stat> than the adversary" gate

Parse the printed comparison literally — the operator is part of the card text:

| Card phrase | Operator |
|---|---|
| "more … than" / "greater … than" | `>` |
| "equal or greater … than" / "equal or lower … than" (on the *target*) | `>=` / `<=` |

```php
$actor = $theah->getDuelRoundActor();
$adversary = $theah->getDuelRoundOpponent();
// "more Influence than" → strict >
return $actor->ModifiedInfluence > $adversary->ModifiedInfluence;
// "equal or greater Influence than" → >=
return $actor->ModifiedInfluence >= $adversary->ModifiedInfluence;
```

Use **modified** stats (`ModifiedInfluence`, `ModifiedFinesse`, etc.), not the printed base — the comparison must honor live modifiers. Reference: `Maneuver_01115` (Finesse comparison), `Maneuver_03008` ("more" → `>`), `Maneuver_03033` ("equal or greater" → `>=`), `Technique_01196` (equal-or-greater Combat+Influence).

When the resolve effect wounds the adversary, also gate availability on `! $theah->game->characterIsInDiscardOrLocker($adversary)` so the maneuver is not offered against an already-destroyed opponent.

### Adding Riposte/Parry/Thrust during calc

`EventDuelCalculateManeuverValues` exposes plain int fields (`$riposte`, `$parry`, `$thrust`) that you mutate directly — unlike `EventDuelCalculateCombatCardStats` which uses `addRiposte`/`addParry`/etc. methods that respect `DashedX` flags.

```php
$event->riposte += 1;
$event->explanations[] = sprintf(
    $event->theah->game->translate("%s adds 1 Riposte."),
    $this->getOwningCard($event->theah)->getInjectCode()
);
```

The calc event can fire multiple times during a single round (recalc on engage state changes etc.) — so put **one-shot** side effects (draw a card, wound, transition) in `EventResolveManeuver`, which fires once.

References: `Maneuver_01061` (conditional draw on equipped Weapon), `Maneuver_01084` (Duelist gate + adversary Thrust bonus next round + combat-card discount when adversary engaged), `Maneuver_01115` (cross-player hand-pick discard via `createTransitionEvent` to the adversary's controller), `Maneuver_01166` / `Maneuver_03036` (+N for each other dueling-line card), `Maneuver_03008` (Gambling gate + Influence comparison + Riposte+draw), `Maneuver_03009` (Strega gate + `-1 Thrust` in calc + wound adversary in resolve), `Maneuver_03011` (Gambling gate + "control trait X at duel location" → pure `+1 Riposte` in calc), `Maneuver_03033` (Gambling gate + equal-or-greater Influence → pure-resolve wound adversary, no calc), `Maneuver_03045` (Gambling gate only + `+2 Riposte` in calc + wound **participant** in resolve), `Maneuver_03048` (Pattern C.6 — Riposte += `getCurrentDuelThreat` to move all threat), `Maneuver_03070` (Pattern C.6 — Parry += excess over adversary `CHALLENGE_STAT`), `Maneuver_03058` (Pattern C.7 — +N Parry and Thrust per opposing at duel location).

### "Wound your participant" vs "Wound the adversary"

Parse the wound target literally — both appear on Gambling Maneuvers and they are not interchangeable:

| Card phrase | Wound target in `EventResolveManeuver` |
|---|---|
| "Wound the adversary" / "Wound them" (adversary context) | `$theah->getDuelRoundOpponent()` — also gate `isAvailable` on `! characterIsInDiscardOrLocker($adversary)` when wound is the (or a) payoff. See `Maneuver_03009`, `Maneuver_03033`. |
| "Wound your participant" | `$theah->getDuelRoundActor()` — your own duel actor. No discard/locker availability gate (the actor is present to play the Maneuver). See `Maneuver_03045`, `Maneuver_02018`. |

When the same Maneuver also adds Riposte/Parry/Thrust, keep the stat mutation in `EventDuelCalculateManeuverValues` and the wound in `EventResolveManeuver` (calc can re-fire; resolve is once). Gambling gate remains `DUEL_GAMBLED` only unless the text adds a further comparison (`_03008` / `_03033`).

### Pattern C.4 — "+X [stat] for each other card in your dueling line" (± conditional adversary discard)

"Other cards in your dueling line" means every card at `Game::LOCATION_DUELING_LINE` for the Risk's controller **except this combat card itself**. By calc/resolve time the card is already in the line, so you must exclude it:

```php
$owner = $this->getOwningCard($event->theah);
$cards = $event->theah->getCardObjectsAtLocation(Game::LOCATION_DUELING_LINE, $owner->ControllerId);
unset($cards[$owner->Id]);
$count = count($cards);
$event->riposte += $count;   // or parry / thrust per the printed text
```

Pure scaling (no side effect) needs only the `EventDuelCalculateManeuverValues` branch — see `Maneuver_01166` (+1 Parry per other card). Skip the explanation line when `$count == 0` to avoid "adds 0 …" noise.

**Conditional "If you have N or more other cards … the adversary discards a card":** keep the calc branch unconditional (0 other cards → +0 is fine). In `EventResolveManeuver`, gate the discard transition on `$count >= N`. Also skip when the adversary's hand is empty:

```php
$hand = $event->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $adversary->ControllerId);
if (count($hand) == 0) return;
```

**WHY empty-hand skip at resolve, not `isAvailableToPlayer`:** the discard is an *extra* clause on a maneuver the player still wants for the Riposte scaling. Putting the hand check on availability (as `Maneuver_01108a` does when discard *is* the whole effect) would hide the maneuver entirely when the adversary has no cards. Resolve-time skip avoids a stuck activeplayer chooser without suppressing the useful calc.

Adversary hand-pick discard does **not** need `IRiskThatTargetsCharacters` / `IAbilityThatTargetsCharacters` — those mark character choosers. Wire the sub-state like `Maneuver_01115` (JS: `factionHand.setSelectionMode('single')`, Confirm via `onCardDiscarded()`, enable Confirm in `EventHandlers.js` on selection).

References: `Maneuver_01166` (pure line-count calc), `Maneuver_03036` (Duelist + Riposte scaling + ≥3 discard), `Maneuver_01115` / `Maneuver_01108a` (discard chooser / hand-gated availability when discard is the only effect).

### Pattern C.5 — Next-round gamble control ("cannot gamble" / "you choose their combat card")

Two related texts that arm a lock on `EventResolveManeuver` for **the adversary's next round**. Split distinct trait-prefixed Maneuvers into `a`/`b` files (mirror `_01108` / `_03046`).

#### Cannot gamble (`Maneuver_03047b`, `Technique_02037`)

```php
// Arm on resolve:
$this->CancelAdversaryGamble = true;
$this->BlockedAdversaryCharacterId = $adversary->Id;

// Block in eventCheck (not handleEvent):
if ($event instanceof EventDuelAttemptGamble
    && $this->CancelAdversaryGamble
    && $event->actorId == $this->BlockedAdversaryCharacterId)
{
    throw new UserException(...);
}
```

**Clear via ControllerId on Risk Maneuvers:** Techniques clear when `$owningCharacter->Id == $event->actorId` on `EventDuelNewRound`. A Maneuver lives on a Risk in the dueling line — there is no owning character. Clear when the new round's actor `ControllerId == $owner->ControllerId` (your next turn starts). Also clear on `EventManeuverCanceled` / `EventDuelEnd`.

#### You choose their combat card (`Maneuver_03047a`)

Do **not** hijack on `EventDuelAttemptGamble` — the adversary must still commit to gambling and reveal. Hijack on **`EventDuelGambleCardsRevealed`** when `$event->actorId == $this->BlockedAdversaryCharacterId`:

1. `notify->all` waiting log (`must choose the adversary's combat card from the revealed gamble cards`) — state description alone is not enough for watchers.
2. `queueEvent(createTransitionEvent($owner->ControllerId, $owner->Id, "NNNNN", $this->Id))`.
3. Wire `"NNNNN"` under **`DUEL_GAMBLE_REVEALED_EVENTS.transitions`** (not `DUEL_RESOLVE_MANEUVER_EVENTS`) → custom GameState (e.g. `DUEL_CHOOSE_GAMBLE_CARD_NNNNN` / id `5270NNNNN`).
4. Named transitions must match `actGambleCardChosen`: `"useManeuver"` → `DUEL_USE_MANEUVER_FROM_COMBAT_CARD`, `"noManeuver"` → `DUEL_CHOOSE_GAMBLE_CARD_EVENTS`. No `actBack` (gamble already committed).

**Transition priority:** `EventTransition` defaults to priority 8; reaction transitions use priority 6. So Ivy-style "before choosing" reactions (`Reaction_02042`) still run first — do not `stackEvent` the choose transition ahead of them.

**Framework: deck = actor, not active player.** When the Maneuver owner is active but the gamble is the adversary's:
- `argsDuelChooseGambleCard` / `actGambleCardChosen` must read/write the **duel-round actor's** faction deck (`getDuelRoundActor()->ControllerId`), not `getActivePlayerId()`.
- Before `nextState("useManeuver"|"noManeuver")`, `changeActivePlayer($actor->ControllerId)` so combat-card maneuvers belong to the gambler.

**Public reveal for the stolen-chooser state:** Prefer public `cards` (everyone + spectators) via `getArgsFromManeuver` + State `argsForState()` (01077 shape — client path `args.args.args.cards`). Do **not** park a one-off helper on `ArgumentsTrait`. Stock `duelChooseGambleCard` stays `_private.active` for normal gambles. Select/Confirm only when `isCurrentPlayerActive()`.

**Args / act live on the Maneuver:** `getArgsFromManeuver` for the choose state; `actFromManeuverWithId` clears the lock then calls `$game->actGambleCardChosen($id)`. Clear also on adversary `EventDuelEndOfRound`, owner `EventDuelNewRound`, cancel, duel end.

References: `Maneuver_03047a` / `Maneuver_03047b` (Proper Drama), `Technique_02037` (cannot-gamble Technique shape), `Maneuver_01108a`/`b` (dual a/b Maneuvers), `Maneuver_01077` (`getArgsFromManeuver` + public `cards` + `argsForState`).

### Pattern C.6 — Move / remove / discard-excess threat (Riposte vs Parry)

In-round threat lives on `duel_round.ending_<side>_threat`. `DB::updateRoundWithCombatStats` applies Riposte / Parry / Thrust in order:

| Channel | Effect on actor threat | Effect on adversary threat |
|---|---|---|
| **Riposte** | Subtracts (capped by current actor threat) | **Adds** the same amount — threat is *moved* |
| **Parry** | Subtracts (capped) | None — threat is *removed* / *discarded* |
| **Thrust** | None | Adds |

So printed threat-pool maneuvers map to calc, not globals / `PENDING_*_THREAT` (those are challenge-issuance and cross-round carry — Patterns C.2 / Final Strike):

| Card phrase | Calc mutation | Mirror |
|---|---|---|
| **"Move all threat from your participant to the adversary"** | `$event->riposte += $theah->getCurrentDuelThreat($actor->Id)` | `Maneuver_03048` (Wily) |
| **"Remove all threat from [participant]"** | `$event->parry += $theah->getCurrentDuelThreat($characterId)` | `Technique_02012` (Turais) |
| **"Discard / remove threat … in excess of your adversary's [duel] stat"** | `$event->parry += max(0, threat - adversaryDuelStat)` | `Maneuver_03070` (Comforting) |

```php
$actor = $event->theah->getDuelRoundActor();
$threat = $event->theah->getCurrentDuelThreat($actor->Id);
if ($threat > 0)
{
    $event->riposte += $threat; // or parry for "remove all"
    $event->explanations[] = sprintf(...);
}
```

**Excess-of-duel-stat (Comforting):** "Discard threat … in excess of your adversary's stat value used for the duel" is still C.6 (Parry / remove), **not** a full clear and **not** Riposte. Cap with the adversary's **Modified** duel-stat from `Game::CHALLENGE_STAT` — same `match` as Restricted Hostilities in `stDuelEndOfRound`:

```php
$adversary = $event->theah->getDuelRoundOpponent();
$combatStatUsed = $event->theah->game->globals->get(Game::CHALLENGE_STAT);
$stat = match ($combatStatUsed) {
    Game::STAT_FINESSE => $adversary->ModifiedFinesse,
    Game::STAT_INFLUENCE => $adversary->ModifiedInfluence,
    default => $adversary->ModifiedCombat,
};
$excess = max(0, $threat - $stat);
if ($excess > 0)
{
    $event->parry += $excess;
}
```

**WHY `CHALLENGE_STAT`, not hardcoded Combat:** the italic example is an Influence duel → adversary's Influence. Do not invent a free-choice button or assume Combat. Null-check actor + adversary before reading.

**WHY skip the explanation when `$threat == 0` / `$excess == 0`:** avoid "moves/discards 0 Threat" noise; activating for no effect is still legal unless the text says otherwise — do not hide the Maneuver behind `threat > 0` / `excess > 0` in `isAvailable` unless the card requires a payoff.

**Dashed Riposte does not kill Maneuver Riposte.** EventHub zeroes Technique riposte when every combat card this round has `DashedRiposte`, but `EventDuelCalculateManeuverValues` has **no** such clamp. Wily (`_03048`) prints dashed Riposte and still moves threat via Maneuver Riposte — do not "fix" that by adding a dashed check. Comforting's printed `DashedRiposte` is likewise irrelevant to its Maneuver **Parry** excess remove (and the card separately prints Parry on the combat-card line).

No sub-state, no sticky Maneuver fields → `// EventManeuverCanceled handler not needed`. Pure-calc shape (see next section).

References: `Maneuver_03048` (move via Riposte + Scoundrel gate + gambled discount), `Technique_02012` (remove-all via Parry), `Maneuver_03070` (excess via Parry + `CHALLENGE_STAT`), `Theah::getCurrentDuelThreat`, `StatesTrait::stDuelEndOfRound` (Restricted Hostilities `match`).
### Pattern C.7 — "+X [stat] for each opposing character" (location-scoped, often unstated)

For Maneuvers like **"Gambling Maneuver: +1[Parry] and +1[Thrust] for each opposing character."** — see `Maneuver_03058` (Courageous). The printed text often **omits** "at this location."

**Default scope = duel actor's location**, not all opposing characters in play:

```php
$actor = $event->theah->getDuelRoundActor();
$opposing = $event->theah->getOpposingCharactersAtLocation($actor->Location, $actor->ControllerId);
// getOpposingCharactersAtLocation already requires isControlled() via isNotControlledByPlayer
$count = count($opposing);
$event->parry += $count;
$event->thrust += $count;
```

**WHY not Ren-style global:** `_01121` Ren compares `getCharactersInPlayByPlayerId` for a *passive* "controls equal or more characters" gate — that is a different printed shape ("controls … characters", no location, not a per-character combat-card bonus). Applying global in-play counts to a Maneuver "+1 per opposing character" balloons Parry/Thrust and fights the duel-board idiom used by `Maneuver_01031` / `Maneuver_03011`. Only go global when the text clearly says so (e.g. "each opposing character you/they control" with no location cue and a Ren-like comparison context).

**Pure calc:** `EventDuelCalculateManeuverValues` only; skip `EventResolveManeuver`; `// EventManeuverCanceled handler not needed`. Gambling prefix → `DUEL_GAMBLED` in `isAvailable` (adversary is almost always present, so do not require `$count > 0` to offer the Maneuver). Skip the explanation line when `$count == 0`.

References: `Maneuver_03058`, contrast `_01121` (global in-play comparison), `Maneuver_01031` / `Maneuver_03011` (location counts).

### Pattern C.8 — Adversary-deck peek → reveal one for Parry/Thrust → replace unchosen (± trait sink)

For Maneuvers like **"Look at the top three cards of your adversary's deck. Reveal one and add its [Parry] or [Thrust] to this card. Replace them in any order. If your participant is an Academic, sink any of those cards instead of replacing them."** — see `Maneuver_03059` (Insightful).

This is **C.3 timing plus deck UI**, not a pure-resolve look. The Parry/Thrust bonus must land in `EventDuelCalculateManeuverValues` this round.

#### Timing (must use C.3 / `EventManeuverActivated`)

`stResolveManeuverFromCombatCard` queues Activate → Resolve → Calculate up front. Peeking or choosing the stat from `EventResolveManeuver` is **too late** — calc has already run (or will run before your prompt returns).

1. On `EventManeuverActivated`: peek via `getCardsOnTopOfPlayerFactionDeck($adversary->ControllerId, N)` into `Game::CHOSEN_CARD`, notify "looks at", `stackEvent("NNNNN")`.
2. Every intermediate step until the calc-driving Parry/Thrust choice (and preferably until deck mutations finish) also `stackEvent`s the next transition — same discipline as `Maneuver_03035`. Do **not** `queueEvent` mid-chain.
3. Apply stored `$ChooseParry` / `$BonusAmount` in `EventDuelCalculateManeuverValues`. Real `EventManeuverCanceled` clears Maneuver fields + `CHOSEN_CARD`.

#### Reveal vs "those cards" for sink/reorder

**Eddie correction (do not regress):** after the player picks the card to reveal for the stat, **sink and reorder pools are the unchosen looked-at cards only** — exclude `$RevealedCardId`. The revealed pick stays in its current deck position (not offered again). Academic sink is skipped when unchosen count is 0.

Parse "Replace them" / "sink any of those" as the **rest** of the look, not including the reveal pick. Keep a `getUnchosenLookedAtCards()` helper shared by sink args/act, reorder args/act, and `finishReplaceOrReorder`.

#### Deck ops and UI

| Step | Mirror | Notes |
|---|---|---|
| Peek adversary faction deck | `Technique_01010` | `getDuelRoundOpponent()` + `getCardsOnTopOfPlayerFactionDeck` (auto-reshuffles discard; may return &lt; N) |
| Private chooseList look / reorder | `Reaction_03052` | `argsForStatePrivate`; JS `On*.faf.js` + `EventHandlers.js` sort tags |
| Public reveal | `Action_01038` | No `createCardRevealedEvent` for peeks — `notify->all` with inject codes |
| Printed Parry/Thrust of revealed card | `IFactionCard` | `$card->Parry` / `$card->Thrust`; Characters → 0. Do **not** permanently mutate the Risk's printed stats — calc event fields only |
| Optional multi-sink then reorder | `Action_02002` / `_02005` | Sink via **immediate** `insertCardOnExtremePosition(..., false)` (`Technique_01010`). WHY not queued `createCardAddedToFactionDeckEvent`: reorder top-inserts in the same act chain race ahead of EVENTS-drained sinks |
| Pass = sink none | Academic "any" | Pass allowed; Confirm enables when selection length &gt; 0 |

#### Availability and trait upgrades

- Gate `isAvailableToPlayer` on adversary present and **deck + discard &gt; 0** — not exact N. Operate on actual count after peek.
- Participant trait ("If … Academic") is **not** an availability gate — it unlocks the optional sink step after the stat choice. Skip that step when unchosen count is 0.

#### Wiring

- States `DUEL_RESOLVE_MANEUVER_NNNNN` … `_4` (`5250NNNNN` family); wire **all** transition strings under `DUEL_RESOLVE_MANEUVER_EVENTS` **and** mirror under `DUEL_CHOOSE_TECHNIQUE_EVENTS` (Miyato/Ota, same as `03024`/`03035`).
- Look/sink/reorder: private chooseList. Stat choice: public `argsForState` with dynamic `+N Parry` / `+N Thrust` button labels.
- No `IRiskThatTargetsCharacters` (deck chooseList, not a character chooser).

References: `Maneuver_03059`, `Maneuver_03035` (stackEvent multi-step), `Reaction_03052`, `Technique_01010`, `Action_02002`, `Action_01038`.

### Pattern C.9 — Swap participant with your other character at this location

For Maneuvers like **"Maneuver: Swap your participant with your other character at this location"** / **"Gambling Maneuver: +1[Riposte] and swap …"** — see `_03069` (Hop on Board). Mid-duel **participant replace**, not a board move.

#### Core resolve path (mirror Daniella duel, not Bastien calc-time)

1. **`isAvailableToPlayer`** — ≥1 other character you control at `$actor->Location` (`getCharactersAtLocationByPlayerId`, exclude actor). Keep Harpooned participants **visible** (do not hide for `HARPOON_CONDITION`).
2. **`EventResolveManeuver`** — `queueEvent(createTransitionEvent(..., "NNNNN", $this->Id))` to a friendly character chooser. **Not** C.3 `stackEvent` from Activated: the swap does not drive calc. Gambling **+X Riposte** stays in `EventDuelCalculateManeuverValues`; Resolve→queue lands the chooser **after** pending calc (same ordering as `Maneuver_01108a` discard).
3. **`actFromManeuverWithId`** — validate same controller + same location + not self; then `$theah->swapParticipantsInDuel($duelId, $round, $actor->Id, $target->Id)` (Daniella `Technique_03013` duel branch). Do **not** defer swap to `EventDuelCalculateManeuverValues` (Bastien `Technique_01063Swap` calc-time path is for Techniques that also rewrite challenge threat).

#### Harpoon vs Lodestone / Shackles

| Condition | Blocks swap? | What to do on C.9 |
|---|---|---|
| **Harpoon** (`HARPOON_CONDITION` — cannot be swapped) | **Yes** | Activate-time `eventCheck` on `EventManeuverActivated` (explanatory `UserException`, button stays visible — same WHY as `Technique_01063Swap` / `Technique_03013`) **and** confirm-time check in `actFromManeuverWithId`. Central gate already in `Theah::swapParticipantsInDuel` (pre-mutate). |
| **Lodestone** | No | Opponent Home **moves** only. |
| **Shackles** | No | **Cannot move** — swap ≠ move. Do **not** cargo-cult activate-time Shackles/Lodestone checks onto swappers. |

#### Dual plain + Gambling sharing one chooser

Split `Maneuver_NNNNNa` / `Maneuver_NNNNNb`. Both may `createTransitionEvent(..., "NNNNN", $this->Id)` into the **same** GameState. When Gambling only adds calc Riposte on the shared swap, **`b extends a`** is fine (`Maneuver_03069b`) — still put `// EventManeuverCanceled handler not needed` in **both** files (pre-commit matches `extends Maneuver…`).

No printed **"Target"/"target"** → no `IRiskThatTargetsCharacters` / `IAbilityThatTargetsCharacters` (private validation helper only — same as `_03060` / `_03068`).

#### Wiring + Miyato/Ota

- State id `5250NNNNN`, name `duelResolveManeuver_NNNNN`, JS trio like `duelResolveManeuver_03035` (highlight + Confirm).
- Wire `"NNNNN"` under **`DUEL_RESOLVE_MANEUVER_EVENTS`**.
- **Also** mirror under **`DUEL_CHOOSE_TECHNIQUE_EVENTS`** when the Risk is **Neutral or Ussura** (or any faction Miyato/Ota can copy from that block). WHY: `Technique_02043a` clones the Maneuver and re-queues Activate→Resolve→Calc while still in choose-technique EVENTS; a Resolve-queued `"NNNNN"` without that key → impossible transition. See the Neutral/Ussura comment block in `states.inc.php`. Eddie confirmed keep this for `_03069`.

References: `_03069` / `Maneuver_03069a`/`b`, `Technique_03013` (duel swap in act), `Technique_01063Swap` (Harpoon activate WHY), `Theah::swapParticipantsInDuel`, contrast move-only attachments `_03065` / `_03066`.

### Pure-calc maneuvers (no `EventResolveManeuver` needed)

When the maneuver only adds/subtracts stat values and has no one-shot side effect (no draw, no wound, no transition), implement **only** the `EventDuelCalculateManeuverValues` branch and skip `EventResolveManeuver` entirely. The framework still rolls back the calc on cancel, and there's nothing to resolve. Negative deltas are fine (`$event->thrust -= 3`, `$event->parry -= 1` — same as `Maneuver_03009`'s −1 Thrust).

**Two distinct pure-calc Maneuvers on one Risk** (even with the **same** trait prefix, e.g. two Duelist Maneuvers): still split `Maneuver_NNNNNa` / `Maneuver_NNNNNb` — do not merge into one class with a mode. Each gets its own Duelist/`DUEL_GAMBLED` gate + calc branch + `EventManeuverCanceled handler not needed` comment. If the card also prints a shared "-1 cost while …" clause, hang `getManeuverFromCombatCardDiscount` on **exactly one** of them (Pattern E dual-Maneuver footgun — `Card` sums).

Reference: `Maneuver_03011` ("control X at duel location" → `+1 Riposte`), `Maneuver_03048` / `Maneuver_03070` (Pattern C.6 threat move / excess remove — same pure-calc discipline), `Maneuver_03058` (Pattern C.7 opposing-character scaling), `Maneuver_04007a`/`b` (dual Duelist pure-calc ± Riposte/Parry/Thrust + wounds discount on `a` only).

### Pure-resolve maneuvers (no calc branch)

When the maneuver has **only** a one-shot side effect (wound adversary, draw, move Home, …) and no Riposte/Parry/Thrust change, implement **only** `EventResolveManeuver` and skip `EventDuelCalculateManeuverValues`. Still include the `// EventManeuverCanceled handler not needed` comment. Call `$theah->eventCheck($woundEvent)` before `queueEvent` for wound effects.

```php
if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
{
    $owner = $this->getOwningCard($event->theah);
    $adversary = $event->theah->getDuelRoundOpponent();
    $woundEvent = EventFactory::createCharacterBeingWoundedEvent(
        $adversary->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id
    );
    $event->theah->eventCheck($woundEvent);
    $event->theah->queueEvent($woundEvent);
}
```

Reference: `Maneuver_03033` (Glorious — Gambling + Influence ≥ → wound), `Maneuver_01055` (Ranged Weapon → wound), `Maneuver_01033` (Influence > → move adversary Home), `Maneuver_03072` (Sabotage — destroy **all** engaged non-Fake attachments on adversary; snapshot list then unequip+discard; gate `! characterIsInDiscardOrLocker` + ≥1 engaged; contrast choose-one `Technique_02026b`).

### Pattern C.2 — Suppress end-of-round threat→wound conversion (with optional carry-forward)

"Your participant's threat is not converted to wounds this round" — the **threat-to-wound conversion** happens once per round inside `StatesTrait::stDuelEndOfRound`, NOT continuously during the round. Trying to gate this off `EventDuelCalculateCombatCardStats` or anywhere mid-round is the wrong hook. Three things you have to know to wire it correctly:

#### 1. The conversion mechanics

`stDuelEndOfRound` (StatesTrait.php:~1414) does, in order:

1. **Reads** `duel_round.ending_<actor>_threat` (plus the `<side>_threat_is_lethal` flag).
2. **Wipes** both fields to `0` via direct SQL (StatesTrait.php:~1453) — this is critical: by the time anything else runs, the threat is *gone* from the DB row.
3. Computes `$wounds = $threat`, possibly reduced by Restricted Hostilities (stat cap when non-lethal).
4. **Queues** `EventCharacterBeingWounded($actor->Id, $adversary->Id, $wounds, $reason)` (StatesTrait.php:~1492).
5. Queues `EventDuelEndOfRound`.

So any maneuver that wants to suppress this conversion gets its window when `EventCharacterBeingWounded` fires.

#### 2. Identifying THIS wound event

`EventCharacterBeingWounded` is also fired by many other things (other wound effects, maneuvers, techniques). The conversion event has a unique signature: **`$event->characterId == actor.Id && $event->sourceId == adversary.Id`** — that pairing only happens for the end-of-round threat→wound conversion. Gate on it:

```php
if ($event instanceof EventCharacterBeingWounded && $this->IsActive)
{
    $theah = $event->theah;
    $actor = $theah->getDuelRoundActor();
    if ($actor === null || $event->characterId != $actor->Id) return;

    $adversaryId = $theah->getDuelOpponentId($actor->Id);
    if ($event->sourceId != $adversaryId) return;
    // ... safe to suppress
}
```

#### 3. Carrying the threat forward (`PENDING_<side>_THREAT`)

If the card text rolls the suppressed threat into next round, **don't** try to keep `ending_<actor>_threat` populated — the SQL wipe (step 2 above) zeroed it before the wound event was even queued, so reading it back gives 0.

The supported channel is the `PENDING_CHALLENGER_THREAT` / `PENDING_DEFENDER_THREAT` globals. `stDuelNewRound` reads them at StatesTrait.php:~1130–1144, adds them to the next round's starting threat, and deletes them. Capture the wound amount **before** zeroing, route to the right side via `getDuelChallengerId()`:

```php
$carryOver = $event->wounds;
if ($carryOver <= 0) return;
$event->wounds = 0;

$game = $theah->game;
$challengerId = $theah->getDuelChallengerId();
if ($actor->Id == $challengerId)
{
    $pending = $game->globals->get(Game::PENDING_CHALLENGER_THREAT, 0);
    $game->globals->set(Game::PENDING_CHALLENGER_THREAT, $pending + $carryOver);
}
else
{
    $pending = $game->globals->get(Game::PENDING_DEFENDER_THREAT, 0);
    $game->globals->set(Game::PENDING_DEFENDER_THREAT, $pending + $carryOver);
}
```

Reference: `Maneuver_02039` (Add Threat — adds +1 to both sides on the next round's pool). `Maneuver_03023` (Second Wind — captures the suppressed conversion amount).

#### 4. Also zero `duel_round.wounds_taken`

Zeroing `$event->wounds` stops the wound from being applied to the character row, but **`duel_round.wounds_taken` was already incremented during the round** by `DB::updateRoundThreats` (DB.php:~539–552) as a running "wounds the actor is about to take" tally. If you don't reset it:

- The `updateRoundThreats` notification still ships the inflated count to the client (EventHub.php:~2197, ~2223) — UI displays a wound count that never happened.
- `Theah::duelParticipantWoundsTaken()` sums `wounds_taken` across the participant's **prior** rounds. `Maneuver_01107` reads that aggregate. With the suppression in place but the column unchanged, downstream cards see wounds that never landed.

Add the row update inside the same `EventCharacterBeingWounded` branch:

```php
$duelId = $game->globals->get(Game::DUEL_ID);
$round = $game->globals->get(Game::DUEL_ROUND);
$game->DbQuery("UPDATE duel_round SET wounds_taken = 0 WHERE duel_id = $duelId AND round = $round");
```

#### 5. "Adversary absent" predicate

When the suppression has an "unless adversary absent" gate, both of these are valid; using both is cheap and explicit:

```php
$adversary = $theah->getCharacterById($adversaryId);
if ($theah->game->characterIsInDiscardOrLocker($adversary)
    || $adversary->Location != $actor->Location)
{
    return;   // adversary absent — let wound resolve normally
}
```

Destroyed characters have `Location` set to `"Locker-…"`/`"Discard-…"`, so the location-mismatch check subsumes the destroyed case, but `characterIsInDiscardOrLocker` is the canonical destroyed test (memory feedback) and reads cleanly.

#### 6. Lethality is not preserved across the rollover

There is no `PENDING_<side>_THREAT_IS_LETHAL` global. If the suppressed threat was lethal, the rolled-over threat lands non-lethal. `Maneuver_02039` has the same limitation. If a future card's text requires preserving lethality across rounds, the right move is to add the global rather than special-case it in the card.

#### 7. State tracking on the Maneuver

Use a `public bool $IsActive` field on the Maneuver, set on `EventResolveManeuver`, cleared on `EventManeuverCanceled` and `EventDuelEndOfRound`. Mark `$owner->IsUpdated = true` whenever you flip it so the framework persists. The `EventDuelEndOfRound` reset is needed because the maneuver instance lives on `$theah->cards` across rounds — without resetting, the next round's conversion would also be suppressed.

References: `Maneuver_03023` (Second Wind — full pattern with carry-forward), `Maneuver_02039` (Add Threat — `PENDING_*_THREAT` write-only producer side).

### "You control a trait X at the duel location" gate

```php
$actor = $theah->getDuelRoundActor();
if ($actor === null) return false;

foreach ($theah->getCharactersAtLocation($actor->Location) as $character)
{
    if ($character->ControllerId != $playerId) continue;
    if ($character->hasTrait("Thug") || $character->hasTrait("Bodyguard")) return true;
}
return false;
```

`$actor->Location` is the canonical "this location" in a maneuver — duels always take place at the actor's (and adversary's) location, and there is no separate "duel location" global. The `ControllerId == $playerId` check excludes uncontrolled characters (their `ControllerId == 0`), so no extra `isControlled()` call is needed. Reference: `Maneuver_03011`.

### "-X [Stat] • Wound the adversary" pattern

A common maneuver shape. Two-phase wiring:

```php
if ($event instanceof EventDuelCalculateManeuverValues && $event->maneuverId == $this->Id)
{
    $owner = $this->getOwningCard($event->theah);
    $event->thrust -= 1;   // or riposte / parry
    $event->explanations[] = sprintf($event->theah->game->translate("%s subtracts 1 Thrust."), $owner->getInjectCode());
}

if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
{
    $owner = $this->getOwningCard($event->theah);
    $adversary = $event->theah->getDuelRoundOpponent();

    $woundEvent = EventFactory::createCharacterBeingWoundedEvent($adversary->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
    $event->theah->eventCheck($woundEvent);
    $event->theah->queueEvent($woundEvent);
}
```

Note the `eventCheck($woundEvent)` call before `queueEvent` — gives prevention/redirection effects a chance to fire. Reference: `Maneuver_03009`, `Maneuver_01055` (Ranged variant), `Technique_01050` (Technique variant of the same shape).

### Pattern C.3 — Choice-at-activation Maneuver (same player picks how the calc applies)

For "Maneuver: [gate] • +X [stat A] or +X [stat B]" (and similar "pick one of two effects" shapes where the chooser is the maneuver's own controller, not the adversary), prompt the player **at activation time** (before the calc phase) and store the choice on the Maneuver object so the calc-event branch can read it.

Wire it as:

1. **Private choice field** on the Maneuver (e.g., `private bool $ChooseParry = false;`). Reset it in `EventManeuverCanceled` — the maneuver instance lives on `$theah->cards` across rounds, so without reset the next activation would default to the prior choice. (For two-branch choices, also reset in `__construct` to make the default explicit.)
2. **`EventManeuverActivated` handler** — `stackEvent` (not `queueEvent`) a `createTransitionEvent($owner->ControllerId, $owner->Id, "NNNNN", $this->Id)`. `stackEvent` is what makes the choice prompt fire *before* the calc-phase events.
3. **GameState class** `State_duelResolveManeuver_NNNNN` under `modules/php/States/<expansion>/`:
   - `id: States::DUEL_RESOLVE_MANEUVER_NNNNN` (constant value `52500000 + NNNNN`, prefix `5250` — NOT `4` or `5290`).
   - `name: "duelResolveManeuver_NNNNN"`.
   - `transitions: ["" => States::DUEL_RESOLVE_MANEUVER_EVENTS]` (empty default — `actFromManeuverWithId` calls `$game->gamestate->nextState()` with no arg).
   - Possible action: `actFromCardWithId(string $id)` → `$this->game->actFromCardWithId($id)`.
   - `zombie(int $playerId)` → `$this->game->gamestate->nextState()`.
4. **`states.inc.php` wiring** — add `"NNNNN" => States::DUEL_RESOLVE_MANEUVER_NNNNN` to `DUEL_RESOLVE_MANEUVER_EVENTS.transitions` (NOT `HIGH_DRAMA_PLAYER_TURN_EVENTS`).
5. **`States.php`** — `const DUEL_RESOLVE_MANEUVER_NNNNN = 525<NNNNN>;` (alphabetize within the `DUEL_RESOLVE_MANEUVER_*` block).
6. **Override `actFromManeuverWithId(Game $game, int $state, string $stateName, int $id)`** — branch on `$state == States::DUEL_RESOLVE_MANEUVER_NNNNN`, set the choice field, mark `$owner->IsUpdated = true`, emit a `notify->all("message", ...)` so the log records which branch was picked, then `$game->gamestate->nextState()` (no arg — `""` is the default key the GameState transitions table uses).
7. **`EventDuelCalculateManeuverValues` branch on the stored field** — if/else over the choice; each branch mutates the appropriate field (`$event->parry`, `$event->thrust`, etc.) and pushes an `$event->explanations[]` line via `$owner->getInjectCode()`.
8. **JS `OnUpdateActionButtons.<expansion>.js`** — under `methods`, add `'duelResolveManeuver_NNNNN': () => { ... }` with one button per choice. Use `addActionButton(btnId, _('Label'), () => this.bgaPerformAction('actFromCardWithId', { id: N }))` — no confirm step, no card chooser. Mirror the buttons in `OnEnteringState`/`OnLeavingState` only if you need highlighting; the simple two-button case skips both.

```php
// Maneuver_NNNNN
private bool $ChooseParry = false;

public function handleEvent(Event $event)
{
    parent::handleEvent($event);

    if ($event instanceof EventManeuverActivated && $event->maneuverId == $this->Id)
    {
        $owner = $this->getOwningCard($event->theah);
        $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "NNNNN", $this->Id);
        $event->theah->stackEvent($transition);
    }

    if ($event instanceof EventDuelCalculateManeuverValues && $event->maneuverId == $this->Id)
    {
        $owner = $this->getOwningCard($event->theah);
        if ($this->ChooseParry) { $event->parry += 2; $event->explanations[] = sprintf(/* … */); }
        else                    { $event->thrust += 2; $event->explanations[] = sprintf(/* … */); }
    }

    if ($event instanceof EventManeuverCanceled && $event->maneuverId == $this->Id)
    {
        $this->ChooseParry = false;
        $this->getOwningCard($event->theah)->IsUpdated = true;
    }
}

public function actFromManeuverWithId(Game $game, int $state, string $stateName, int $id): void
{
    parent::actFromManeuverWithId($game, $state, $stateName, $id);
    if ($state == States::DUEL_RESOLVE_MANEUVER_NNNNN)
    {
        $this->ChooseParry = ($id == 1);
        $this->getOwningCard($game->theah)->IsUpdated = true;
        // emit notify->all("message", ...)
    }
    $game->gamestate->nextState();
}
```

#### Why `EventManeuverActivated` (not `EventResolveManeuver`)

`EventResolveManeuver` fires after the round's stat calc, when one-shot side effects land (wounds, draws, etc.). If the player's choice *drives the calc*, you must capture it before calc runs — that's `EventManeuverActivated`, which fires earlier in the activation sequence. Queuing a transition from `EventResolveManeuver` for a calc-driving choice lands the prompt after calc has already happened, and the choice has no effect.

`EventResolveManeuver` remains the right hook for one-shot side effects that don't influence calc (wound adversary, draw, queue an end-of-round sub-state). The C.3 pattern is specifically for "the choice changes the math."

#### `stackEvent`, not `queueEvent`, on the activation transition

Same priority math as Pattern D.3: `queueEvent` at `MEDIUM_PRIORITY = 3` would land *behind* calc-phase events. `stackEvent` assigns `min(pending priorities) - 1`, guaranteeing the choice prompt fires first.

**Multi-step C.3 (character chooser → then Riposte/Thrust buttons, etc.):** `stResolveManeuverFromCombatCard` queues Activate → Resolve → Calculate **up front**. The first `stackEvent` from `EventManeuverActivated` correctly pre-empts that pending calc. But when state 1 finishes and transitions to state 2, **that second transition must also `stackEvent`** — `queueEvent("NNNNN_2")` lands *behind* the still-pending `EventDuelCalculateManeuverValues`, so calc runs with the default choice flag and the later button press looks like "stats never updated."

Do **not** "fix" this by re-emitting `EventDuelCalculateManeuverValues` after the choice. Fix the ordering: `stackEvent` every intermediate transition until the calc-driving choice is stored. After the final choice, `nextState()` resumes EVENTS and the original pending calc reads the stored flag.

#### Pure-calc variant: no `EventResolveManeuver` handler needed

When both branches are pure stat mutations (no wound / draw / transition), skip `EventResolveManeuver` entirely. The calc-event branch on the stored choice is the entire effect. Reference: `Maneuver_03024` (+2/+2, Sorcerer/Monster gate), `Maneuver_04030` (+1/+1, no gate).

#### Choice-with-side-effect variant: queue side effects in `actFromManeuverWithId`

When one branch has a side effect (wound the adversary, draw, etc.), queue those events directly from `actFromManeuverWithId` after recording the choice — don't defer to `EventResolveManeuver`. The Maneuver is mid-activation; events queued here land at the right point in the activation sequence. Reference: `Maneuver_01135` (branch 2 queues `createCharacterBeingWoundedEvent` from `actFromManeuverWithId`).

#### Wound-your-other-character cost + choice (multi-step C.3)

"Maneuver: Wound your other character at this location • +1 Riposte or +2 Thrust" (and similar cost • or-choice shapes):

1. **`isAvailableToPlayer`** — at least one other character you control at `$actor->Location` (`getCharactersAtLocationByPlayerId`, exclude actor).
2. **State 1** (`duelResolveManeuver_NNNNN`) — friendly character chooser (`IAbilityThatTargetsCharacters` on the Maneuver; `IRiskThatTargetsCharacters` on the Risk). JS: `highlightCardsAsSelectable` + confirm, same shape as `highDramaPhase03034` / `duelResolveManeuver_01051`.
3. **State 1 → 2** — save `$WoundTargetId`, **`stackEvent`** transition `"NNNNN_2"` (not `queueEvent`).
4. **State 2** (`duelResolveManeuver_NNNNN_2`) — Riposte/Thrust (or Parry/Thrust) buttons. On choice: set the calc flag, queue the wound via `createCharacterBeingWoundedEvent` + `eventCheck`, `nextState()`.
5. **Calc** — `EventDuelCalculateManeuverValues` branches on the stored flag (same as single-step C.3).

Reference: `Maneuver_03035` (Loyal).

#### State-tracking discipline

If the maneuver has any cross-round state beyond the choice (a `next-round` modifier, an `IsActive` flag), reset it in both `EventManeuverCanceled` AND `EventDuelEnd` (and `EventDuelEndOfRound` for "next round only" effects). The choice field itself only needs `EventManeuverCanceled` reset — the next activation will overwrite it. Multi-step: also clear `$WoundTargetId` (etc.) on cancel.

References: `Maneuver_01135` (template; choice gates a side-effect branch with cross-round Thrust reduction), `Maneuver_03024` (Superstitious — pure-calc +2/+2, Sorcerer/Monster gate), `Maneuver_04030` (Tip the Scales — pure-calc +1/+1, no gate), `Maneuver_03035` (Loyal — multi-step wound cost + Riposte/Thrust choice).

### Pattern C.1 — Final Strike maneuver (post-death effect; optionally with player choice)

"Final Strike • <effect>" activates **when your participant is destroyed the round this card is played.** Two shapes:

- **Pure-data on-death** (no player input): mutate threat / queue draw / fire notify. Reference `_01082` (A Heroic End — `+2 Threat` + Lethal to adversary when participant dies).
- **On-death with player choice** (en garde a target, pick a card to discard, etc.): queue a `createTransitionEvent` from the destroyed handler into a card-specific sub-state. Reference `_03022` (Overzealous — En Garde target at the location + conditional draw if participant was Zealot/Hunter).

Skeleton (player-choice variant):

```php
private int $FinalStrikeParticipantId = 0;
private string $DuelLocation = "";   // capture at resolve time — see "Destroyed character is in the locker" below.

public function handleEvent(Event $event)
{
    parent::handleEvent($event);

    if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
    {
        $owner = $this->getOwningCard($event->theah);
        $this->FinalStrikeParticipantId = $event->theah->getDuelOpponentId($event->adversaryId);
        $participant = $event->theah->getCharacterById($this->FinalStrikeParticipantId);
        $this->DuelLocation = $participant->Location;
        $owner->IsUpdated = true;
    }

    if ($event instanceof EventCharacterDestroyed && $event->characterId == $this->FinalStrikeParticipantId)
    {
        $game = $event->theah->game;
        if (! $game->globals->get(Game::IN_DUEL)) return;

        $character = $event->theah->getCharacterById($this->FinalStrikeParticipantId);
        $owner = $this->getOwningCard($game->theah);
        $playerId = $character->ControllerId;   // still valid mid-destroy — see ControllerId note below.

        // ... conditional pure-data effects (draw, notify) ...

        $transitionEvent = EventFactory::createTransitionEvent($playerId, $owner->Id, "NNNNN", $this->Id);
        $event->theah->queueEvent($transitionEvent);
    }

    if ($event instanceof EventManeuverCanceled && $event->maneuverId == $this->Id)
    {
        $this->FinalStrikeParticipantId = 0;
        $this->DuelLocation = "";
        $owner = $this->getOwningCard($event->theah);
        $owner->IsUpdated = true;
    }

    if ($event instanceof EventDuelNewRound && $this->FinalStrikeParticipantId != 0)
    {
        $owner = $this->getOwningCard($event->theah);
        $owner->IsUpdated = true;
        $this->FinalStrikeParticipantId = 0;
        $this->DuelLocation = "";
    }
}
```

#### Destroyed character is in the locker by selection time — capture `$DuelLocation` at resolve

By the time the player makes the en-garde / discard / etc. choice, **the destroyed participant has been moved to the locker.** `$actor->Location` and `$theah->getDuelRoundActor()->Location` will return the locker location, NOT the duel location. Any `getCharactersAtLocation($actor->Location)` query that runs in `getArgsFromManeuver` / `isValidTargetForAbility` / `actFromManeuverPass` will look at the wrong location and return an empty (or wrong) target set.

Capture `$participant->Location` on `EventResolveManeuver` (when the participant is still alive at the duel location), store it on the Maneuver object, and route all post-death location queries through it. Reset alongside `$FinalStrikeParticipantId` in `EventManeuverCanceled` and `EventDuelNewRound`. Mirror `_03022::DuelLocation` + `getResolutionLocation(Theah $theah)` helper.

#### Mid-destroy character lookups are valid

When `EventCharacterDestroyed` fires, the character has `IsDying = true` but has NOT yet been physically moved (the destroy event is queued, not applied; the move happens in the central hub handler later in the same loop). So inside your `EventCharacterDestroyed` handler:

- `$theah->getCharacterById($event->characterId)` returns the character.
- `$character->ControllerId` is still the original controller (use this to pick the player who will make the post-death choice — NOT `getActivePlayerId()`, which is the current duel actor and may be the *killer*, not the controller of the destroyed participant).
- `$character->hasTrait(...)` works for conditional gates ("if your participant was a Zealot or Hunter").

#### State naming: `DUEL_END_OF_ROUND_NNNNN`, not `DUEL_RESOLVE_MANEUVER_NNNNN`

The maneuver's "resolve" phase already ran (that's where you queued the participant-tracking). The transition into the player-choice state fires from `EventCharacterDestroyed` during the **end-of-round events loop** (state `5290` `DUEL_END_OF_ROUND_EVENTS`), because that's where wound-driven destruction usually completes. Wire accordingly:

- **States.php constant:** `DUEL_END_OF_ROUND_NNNNN = 52901NNN` (or `52903NNN` for faf, etc. — pattern is `5290` + zero-padded card number).
- **`states.inc.php`:** add `"NNNNN" => States::DUEL_END_OF_ROUND_NNNNN` to the `DUEL_END_OF_ROUND_EVENTS.transitions` map (NOT to `DUEL_RESOLVE_MANEUVER_EVENTS`). If you wire it under `DUEL_RESOLVE_MANEUVER_EVENTS` you'll get a runtime `transition NNNNN is impossible at this state (5290)` error.
- **GameState class:** `modules/php/States/<expansion>/State_duelEndOfRound_NNNNN.php`, `name: "duelEndOfRound_NNNNN"`, `transitions: ["" => States::DUEL_END_OF_ROUND_EVENTS]`. Mirror `State_duelEndOfRound_01096`.
- **JS handlers:** all three `On*.<expansion>.js` files keyed `'duelEndOfRound_NNNNN'`. Use private args (`args.args._private.args.characterIds`) — the state should use `argsForStatePrivate`.

Why end-of-round specifically: wounding during combat resolution carries the death over into `DUEL_END_OF_ROUND_EVENTS` for final processing. The transition event you queued from `EventCharacterDestroyed` is dequeued there; only states whose `transitions` map declares the transition string can accept it.

#### Pass button + gate-on-pass for "target if able" prompts

"En garde target character at this location" (and similar) is a do-if-able prompt — there may be no valid target (everyone at the location is already en garde, no characters at the location, etc.). Wire a Pass affordance:

- **GameState class** — declare `actFromCardPass` as a second `#[PossibleAction]`:
  ```php
  #[PossibleAction]
  public function actFromCardPass(): void { $this->game->actFromCardPass(); }
  ```
- **Maneuver** — override `actFromManeuverPass` and **throw `UserException` if valid targets exist** (player cannot pass when they have a legal choice); otherwise notify + `$game->gamestate->nextState()`:
  ```php
  public function actFromManeuverPass(Game $game, int $state): void
  {
      parent::actFromManeuverPass($game, $state);
      if ($state == States::DUEL_END_OF_ROUND_NNNNN)
      {
          $location = $this->getResolutionLocation($game->theah);
          if (count($this->getValidTargets($game->theah, $location)) > 0)
              throw new UserException($game->translate("There are targets — you must choose one."));
          $owner = $this->getOwningCard($game->theah);
          $game->notify->all("message", clienttranslate('${maneuver_inject_code}: No valid target.'), [
              "maneuver_inject_code" => $owner->getInjectCode(),
          ]);
          $game->gamestate->nextState();
      }
  }
  ```
- **JS `OnUpdateActionButtons`** — add the alert-color Pass button alongside the Confirm button:
  ```js
  this.statusBar.addActionButton(_('Pass'), () => this.bgaPerformAction('actFromCardPass', {}), { id: 'actPass', color: 'alert' });
  ```

The gate is what keeps the Pass button honest — without it, a player could skip a mandatory effect by clicking Pass. Mirror `_03022::actFromManeuverPass`.

### Cross-player maneuver sub-state (adversary picks something)

When the maneuver effect requires the **opposing** controller to pick (e.g., "they discard a card from their hand"), queue a `createTransitionEvent($adversary->ControllerId, ...)` from `EventResolveManeuver`, register the new state in `states.inc.php` under the Duel resolve-maneuver transitions, and implement `actFromManeuverWithId` to validate the pick. Reference: `Maneuver_01115` (Taunt — Finesse-gated adversary-discards-a-card flow), `Maneuver_03036` (line-count-gated discard — Pattern C.4).

**Empty hand:** never enter the chooser with zero hand cards. If discard is the *only* effect, gate `isAvailableToPlayer` on `count(hand) > 0` (`Maneuver_01108a`). If discard is conditional on top of a still-useful calc (C.4), skip the transition at resolve instead — see Pattern C.4.

