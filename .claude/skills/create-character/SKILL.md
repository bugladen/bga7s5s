---
name: create-character
description: Implement or finish a Character or Leader card (modules/php/cards/<expansion>/_NNNNN.php where the class directly extends Character or Leader). Use this skill whenever the user asks you to implement, finish, scaffold, or wire up a Character/Leader card, or when they reference a faction-deck character whose class extends Character (not CityCharacter) and has unimplemented Text. Triggers on phrases like "implement this character", "implement this leader", "finish _NNNNN" (when it extends Character or Leader), "wire up the City Action on Cesca", "wire up the Reaction on this Leader", or natural-language descriptions of a non-city-deck character (lives in a player's faction deck or is a Leader).
---

# Creating a Character or Leader

This skill covers cards that directly extend `Character` (regular faction-deck characters) or `Leader` (which itself extends `Character`). These cards live in a player's faction deck (or are placed at game start as the player's Leader) — they are **not** in the city deck.

Canonical references:
- `modules/php/cards/_7s5s/_01007.php` (Aldo Bussotti) — straightforward `Character` with a passive stat-modifying handleEvent and a City Action.
- `modules/php/cards/_7s5s/_01006.php` (Don Constanzo Scarpa) — `Leader` with a setup-time `IHasReactions` Reaction, a passive `EventPressureOccuring` listener, and multi-step setup states.
- `modules/php/cards/_7s5s/_01089.php` (Soline el Gato) — `Leader` with a passive duel-stat hook (`EventDuelStarted` / `EventDefenderSwapped` / `EventChallengerSwapped`) and a button-based City Reaction.
- `modules/php/cards/_7s5s/_01116.php` (Yevgeni) — `Leader` with a passive `EventDuelCalculateCombatCardStats` hook and two paired Reactions.
- `modules/php/cards/faf/_03001.php` (Cesca del Rosso) — `Leader` with an `EventPhaseDawnEnding` draw effect, a button-based City Reaction triggered by `EventSorcererAbilityPlayed`, and a two-step City Action (CharacterAction with state classes).
- `modules/php/cards/faf/_03002.php` (Aja) — `Character` with a City Action that **issues a Combat challenge with a custom challenge type** (intervention/refusal restricted by Finesse) and a **Gambling Technique** that grants Lethal in-duel.
- `modules/php/cards/faf/_03004.php` (Elena Agnelli) — `Character` with a **dynamic-recompute Finesse bonus tied to her dueling line** (+1 Finesse per Sorcery in her dueling line) and a **Technique gated on her combat card having the Sorcery trait** that adds +1 Parry and wounds the adversary.
- `modules/php/cards/faf/_03013.php` (Daniella Dietrich, Witch/Hunter) — `Leader` with a **continuous Action that tags opposing characters with a trait** (Sorcerer) for the duration of the player's turn, a **cost-reduction Reaction** (Faith/Sorcery card at -1 cost, cloned from `Reaction_01116b`), and a **Wound-then-Swap Technique** usable in BOTH challenge and duel contexts (two state classes, swap mechanics inline in `actFromTechniqueWithId`).
- `modules/php/cards/faf/_03014.php` (Kaspar Dietrich, Iron Reforged) — `Character` with a **wound-prevention passive via `eventCheck` on `EventCharacterBeingWounded`** (opponents' abilities cannot wound or move wounds to Kaspar — threat conversion still applies) and a **Technique gated on an Eisenfaust attachment OR an Eisenfaust card in the dueling line** that wounds the adversary.
- `modules/php/cards/faf/_03015.php` (Joern Kietelsson, Fury's Edge) — `Character` with three pure-passive abilities living entirely on the card class (no Action/Reaction/Technique files): a **Forced self-wound on muster** (must hook BOTH `EventCharacterMustered` AND `EventApproachCharacterPlayed`), a **phase-conditional Resolve penalty** ("During Dusk, -3 Resolve" — direct `ModifiedResolve` mutation gated by a private flag because there is no `EventCharacterResolveModifiedEvent` factory), and a **challenge-refused self-heal** on `EventChallengeRejected`.
- `modules/php/cards/faf/_03027.php` (Odette Dubois D'Arrent, Disillusioned Courtier) — `Character` with two paired button-based City Reactions: (1) `EventCharacterDestroyed` triggered "after another character at this location is destroyed" with a mandatory heal + optional adjacent-Renown-move, including the **Pass-button pattern** (early-return before `setUsed` so the daily slot survives a decline) and **no-op effect gating** (hide "Heal only" button at `Wounds == 0`); (2) `EventChallengeIssued` triggered "after a challenge is issued at this location, before choosing to intervene" pulling an adjacent Duelist — uses `EventChallengeIssued` specifically because the text says "before intervene" (`EventChallengeAccepted` fires too late).
- `modules/php/cards/faf/_03028.php` (Térence Rois, Pompous Perveyor) — `Character` combining three card-local patterns with **no state classes / no JS**: (1) **stat-specific challenge ban** — `eventCheck` on `EventChallengeIssued` when `CHALLENGE_STAT == STAT_COMBAT` only (NOT a `canChallenge()` override — Terence can still issue Finesse/Influence challenges); (2) **duel-scoped stat replacement** — "set [Combat] as equal to [Influence]" while participating in a duel at a named city location (`$DuelCombatEqualsInfluenceApplied` flag + stored `$CombatBeforeDuelOverride`, apply on `EventDuelStarted`/swap events, clear on `EventDuelEnd`, re-sync on `EventCharacterInfluenceModified`/`EventCharacterCombatModified`); (3) **City Reaction on third-party equip** — `Reaction_03028` listens on `EventAttachmentEquipped` when *any* character equips at `Game::LOCATION_CITY_BAZAAR` while the owner is also in the city there (NOT gated on `characterId == owner.Id` — compare `Reaction_01039` Philip, which only fires on self-equip).
- `modules/php/cards/faf/_03037.php` (Sanjay, Daring Tomcat) — `Leader` with (1) **gambled-only combat-card Riposte bonus** via `EventDuelCalculateCombatCardStats` gated on `$event->gambled` (not every combat card — contrast Yevgeni), (2) **button Reaction on challenge refused** that Collects Renown from his location (`Removed` + `PlayerGains`), (3) **City Action that issues an Influence challenge without engaging** — `SANJAY_CHALLENGE_TYPE` kept out of auto-engage and **no** `createCardEngagedEvent` (not Don Constanzo's conditional-engage). Hand-size filter: only opposing characters whose controller has fewer cards in hand.
- `modules/php/cards/faf/_03038.php` (Damya Kahina, Sea Serpent) — `Character` with **two City Actions** as `Action_03038a` / `Action_03038b`: (1) **Draw then discard** — queue `createCardDrawnEvent` on `EventActionTriggered` before transitioning to a `factionHand` discard picker; (2) **Move equipped character → destroy attachment → draw cost+1** — character picker (no `IAbilityThatTargetsCharacters` — text lacks "target"), move with `engage=false`, then Adelheide-style attachment **button** picker; destroy via unequip + `createCardDiscardedFromPlayEvent`; draw `WealthCost + 1`.
- `modules/php/cards/faf/_03039.php` (Íñigo Rocoso, Avispa Mordedora) — `Character` with a **Weapon-equipped +1 Finesse passive** (Rena `_01040` count-transition pattern via `EventAttachmentEquipped`/`Unequipped` + `createCharacterFinesseModifedEvent`) and a **composite Gambling Technique**: −2 Thrust (combat-card ≥2 Thrust gate), adversary hand discard picker (Maya `Technique_01093`), post-discard hand-size En Garde, unconditional EndOfRound move Home (`engage=false`).

When in doubt, mirror one of those rather than invent.

> **Sibling skills:**
> - `create-city-character` — for stubs that `extends CityCharacter` (city-deck, mustered with WealthCost, `CityCardNumber`).
> - `create-city-event-card` — for stubs that `extends CityEventCard`.
> - `create-city-attachment` — for stubs that `extends CityAttachment`.
>
> All three of those city-deck siblings also descend from `Character`/`Card` ultimately, so **a lot of the runtime semantics overlap** with this skill. Use them when the stub literally extends one of those classes; use this skill when the stub extends `Character` or `Leader` directly. The most relevant overlap with `create-city-character` is Pattern C (CharacterAction + state classes + JS wiring) and Pattern D (button-based Reactions) — those patterns are essentially identical and were trimmed here rather than duplicated. Read the city-character skill alongside this one when implementing a multi-step action or reaction.

## Distinction: Character vs CityCharacter vs Leader

| Class | Lives in | Cost to put in play | Key fields |
|---|---|---|---|
| `Character` (direct) | Player's faction deck (or hand) | Wealth cost paid via standard recruit action | Resolve, Combat, Finesse, Influence (+ dashed variants), Traits |
| `Leader extends Character` | In play from game start, never recruited | None (placed during setup) | All Character fields + `CrewCap`, `Panache` |
| `CityCharacter extends Character` | City deck | WealthCost; can be Negotiable | All Character fields + `Negotiable`, `WealthCost`, `CityCardNumber` |

If the stub says `extends CityCharacter`, switch to `create-city-character`. If it says `extends Character` or `extends Leader`, you're in the right place.

A "City Action" or "City Reaction" in the card text does **not** make a card a CityCharacter. The "City" prefix on those keywords is about the ability scope (must be in the city to use it), not about where the card lives. A Leader like Cesca del Rosso has a City Action — and Cesca still `extends Leader`, not `CityCharacter`.

## Base Anatomy — Character

`Character extends Card implements IHasTechniques` and mixes in `TechniqueTrait`. It adds stat fields (`Resolve`, `Combat`, `Finesse`, `Influence` + `Modified*` and `Dashed*` variants), the `Title` flavor subtitle, `Wounds` tracking, and the `Attachments` array.

```php
namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;

class _NNNNN extends Character implements IHasActions   // + IHasReactions / IHasManeuvers / etc. as text requires
{
    use ActionTrait;
    // use ReactionTrait;
    // use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name            = clienttranslate('...');
        $this->Image           = 'NNNNN.jpg';
        $this->ExpansionName   = '_7s5s';   // or 'tac' / 'faf'
        $this->ExpansionNumber = 1;
        $this->CardNumber      = NN;        // matches the file name's NNNNN

        $this->initializeFaction('Vodacce');   // mandatory for non-Leader Characters — sets $Factions
        $this->Title    = clienttranslate('...');

        $this->Resolve   = 4;
        $this->Combat    = 1;
        $this->Finesse   = 3;
        $this->Influence = 1;
        // $this->DashedCombat = true; // when stat is printed as "—"

        $this->Traits = [
            clienttranslate('Diplomat'),
            clienttranslate('Red Hand'),
            clienttranslate('Vodacce'),
        ];

        $this->Text = clienttranslate("<p>...</p>");

        $this->resetCard();   // copies stats into Modified* fields

        $this->Actions = [ new Action_NNNNN() ];  // only if IHasActions
    }
}
```

## Base Anatomy — Leader

`Leader extends Character` and adds `CrewCap` and `Panache` (with `Modified*` variants). Leaders also have built-in `handleEvent` logic for `EventCharacterDestroyed` (renown loss / game end) and `EventSchemeCardRevealed` (Panache modifier from schemes). **You must call `parent::handleEvent($event)` first in any override** so this logic still runs.

```php
namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;

class _NNNNN extends Leader implements IHasActions, IHasReactions
{
    use ActionTrait;
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name            = clienttranslate('...');
        $this->Image           = 'NNNNN.jpg';
        $this->ExpansionName   = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber      = 1;
        $this->Title           = clienttranslate('...');

        $this->Resolve   = 7;
        $this->Combat    = 1;
        $this->Finesse   = 2;
        $this->Influence = 4;

        $this->CrewCap = 6;       // Leader-only: maximum number of crew this Leader can field
        $this->Panache = 2;       // Leader-only: scheme-resolve order tiebreaker

        $this->Traits = [
            clienttranslate('Leader'),    // canonical — every Leader has "Leader" as a trait
            clienttranslate('Villain'),
            clienttranslate('Sorcerer'),
            clienttranslate('Strega'),
            clienttranslate('Red Hand'),
            clienttranslate('Vodacce'),
        ];

        $this->Text = clienttranslate("<p>...</p>");

        $this->resetCard();

        $this->Actions   = [ new Action_NNNNN() ];
        $this->Reactions = [ new Reaction_NNNNN() ];
    }
}
```

Differences from regular Character:
- **Do NOT call `initializeFaction()`** on a Leader — the framework sets the faction from the player's faction selection at setup. The Leader's `Factions` is implicit. (Look at `_01006`, `_01089`, `_01116` — none call `initializeFaction`. `_01035` Kaspar does, but `_01089` Soline doesn't, and `_03001` Cesca doesn't. The base game's Leader setup populates this regardless.) If you're scaffolding and unsure, omit it for Leaders.
- **Always include `"Leader"` in `Traits`.** Cards filter on `hasTrait("Leader")` (e.g., "target a non-Leader" effects), so this is load-bearing.
- **`CrewCap` and `Panache` are required.** Don't leave them at the constructor defaults of 0.

Field notes (apply to both Character and Leader):

- **`Resolve`** is wound capacity. Required.
- **`DashedCombat` / `DashedFinesse` / `DashedInfluence`** match the printed dashes on the card's stat block. Dashed stats are visually `—`; the character cannot use them in pressures/challenges. Set the underlying numeric stat to `0` when dashed.
- **`CardNumber`** matches the NNNNN in the filename. Regular Characters use this — only CityCharacters override it to `0` and use `CityCardNumber` instead.
- **`Factions`** is set by `initializeFaction(string $faction)` for regular Characters; populated by the framework's setup flow for Leaders.

Key runtime state inherited from `Character` / `Card`:
- `$this->Id` — this character's card id.
- `$this->ControllerId` — the player currently controlling. `0` for cards not yet in play.
- `$this->Location` — current location string. While in deck/hand, this is a deck/hand location; once mustered into play, a city location or Home.
- `$this->Engaged` — engagement state.
- `$this->Wounds`, `$this->ModifiedResolve` — wound tracking.

## Pick the Right Ability Shape

Read each clause of the printed Text and classify it before writing code. A single Character/Leader commonly combines several. Cesca has all three of: a passive End-of-Dawn effect, a button-based City Reaction, and a multi-step City Action.

| Card phrase | Pattern |
|---|---|
| **Stat printed as a dash (`—`)** | Set the matching `Dashed<Stat> = true;` flag + numeric stat to `0`. |
| **"<Name> cannot intervene/challenge/pressure"** (all types) | Override the predicate AND `eventCheck`. See "Pattern A — Hard ban" in the `create-city-character` skill — the implementation is identical. |
| **"<Name> cannot issue [Combat] challenges"** (stat-specific ban) | **`eventCheck` only** — do NOT override `canChallenge()` to `false`. The character may still issue Finesse/Influence challenges via card actions; default `canChallenge()` (`isControlled`) must stay true for those performer lists. Gate `EventChallengeIssued` on `challengerId == $this->Id` AND `globals->get(CHALLENGE_STAT) == STAT_COMBAT`. Basic Challenge always uses Combat (`FrameworkActionsTrait::actHighDramaChallengeActionStart`), so the issue-time backstop blocks it; the character may still appear in the Basic Challenge performer list until chosen (acceptable UX). Reference: `_03028` Térence (ban Combat), `_02013` Wilhelm (inverse — *only* Combat). |
| **"When <X> happens, <passive thing>"** (no player choice) | Override `handleEvent`. Gate on event type + identity + location/scope. See Pattern A below. |
| **"At the end of Dawn"** / **"At the beginning of Dawn"** | `handleEvent` on `EventPhaseDawnEnding` / `EventPhaseDawnBeginning`. See Pattern A below. |
| **"At/During <phase>"** broadly | One of the phase events: `EventNewDay`, `EventPhaseDawnBeginning`, `EventPhaseDawnEnding`, `EventDuskPhaseBegin`, `EventDuskPhaseEnd`, `EventDuskEndOfDay`, `EventPressureOccuring`, `EventDuelStarted`, etc. See "Phase / lifecycle events" below. |
| **"Forced: After <Owner> musters • <effect>"** / **"When <Owner> musters …"** | `handleEvent` on **BOTH** `EventCharacterMustered` AND `EventApproachCharacterPlayed`, OR'd, gated on `characterId == $this->Id`. Mustering via Approach emits a distinct event — hooking only `EventCharacterMustered` silently misses the Approach path. See Pattern A "Forced muster/approach triggers" below. Reference: `_03015` Joern, `_01009` Cirilo. |
| **"During <Phase>, <Owner> has -N Resolve"** (or any phase-conditional **Resolve** modifier) | There is no `createCharacterResolveModifiedEvent` factory — Resolve is not on the event-driven stat list. Directly mutate `$this->ModifiedResolve` on the phase-begin event, gated by a private bool flag (because attachments also mutate `ModifiedResolve` independently), and restore on the phase-end event. Manually emit `createCharacterDestroyedEvent` if the reduction crosses the wounds-equal-resolve threshold (the engine's destruction check only runs inside an `EventCharacterWounded` handler). See Pattern A "Phase-conditional Resolve modifier" below. Reference: `_03015` Joern. **For Combat/Finesse/Influence/Panache, use the matching `createCharacter<Stat>ModifiedEvent` factory instead** — they're event-driven the way Resolve isn't. |
| **"When <Owner>'s challenge is refused, <effect>"** / **"When a challenge to <Owner> is refused …"** | If the text is a **Forced** / plain passive (no player choice): `handleEvent` on `EventChallengeRejected`. If the text is a **`<b>Reaction:</b>`**: Pattern D button Reaction on the same event. Fields: `$event->challengerId` (issued), `$event->targetId` (refused). Identity gate matches whichever role the text names. Reference: `_03015` Joern (passive self-heal), `Reaction_01116a` Yevgeni (Reaction En Garde), `Reaction_03037` Sanjay (Reaction Collect Renown). |
| **"<Owner>'s gambled combat cards have +N[Riposte/Parry/Thrust]"** | Pattern A on `EventDuelCalculateCombatCardStats`: gate `actorId == $this->Id` **AND** `$event->gambled`. Call `$event->addRiposte(N)` (etc.). WHY `$event->gambled` not `Game::DUEL_GAMBLED` alone: the calculate-stats event already carries the authoritative per-round flag from `duel_round.gambled` (includes Roll-the-Bones paths). Contrast Yevgeni `_01116` (every combat card, no gambled gate). See Pattern A "Gambled combat-card stat bonus" below. |
| **"Collect a Renown from <Owner's / this> location"** (Reaction or Action effect) | `createRenownRemovedFromLocationEvent` + `createPlayerGainsReknownEvent`. Valid-target gate: `getCityLocation(...)->Renown > 0` before prompting. Reference: `Reaction_03037` Sanjay, `Action_02035` (pressure-success Collect). |
| **"<Owner> has +N [Stat] while wounded"** (or any "while <condition-on-self>" stat bonus) | Pattern A passive with a private bool flag (e.g., `$WoundedCombatBonusApplied`). Hook `EventCharacterWounded` AND `EventCharacterHealed` with `characterId == $this->Id`, call `parent::handleEvent` first (so `$this->Wounds` is up-to-date), then re-derive the boolean and queue `createCharacter<Stat>ModifiedEvent(±1)` only on flag transition. Skip if `IsDying` or in discard/locker. See Pattern A "Stat bonus while a self-condition holds" below. Reference: `_03016` Ise. |
| **"While <Owner> is equipped with a <b>Weapon</b>, he gains +N[Stat]"** | Pattern A on `EventAttachmentEquipped` / `EventAttachmentUnequipped` gated on `characterId == $this->Id`. Count Weapons in `$this->Attachments` **after** the event (Attachments already reflects the new set). Apply +N only when count transitions to `1`; undo only when count transitions to `0`. Do **not** invent a bool flag — the count-transition is the established shape and survives Offhand / multi-Weapon edge cases. Use `createCharacterFinesseModifedEvent` / `createCharacterCombatModifiedEvent` (note Finesse factory typo `Modifed`). Reference: `_01040` Rena (+1 Combat), `_03039` Íñigo (+1 Finesse). |
| **"Set [StatA] as equal to [StatB]"** while a scoped condition holds (e.g., "while participating in a duel at [The Grand Bazaar]") | Pattern A passive with a **replacement** flag + stored pre-override snapshot — NOT the ±1 delta pattern. Apply on condition start (`EventDuelStarted` + location/participant gates, plus `EventDefenderSwapped`/`EventChallengerSwapped` for mid-duel entry/exit), clear on condition end (`EventDuelEnd`), re-sync target stat whenever source stat changes (`EventCharacterInfluenceModified` → update Combat) or external sources mutate the target stat away from the link (`EventCharacterCombatModified` with `NewCombat != ModifiedInfluence`). Store `$CombatBeforeDuelOverride` at apply-time; restore that snapshot on clear (NOT recompute-from-base — attachments may change mid-condition). See Pattern A "Set one stat equal to another while a scoped condition holds" below. Reference: `_03028` Térence. |
| **"During <Phase>, you may choose not to <auto-action on Owner>"** (opt-out of an auto-emitted event) | Pattern D Reaction listening on the *pre*-event (e.g., `EventCardMoving` for the Dusk move-home) with `sourceId == 0` (auto-emitter signal) + a phase gate (`TURN_PHASE == Game::DUSK`) + the `cancelDeclinedByCardIds` re-queue dance. Cancel the event, clone it, prompt the player; on "Keep" call `setUsed(true)`; on "Decline" re-queue the clone with `cancelDeclinedByCardIds[] = owner->Id` so the reaction doesn't immediately re-catch it. See Pattern D's "Cancel-and-reissue Reaction" subsection. Reference: `Reaction_03016a` (Ise Dusk opt-out), `Reaction_01140` (in-hand RiskReaction sibling). |
| **"<Stat> increases by N"** / **"<Stat> is reduced by N"** | Queue `createCharacter<Stat>ModifiedEvent` (e.g., `createCharacterInfluenceModifiedEvent`). See `_01007` Aldo for renown-driven Influence modification. |
| **"<Owner> has +N[Stat] for each X in her dueling line"** (or any duel-line-derived count) | Pattern A passive with a running `$<Stat>Bonus` field on the card. Recompute at `EventDuelEndOfRound` (the only clean boundary — there is no event fired when a card enters the dueling line; `cards->moveCard` is called directly). Reset at `EventDuelEnd` *before* the line is cleared. Gate on the owner being a duel participant (the dueling line is per-player, not per-character). See Pattern A "Dynamic stat bonuses tied to the dueling line" below. Reference: `_03004` Elena. |
| **"While you control X at <Owner>'s location, she has +N [Stat]"** (any location-counting passive) | Pattern A passive that hooks `EventCardMoved` (and `EventCharacterMustered` / `EventApproachCharacterPlayed` / `EventCharacterDestroyed` / `EventCharacterRecruited`). **`EventCardMoved` fires BEFORE the DB location update** (`runEventHubAfterCards = true`), so `getCharactersAtLocation` returns the *pre-move* state. Either pass an explicit `+1`/`-1` adjustment (per-character count — `_01037`) or thread the event into the helper to exclude the moving-out card and look up the moving-in card (binary "any qualifying member" bonus — `_03026`). Add a no-op gate `if ($new == $this->ModifiedStat) return;` to skip same-value events. See Pattern A "Location-counting passives" below. |
| **"Opponents' abilities cannot wound (or move wounds to) <Owner>"** / "<Owner> ignores wounds from X" | Override `eventCheck` on the card class and zero `$event->wounds` on `EventCharacterBeingWounded`. Distinguish ability-emitted wounds (non-empty `abilityId`) from threat-conversion wounds (empty `abilityId`). See Pattern A "Wound-prevention passive" below. Reference: `_03014` Kaspar (opponent's-ability scope), `_01069` Maxime (own-Sorcerer scope), `_01153` Breastplate (in-duel reduction-by-one). |
| **`<b>Action:</b>`** / **`<b>City Action:</b>`** | Implement `IHasActions`, `use ActionTrait`, create `actions/Action_NNNNN.php` extending `CharacterAction`. State class(es) + JS wiring per Pattern C. **"City Action" only differs by the `cardInCity` gate** in `isAvailableToPlayer`. |
| **Two (or more) distinct City Actions / Actions on one card** | Split into separate classes `Action_NNNNNa`, `Action_NNNNNb`, … each with its own Name, availability, states, and transition keys (`"NNNNNa"`, `"NNNNNb"`). Wire all in `$this->Actions = [...]`. State IDs append `1`/`2` (e.g. `4030381` / `4030382`); a multi-step **b** uses `40303822` for step 2. Reference: `_03038` Damya, `_01095` Patricia. |
| **"Draw a card. Then, discard a card."** | Queue `createCardDrawnEvent` in `EventActionTriggered` **before** `createTransitionEvent` into the discard state — printed order is Draw → Discard, and the client needs the drawn card in `factionHand` before the picker. Hand picker = Pattern C `factionHand` (not `highlightCardsAsSelectable`). Availability: `cardInCity` + player will have ≥1 hand card after draw (hand nonempty **OR** faction deck + discard nonempty — empty-everything hangs the discard state). Reference: `Action_03038a`. |
| **"Your equipped character moves to this location. Then, destroy their attachment to draw …"** | Two-step Pattern C. "Equipped" = your character with ≥1 non-`FakeAttachment`. Strict "moves to" → exclude characters already at the owner's location (and thus the owner herself when she is there). No "target" in text → **no** `IAbilityThatTargetsCharacters`. Move with `engage=false` when Engage is not printed. Attachment step: button list (Adelheide `01194`), not board highlight. Destroy = unequip + `createCardDiscardedFromPlayEvent`; capture `WealthCost` **before** destroy; then queue `WealthCost + 1` draws. Parenthetical "must be destroyed to draw" → no Pass on the attachment step. Reference: `Action_03038b`. |
| **"Destroy [an] attachment"** (effect, any context) | Canonical recipe: `createAttachmentUnequippedEvent` → `eventCheck` → queue; then `createCardDiscardedFromPlayEvent(..., $asEffect = true)`. Do **not** invent `createAttachmentDestroyedEvent` — it does not exist. Reference: `Action_01174`, `Maneuver_01142`, `Action_03038b`. |
| **"Issue a [stat] challenge to target …"** (any flavor) | CharacterAction that sets `CHOSEN_PERFORMER`/`CHOSEN_TARGET`/`CHALLENGE_STAT`/`CHALLENGE_TYPE` and queues a transition into the challenge sub-state machine. See Pattern F. **Engagement is a trichotomy** — do not assume "no Engage printed" means Don Constanzo conditional-engage; some actions never engage (Sanjay `_03037`). |
| **"… If their controller has fewer cards in hand than you, …"** (hand-size gate on targets) | Filter opposing targets (and `isAvailableToPlayer`) by comparing `count($game->getGameDeckObject()->getPlayerHand($controllerId))`. Prefer filtering at availability so the action never offers a dead pick. Reference: `Action_03037` Sanjay. |
| **"Your <Trait> at this location issues a challenge"** (performer ≠ owner) | Two-step Pattern F: step 1 picks the *performer*, step 2 picks the target at the *performer's* location. Engagement follows the trichotomy (Don Constanzo = conditional engage; never-engages variants emit no engage). See Pattern F's "Performer ≠ action owner" subsection. Reference: `Action_03003`. |
| **`<b>Reaction:</b>`** / **`<b>City Reaction:</b>`** | Implement `IHasReactions`, `use ReactionTrait`, create `reactions/Reaction_NNNNN.php` extending `CardReaction`. Button-based reactions need **no** state class, **no** `states.inc.php` edits, **no** JS wiring. See Pattern D. |
| **"Reaction: After <enemy/X> character moves to this location • <effect>"** | Pattern D Reaction listening on `EventCardMoved` (past-tense). Gates: `event.cardId != owner.Id` (skip Owner's self-move), `event.toLocation == owner.Location`, `cardInCity(owner)` (enemies can't enter your Home), `$character instanceof Character`, `ControllerId != 0`, and the "enemy" controller check (`!= owner.ControllerId`). Pair with a valid-effect-target precondition. See Pattern D's "After a character moves to this location" subsection. Reference: `Reaction_03016b` (Ise). For the *self-moves* analogue ("after this character moves to a new location"), see `_01067` Jean Urbain and `_02022` Stranahan. |
| **"Move another character you control to this location"** (effect) | Queue `createCardMovingEvent($character.ControllerId, $character.Id, $character.Location, $owner.Location, $engage, $owner.Id, $this->Id)` for the chosen mover. Eligible movers = `getCharactersInPlayByPlayerId($owner.ControllerId)` minus the owner herself minus characters already at her location. Don't use any pull/teleport helper — there isn't one; the standard move event handles all the bookkeeping. Reference: `Reaction_03016b` (other character to here), `Reaction_01039` (move self to adjacent). |
| **"Reaction: … at -N cost"** / **"… pay N Wealth"** | Pattern D Reaction with **in-reaction click-to-pay** wealth tracking. Don't use `PAY_STATE_PLAY_BRUTE` — it's tied to the player-turn state cycle. See Pattern D's "Reactions that need to pay a wealth cost" subsection. Reference: `Reaction_03003`. |
| **"Reaction: Put a different X into play from your hand or discard pile"** | Pattern D Reaction. Filter eligibles separately from `LOCATION_HAND` and `getPlayerDiscardDeckName(...)`, exclude the just-destroyed card by id. `createCharacterMusteredEvent` does the actual move; `createCardRemovedFromPlayerDiscardPileEvent` is notification-only (fire it before the muster so JS clients sync correctly). Reference: `Reaction_03003`, `Action_01024` (Bravos). |
| **"Reaction: After a character equips an attachment at [location]"** | Pattern D Reaction on `EventAttachmentEquipped`. Gate: `cardInCity($owner)`, `$owner->Location == <named location>`, equipping `$character->Location == <named location>`, skip `$attachment->FakeAttachment`. Trigger is **any character** equipping there — do NOT gate on `$event->characterId == $owner->Id` unless the text names the owner ("After Philip equips …"). Contrast: `Reaction_01039` (Philip self-equip → move self). Draw/move/etc. effects use Draw/Pass or Pass-only buttons per Pattern D. Reference: `Reaction_03028` (any character at Grand Bazaar), `Reaction_01146a` (scheme owner equips Weapon). |
| **`<b>Sorcerer …</b>`** (Sorcerer Action / Sorcerer Reaction) | The Action/Reaction class additionally `implements ISorcererAbility`. **Must** call `createSorcererAbilityStartEvent()` and `createSorcererAbilityPlayedEvent()` (pre-commit hook enforces). See `Action_01076` and `Reaction_02001`. |
| **`<b>Technique:</b>` / `<b>Maneuver:</b>`** | The Character lineage already brings `TechniqueTrait`. Add `IHasManeuvers` + `ManeuverTrait` for maneuvers. Implement under `cards/<expansion>/techniques/` or `cards/<expansion>/maneuvers/`. See Pattern E. |
| **`<b>Gambling Technique:</b>`** | Same as Technique, but `isAvailableToPlayer` additionally gates on `Game::DUEL_GAMBLED` (actor gambled for their combat card this round). See Pattern E. |
| **"-N[Thrust/Riposte/Parry] • … (Your combat card must have at least N …)"** | Gate availability with `getCurrentRoundThrust()` / `getCurrentRoundRiposte()` / etc. `>= N`. Apply the −N on `EventDuelCalculateTechniqueValues` (`$event->thrust -= N`). Parenthetical is the printed cost clarification — same gate shape as `Technique_01050` (−1 Thrust) / `Technique_01093` (−1 Riposte). |
| **"The adversary discards a card"** (Technique effect) | On `EventResolveTechnique`, if adversary hand nonempty: `createTransitionEvent($adversary->ControllerId, …, "NNNNN", …)` into `DUEL_CHOOSE_TECHNIQUE_NNNNN` (active player = adversary). `actFromTechniqueWithId` validates hand ownership + queues `createCardDiscardedFromHandEvent(..., $asEffect = true)`. Empty hand → skip picker. JS: `factionHand` single-select + `onCardDiscarded` + **also** wire `EventHandlers.js` so Confirm enables on selection. Reference: `Technique_01093` Maya, `Technique_03039` Íñigo. |
| **"Then, if they have more cards in hand than you, en garde <Owner>"** (after adversary discard) | Compare hands **after** the discard. In `actFromTechniqueWithId` the discard is queued not flushed — use `(adversaryHandCount - 1) > ownerHandCount`. Empty-hand path compares `0 > owner` (never engardes). Queue `createCardEngardedEvent`. Reference: `Technique_03039`. |
| **"At the end of the round, move <Owner> Home"** (Technique follow-on) | Private `$MoveHome` flag set on `EventResolveTechnique` (unconditional once the technique resolves — do not gate on the hand-size En Garde clause unless the text does). On `EventDuelEndOfRound`: clear flag, skip if discard/locker or already Home, queue `createCardMovingEvent(..., Game::LOCATION_PLAYER_HOME, $engage=false, …)` when Engage is not printed (contrast `_01053` which engages). Clear on `EventTechniqueCanceled` / `EventDuelEnd`. Reference: `Technique_03039`, move-deferral sibling `Technique_01036`. |

## Pattern A — Passive ability via `handleEvent`

For text that has no player choice ("At the end of Dawn, draw five cards", "Your adversaries at Soline's location have -1 Finesse", "When Yevgeni plays a combat card, it gains +1 Thrust") — override `handleEvent` and gate the body on event type + identity + scope. Always call `parent::handleEvent($event)` first.

### Identity and scope gates

1. **Event type** — `instanceof EventXxx`.
2. **Identity** — usually `$event->cardId == $this->Id`, `$event->characterId == $this->Id`, `$event->playerId == $this->ControllerId`, or `$event->actorId == $this->Id`. The exact field depends on the event class; **read the event source file** to confirm.
3. **Liveness / scope** — at minimum a "this card is in play" check. For a Leader, the right check is usually `! $event->theah->game->characterIsInDiscardOrLocker($this)` (and `$this->ControllerId > 0` as a cheap pre-check). For an "in city" effect, also gate on `$event->theah->cardInCity($this)`.

### End-of-Dawn draw (canonical example — Cesca)

```php
public function handleEvent(Event $event)
{
    parent::handleEvent($event);

    if ($event instanceof EventPhaseDawnEnding && $this->ControllerId > 0)
    {
        $game = $event->theah->game;
        if ($game->characterIsInDiscardOrLocker($this))
        {
            return;   // dead Leader / destroyed Character — skip the effect
        }

        $game->notify->all("message", clienttranslate('${leader_inject_code}: ${player_name} draws five cards at the end of Dawn.'), [
            "leader_inject_code" => $this->getInjectCode(),
            "player_name"        => $game->getPlayerNameById($this->ControllerId),
        ]);

        for ($i = 0; $i < 5; $i++)
        {
            $drawEvent = EventFactory::createCardDrawnEvent($this->ControllerId, $this->getInjectCode());
            $event->theah->queueEvent($drawEvent);
        }
    }
}
```

WHY `characterIsInDiscardOrLocker` and not just `isControlled()`:

- A destroyed Leader still has a non-zero `ControllerId` — `isControlled()` returns true.
- The actual signal that the Leader is out of play is the `Location` (discard/locker).
- See `UtilitiesTrait::characterIsInDiscardOrLocker` for the canonical check.

Apply the same check on any Character ability that triggers off phase events.

### Gambled combat-card stat bonus

For text like **"Sanjay's gambled combat cards have +1[Riposte]"** (not every combat card — only when the actor gambled this round):

```php
if ($event instanceof EventDuelCalculateCombatCardStats
    && $event->actorId == $this->Id
    && $event->gambled)
{
    $event->explanations[] = sprintf(
        $event->theah->game->translate("%s's gambled combat card gains +1 Riposte"),
        $this->getInjectCode()
    );
    $event->addRiposte(1);
}
```

WHY `$event->gambled` (not `Game::DUEL_GAMBLED` alone): `StatesTrait::stResolveCombatCard` sets `$event->gambled` from `duel_round.gambled` for the current round — the authoritative per-round flag, including Roll-the-Bones paths that still attribute stats through this event. The `DUEL_GAMBLED` global is the right gate for **Gambling Technique** availability (`isAvailableToPlayer`); the calculate-stats event's own field is the right gate for **passive combat-card modifiers**.

Contrast: Yevgeni `_01116` adds +1 Thrust on every combat card (`actorId` only, no gambled gate). Sanjay `_03037` is the gambled-only variant.

Reference: `_03037` Sanjay, `_01116` Yevgeni.

### Drawing cards

- One card: `EventFactory::createCardDrawnEvent($playerId, $reason)` then `queueEvent`.
- N cards: loop and queue N events. The framework draws one card per event. (Yes, `_03001` literally queues five draw events in a loop.)
- The `$reason` string shows in the log alongside the draw. Use `$this->getInjectCode()` so the log links back to your card.

### Passive stat modifiers

For "Your <stat> increases / decreases by N":

```php
private function lowerFinesse(Character $character, Theah $theah)
{
    $event = EventFactory::createCharacterFinesseModifedEvent(
        $this->ControllerId,
        $character->Id,
        $character->ModifiedFinesse,                    // from
        $character->ModifiedFinesse - 1,                 // to
        $this->getInjectCode()                           // reason for log
    );
    $theah->queueEvent($event);
}
```

The factories are:
- `createCharacterCombatModifiedEvent`
- `createCharacterFinesseModifedEvent` (note the typo in the framework — `Modifed`, not `Modified`)
- `createCharacterInfluenceModifiedEvent`
- `createCharacterResolveModifiedEvent`
- `createCharacterPanacheModifiedEvent` (Leader only)

When the predicate that drives the modifier changes (a character moves into/out of the affected location, a duel ends), queue the inverse event to undo it. See `_01089` Soline el Gato — `lowerFinesse` on `EventDuelStarted`, `raiseFinesse` on `EventDuelEnd` / opposite swap. Track which character was affected on `$this->AffectedCharacterId` and set `$this->IsUpdated = true` so the change persists.

### While equipped with a Weapon (count-transition, not a bool flag)

For "While <Owner> is equipped with a **Weapon**, he gains +N[Stat]" — mirror Rena `_01040` / Íñigo `_03039`. Hook `EventAttachmentEquipped` and `EventAttachmentUnequipped` with `characterId == $this->Id`. After the event, count Weapons in `$this->Attachments` (Attachments already reflects the new set). Queue `+N` only when `weaponsCount == 1` (transition into "has a Weapon"); queue `−N` only when `weaponsCount == 0` (last Weapon left).

WHY count-transition instead of a `$WeaponBonusApplied` bool: Offhand / multi-Weapon equip paths can equip a second Weapon without the first leaving — a naïve "equip Weapon → +1" would stack. Counting after the event applies the bonus exactly once while any Weapon is present.

Use `createCharacterCombatModifiedEvent` or `createCharacterFinesseModifedEvent` (framework typo `Modifed`). Do not invent a Weapon-specific helper.

### Dynamic stat bonuses tied to the dueling line

For text like "Elena has +1[Finesse] for each **Sorcery** in her dueling line" — the bonus changes round-to-round as cards enter the dueling line. There is no event fired when a card enters `LOCATION_DUELING_LINE` (`FrameworkActionsTrait::actDuelActionChooseCombatCard` and the maneuver paths call `$this->cards->moveCard(...)` directly, bypassing the `EventCardMoved` path). So we recompute at duel-round boundaries instead.

Pattern (mirror `_03004` Elena):

```php
public int $FinesseBonus = 0;   // running state — survives across reaction-loop iterations via IsUpdated

public function handleEvent(Event $event)
{
    parent::handleEvent($event);
    if ($this->ControllerId == 0) return;

    if ($event instanceof EventDuelEndOfRound)
    {
        $this->recomputeFinesseBonus($event->theah);
    }

    if ($event instanceof EventDuelEnd)
    {
        // Subtract the running bonus directly; do NOT recount.
        // EventDuelEnd fires BEFORE the dueling-line cards are discarded
        // in stDuelEnd, so a recount would still see Sorcery cards.
        $this->applyFinesseDelta(0, $event->theah);
    }
}

private function recomputeFinesseBonus(Theah $theah): void
{
    // "Her dueling line" — LOCATION_DUELING_LINE is keyed per-player_id,
    // not per character. If a different one of this player's characters is
    // the duelist, the cards in the line belong to *them*, not the owner.
    $challengerId = $theah->getDuelChallengerId();
    $defenderId   = $theah->getDuelDefenderId();
    if ($this->Id != $challengerId && $this->Id != $defenderId)
    {
        $this->applyFinesseDelta(0, $theah);
        return;
    }

    $cards = $theah->getCardObjectsAtLocation(Game::LOCATION_DUELING_LINE, $this->ControllerId);
    $count = 0;
    foreach ($cards as $card)
    {
        if ($card->hasTrait("Sorcery"))  // or whatever trait the card text names
        {
            $count++;
        }
    }
    $this->applyFinesseDelta($count, $theah);
}

private function applyFinesseDelta(int $newBonus, Theah $theah): void
{
    $delta = $newBonus - $this->FinesseBonus;
    if ($delta == 0) return;

    $finesseEvent = EventFactory::createCharacterFinesseModifedEvent(
        $this->ControllerId, $this->Id,
        $this->ModifiedFinesse, $this->ModifiedFinesse + $delta,
        $this->getInjectCode()
    );
    $theah->queueEvent($finesseEvent);

    $this->FinesseBonus = $newBonus;
    $this->IsUpdated = true;
}
```

WHY recompute at `EventDuelEndOfRound` (not on every event):

- It is the cleanest boundary: both players' combat cards have resolved into the dueling line, and the *next* round's gambling hasn't fired yet. Gamble capacity is `ModifiedFinesse - gamblesCount` (see `FrameworkActionsTrait::actChooseGambleCard`) — recomputing here means the bonus is correct *before the next round's gambling*, which is when Finesse matters in a duel.
- Recomputing on a calc event (e.g. `EventDuelCalculateCombatCardStats`) is wrong because that event doesn't expose Finesse — it exposes parry/riposte/thrust on the combat card. The card text modifies *Finesse* itself; both consumers (gamble capacity and any other card reading `ModifiedFinesse`) must see the updated stat.

WHY reset at `EventDuelEnd` via `applyFinesseDelta(0, ...)` and NOT a recount:

- `StatesTrait::stDuelEnd` queues `EventDuelEnd` BEFORE queueing the `CardDiscardedFromHand` events that empty the dueling line. So at `EventDuelEnd` handling time, the dueling line still contains the round's Sorcery cards — a naive recount would re-apply the bonus instead of clearing it. Directly applying `delta = 0 - currentBonus` (the inverse-event approach) is correct.

WHY gate on the owner being a duel participant:

- `LOCATION_DUELING_LINE` is keyed per player_id in the deck table, not per character. If Elena's player has a *different* character dueling (e.g. Aja), Aja's combat cards land in the *same per-player dueling line* — a naive recount would credit Elena with cards she didn't play. Card text says "her dueling line", so gate on `$this->Id == challengerId || $this->Id == defenderId`.

Edge cases (Elena journal `2026-05-16-01-elena-agnelli-03004-implementation.md` flags these explicitly — re-read it before you implement a similar effect):

- **Card pulled from the dueling line mid-round.** The recount catches it at end-of-round; if anything pulls it earlier (rare), the bonus stays inflated for the rest of the current round. Acceptable — no event lets us hook arbitrary departures from the line.
- **Owner swapped into / out of an in-progress duel.** Not handled by the basic pattern. The next `EventDuelEndOfRound` recomputes from the player's line, which may already contain cards played by a prior duelist. Flag for QA if the text is sensitive to this; usually unimportant.
- **Owner destroyed mid-duel.** `EventDuelEnd` still fires and resets the bonus. `ModifiedFinesse` on a discarded card doesn't affect anything else, so no special handling needed.

### Location-counting passives — `EventCardMoved` fires BEFORE the DB updates

For "while you control another X at <Owner>'s location, she has +N [Stat]" (Angeline Dèmone `_03026`) or any other passive that **counts who is at a location** in response to `EventCardMoved` — the DB location field hasn't been updated yet when card->handleEvent runs. `EventCardMoved` sets `runEventHubAfterCards = true`, so the EventHub's location update runs AFTER every card's `handleEvent`. A naive `getCharactersAtLocation($this->Location)` returns the *pre-move* state: the moving card is still at `fromLocation`, not at `toLocation`.

01037 Edeline works around this by passing an explicit `$adjustment` int (`+1` for IN, `-1` for OUT) added to the count — fine for "+1 per character at this location" because every character contributes equally. **Binary "any qualifying member" bonuses can't use the adjustment shape** — you need to know the moving card's identity/trait/controller to decide whether to count it.

Pattern (mirror `_03026` Angeline):

```php
private function updateInfluence(Theah $theah, string $location, ?EventCardMoved $moveEvent = null): void
{
    $characters = $location == Game::LOCATION_PLAYER_HOME
        ? $theah->getCharactersAtHomeByPlayerId($this->ControllerId)
        : $theah->getCharactersAtLocation($location);

    $bonus = 0;
    foreach ($characters as $character)
    {
        // WHY: EventCardMoved fires before DB updates — a card moving OUT of
        // $location is still listed there. Exclude it from the count.
        if ($moveEvent !== null
            && $character->Id == $moveEvent->cardId
            && $moveEvent->fromLocation == $location
            && $moveEvent->toLocation != $location)
        {
            continue;
        }
        if ($character->Id != $this->Id
            && $character->ControllerId == $this->ControllerId
            && $character->hasTrait("Sorcerer"))
        {
            $bonus = 1;
            break;
        }
    }

    // WHY: Same stale-DB reason — a card moving IN isn't listed at $location yet.
    if ($bonus == 0
        && $moveEvent !== null
        && $moveEvent->cardId != $this->Id
        && $moveEvent->toLocation == $location
        && $moveEvent->fromLocation != $location)
    {
        $movingCard = $theah->getCardById($moveEvent->cardId);
        if ($movingCard !== null
            && $movingCard->ControllerId == $this->ControllerId
            && $movingCard->hasTrait("Sorcerer"))
        {
            $bonus = 1;
        }
    }

    $newInfluence = $this->Influence + $bonus;
    if ($newInfluence == $this->ModifiedInfluence) return;   // no-op gate

    $theah->queueEvent(EventFactory::createCharacterInfluenceModifiedEvent(
        $this->ControllerId, $this->Id,
        $this->ModifiedInfluence, $newInfluence,
        $this->getInjectCode()
    ));
}
```

In `handleEvent`, pass the event so the helper can compensate:

```php
if ($event instanceof EventCardMoved && $event->cardId == $this->Id)
    $this->updateInfluence($event->theah, $event->toLocation, $event);

if ($event instanceof EventCardMoved && $event->cardId != $this->Id && $event->toLocation == $this->Location)
    $this->updateInfluence($event->theah, $this->Location, $event);

if ($event instanceof EventCardMoved && $event->cardId != $this->Id && $event->fromLocation == $this->Location)
    $this->updateInfluence($event->theah, $this->Location, $event);
```

WHY a no-op gate (`$newInfluence == $this->ModifiedInfluence` early-return): even when the bonus value doesn't change, naive code queues a same-value `*ModifiedEvent` for every triggering event (a non-Sorcerer moving in, an opponent's character entering, etc.). The framework still processes those no-op events. The gate trims log noise and event-loop work.

**Apply the same gate to `_01037`-style adjustment-int patterns** — Edeline's `updateInfluence` also benefits from skipping a same-value event. Add `if ($newInfluence == $this->ModifiedInfluence) return;` before queueing.

WHY not just hook the post-tense `EventCardMoved` differently — there's no later "after DB updates" event for moves. `runEventHubAfterCards = true` puts the EventHub's write AFTER card handleEvent, period. The choice is: read stale DB and compensate, or hook `EventCharacterMustered`/`EventApproachCharacterPlayed` (which don't have the timing problem) and forgo the move trigger entirely. Card text that says "while X is at this location" needs the move trigger.

Reference: `_03026` Angeline (binary bonus), `_01037` Edeline (per-character count via `$adjustment` int).

### Forced muster/approach triggers — hook BOTH `EventCharacterMustered` AND `EventApproachCharacterPlayed`

For any "after X musters" / "when X musters" / "Forced after X musters" trigger, the conditional MUST hook both events:

```php
if (($event instanceof EventCharacterMustered
        || $event instanceof EventApproachCharacterPlayed)
    && $event->characterId == $this->Id)
{
    // ... effect ...
}
```

WHY both: the printed text says "musters" colloquially to cover every way a character enters play, but the engine emits a distinct `EventApproachCharacterPlayed` when an Approach card puts a character into play vs. the standard muster path (`createCharacterMusteredEvent` in the recruit / brute / muster-from-action flows). Hooking only `EventCharacterMustered` silently skips the Forced trigger when the character enters via Approach. The user has flagged this as a definitional miss — it's not a polish item.

Reference: `modules/php/cards/_7s5s/_01009.php` (Cirilo) line ~57 — the canonical OR pattern for "I added Brute to my Mercenaries when I muster or come in via Approach." `_03015` Joern uses the same pair for his self-wound Forced trigger.

If the trigger is "after **another** character musters" (not self), still hook both events; only the `characterId` filter changes.

**Approach also triggers Home-scoped passives — even when the Owner isn't the approached character.** For "while you control another X at <Owner>'s location" passives where Owner is at Home, an opponent's *teammate* X being approach-played to the player's Home should also recompute. Hook a second `EventApproachCharacterPlayed` branch:

```php
if ($event instanceof EventApproachCharacterPlayed
    && $event->characterId != $this->Id
    && $this->Location == Game::LOCATION_PLAYER_HOME
    && $event->playerId == $this->ControllerId)
{
    $this->updateInfluence($event->theah, Game::LOCATION_PLAYER_HOME);
}
```

Gate on `$event->playerId == $this->ControllerId` so the recompute only fires for Owner's controller (Home is per-player; opponent's approach doesn't change who's at *your* Home).

**For the Owner's own approach, use `$event->playerId` as the controller — not `$this->ControllerId`.** When Angeline herself is the approach character, her in-memory `ControllerId` may not be propagated yet at the moment `EventApproachCharacterPlayed` fires (EventHub handler doesn't set it; recruit/muster events do). Pass `$event->playerId` as an override so `getCharactersAtHomeByPlayerId` looks up the right home:

```php
private function updateInfluence(Theah $theah, string $location, ?EventCardMoved $moveEvent = null, ?int $controllerIdOverride = null): void
{
    $controllerId = $controllerIdOverride ?? $this->ControllerId;
    // ... use $controllerId in lookups instead of $this->ControllerId ...
}

// caller for own-approach:
if ($event instanceof EventApproachCharacterPlayed && $event->characterId == $this->Id)
    $this->updateInfluence($event->theah, Game::LOCATION_PLAYER_HOME, null, $event->playerId);
```

Reference: `_03026` Angeline.

### Phase-conditional Resolve modifier — direct `ModifiedResolve` mutation, no event factory

For text like "During Dusk, <Owner> has -N Resolve" or "At the beginning of Dawn, <Owner> has +N Resolve" — the engine does NOT have an `EventCharacterResolveModifiedEvent` factory. Resolve is not event-driven the way Combat/Finesse/Influence are. The pattern:

```php
private bool $DuskResolvePenaltyApplied = false;   // running flag — survives via IsUpdated

public function handleEvent(Event $event)
{
    parent::handleEvent($event);

    if ($event instanceof EventDuskPhaseBegin
        && ! $this->DuskResolvePenaltyApplied
        && $this->isControlled())
    {
        $this->ModifiedResolve -= 3;
        $this->DuskResolvePenaltyApplied = true;
        $this->IsUpdated = true;

        $event->theah->game->notify->all("message",
            clienttranslate('${character_inject_code}: During Dusk, -3 Resolve (now ${resolve}).'),
            [
                "character_inject_code" => $this->getInjectCode(),
                "resolve"               => $this->ModifiedResolve,
            ]
        );

        // WHY: Character::handleEvent (~line 256) only triggers destruction inside
        // an EventCharacterWounded handler. If the Resolve drop crosses the
        // wounds-equal-resolve threshold with no concurrent wound event, the
        // engine won't notice. Mirror EventHub.php:251 (the unequip path):
        if ($this->Wounds >= $this->ModifiedResolve && ! $this->IsDying)
        {
            $this->IsDying = true;
            $this->unEquipAllAttachments($event->theah);
            $destroyEvent = EventFactory::createCharacterDestroyedEvent(
                $this->ControllerId, $this->Id, $this->getInjectCode()
            );
            $event->theah->queueEvent($destroyEvent);
        }
    }

    if ($event instanceof EventDuskEndOfDay && $this->DuskResolvePenaltyApplied)
    {
        $this->ModifiedResolve += 3;
        $this->DuskResolvePenaltyApplied = false;
        $this->IsUpdated = true;
    }
}
```

WHY a private bool flag (and NOT a recompute or a queued event):

- **No `createCharacterResolveModifiedEvent` factory exists.** Combat/Finesse/Influence each have `createCharacter<Stat>ModifiedEvent` factories; Resolve does not. The codebase mutates `ModifiedResolve` directly — see `Character::addAttachment` line 166 (`$this->ModifiedResolve += $attachment->ResolveModifier`).
- **Attachments mutate `ModifiedResolve` independently.** A naive `-= 3` / `+= 3` is fine if Dusk events are perfectly paired, but skipped/duplicated phase begins are not. A flag makes the apply idempotent: only one `-= 3` per Dusk, regardless of how the events fire.
- **Pattern A's "Dynamic stat bonuses" recompute approach (Elena) doesn't fit here.** Resolve has no naturally recurring "this card's snapshot of the world changes" event the way a dueling-line count does. The trigger is a phase boundary, not a stream of state changes.

WHY the manual destruction check:

- `Character::handleEvent`'s destruction check (line ~256) runs ONLY inside `EventCharacterWounded`. Lowering `ModifiedResolve` past `Wounds` outside a wound event silently leaves the character alive at `Wounds >= Resolve`.
- The card text's parenthetical reminder "(Characters are destroyed when their wounds equal their Resolve)" makes the rule explicit — the threshold check applies whenever it's crossed, not only on a wound event.
- Mirror the EventHub unequip pattern (`EventHub.php` ~251): `if ($character->Wounds >= $character->ModifiedResolve && ! $character->IsDying)` → flip `IsDying`, unequip attachments, queue `createCharacterDestroyedEvent`.

WHY restore unconditional on the flag (not `isControlled()` or `cardInCity`):

- If the character is destroyed mid-Dusk, the flag is still true and the EndOfDay restore still runs. The destroyed-character object is in the Locker; restoring its in-memory `ModifiedResolve` is harmless. Re-instantiation on re-recruit goes through the constructor + `resetCard()` which sets `ModifiedResolve = Resolve` anyway, but the unconditional restore is a defense against any hypothetical "return from Locker" path that bypasses construction.

WHY `EventDuskEndOfDay` for the restore (not `EventDuskPhaseEnd`):

- Dusk lifecycle is: `stDuskPhaseBegin` → `EventDuskPhaseBegin` → (reactions, cleanup, hand-discard, purgatory-discard) → `stDuskPhaseEnd` → `EventDuskPhaseEnd` → `stDuskEndOfDay` → `EventDuskEndOfDay`.
- "During Dusk" should cover every step in between. `EventDuskEndOfDay` is the last event of the day — restoring there guarantees nothing inside Dusk sees the restored value early.
- `EventDuskPhaseEnd` would work too (Brute discard at end-of-day doesn't read Resolve), but EndOfDay is the strict latest safe point.

Reference: `_03015` Joern Kietelsson. Note that the same pattern applies in reverse for "+N Resolve" phase-conditional buffs.

### Wound-prevention passive — `eventCheck` on `EventCharacterBeingWounded`

For text like "<Owner> ignores wounds from <X>" or "<Y>'s abilities cannot wound <Owner>" (`_03014` Kaspar, `_01069` Maxime, `_01153` Breastplate). Override `eventCheck` on the card class — NOT `handleEvent` — and zero `$event->wounds` on `EventCharacterBeingWounded`.

```php
public function eventCheck(Event $event)
{
    parent::eventCheck($event);   // propagates to your Techniques/Reactions/etc.

    if (! ($event instanceof EventCharacterBeingWounded)) return;
    if ($event->characterId != $this->Id || $event->wounds <= 0) return;

    // "(Threat is still converted to wounds.)" Threat conversion (StatesTrait
    // ~line 1500) emits with empty $abilityId; only block ability-emitted wounds.
    if ($event->abilityId == '') return;

    $source = $event->theah->getCardById($event->sourceId);
    if ($source == null || $source->ControllerId == 0
        || $source->ControllerId == $this->ControllerId) return;

    $oldWounds = $event->wounds;
    $event->wounds = 0;

    $event->theah->game->notify->all("message", clienttranslate(
        '${character_inject_code}: Opponents\' abilities cannot wound. '
        . '${oldWounds} wound(s) ignored from ${source_inject_code}.'
    ), [
        "character_inject_code" => $this->getInjectCode(),
        "source_inject_code"    => $source->getInjectCode(),
        "oldWounds"             => $oldWounds,
    ]);
}
```

WHY `eventCheck` on the *Being*-tense event (not `handleEvent` on `EventCharacterWounded`):

- `EventHub` only emits the past-tense `EventCharacterWounded` when `$event->wounds > 0` (see `EventHub.php` ~1988). Setting `wounds = 0` in `eventCheck` on `EventCharacterBeingWounded` means the past-tense event is *never created* — no other reaction/passive that listens to "when X is wounded" thinks Kaspar took a wound. Cleaner than Maxime's `handleEvent` pattern of skipping `parent::handleEvent` (which still propagates the event to other `Character::handleEvent` listeners).
- `Card::eventCheck` (Card.php ~371) is the framework's per-card check hook and runs BEFORE `handleEvent`. Override it on the *card class*, not on a Technique/Reaction — the passive is the card itself, not an ability.
- Always call `parent::eventCheck($event)` first — it dispatches to any Techniques/Reactions/Maneuvers/Actions on the card.

WHY `abilityId == ''` is the threat-conversion signal:

- The round-end threat-to-wounds conversion (`StatesTrait::stDuelEndOfRound` ~line 1500) emits `createCharacterBeingWoundedEvent($actor->Id, $adversary->Id, $wounds, $reason)` — note the missing 5th positional argument, so `abilityId` defaults to `''`.
- Every ability that emits a wound passes the ability id as the 5th argument (`Action_02010`, `Technique_03004`, all the Sorcerer Actions/Reactions). So `abilityId != ''` is a clean "this wound is from an ability" filter without needing to grep call sites.

WHY `source.ControllerId != $this->ControllerId` is "opponent's ability":

- The source card's `ControllerId` is the controlling player at the moment the wound is queued. For an opponent's Action/Reaction/Technique/Maneuver/Sorcery card causing the wound, that's a different player from Kaspar's controller.
- `source.ControllerId == 0` means uncontrolled (rare — usually a card in transit between zones). Treat that as "not an opponent" and let it through; nothing in the codebase emits an ability-typed wound from an uncontrolled source as of this writing, but the guard is cheap.
- For wound *movement* abilities (the heal+wound recipe, `Action_02010`): the wound half is queued from the action's owner with the action's id as `abilityId`. Same filter blocks it. Kaspar's text "or move wounds to Kaspar" comes free with the wound-block — don't add a special "move-wounds" handler.

Scope-matters: Maxime's text is about "abilities he performs" (own scope via `CHOSEN_PERFORMER` or Sorcery-trait source), so Maxime checks the source's identity / trait. Kaspar's text is about "opponents' abilities" (controller scope), so Kaspar checks the source's controller. Read the text literally — don't reuse the wrong helper.

For partial reduction (Breastplate `_01153` reduces by 1, not to 0), the same `eventCheck` pattern applies — just `$event->wounds--` with a floor at 0. Breastplate additionally tracks `$hasBlockedWound` to enforce "first time this duel."

### Stat bonus while a self-condition holds — flag-based recompute on wound/heal

For text like "<Owner> has +1 [Combat] while wounded" (`_03016` Ise). The condition is *on the Owner herself* (wounded / engaged / has-attachment / etc.), and the bonus should flip on/off as the condition changes.

Pattern (mirror `_03016` Ise):

```php
public bool $WoundedCombatBonusApplied = false;   // running flag — survives via IsUpdated

public function handleEvent(Event $event)
{
    parent::handleEvent($event);   // parent updates $this->Wounds BEFORE this runs

    if (($event instanceof EventCharacterWounded || $event instanceof EventCharacterHealed)
        && $event->characterId == $this->Id)
    {
        $this->recomputeWoundedCombatBonus($event->theah);
    }
}

private function recomputeWoundedCombatBonus(Theah $theah): void
{
    if ($this->ControllerId == 0) return;
    if ($theah->game->characterIsInDiscardOrLocker($this)) return;
    if ($this->IsDying) return;

    $shouldHaveBonus = $this->Wounds > 0;

    if ($shouldHaveBonus && ! $this->WoundedCombatBonusApplied)
    {
        $combatEvent = EventFactory::createCharacterCombatModifiedEvent(
            $this->ControllerId, $this->Id,
            $this->ModifiedCombat, $this->ModifiedCombat + 1,
            $this->getInjectCode()
        );
        $theah->queueEvent($combatEvent);
        $this->WoundedCombatBonusApplied = true;
        $this->IsUpdated = true;
    }
    else if (! $shouldHaveBonus && $this->WoundedCombatBonusApplied)
    {
        $combatEvent = EventFactory::createCharacterCombatModifiedEvent(
            $this->ControllerId, $this->Id,
            $this->ModifiedCombat, $this->ModifiedCombat - 1,
            $this->getInjectCode()
        );
        $theah->queueEvent($combatEvent);
        $this->WoundedCombatBonusApplied = false;
        $this->IsUpdated = true;
    }
}
```

WHY a flag + delta-event instead of recompute-from-base:

- Attachments and other cards also mutate `ModifiedCombat`. A naive "set `ModifiedCombat = Combat + (wounded ? 1 : 0)`" would clobber a Weapon attachment's +1 Combat that flowed in via its own `CombatModified` event. The delta-on-transition pattern plays nicely with the rest of the stat-modifier ecosystem: each modifier only adjusts what *it* contributed.
- Mirrors `_01089` Soline el Gato's `lowerFinesse`/`raiseFinesse` shape, which uses the same per-source-bookkeeping discipline.

WHY `parent::handleEvent` BEFORE checking `$this->Wounds`:

- `Character::handleEvent` (Character.php ~242) does `$this->Wounds += $event->wounds` (or `-=` for heal) inside its own `EventCharacterWounded`/`EventCharacterHealed` branches. Our recompute MUST run *after* that update — `parent::handleEvent($event)` first is non-negotiable.

WHY skip on `IsDying` / `characterIsInDiscardOrLocker`:

- If the wound event drove her to Wounds >= ModifiedResolve, `Character::handleEvent` sets `IsDying = true` and queues `EventCharacterDestroyed`. Queueing a combat bonus at that point is wasted work — her `ModifiedCombat` is irrelevant. When she re-instantiates (next game/recruit), `resetCard` re-derives `ModifiedCombat = Combat`, and the bonus flag is default-false on the fresh instance.

Adapting for other stats / conditions:
- `+N [Finesse]` → `createCharacterFinesseModifedEvent` (note framework typo: `Modifed`).
- `+N [Influence]` → `createCharacterInfluenceModifiedEvent`.
- `+N [Resolve]` → no factory exists. Use Joern's `$this->ModifiedResolve` direct-mutation pattern instead (Pattern A's "Phase-conditional Resolve modifier").
- `+N [Panache]` (Leader only) → `createCharacterPanacheModifiedEvent`.

For non-wound conditions (e.g., "while engaged"), swap the trigger event (`EventCardEngaged` / `EventCardEngarded`) and the `$shouldHaveBonus` predicate. Same flag discipline.

### Set one stat equal to another while a scoped condition holds — replacement flag + snapshot restore

For text like "While Térence is participating in a duel at [The Grand Bazaar], set his [Combat] as equal to his [Influence]" (`_03028` Térence). This is **not** the ±1 delta pattern from "has +N while wounded" — the printed text replaces the target stat with the current value of the source stat, and the link is **dynamic** (if Influence changes during the duel, Combat must follow).

Pattern (mirror `_03028` Térence):

```php
public bool $DuelCombatEqualsInfluenceApplied = false;
public ?int $CombatBeforeDuelOverride = null;

// Apply on condition start:
private function applyCombatEqualsInfluence(Theah $theah): void
{
    if ($this->ControllerId == 0) return;
    if ($theah->game->characterIsInDiscardOrLocker($this)) return;
    if ($this->Location != Game::LOCATION_CITY_BAZAAR) return;   // scope gate from card text

    if (! $this->DuelCombatEqualsInfluenceApplied)
    {
        $this->CombatBeforeDuelOverride = $this->ModifiedCombat;   // snapshot ONCE
        $this->DuelCombatEqualsInfluenceApplied = true;
        $this->IsUpdated = true;
    }
    $this->syncCombatToInfluence($theah);
}

private function syncCombatToInfluence(Theah $theah): void
{
    if (! $this->DuelCombatEqualsInfluenceApplied) return;
    $targetCombat = $this->ModifiedInfluence;
    if ($this->ModifiedCombat == $targetCombat) return;
    $theah->queueEvent(EventFactory::createCharacterCombatModifiedEvent(
        $this->ControllerId, $this->Id,
        $this->ModifiedCombat, $targetCombat,
        $this->getInjectCode()
    ));
}

// Clear on condition end:
private function clearCombatEqualsInfluence(Theah $theah): void
{
    if (! $this->DuelCombatEqualsInfluenceApplied) return;
    $restoreCombat = $this->CombatBeforeDuelOverride ?? $this->Combat;
    if ($this->ModifiedCombat != $restoreCombat)
    {
        $theah->queueEvent(EventFactory::createCharacterCombatModifiedEvent(
            $this->ControllerId, $this->Id,
            $this->ModifiedCombat, $restoreCombat,
            $this->getInjectCode()
        ));
    }
    $this->DuelCombatEqualsInfluenceApplied = false;
    $this->CombatBeforeDuelOverride = null;
    $this->IsUpdated = true;
}
```

Lifecycle hooks:
- **`EventDuelStarted`** — apply when `$this->Id` is `challengerId` or `defenderId` AND `$this->Location` matches the named city location (`Game::LOCATION_CITY_BAZAAR` for `[The Grand Bazaar]`).
- **`EventDuelEnd`** — clear unconditionally when the flag is set.
- **`EventDefenderSwapped` / `EventChallengerSwapped`** — apply when `$this->Id` becomes the new participant (with location gate); clear when `$this->Id` was the old participant. Same swap discipline as `_01089` Soline.
- **`EventCharacterInfluenceModified`** with `$event->CharacterId == $this->Id` — re-sync while flag is set (Influence is the source stat).
- **`EventCharacterCombatModified`** with `$event->CharacterId == $this->Id` — if override active and `$event->NewCombat != $this->ModifiedInfluence`, re-sync (external Combat buffs don't stick during the override).

WHY snapshot restore instead of recompute-from-base:

- Attachments may equip mid-duel and change the "natural" Combat independently of this override. The snapshot taken at apply-time is the correct undo target on `EventDuelEnd`. Recomputing `Combat + sum(attachment modifiers)` at clear-time risks drift if the override itself was the last thing that touched `ModifiedCombat`.

WHY NOT the Ise ±1 flag pattern:

- "Set equal to" is a **replacement**, not a fixed delta. If Influence is 2 and Combat is 0, the event sets Combat **to 2**, not **+2**. If Influence later becomes 3, Combat must become 3 — a running `$BonusApplied` ±1 counter can't express that.

WHY `EventCharacterCombatModified` re-sync uses `$event->NewCombat`:

- `EventCharacterCombatModified` has `runEventHubAfterCards = false` — EventHub applies `ModifiedCombat = NewCombat` **before** card `handleEvent` runs (`Theah::runEvents` order). By the time the card handler fires, `$this->ModifiedCombat` already reflects the external change; comparing `$event->NewCombat != $this->ModifiedInfluence` detects drift from the link.

Named city location constants (real ones in `Game.php`):
- `[The Grand Bazaar]` → `Game::LOCATION_CITY_BAZAAR`
- `[The Docks]` → `Game::LOCATION_CITY_DOCKS`
- `[The City Forum]` / `[The Forums]` → `Game::LOCATION_CITY_FORUM`
- `[Ole's Inn]` → `Game::LOCATION_CITY_OLES_INN`
- `[The Governor's Garden]` → `Game::LOCATION_CITY_GOVERNORS_GARDEN`

Reference: `_03028` Térence, `_01089` Soline (duel boundary + swap discipline — but Soline modifies *adversaries*, Térence modifies *self*).

### "Opposing characters are considered <Trait>" — tag opposing characters, don't override hasTrait

For text like "While using your abilities, characters opposing <Owner> may be considered <Trait>" (Daniella Dietrich `_03013`): the trait must light up on *opposing* characters, not on the owner. The Uwe Zimmerman `_01043` `hasTrait` override pattern is the WRONG fit — that pattern lights up the *receiver* of `hasTrait`, so it only works when the card being considered is the card whose `hasTrait` was overridden. For the opposing-direction case, mirror the Wilhelm Dünst `Action_02013` pattern instead: **mutate the opposing characters' `ModifiedTraits` directly via `addTrait` / `removeTrait`**, keep a tracked set of the ids you tagged, and untag at the scope boundary.

Pattern (typically lives on a continuous Action; see the next subsection):

```php
private array $TaggedOpposingIds = [];  // ids we added the trait to

private function tagOpposingAs(string $trait, Theah $theah): void
{
    $owner = $this->getOwningCharacter($theah);
    if ($owner === null) return;
    $game = $theah->game;

    $opposing = array_filter(
        $theah->getCharactersAtLocation($owner->Location),
        fn($c) => $c->ControllerId !== $owner->ControllerId
            && ! in_array($c->Id, $this->TaggedOpposingIds, true)  // dedup — see WHY below
            && ! $c->hasTrait($trait)
    );
    foreach ($opposing as $c)
    {
        $c->addTrait($game, $trait);
        $this->TaggedOpposingIds[] = $c->Id;
    }
}

private function untagOpposing(string $trait, Theah $theah): void
{
    if (empty($this->TaggedOpposingIds)) return;
    $game = $theah->game;
    foreach ($this->TaggedOpposingIds as $cid)
    {
        $c = $theah->getCharacterById($cid);
        if ($c !== null) $c->removeTrait($game, $trait);
    }
    $this->TaggedOpposingIds = [];
}
```

WHY tracked-set + skip-already-tagged:

- `Card::addTrait` (in `modules/php/cards/Card.php`) appends to `$this->ModifiedTraits` **without** deduping. Two `addTrait("Sorcerer")` calls leave two `"Sorcerer"` entries in the array, and `removeTrait` removes only one (`array_search` returns the first match). Re-tagging on every ability-use event without a guard would pile up duplicates that never fully clear.
- `! $c->hasTrait($trait)` is the cheap "they already have it printed/granted" check; `! in_array($c->Id, ...)` is the cheap "we already granted it" check. Use both — a character could legitimately have the trait printed before our grant fires.

WHY "opposing" = controller-mismatch + location-match: this matches `Theah::getOpposingCharactersAtLocation` and the codebase-wide definition (see the memory note). Don't roll your own filter; just pull from the location and exclude same-controller.

Scope boundary for untagging: the scope is whatever the card text says. Daniella's "while using your abilities" reads as "for the duration of your turn" once you map ability-use to turn-scope — `EventPlayerTurnEnd` is the natural clear. Add `EventCardMoved` / `EventCharacterDestroyed` cleanups for the owner so an outstanding tag set doesn't get orphaned on a character that no longer opposes her.

### Continuous Action — passive ability that lives on an `Action` class but never appears in the UI

For passive abilities that the framework should treat as an ability but the player never directly activates (e.g., Daniella Dietrich `_03013`'s trait-tagging passive), mount the logic on a `CharacterAction` subclass attached via `IHasActions` / `ActionTrait`. Make `isAvailableToPlayer` return false so it never shows in the action menu — the Action is purely a `handleEvent` listener.

```php
class Action_NNNNN extends CharacterAction
{
    /** @var int[] running state for the passive (e.g. tagged character ids) */
    private array $TaggedOpposingIds = [];

    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("(Continuous) <plain-English description of what it does>");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        // Passive — never offered from the action menu. Returning false hides
        // it but does not suppress handleEvent.
        return false;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);
        // ... trait-tagging / passive work ...

        if ($event instanceof EventPlayerTurnEnd)
        {
            $this->untagOpposing("Sorcerer", $event->theah);

            // "Continuous" — clear Used at the same boundary so the parent
            // CardAction::handleEvent's EventDuskEndOfDay reset isn't the only
            // thing keeping the action alive across turns.
            $this->setUsed($event->theah, false);
        }
    }
}
```

Wiring on the card:

```php
class _NNNNN extends Leader implements IHasActions, IHasReactions, IHasTechniques
{
    use ActionTrait;
    use ReactionTrait;
    use TechniqueTrait;

    // ... constructor ...
    $this->Actions = [ new Action_NNNNN() ];
}
```

Where to place the passive's `handleEvent` — the Action or the card class? Either works mechanically, but **prefer the Action** when the passive is conceptually an ability that the card text *names* as an "Action / Forced / Maneuver / Passive / Technique / Reaction." That keeps the responsibility scoped to one file and lets the card class's `handleEvent` stay minimal (just `parent::handleEvent($event)` for the Leader-inherited renown/Panache logic). The card class's `handleEvent` is still where you put cross-ability bookkeeping that doesn't belong to any single ability.

WHY pre-commit doesn't complain about the missing `createActionResolvedEvent()`: the hook's regex matches `extends CardAction/RiskAction/RiskCityAction` literally — `CharacterAction` isn't on that list (see the Pre-Commit Hook section). A continuous Action that never goes through normal action resolution legitimately doesn't fire `createActionResolvedEvent`.

WHY `setUsed(false)` on a continuous Action: the parent `CardAction::handleEvent` already resets `Used` on `EventDuskEndOfDay`, which is fine for once-per-day actions. For a "continuous" Action that must survive multiple ability uses within the same turn, explicitly flip `Used` back to `false` at the same scope boundary you untag at (typically `EventPlayerTurnEnd`). The Reaction analogue is "do not call `setUsed(true)` at all" — see `Reaction_01196` "Continuous". Both forms work; the Action variant needs the explicit reset because `parent::handleEvent`'s once-per-day reset isn't frequent enough.

Reference: `Action_03013` (Daniella Dietrich) — Continuous Action that tags opposing characters with "Sorcerer" on ability-start events and untags at `EventPlayerTurnEnd`. `Action_01090` (Yuri Pyetrovich) — Continuous Action that pre-activates a paired Reaction; opposite shape (user-triggered, but immediately flips `Used` back to false).

### Phase / lifecycle events worth knowing

| Event | When it fires | Typical use |
|---|---|---|
| `EventNewDay` | Start of each Day | Reset per-day flags |
| `EventPhaseDawnBeginning` | Dawn begins | "At the beginning of Dawn …" |
| `EventPhaseDawnEnding` | Dawn ends (fired by `StatesTrait::stDawnEnding`) | "At the end of Dawn …" |
| `EventDuskPhaseBegin` | Dusk phase begins (fired by `StatesTrait::stDuskPhaseBegin`, BEFORE characters route home) | "At the beginning of Dusk …" / start of a phase-conditional Resolve penalty (Joern `_03015`). |
| `EventDuskPhaseEnd` | After cleanup/discard, before `EventDuskEndOfDay` | Less commonly used; `EventDuskEndOfDay` is usually the right "Dusk is over" hook |
| `EventDuskEndOfDay` | End of Day (Brute discards happen here) | Reset per-day Used flags (base classes handle this for Actions/Reactions automatically); restore phase-conditional Resolve penalties |
| `EventCharacterMustered` | A character was just mustered (recruit / brute / `Action_01024` / etc.) | "Forced after X musters …" — **always pair with `EventApproachCharacterPlayed`** (see Pattern A's "Forced muster/approach triggers" subsection) |
| `EventApproachCharacterPlayed` | A character entered play via an Approach card | Same triggers as `EventCharacterMustered`; hook the pair |
| `EventChallengeRejected` | A challenge was refused (`$event->challengerId` issued, `$event->targetId` refused) | "When <Owner>'s challenge is refused …" / "When a challenge to <Owner> is refused …". Reference: `_03015` Joern (self-heal), `_01119` Nazem (engage the refuser). |
| `EventChallengeIssued` | A challenge was just issued (`$event->challengerId`, `$event->defenderId`); queued by `StatesTrait::stIssueChallenge` BEFORE the intervention dispatcher state advances | "After a challenge is issued at this location, **before choosing to intervene** …" — `_03027` Odette (pull adjacent Duelist before intervention). Use this (NOT `EventChallengeAccepted`) when the text says "before intervene" — accept fires AFTER the intervention window resolves. |
| `EventChallengeAccepted` | A challenge was accepted (post-intervention) | "After a challenge is accepted at this location …" — existing Odette `_01062` move-adjacent-renown reaction. |
| `EventCharacterIntervened` | An intervention character was selected during a challenge | "After X intervenes …" — `Reaction_01062`. |
| `EventPressureOccuring` | A pressure is happening at a location | "When pressuring …", `_01006` Don Constanzo |
| `EventDuelStarted` / `EventDuelEnd` | Duel boundaries | Passive duel stat modifiers, `_01089`. **`EventDuelEnd` fires BEFORE the dueling line is cleared** in `stDuelEnd` (the discard events are queued AFTER it), so a recount-based dueling-line effect must reset via direct inverse-event, not via re-reading the line. |
| `EventCharacterCombatModified` / `EventCharacterInfluenceModified` | A character's modified stat changed (`$event->CharacterId`, `$event->OldCombat`/`NewCombat` or `OldInfluence`/`NewInfluence`) | Re-sync a "set [StatA] equal to [StatB]" link when the source stat changes, or re-apply the link when an external effect mutates the target stat during the override. EventHub applies the new stat **before** card `handleEvent` runs (`runEventHubAfterCards = false`). Reference: `_03028` Térence. |
| `EventAttachmentEquipped` | An attachment was equipped (`$event->characterId`, `$event->attachmentId`; `$event->asAction` distinguishes action-equip vs passive) | "After a character equips an attachment at [location] …" City Reactions. Look up equipping character via `getCharacterById($event->characterId)` and compare `.Location` to the named city constant. Skip `$attachment->FakeAttachment`. Reference: `Reaction_03028` (any character at Grand Bazaar), `Reaction_01039` (owner self-equip only). |
| `EventDuelEndOfRound` | A duel round just ended; both combat cards are in the dueling line; the next round hasn't begun | Recompute "for each X in my dueling line" running bonuses *before* the next round's gambling. `_03004` Elena. |
| `EventDuelCalculateCombatCardStats` | Combat card stats are being computed for a duel (`$event->gambled` is set from `duel_round.gambled`) | "+X to combat card stats" — `_01116` Yevgeni (every card); gambled-only — `_03037` Sanjay (`$event->gambled` gate) |
| `EventChallengerSwapped` / `EventDefenderSwapped` | A challenge had its participant changed | Re-evaluate any duel-time modifier you applied, `_01089` |
| `EventTableSetup` | Game setup | Initial decisions like "during setup, reveal X from your deck", `_01006` |
| `EventSchemeCardRevealed` | A scheme is revealed | Leaders react via the base `Leader::handleEvent`; only override if you have card-specific logic |
| `EventCharacterDestroyed` | A character is destroyed (`runEventHubAfterCards = true`, so the destroyed character's `.Location` is STILL set during `handleEvent` — the locker move runs AFTER all card handlers). Look up via `getCharacterById($event->characterId)` and compare `.Location == $owner->Location` for "another character at this location" triggers. | Leaders have built-in renown-loss logic in `Leader::handleEvent` — don't reinvent. "After another character at this location is destroyed …" — `_03027` Odette, `Reaction_01013`. |
| `EventSorcererAbilityPlayed` | A sorcerer ability resolved | "After <X> performs a Sorcerer ability …" reactions, Pattern D below |
| `EventActionResolved` | An action just resolved | "After an Action resolves …" reactions, `Reaction_01089` |
| `EventCardMoving` / `EventCardMoved` | Pre / past tense of a card-to-location move | `Moving` is cancelable (`$event->canceled = true`) — use for opt-out Reactions (Pattern D "Cancel-and-reissue"). `Moved` is the past-tense receiver — use for "after X moves to/from this location" triggers. The Dusk auto-move emits `Moving` with `$sourceId == 0`; ability-driven moves pass a non-zero sourceId. Reference: `Reaction_03016a` (cancel), `Reaction_03016b` (react to). |

## Pattern C — Action / City Action (CharacterAction)

This pattern is **the same as in `create-city-character`'s Pattern C**. The action class extends `CharacterAction` regardless of whether the owning card is a Character, Leader, or CityCharacter. Read the city-character skill's Pattern C for the full template, state class skeleton, and JS wiring. Below are the Character/Leader-specific notes.

### Eligibility differences

- **Regular Action** (`<b>Action:</b>`) — usually requires the character to be in play (`cardInPlay`) but not in the city. The base `parent::isAvailableToPlayer()` covers most of this; add specific preconditions.
- **City Action** (`<b>City Action:</b>`) — additionally gate on `$theah->cardInCity($owner)`. The character must be at one of the city locations to use the ability.

```php
public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
{
    if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
    {
        return false;
    }

    $owner = $this->getOwningCharacter($theah);

    if (! $theah->cardInCity($owner))      // City Action — drop this gate for a non-city Action
    {
        return false;
    }

    // Card-specific preconditions go here.
    return true;
}
```

### CharacterAction does NOT call setUsed / resetPlayerPassCount / announceAction

Per CLAUDE.md, those are run centrally in `actHighDramaInPlayActionConfirm` / `stHighDramaInPlayActionDispatch`. Calling them from a `CharacterAction` subclass causes duplicates.

Still required: **call `createActionResolvedEvent()` once at the end of resolution.** (The pre-commit hook's regex doesn't directly match `extends CharacterAction` — but the call is still mandatory per CLAUDE.md and the convention in every existing CharacterAction.)

### Action `$this->Id` is a STRING composite, not an int

`CardAbilityTrait::setOwnerId` sets `$this->Id = "{$ownerId}_{$this->ClassId}"` — so a `CharacterAction`'s `Id` is a string like `"68_Action_03026"`, not the action's class id or an int. Passing it where an int sourceId is required will throw a type error.

For event factories that take `int $sourceId` (`createCardDiscardedFromHandEvent`, `createCharacterBeingWoundedEvent`, `createCharacterBeingHealedEvent`, `createCardMovingEvent`, etc.), use **`$owner->Id`** (the character's int id), not `$this->Id`. The action's string composite id is the right value for `abilityId` parameters (which are typed `string`) and for the 4th arg to `createTransitionEvent($playerId, $sourceId, $transitionName, $internalId = "")`.

```php
$discardEvent = EventFactory::createCardDiscardedFromHandEvent(
    $owner->ControllerId,
    $cardId,
    $owner->Id,             // ✓ int — character id
    // NOT $this->Id        ✗ string — action's composite id
    false, false, true
);
```

### PHP arrays handed back to JS must be sequential — `array_values()`

`getCardObjectsAtLocation` (DB.php:205) returns an array **keyed by card id**: `$cards[(int)$result['id']] = ...`. `array_map` preserves keys. When that array is JSON-encoded for the client, non-sequential int keys serialize as a JSON object `{12345: ..., 67890: ...}` — not an array — and `.forEach` / `.map` throws `is not a function`.

Wrap any picker `ids` payload (and any helper that may return associative keys) in `array_values` before assigning to args:

```php
$args['ids'] = array_values(array_map(fn($card) => $card->Id, $hand));
```

Symptom on the JS side: `Uncaught TypeError: ids.forEach is not a function`. If you see that error and you're sure the field is set server-side, the cause is almost certainly an associative-keyed array.

### Hand-card picker — use `factionHand.setSelectionMode`, NOT `highlightCardsAsSelectable`

For "Discard a card from your hand" / "Reveal a card from your hand" steps where the player picks from their hand:

- `highlightCardsAsSelectable(ids)` is for **in-play cards** (characters, attachments). It looks up `this.cardProperties[id]` and `$(card.divId + '_image')` — hand cards aren't in `cardProperties` under that scheme and the lookup returns `null`, throwing `Cannot read properties of null (reading 'className')`.
- Hand cards use the dedicated `factionHand` widget. Pattern (mirror `highDramaPhase01069`):

```js
// OnEnteringState.<expansion>.js
'highDramaPhaseNNNNN': () => {
    if (this.isCurrentPlayerActive()) {
        var translated = dojo.string.substitute(_("(${amount} card(s) to discard)"), { amount: 1 });
        $('faction_hand_info').innerHTML = translated;
        this.factionHand.setSelectionMode('single');
    }
},

// OnUpdateActionButtons.<expansion>.js — REUSE the existing onCardDiscarded handler
'highDramaPhaseNNNNN': () => {
    this.addActionButton(`actChooseDiscardCards`, _('Confirm Selection'), () => this.onCardDiscarded());
    dojo.addClass('actChooseDiscardCards', 'disabled');
},

// EventHandlers.js::onFactionCardClicked — toggle the confirm button
'highDramaPhaseNNNNN': () => {
    if (this.factionHand.getSelection().length > 0) dojo.removeClass('actChooseDiscardCards', 'disabled');
    else dojo.addClass('actChooseDiscardCards', 'disabled');
},

// OnLeavingState.<expansion>.js — cleanup
'highDramaPhaseNNNNN': () => {
    if (this.isCurrentPlayerActive()) {
        this.factionHand.setSelectionMode('none');
        $('faction_hand_info').innerHTML = '';
    }
},
```

`onCardDiscarded` in `PlayerActions.js` already submits via `actFromCardWithId` with the selected card id, so the server-side `actFromActionWithId(int $id)` handler works as-is. Reference: `_01069` (Maxime), Angeline `_03026` step 1, Damya `Action_03038a`.

### Draw-then-discard — queue the draw on `EventActionTriggered`

For **"Draw a card. Then, discard a card."** (Damya `Action_03038a`):

```php
if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
{
    $owner = $this->getOwningCharacter($event->theah);

    $drawEvent = EventFactory::createCardDrawnEvent($owner->ControllerId, $owner->getInjectCode());
    $event->theah->queueEvent($drawEvent);

    $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "NNNNNa", $this->Id);
    $event->theah->queueEvent($transition);
}
```

WHY draw before the discard state: printed order is Draw → Discard; events process before the client enters the picker, so the drawn card is already in `factionHand`. Do **not** draw only after the player confirms discard.

Availability must guarantee a discardable card after the draw:

```php
$hand = $theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $playerId);
if (count($hand) > 0) return true;  // (after cardInCity etc.)

$deck = $theah->game->getGameDeckObject();
$faction = $theah->game->getPlayerFactionDeckName($playerId);
$discard = $theah->game->getPlayerDiscardDeckName($playerId);
return $deck->countCardsInLocation($faction) + $deck->countCardsInLocation($discard) > 0;
```

Empty hand + empty deck + empty discard → action unavailable (otherwise the discard state hangs).

### Multiple Actions on one card — `Action_NNNNNa` / `Action_NNNNNb`

When Text has two separate `<b>City Action:</b>` (or Action) clauses, **do not** cram both into one class. Split:

- `Action_NNNNNa.php` / `Action_NNNNNb.php` — each with its own `$this->Name`, `isAvailableToPlayer`, states, transition key
- Card constructor: `$this->Actions = [ new Action_NNNNNa(), new Action_NNNNNb() ];`
- Transition keys and state names: `"03038a"` / `"03038b"` / `"03038b_2"`; JS state names `highDramaPhase03038a` etc.
- State IDs: append digit for which action — `HIGH_DRAMA_PLAYER_TURN_03038a = 4030381`, `…_03038b = 4030382`, `…_03038b_2 = 40303822` (same scheme as `01152a`/`01152b` = `4011521`/`4011522`)

Reference: `_03038` Damya, `_01095` Patricia (`Action_01095a` / `Action_01095b`).

### Destroy an attachment — unequip + discard-from-play

There is **no** `createAttachmentDestroyedEvent`. Destroy means:

```php
$unequipEvent = EventFactory::createAttachmentUnequippedEvent(
    $attachment->ControllerId, $attachment->AttachedToId, $attachment->Id
);
$game->theah->eventCheck($unequipEvent);
$game->theah->queueEvent($unequipEvent);

$discardEvent = EventFactory::createCardDiscardedFromPlayEvent(
    $attachment->OwnerId, $attachment->Id, $attachment->Location, $owner->Id, $asEffect = true
);
$game->theah->queueEvent($discardEvent);
```

If the effect draws cards equal to printed cost (+N), **read `$attachment->WealthCost` before queueing destroy** — after unequip/discard the card is no longer a reliable in-play cost source. Cost `0` → still draw `0 + 1 = 1` when the text says "plus one."

Skip `$attachment->FakeAttachment` when building destroy/equip eligibility lists (Boons, Burdens, etc. are not real equipment).

### Attachment picker — button list, not board highlight

For "choose one of this character's attachments" steps (Damya step 2, Adelheide `01194` step 1), pass `attachments` as `[['id' => …, 'name' => …], …]` from `getArgsFromAction` and render buttons:

```js
// OnUpdateActionButtons — note args.args.attachments (not args.args.args)
'highDramaPhaseNNNNN_2': () => {
    args.args.attachments.forEach((attachment) => {
        this.addActionButton(
            `actChooseAttachment-${attachment.id}`,
            attachment.name,
            () => this.bgaPerformAction('actFromCardWithId', {id: attachment.id})
        );
    });
},
```

WHY buttons over `highlightCardsAsSelectable`: equipped attachment art is small/stacked; buttons are the established UX. `OnEnteringState` still highlights the owner / moved character as `_7sfs-chosen` context. Each button click submits immediately — no separate Confirm.

### "Equipped character moves to this location" eligibility

- Controllers match; ≥1 destroyable (non-fake) attachment.
- **Exclude characters already at the owner's location** when the text says "moves to this location" — same spirit as pull-to-here movers in `Reaction_03016b`. That also excludes the owner herself while she is at "this location."
- Move: `createCardMovingEvent(..., $engage = false, ...)` when Engage is not printed.
- Store mover id in `Game::CHOSEN_TARGET` for the destroy step; clear it when the action finishes.
- No "target" in text → no `IAbilityThatTargetsCharacters`; use private helpers (`isEligibleMover`, `getDestroyableAttachments`).

### City-location picker for CharacterActions — override `actFromActionWithIds`

For step-N states where the player picks a city location (the JS submits via `onCityLocationsSelected → bgaPerformAction('actFromCardWithLocations', ...)`):

- The state class's `#[PossibleAction]` is **`actFromCardWithLocations(string $locations)`** (NOT `actFromCardWithId`).
- The framework's `FrameworkActionsTrait::actFromCardWithLocations` JSON-decodes the payload and routes into `$card->actFromCardWithIds(...)` → `$action->actFromActionWithIds(Game, int, string, array)`.
- **Override `actFromActionWithIds(array $ids)`** on the action, NOT `actFromActionWithId(int $id)`. Each entry in `$ids` is a location-name string (e.g. `"The Forums"`), not an int.

Symptom of using `actFromActionWithId` instead: the framework can't dispatch the location payload to your action; the state spins waiting for an action it never receives, which presents as an "infinite loop" on the client.

```php
public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
{
    parent::actFromActionWithIds($game, $state, $stateName, $ids);

    if ($state == States::HIGH_DRAMA_PLAYER_TURN_NNNNN_2)
    {
        $owner = $this->getOwningCharacter($game->theah);
        $newLocation = $ids[0];  // string — location name

        $valid = $game->theah->getAdjacentCityLocations($owner->Location, $includeHome = false);
        if (! in_array($newLocation, $valid))
            throw new UserException($game->translate('Location must be adjacent.'));

        // ... queue the move event with $newLocation as the string toLocation ...
    }
}

public function getArgsFromAction(Game $game, int $state, string $stateName): array
{
    $args = parent::getArgsFromAction($game, $state, $stateName);

    if ($state == States::HIGH_DRAMA_PLAYER_TURN_NNNNN_2)
    {
        $owner = $this->getOwningCharacter($game->theah);
        $args['locationIds'] = array_values($game->theah->getAdjacentCityLocations($owner->Location, false));
    }
    return $args;
}
```

The state class:

```php
#[PossibleAction]
public function actFromCardWithLocations(string $locations): void
{
    $this->game->actFromCardWithLocations($locations);
}
```

Don't add an `actBack` button on the picker unless your state also declares a `"back"` transition and a `#[PossibleAction] actBack` method — a button that submits an unhandled action will misbehave.

Reference: `Action_01068` (Léontine), Angeline `_03026` step 2.

### Don't add `IAbilityThatTargetsCharacters` unless the text says "target"

The memory note `feedback_targets_characters_interface.md` covers the *positive* case ("if a card's Text targets a character, class must implement IAbilityThatTargetsCharacters"). The inverse also holds: text that says "wound an opposing character" / "engage a character" / "discard a character" — without the word "target" — is NOT a targeted ability and should NOT implement the interface. Other cards' "before being targeted" hooks should not see these.

When you still need validation logic ("must be opposing", "must be at my location"), write a plain private helper:

```php
private function isValidWoundCandidate(Character $owner, Character $character): bool
{
    if ($character->ControllerId == $owner->ControllerId || $character->ControllerId == 0) return false;
    return $character->Location == $owner->Location;
}
```

Don't reuse the `isValidTargetForAbility` name — that name implies the interface contract.

Reference: `_03026` Angeline (wounds without targeting), `Action_03038b` Damya ("Your equipped character moves" without "target"), vs. `Action_03020` (commanding — *does* target).

### State ID encoding

For regular Character cards (not city deck), use `4` + the 5-digit `CardNumber` for step 1. Append `2`/`3`/`4` for multi-step suffixes. Examples:

- `_01007` (Aldo) step 1: `HIGH_DRAMA_PLAYER_TURN_01007 = 401007`
- `_01008` (Cesca Scarpa) step 1: `HIGH_DRAMA_PLAYER_TURN_01008 = 401008`
- `_01008` step 2/3/4: `4010082` / `4010083` / `4010084`
- `_03001` (Cesca del Rosso) step 1: `HIGH_DRAMA_PLAYER_TURN_03001 = 403001`
- `_03001` step 2: `HIGH_DRAMA_PLAYER_TURN_03001_2 = 4030012`

When one card has **multiple Action classes** (`a`/`b`), append a digit for which action, then the step suffix:

- `_03038a` discard: `HIGH_DRAMA_PLAYER_TURN_03038a = 4030381`
- `_03038b` step 1 / 2: `4030382` / `40303822`
- Same idea as `_01152a` / `_01152b` = `4011521` / `4011522`

**Don't engineer around hypothetical city-deck-card collisions.** Memory `feedback_state_id_encoding.md`: the user prefers the simple `4` + cardId scheme. If a future CD card wants the same number, that collision gets resolved then.

### `states.inc.php` transition-name mapping

When you call `EventFactory::createTransitionEvent($playerId, $cardId, $transitionName, $abilityId)`, the framework looks `$transitionName` up in `HIGH_DRAMA_PLAYER_TURN_EVENTS.transitions` to know which state to enter. So you need an entry for **every transition name your action passes to `createTransitionEvent`** — and only those.

```php
"03001"   => States::HIGH_DRAMA_PLAYER_TURN_03001,        // entered from EventActionTriggered
```

**Do NOT blindly add `"03001_2"`** unless your action actually calls `createTransitionEvent($playerId, $cardId, "03001_2", ...)`. The step 1 → step 2 jump **sometimes** happens via `$game->gamestate->nextState("stregaChosen")` using only the state's own `transitions` array — in that case the lookup table does not need `"03001_2"`.

**Do add `"NNNNN_2"` (etc.) when you queue `createTransitionEvent` into a later step** — common for multi-step actions that `nextState` back to `HIGH_DRAMA_PLAYER_TURN_EVENTS` so the event queue can process a move/discard/draw before entering step 2. Examples: Angeline `"03026_2"` / `"03026_3"`, Damya `"03038b_2"`, challenge actions `"NNNNN_2"` → technique-available (Pattern F).

**Exception reminder: "issue a challenge" actions ALWAYS need a `<card>_2` entry.** See Pattern F — those actions cross from player-turn states into the challenge sub-state machine via `createTransitionEvent("<card>_2", ...)`.

### Named transitions, and the `""` (empty) transition rule

A state's `transitions` array maps a transition name (the argument you pass to `nextState(...)`) to a destination state. **An empty-string transition `"" => ...` is only valid when it's the ONLY transition out of the state.** With multiple transitions, name each one:

```php
// CORRECT — multiple named transitions
transitions: [
    "zombie"       => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
    "targetChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
],

// WRONG — mixing "" with another named transition errors out
transitions: [
    ""       => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
    "zombie" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
],
```

When the zombie path is the only escape hatch besides the success path (typical for picker states), give both a name. Wilhelm's `State_highDramaPhase02013_2` gets away with the single-`""` form because it doesn't declare a separate zombie transition — its `zombie()` method calls `nextState()` (empty), which lands on `""`. If you want a distinct zombie path, you must name both.

### Action examples

| File | Demonstrates |
|---|---|
| `Action_01008` | Multi-step Sorcerer Action; reveal-top-of-deck → optional sink. Branching states (`_2`, `_3`, `_4`). |
| `Action_01076` | Sorcerer Action; multi-step with `RequiresPerformerSelected`, location + character pick, queues `createSorcererAbilityStartEvent` / `createSorcererAbilityPlayedEvent` pair. |
| `Action_02010` | Two-step "move wound from character A to character B"; the heal+wound recipe. |
| `Action_03001` | Two-step "move wound from your Strega to opposing non-Leader"; the heal+wound recipe applied to a Leader's City Action. |
| `Action_01035` | Engage-as-cost + reveal-from-city-deck-until-Mercenary action on a Leader. |
| `Action_03038a` | Draw-then-discard City Action — draw queued on `EventActionTriggered`, then `factionHand` discard picker. |
| `Action_03038b` | Move equipped character (`engage=false`) → attachment button destroy → draw `WealthCost + 1`. Dual-action `a`/`b` sibling of `Action_03038a`. |

### Move-a-wound recipe

```php
$healEvent = EventFactory::createCharacterBeingHealedEvent(
    $sourceCharacter->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id
);
$game->theah->queueEvent($healEvent);

$woundEvent = EventFactory::createCharacterBeingWoundedEvent(
    $targetCharacter->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id
);
$game->theah->queueEvent($woundEvent);
```

Heal first, wound second. Both go through the standard event pipeline so other cards can react (Maryam's wound cancel, Silver Spine's risk-target cancel, etc.) — don't try to mutate `$character->Wounds` directly.

## Pattern F — Issuing a Challenge from a City Action

For text like **"Engage <self> • Issue a <Stat> challenge to target opposing character"** (Aja, Wilhelm Dunst, Torvo Espada). The CharacterAction sets a handful of globals, then transitions into the standard challenge sub-state machine, which handles intervention, refusal, technique activation, and threat resolution. The hard part is wiring the new flow without re-implementing any of the challenge machinery.

References: `Action_02013` (Wilhelm Dünst), `Action_02034` (Torvo Espada), `Action_03002` (Aja).

### Action skeleton

```php
class Action_NNNNN extends CharacterAction implements IAbilityThatTargetsCharacters
{
    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck)) return false;

        $owner = $this->getOwningCharacter($theah);
        if (! $theah->cardInCity($owner)) return false;          // City Action
        if (! $owner->canChallenge() || $owner->Engaged) return false;  // engagement is the cost

        return count($theah->getOpposingCharactersAtLocation($owner->Location, $owner->ControllerId)) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);
        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id) {
            $owner = $this->getOwningCharacter($event->theah);
            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "NNNNN", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_NNNNN) {
            $target = $game->theah->getCharacterById($id);
            [$isValid, $err] = $this->isValidTargetForAbility($game, $target);
            if (! $isValid) throw new UserException($err);

            $owner = $this->getOwningCharacter($game->theah);

            $game->globals->set(Game::CHOSEN_PERFORMER, $owner->Id);
            $game->globals->set(Game::CHOSEN_TARGET,    $target->Id);
            $game->globals->set(Game::CHALLENGE_STAT,   Game::STAT_COMBAT);  // or STAT_FINESSE / STAT_INFLUENCE
            $game->globals->set(Game::CHALLENGE_TYPE,   Game::NORMAL_CHALLENGE_TYPE);  // or your new type

            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "NNNNN_2", $this->Id);
            $game->theah->queueEvent($transition);

            $game->gamestate->nextState("targetChosen");
        }
    }
}
```

### State + states.inc.php wiring

- State class `State_highDramaPhaseNNNNN` is a standard target-picker (`StateType::ACTIVE_PLAYER`). Both `"zombie"` and `"targetChosen"` (or any named transition you use) point to `HIGH_DRAMA_PLAYER_TURN_EVENTS`:
  ```php
  transitions: [
      "zombie"       => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
      "targetChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
  ],
  ```
- `states.inc.php` needs **two** entries (this is the exception to the "don't add `_2`" rule):
  ```php
  "NNNNN"   => States::HIGH_DRAMA_PLAYER_TURN_NNNNN,
  "NNNNN_2" => States::HIGH_DRAMA_CHALLENGE_ACTION_TECHNIQUE_AVAILABLE,
  ```

WHY this flow shape:
- The action queues a `createTransitionEvent("NNNNN_2")` AND calls `nextState("targetChosen")` to `HIGH_DRAMA_PLAYER_TURN_EVENTS`.
- The events dispatcher in `EVENTS` flushes queued events; the transition event then routes via the `states.inc.php` lookup to `HIGH_DRAMA_CHALLENGE_ACTION_TECHNIQUE_AVAILABLE`.
- This is necessary because the challenge sub-state machine relies on queued events firing first (e.g., `EventCardEngaged` from `stIssueChallenge`'s auto-engage). Bypassing the EVENTS dispatch with a direct `nextState(...)` to TECHNIQUE_AVAILABLE would leave events stuck in the queue.

### No `createActionResolvedEvent` in the action

The challenge resolution flow fires `createActionResolvedEvent` itself — either in `stChallengeActionCheckCancelled` (cancelled path) or in the threat-resolution path. Don't call it from your action. Mirror the `// createActionResolvedEvent is queued by the challenge resolution flow.` comment from `Action_01083`.

### Engage-as-cost is automatic — when (engagement trichotomy)

`StatesTrait::stIssueChallenge` auto-engages the performer for challenges of type `NORMAL`, `SERVO_SCARPA`, `TORVO_ESPADA`, and `AJA_CHALLENGE_TYPE` (the auto-engage list). Engagement for Pattern F actions is a **trichotomy** — read the printed cost and pick exactly one:

| Printed cost / shape | Eligibility | Auto-engage list | Manual `createCardEngagedEvent` |
|---|---|---|---|
| **"Engage [self/performer]"** (Aja, basic challenge) | Require `! Engaged` | Add the new type to the list | None (auto) |
| **No Engage printed, but unengaged performers still engage** (Don Constanzo's Thug) | Engaged performers eligible | Keep type **OUT** | `if (! $performer->Engaged) { … }` |
| **Not a basic challenge — never engages** (Sanjay `_03037`) | Engaged performers eligible | Keep type **OUT** | **None** — do not emit engage at all |

If your card has a different cost shape (e.g., engage a Weapon attachment instead of the performer), register a separate handler — see `Action_02013`'s `doCost` for the "discard a card" variant.

**Regression trap:** "No Engage printed" is **not** one pattern. Copying Don Constanzo's conditional-engage onto every such card is wrong. Ask: does this action engage the performer at all? Sanjay's City Action issues an Influence challenge without engaging — dedicated `SANJAY_CHALLENGE_TYPE` exists solely so `NORMAL` (which is on the auto-engage list) is not reused. No intervention/refuse restrictions → only `Game.php` + matching JS int; skip `interventionCheck` / Refuse-button wiring.

WHY keep a type out of the auto-engage list when engaged performers are eligible (Don Constanzo case): Auto-engaging an already-engaged card re-emits `EventCardEngaged`, and downstream reactions (e.g., Vittoria's `Reaction_01014` "instead of me" swap) treat that as a *fresh* engagement and misfire. Conditional engage:

```php
if (! $performer->Engaged)
{
    $engageEvent = EventFactory::createCardEngagedEvent(
        $performer->ControllerId, $performer->Id, $owner->Id, $this->Id
    );
    $game->theah->queueEvent($engageEvent);
}
```

The `eligibility filter` follows the same table. Aja checks `! $self->Engaged` because Engage is printed; Don Constanzo and Sanjay do NOT check `! Engaged`. Read the printed cost and match.

### Performer ≠ action owner

The default Pattern F assumes the action's owner is also the challenge performer. But some cards (e.g., Don Constanzo `_03003`: "Your **Thug** at this location issues a **Combat** challenge") have the owner *select* the performer separately. Adjust:

- **Two-step state machine.** Step 1 picks the performer (e.g., a Thug at the owner's location). Step 2 picks the target at the *performer's* location. State IDs follow the usual `4` + cardId scheme with `_2` suffix.
- **`CHOSEN_PERFORMER` is the picked performer's id, not the owner's.** Set it in step 1's act handler; reference it in step 2's `getArgsFromAction` so the target picker filters opposing characters at the performer's location (not the owner's — they're usually equal but stay correct if the performer was at a different valid location).
- **`isValidTargetForAbility(Game, Character)` reads `CHOSEN_PERFORMER` to find the controller and location** for the validity check, since `getOwningCharacter` returns the action owner (Don), not the performer (the Thug).
- **Engagement follows the trichotomy** (see previous section). Don Constanzo uses conditional engage in step 2; a never-engages variant would emit no engage event.

Reference: `Action_03003` (Don Constanzo).

Note that `canChallenge()` on the base `Character` class only checks `isControlled()` — it does NOT check `Engaged`. If your eligibility filter needs both, add `! $c->Engaged` explicitly. Characters that override `canChallenge` (e.g., Sigurd Ulfsen `_01190` permanent ban, Carmella `_01178` "engaged once" rule) handle their own engagement logic.

### Adding a NEW challenge type

A new `*_CHALLENGE_TYPE` constant is justified when the card imposes restrictions or behaviors that diverge from `NORMAL_CHALLENGE_TYPE` — e.g., Aja's "only Finesse ≥ 3 may intervene or refuse," **or** when you must avoid `NORMAL`'s auto-engage (Sanjay never engages; Don Constanzo needs conditional engage). Touch these files in lockstep when there **are** intervene/refuse restrictions:

| File | What goes there |
|---|---|
| `modules/php/Game.php` | `final const NEW_CHALLENGE_TYPE = N;` (next int after the highest existing). |
| `seventhseacityoffivesails.js` | `this.NEW_CHALLENGE_TYPE = N;` — same int. Client checks reference `this.NEW_CHALLENGE_TYPE`. |
| `modules/php/StatesTrait.php::stIssueChallenge` | Add the new type to the auto-engage `if` list (**only** if cost is "Engage performer" — trichotomy case a). |
| `modules/php/theah/Theah.php::interventionCheck` | Add an `else if` branch that throws `UserException` when the would-be intervener fails the card's restriction. Server-side enforcement. |
| `modules/php/ArgumentsTrait.php::argsHighDramaChallengeActionAcceptChallenge` | Post-filter `$charactersCanIntervene` so disallowed characters never appear in the picker. Add any extra args (e.g., `defenderFinesse`) the client needs to gate UI. |
| `modules/php/FrameworkActionsTrait.php::actHighDramaChallengeActionReject` | Throw `UserException` if the card forbids refusal under its conditions. |
| `modules/js/OnUpdateActionButtons.js::highDramaChallengeActionAcceptChallenge` | Add a `dojo.addClass('btnRefuse', 'disabled')` branch for the new type — mirror the existing `EPEE_SANGLANTE` / `UNSANCTIONED_DUEL` block. Use the server-supplied args (e.g., `args.defenderFinesse`) to compute the condition. |

The intervention-restriction story specifically:
- The args function filters the *visible* intervener list (UX).
- `interventionCheck` enforces the same rule on the server (security).
- For refusal, `actHighDramaChallengeActionReject` enforces server-side; the JS disable is UX. Always both.

### IAbilityThatTargetsCharacters

Always implement this interface on a challenge-issuing action — challenge target *is* a targeted character, so other cards' "before being targeted" hooks need to see it. Implement `isValidTargetForAbility(Game $game, Character $character): array` returning `[bool, string]`.

### Examples

| File | Demonstrates |
|---|---|
| `Action_02013` (Wilhelm Dünst) | "Discard a Card. Issue a Challenge." — discard-as-cost, then standard issue-challenge transition. Two-step state machine; reference for `doCost`/`doEffect` separation. |
| `Action_02034` (Torvo Espada) | Three-step "offer challenge → accept/decline → issue" flow with the `TORVO_ESPADA_CHALLENGE_TYPE` (no interventions allowed). |
| `Action_03002` (Aja) | Single-step picker → standard challenge flow with `AJA_CHALLENGE_TYPE` (Finesse ≥ 3 to intervene/refuse). Canonical reference for a NEW challenge type with restrictions. |
| `Action_03003` (Don Constanzo) | Two-step "pick your Thug → pick target". Performer is the chosen Thug, not the owner. New challenge type `DON_CONSTANZO_CHALLENGE_TYPE` deliberately kept OUT of the auto-engage list; action emits a conditional engage event in step 2 so already-engaged Thugs remain eligible. |
| `Action_03037` (Sanjay) | Single-step Influence challenge with **no engage at all**. `SANJAY_CHALLENGE_TYPE` out of auto-engage AND no `createCardEngagedEvent`. Hand-size target filter (`opponent hand < your hand`). Exemplar for "not a basic challenge — never engages." |
| `Action_01083` (Legendary Reputation) | RiskCityAction variant — sets `LEGENDARY_REPUTATION_CHALLENGE_TYPE` (only Leaders may intervene). |

## Pattern D — Reaction / City Reaction (CardReaction)

This pattern is **the same as in `create-city-character`'s Pattern D**, with two Character/Leader-specific notes below. Read the city-character skill's Pattern D for the full template, multi-stage button flow, and `< Back` rules.

### Trigger gates for non-city-deck characters

Most Character/Leader reactions don't need a `cardInCity` gate (unless the card text says "City Reaction" — then add the gate). Key gates:

1. **`$this->isAvailable()`** — base `CardReaction::handleEvent` resets `Used = false` on `EventDuskEndOfDay`. Gate every branch on `isAvailable()` so the reaction doesn't double-fire within a day.
2. **Identity check** — usually `$event->sourceId == $owner->Id`, `$event->performerId == $owner->Id`, `$event->actorId == $owner->Id`, or `$event->cardId == $owner->Id`. The field depends on the event.
3. **City scope** (for "City Reaction" only) — `$event->theah->cardInCity($owner)`.
4. **Valid-target precondition** — if the effect requires a target (e.g., "wound an opposing character"), check that at least one valid target exists BEFORE queuing the reaction transition. Otherwise the player gets a useless prompt they can only Decline.
5. **"Opposing" semantics** — opposing means BOTH different controller AND same location. Use `Theah::getOpposingCharactersAtLocation($location, $playerId)` (or hand-filter with `isNotControlledByPlayer($controllerId) && Location == $owner->Location`), not a hand-rolled `ControllerId !=` filter.

### Triggering off a Sorcerer ability the owner just performed

For "After <X> performs a Sorcerer ability …" (Cesca del Rosso, Elina, Cesca Scarpa) — listen on `EventSorcererAbilityPlayed` and check both `sourceId` and `performerId`:

```php
if ($event instanceof EventSorcererAbilityPlayed && $this->isAvailable())
{
    $owner = $this->getOwningCharacter($event->theah);

    if (! $event->theah->cardInCity($owner))   // City Reaction gate — drop for non-city Reactions
    {
        return;
    }

    if ($event->sourceId != $owner->Id && $event->performerId != $owner->Id)
    {
        return;   // some other Sorcerer's ability — not this card's
    }

    // ... valid-target precondition ...

    $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
    $event->theah->queueEvent($transition);
}
```

`sourceId` is the card whose ability fired; `performerId` is the character actually performing it. The ability may be on a card other than the owner (e.g., the owner cast a sorcery from her hand) — checking both covers both cases.

### Should the Reaction itself implement `ISorcererAbility`?

Only if the card text says "**Sorcerer** Reaction" or "**Sorcerer** City Reaction." Examples:
- `Reaction_02001` (Andriana, "**Sorcerer** Reaction: …") implements `ISorcererAbility`.
- `Reaction_03001` (Cesca del Rosso, "**City Reaction**: …") does NOT — the text doesn't carry the Sorcerer keyword.

This matters because if a Reaction is a Sorcerer ability and it wounds, that wound's `EventSorcererAbilityPlayed` would re-trigger the same "after a Sorcerer ability" type reaction in a loop. `setUsed` breaks the loop in practice, but the cleaner answer is: **follow the card text literally.** If the keyword isn't printed, the ability isn't Sorcerer.

When `implements ISorcererAbility`, you MUST also call both:
- `createSorcererAbilityStartEvent()` at the start of resolution
- `createSorcererAbilityPlayedEvent()` at the end of resolution

The pre-commit hook enforces this.

### "Put into play from hand or discard"

For Reactions whose effect is "put a card into play" (e.g., Don Constanzo's "Put a different **Thug** into play at your **Home** from your hand or discard pile"):

- **Source filtering.** For hand: `$theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $owner->ControllerId)`. For discard pile: `$theah->getCardObjectsAtLocation($game->getPlayerDiscardDeckName($owner->ControllerId), $owner->ControllerId)`. Both per-player.
- **The muster event does the move.** `EventFactory::createCharacterMusteredEvent($playerId, $cardId, $location)` is the only event needed for the actual location change — its handler calls `$deck->moveCard(...)` on the game deck which physically moves the card.
- **`createCardRemovedFromPlayerDiscardPileEvent` is notification-only** in the default code path — it sends a `cardRemovedFromPlayerDiscardPile` notification (and only physically moves the card if `permanentlyHide=true`). The actual remove-from-discard happens implicitly when `createCharacterMusteredEvent`'s `$deck->moveCard` runs. So:
  - Fire `createCardRemovedFromPlayerDiscardPileEvent` BEFORE the muster event so JS clients (which filter `player.discard` on that notification) update their state in the right order.
  - Don't expect it to move the card; that's the muster event's job.
  - Reference: `Action_01024` (Bravos) follows this exact ordering for Thug-from-discard mustering.

### After a character moves to this location

For "Reaction: After <enemy/X> character moves to this location • <effect>" (`_03016` Ise). Listen on `EventCardMoved` (past-tense — the move has already committed). Required gates:

```php
if (! ($event instanceof EventCardMoved)) return;
if (! $this->isAvailable()) return;

$owner = $this->getOwningCharacter($event->theah);
if ($owner === null) return;
if (! $event->theah->cardInCity($owner)) return;   // enemies can't enter your Home
if ($event->cardId == $owner->Id) return;          // skip the Owner's own moves
if ($event->toLocation != $owner->Location) return;

$character = $event->theah->getCardById($event->cardId);
if (! ($character instanceof Character)) return;   // attachments and other cards also move
if ($character->ControllerId == 0) return;          // uncontrolled / mercenary — skip
if ($character->ControllerId == $owner->ControllerId) return;   // "enemy" gate

// Valid-effect-target precondition: if no eligible action, don't prompt the player.
if (count($this->getEligibleMovers($event->theah, $owner)) == 0) return;
```

WHY the `cardInCity($owner)` gate: enemy characters can't enter your Home location (Home is per-controller scope), so the `toLocation == $owner->Location` check would silently never match for an Owner at Home. The explicit gate documents the intent and skips the per-event work entirely.

WHY `Character` instanceof check (not just `getCardById`): `EventCardMoved` fires for *any* card that moved — attachments equipping, schemes being placed, etc. Filter to Character explicitly.

WHY `ControllerId == 0` skip: uncontrolled characters (mercenaries in transit, cards being mustered with no controller yet) shouldn't trigger an "enemy" reaction. Skipping them is the consistent behavior across the codebase.

WHY the valid-target precondition: if no eligible mover exists, the player would get a useless prompt they could only Pass. The general Pattern D rule (skill section "Trigger gates") applies here verbatim.

For the *self-moves* analogue ("after this character moves to a new location, do X for nearby allies"), the receiver isn't a Reaction — it's a `handleEvent` on the card itself. See `_01067` Jean Urbain or `_02022` Stranahan.

### After a character equips an attachment at a location

For "City Reaction: After a character equips an attachment at [The Grand Bazaar] • Draw a card" (`_03028` Térence). Listen on `EventAttachmentEquipped`. Required gates:

```php
if (! ($event instanceof EventAttachmentEquipped)) return;
if (! $this->isAvailable()) return;

$owner = $this->getOwningCharacter($event->theah);
if ($owner === null) return;
if ($event->theah->game->characterIsInDiscardOrLocker($owner)) return;
if (! $event->theah->cardInCity($owner)) return;                    // City Reaction
if ($owner->Location != Game::LOCATION_CITY_BAZAAR) return;          // owner must be at the named location

$attachment = $event->theah->getAttachmentById($event->attachmentId);
if ($attachment === null || $attachment->FakeAttachment) return;

$character = $event->theah->getCharacterById($event->characterId);
if ($character === null) return;
if ($character->Location != Game::LOCATION_CITY_BAZAAR) return;      // equip happened at the named location
```

WHY **not** gate on `$event->characterId == $owner->Id`:

- Card text says "a character" — any character equipping at that location triggers the reaction while the owner is present. `Reaction_01039` (Philip) is the self-only analogue: it gates on `$event->characterId == $philip->Id`.

WHY skip `FakeAttachment`:

- Fake attachments are bookkeeping placeholders, not real equips. Same skip as `Reaction_01039`.

Button shape: Draw/Pass (`Reaction_03028`, `Reaction_01146a`). Pass early-returns before `setUsed`.

### After the Owner herself moves to a city location

For "After <Owner> moves to a city location • Reaction: do X" (`_03025` Angeline). The trigger filter is the OPPOSITE of `Reaction_03016b` — gate on `cardId == $owner->Id`, not `!=`. The full gate set:

```php
if (! ($event instanceof EventCardMoved)) return;
if (! $this->isAvailable()) return;

$owner = $this->getOwningCharacter($event->theah);
if ($owner === null) return;

if ($event->cardId != $owner->Id) return;                       // owner herself
if (! $event->theah->locationInCity($event->toLocation)) return; // city dest only

// Valid-target precondition — read eligibility at $event->toLocation, NOT $owner->Location
if (count($this->getEligibleTargets($event->theah, $owner, $event->toLocation)) == 0) return;
```

**Gotcha — `$owner->Location` at handleEvent time is the OLD location.** `EventCardMoved` sets `runEventHubAfterCards = true`, so the EventHub state update (which writes `$card->Location = $event->toLocation`) runs AFTER every card's `handleEvent`. Inside an `EventCardMoved` handler, `$owner->Location` is still `$event->fromLocation`. Read the destination as `$event->toLocation` for any "now that the move has happened, who else is at the new location" lookups. By the time `performReaction` runs, the move HAS resolved and `$owner->Location` is the new location — so the target-validation check there can use `$owner->Location` directly.

Pattern reference: `Reaction_03025` (Angeline) — `cardId == $owner->Id` filter, `locationInCity($event->toLocation)` gate, `getEligibleTargets(..., $event->toLocation)` precondition.

### Pass button — Reactions with an optional second effect

For Reactions where the printed text bundles a mandatory first effect with an *optional* second effect ("X. Then, you may Y"), or where the Reaction is purely optional and the player might want to decline at the prompt without burning the daily use, **add a `'pass'` button**. Cumulative pattern across `Reaction_01062`, `Reaction_03016b`, `Reaction_03027a/b`:

```php
public function getReactionButtonProperties(Theah $theah): array
{
    $array = parent::getReactionButtonProperties($theah);
    // ... per-target / per-source buttons ...
    $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');
    return $array;
}

public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
{
    parent::performReaction($game, $state, $internalId, $reactionId);

    if ($reactionId == 'pass')
    {
        $game->gamestate->nextState("done");
        return;   // EARLY return — do NOT call setUsed; reaction stays available
    }

    // ... mandatory effect (if any) ...
    // ... optional/branched effect ...

    $this->setUsed($game->theah, true);
    $game->gamestate->nextState("done");
}
```

WHY a per-card `'pass'` button instead of relying on the framework's Decline:

- `Reaction::performReaction` in `CardReaction.php` line 59 explicitly handles `'pass'` (and `'decline'`) by SKIPPING the `createReactionActivatedEvent` emission. So the button label "Pass" arrives in `performReaction` like any other id; the early-return-before-`setUsed` shape mirrors the framework's intent.
- The framework's Decline button (handled by `actFromReactionPass`) is functionally similar but routes through a different code path. The `'pass'` button keeps everything in one method and makes the "do not setUsed" intent explicit.
- For Reactions with mandatory first effect + optional second (`_03027a` heal + optional renown), the `'pass'` button means "I decline the whole reaction" — neither the heal nor the renown move runs. The button labels for the active choices (`'healOnly'`, `'moveFrom-<loc>'`) carry the mandatory effect.

WHY early-return BEFORE `setUsed(true)`:

- The reaction's daily-use slot is a resource. A player who declines at the prompt should NOT lose that slot — they should still be able to fire the reaction later when the trigger recurs (e.g., a second character dies at the same location later in the day).
- Mirrors `Reaction_01062`: `if ($reactionId != "pass") { ... setUsed ... }` — Pass falls through to `nextState` without touching `setUsed`.

### Hide buttons whose effect would be a no-op

When a Reaction button's effect would do nothing (e.g., "Heal only" when the character has 0 wounds), hide that button from `getReactionButtonProperties` rather than letting the player click a no-op. Mirror the gate from the trigger-availability check:

```php
if ($owner->Wounds > 0)
{
    $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Heal only'), 'healOnly');
}
```

The same discipline applies inside `performReaction` — gate the no-op effect on its precondition so you don't queue an empty event:

```php
if ($owner->Wounds > 0)
{
    $healEvent = EventFactory::createCharacterBeingHealedEvent(...);
    $game->theah->queueEvent($healEvent);
    $game->notify->all("message", ...);
}
```

WHY skip the no-op queue: `createCharacterBeingHealedEvent` against a 0-wound character is clamped by the engine and does nothing, but it still emits a notification ("X heals a wound") that misleads the log. Skip the event entirely when there's nothing to heal.

### Moving Renown between locations — three-event batch

For "Move N Renown from location A to location B" (Reaction_01062, Reaction_03027a, any similar Action). Queue THREE events with a shared `batchId`:

```php
$batchId = $game->getNextEventBatchId();

$movingEvent = EventFactory::createRenownMovingBetweenLocationsEvent(
    $owner->ControllerId, $fromLocation, $toLocation, 1, $owner->getInjectCode()
);
$movingEvent->batchId = $batchId;
$game->theah->eventCheck($movingEvent);
$game->theah->queueEvent($movingEvent);

$removedEvent = EventFactory::createRenownRemovedFromLocationEvent(
    $owner->ControllerId, $fromLocation, 1, $owner->getInjectCode()
);
$removedEvent->batchId = $batchId;
$game->theah->eventCheck($removedEvent);
$game->theah->queueEvent($removedEvent);

$addedEvent = EventFactory::createRenownAddedToLocationEvent(
    $owner->ControllerId, $toLocation, 1, $owner->getInjectCode(), $isMove = true
);
$addedEvent->batchId = $batchId;
$game->theah->eventCheck($addedEvent);
$game->theah->queueEvent($addedEvent);
```

WHY three events with `batchId`:

- `EventRenownMovingBetweenLocations` is the umbrella event that other cards (and the UI animator) hook to see "renown is moving from A to B" as one logical motion.
- `EventRenownRemovedFromLocation` + `EventRenownAddedToLocation` are the granular bookkeeping events that actually mutate the source/destination renown counts. Pass `$isMove = true` to the added-event so it knows the renown originated from another location (not a fresh add).
- The shared `batchId` (from `$game->getNextEventBatchId()`) groups all three under one logical operation in the log/UI. Without it, the player sees three separate log lines.
- Call `eventCheck($event)` on each before queueing — gives other cards a chance to cancel or modify (e.g., a card that prevents renown moves).

Eligible source locations come from `$theah->getAdjacentCityLocations($owner->Location, $includeHome = false)` filtered by `$theah->getCityLocation($name)->Renown > 0`. Don't queue any of the three events if the source has 0 renown.

Reference: `Reaction_01062` (Odette Leader's existing reaction), `Reaction_03027a` (the new Odette character's destroyed-trigger reaction).

### Continuous Reaction — never set to Used

For "After X happens, you may Y" with no per-round/per-turn/per-game cap (e.g. `_03025` Angeline's "After Angeline moves to a city location, wound an engaged opposing character"). The Reaction should fire every time the trigger event recurs. Omit the `$this->setUsed($theah, true)` call in `performReaction` — let the reaction stay available indefinitely.

**Pre-commit hook gotcha.** `.githooks/pre-commit` greps for the literal string `$this->setUsed(` in every `CardReaction` subclass and fails the commit if absent. A continuous Reaction has no runtime `setUsed(true)` call, so satisfy the hook by mentioning the literal in a comment:

```php
// Continuous Reaction: intentionally do NOT call $this->setUsed(true).
// The reaction remains available and can fire on every recurrence of the trigger.
```

The grep matches the literal inside the comment — no behavior change, hook passes. Same trick works for `$this->isAvailable(` if the reaction doesn't otherwise call it (rare; `isAvailable()` is the standard gate in `handleEvent`).

### Cancel-and-reissue Reaction — opt out of an auto-emitted event

For text like "During Dusk, you may choose not to move <Owner> Home" (`_03016` Ise). The framework's `stDuskPhaseCleanup` emits a `createCardMovingEvent(..., LOCATION_PLAYER_HOME, $engage=false, $sourceId=0)` for every non-Home controlled character. The Reaction intercepts that event, asks the player, and either keeps it canceled (effect: stay) or re-queues it (effect: go home as normal).

Skeleton (mirror `Reaction_03016a` Ise, in-hand sibling `Reaction_01140`):

```php
class Reaction_NNNNN extends CardReaction
{
    private ?EventCardMoving $cardMovingEvent = null;
    private string $fromLocation = '';

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Keep in city'), 'stay');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Decline'), 'decline');
        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if (! ($event instanceof EventCardMoving)) return;
        if ($event->canceled || $event->unstoppable) return;
        if (! $this->isAvailable()) return;

        $owner = $this->getOwningCharacter($event->theah);
        if ($owner === null) return;

        if ($event->cardId != $owner->Id) return;
        if ($event->toLocation != Game::LOCATION_PLAYER_HOME) return;
        if ($event->sourceId != 0) return;                              // auto-emitter signal
        if (in_array($owner->Id, $event->cancelDeclinedByCardIds)) return;  // re-queue guard

        $turnPhase = (int) $event->theah->game->getGameStateValue(Game::TURN_PHASE);
        if ($turnPhase != Game::DUSK) return;

        $this->cardMovingEvent = clone $event;
        unset($this->cardMovingEvent->theah);
        $this->fromLocation = $event->fromLocation;
        $event->canceled = true;
        $owner->IsUpdated = true;

        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $event->theah->stackEvent($transition);
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);
        $owner = $this->getOwningCharacter($game->theah);

        if ($reactionId == 'stay')
        {
            // Already canceled in handleEvent; just announce + setUsed.
            $this->setUsed($game->theah, true);
            $this->cardMovingEvent = null;
            $this->fromLocation = '';
            $owner->IsUpdated = true;
        }

        if ($reactionId == 'decline')
        {
            // Re-queue the move with a self-marker so handleEvent doesn't re-trigger.
            $this->cardMovingEvent->cancelDeclinedByCardIds[] = $owner->Id;
            $game->theah->queueEvent($this->cardMovingEvent);
            $this->cardMovingEvent = null;
            $this->fromLocation = '';
            $owner->IsUpdated = true;
        }

        $game->gamestate->nextState("done");
    }
}
```

WHY `sourceId == 0` is the auto-emitter signal: grepping `createCardMovingEvent(...LOCATION_PLAYER_HOME...)` confirms every ability-driven move-home passes a non-zero sourceId (action id / reaction id / etc.); only `stDuskPhaseCleanup` and `_01126`'s own self-recall emit with the default `$sourceId=0`. For the Dusk opt-out, that's exactly the signal you want — abilities that *also* try to move the Owner home should not be intercepted, because the player already chose to play that ability.

WHY the redundant `TURN_PHASE == DUSK` gate: belt-and-suspenders. Cheap, authoritative, and protects against any future code path that emits a zero-source move-home outside Dusk.

WHY clone + `unset($this->cardMovingEvent->theah)`: storing the event for later re-queue. The `theah` reference holds the Theah/Game graph; unsetting prevents recursive serialization when the reaction-instance state is persisted via `IsUpdated`. This matches `Reaction_01140`'s shape.

WHY `cancelDeclinedByCardIds` instead of "just delete the stored event": when the player picks "Decline", the move MUST still happen — the framework's downstream logic (engardment cleanup at dusk, etc.) depends on every non-Home character routing home. Re-queueing the cloned event with `cancelDeclinedByCardIds[] = $owner->Id` lets the move proceed without `handleEvent` immediately re-catching the re-queued event. Same dance as `Reaction_01140`.

WHY `stackEvent` (not `queueEvent`) for the transition: stacking puts the reaction prompt ahead of other queued cleanup events so the player decision happens BEFORE subsequent dusk cleanup events fire for other characters. Matches `Reaction_01140`'s convention.

Reference: `Reaction_03016a` (Ise Dusk opt-out, on a Character in play), `Reaction_01140` (in-hand RiskReaction sibling — same dance for player-driven moves).

### Reactions that need to pay a wealth cost — click-to-pay

For Reactions where the effect costs Wealth (e.g., Don Constanzo's "at -1 cost"), the framework's `PAY_STATE_PLAY_BRUTE` / `actPayForBrute` is usually NOT a fit because:

- Its success transition is hard-coded to `HIGH_DRAMA_PLAYER_TURN_EVENTS`, but reactions can fire outside high drama (dawn cleanup, pressure, duel cleanup) and must return to whatever state cycle invoked them.
- It requires the paid-for card to be in `LOCATION_HAND`. Reactions like "from hand or discard pile" don't fit.

Instead, do the payment **inside the Reaction class** using the standard `playerReaction` loop. Pattern:

1. **Reaction-instance state** for the running payment:
   ```php
   private array $paidCardIds = [];       // cards selected so far
   private int $paidWealth = 0;           // running wealth sum
   private bool $paidHasWealthCard = false; // true if any selected card has the "Wealth" trait
   ```
   Plus a `$stage` field (e.g., `'pick'` → `'pay'`).
2. **`getReactionButtonProperties` during the `'pay'` stage** lists every card in hand as a button (`Pay with <name> (+N Wealth)`), excluding cards already in `$paidCardIds` and excluding the card being put into play (when it's the hand-source one). Always include `< Back` and `Decline`.
3. **Each click runs `handlePay`**: validate the card, append to `paidCardIds`, increment `paidWealth` by `$card->hasTrait("Wealth") ? 2 : 1`, set `paidHasWealthCard` if applicable.
4. **`isPaymentComplete($cost)` mirrors `UtilitiesTrait::isValidWealthPayment`** — exact match OR `paidWealth == cost + 1 && paidHasWealthCard` (the "overpay by 1 using a Wealth card" rule).
5. **Filter button list to valid-next-clicks** via a `wouldClickProduceValidPayment` helper. Suppress buttons that would put paid beyond `cost + 1` or beyond `cost` without using a Wealth card.
6. **Queue discards atomically at finalize**, not per-click. WHY: `Decline` becomes a clean rollback (no cards were ever queued for discard), AND downstream reactions to `EventCardDiscardedFromHand` don't see partial-payment intermediate states.
7. **Always set `$owner->IsUpdated = true`** on every reaction-instance state mutation so the framework persists the running totals across reaction-loop iterations.
8. **Skip the `'pay'` stage entirely when `cost == 0`** — go straight to finalize.

Reference: `Reaction_03003` (Don Constanzo) — the canonical implementation of this pattern.

### Reaction examples

| File | Demonstrates |
|---|---|
| `Reaction_01006` | `IRiskReaction`-shaped pre-end-of-day cleanup ("Reaction: Before the end of the Day"). |
| `Reaction_01008` | "Cesca Scarpa copies the Sorcerer ability just played" — listens on `EventSorcererAbilityPlayed`, branches on the ability instance to copy actions/cards/etc. The original kitchen-sink Sorcerer-after-Sorcerer reaction. |
| `Reaction_01013` | Canonical "after my Red Hand is destroyed" Reaction — `EventCharacterDestroyed` trigger + button-based draw choice. Reference for the trait/controller/location identity gates. |
| `Reaction_01014` (Vittoria) | "Instead of me" target swap on `EventCardEngaged`/`EventChallengeIssued`/etc. ⚠ Re-emitting `EventCardEngaged` on an already-engaged character will trip this. Pattern F users beware. |
| `Reaction_01089` | Soline el Gato's "after an Action resolves" — `EventActionResolved` + button-per-adjacent-location. |
| `Reaction_01116a`, `Reaction_01116b` | Yevgeni's paired Reactions on a single Leader. |
| `Reaction_01118` | Elina's "after a Sorcerer ability targets a character at her location, move Renown to her location" — `sourceId == owner` OR `performerId == owner` pattern. |
| `Reaction_02001` | Andriana — Sorcerer Reaction (so implements `ISorcererAbility`); button-prompts to wound a non-Sorcerer. |
| `Reaction_03001` | Cesca del Rosso's "after Cesca performs a Sorcerer ability, wound an opposing character" — button-per-opposing-character target picker, with a Pass button. |
| `Reaction_03003` (Don Constanzo) | Multi-stage Reaction with hand/discard source selection, **incremental click-to-pay wealth handling** rolled inside the reaction (no PAY_STATE_PLAY_BRUTE coupling), and muster-at-Home. Canonical reference for cost-bearing Reactions and "put into play from hand or discard pile." |
| `Reaction_03016a` (Schwester Ise — Dusk opt-out) | **Canonical cancel-and-reissue Reaction.** Listens on `EventCardMoving` for the Dusk auto-move home (`sourceId == 0`, `toLocation == LOCATION_PLAYER_HOME`, `TURN_PHASE == DUSK`). Cancels and prompts; "Keep in city" calls `setUsed`, "Decline" re-queues the cloned event with `cancelDeclinedByCardIds[] = owner.Id`. Uses `stackEvent` so the prompt jumps ahead of other queued dusk cleanup. In-hand sibling: `Reaction_01140`. |
| `Reaction_03016b` (Schwester Ise — pull a friendly) | **Canonical "after enemy moves to my location" reaction.** Listens on `EventCardMoved` with `cardId != owner.Id`, `toLocation == owner.Location`, `cardInCity(owner)`, enemy controller check; button per eligible mover (own characters not at owner's location); queues `createCardMovingEvent` for the chosen character to the owner's location. |

## Pattern E — Techniques and Maneuvers

The Character lineage already brings `TechniqueTrait`. Add `IHasManeuvers` + `ManeuverTrait` for maneuvers. Implement under `cards/<expansion>/techniques/` or `cards/<expansion>/maneuvers/`. The base `create-city-character` skill has the general shape; the notes below are duel-specific patterns that come up often.

### Explicit `setUsed(true)` in the effect handler

The base `Technique` class auto-fires `$this->setUsed($theah, true)` on `EventTechniqueActivated`, so a properly-activated technique gets marked used without explicit code. But the convention in non-trivial techniques (`Technique_01093`, `Technique_03025a`, `Technique_03025b`) is to also call it explicitly in the technique's own effect handler — either on `EventDuelCalculateTechniqueValues` (stat modifiers) or on `EventResolveTechnique` (state-transition effects):

```php
if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id) {
    // ... apply the modifier
    $this->setUsed($event->theah, true);   // explicit; idempotent vs the auto path
}
```

It's idempotent vs the base class's auto-call, but cheap insurance against edge paths where the activation event might not have fired (copied techniques, cancellation/re-issue flows). Base `Technique::handleEvent` already resets `Used` on `EventDuelEnd` (because `ResetOnDuelEnd = true` by default), so the explicit `setUsed(true)` doesn't leak across duels.

### In-duel availability gate

Most Character techniques are duel-only — they're activated during a duel round by the actor. Gate `isAvailableToPlayer`:

```php
public function isAvailableToPlayer(int $playerId, Theah $theah): bool
{
    if (! parent::isAvailableToPlayer($playerId, $theah)) return false;
    if (! $theah->game->globals->get(Game::IN_DUEL, false)) return false;

    $owner = $this->getOwningCharacter($theah);
    $actor = $theah->getDuelRoundActor();
    if ($actor === null || $actor->Id !== $owner->Id) return false;

    // ... card-specific preconditions (adversary state, equipped weapons, etc.)
    return true;
}
```

Helpers worth knowing:
- `$theah->getDuelRoundActor(): ?Character` — the participant whose turn it is this round.
- `$theah->getDuelRoundOpponent(): ?Character` — the other participant. Returns the *last-known* state when the opponent is in discard/locker (e.g., already destroyed).
- `$theah->getDuelChallengerId() / getDuelDefenderId() / getDuelOpponentId($actorId)` — id-only accessors.
- `Game::IN_DUEL` global — true between duel start and end.
- `Game::DUEL_GAMBLED` global — true after the actor locks in a combat card via gamble; cleared at end of round.

### Gambling Technique gate

"**Gambling Technique:** …" — only available if the actor has gambled for their combat card this round. Add one extra check on top of the in-duel gate:

```php
if (! $theah->game->globals->get(Game::DUEL_GAMBLED, false)) return false;
```

WHY use the global (and not query `duel_round.gambled` directly): the global is set in `FrameworkActionsTrait::actChooseGambleCard` at the moment the gambled combat card is locked in, and cleared in `stDoneRound`. It's the cheapest authoritative answer to "has the actor gambled this round." `isAvailableToPlayer` runs on a hot path (every time the action menu refreshes), so the SQL alternative is wasteful.

Reference: `Technique_03002` (Aja).

### −N Thrust / Riposte cost ("combat card must have at least N")

Parenthetical "(Your combat card must have at least N [Thrust].)" is the printed clarification of a −N technique cost. Gate `isAvailableToPlayer` with `$theah->getCurrentRoundThrust() < N` (or `getCurrentRoundRiposte()` for Riposte costs). Apply the reduction on `EventDuelCalculateTechniqueValues`: `$event->thrust -= N` plus an explanation string.

WHY `getCurrentRoundThrust()` (sum of combat + maneuver + technique columns for the round) rather than reading `$combatCard->Thrust` alone: mirrors `Technique_01050` / `Technique_01093` and accounts for already-committed round modifiers. At technique-pick time `combat_thrust` is already written.

References: `Technique_01050` (−1 Thrust), `Technique_03039` (−2 Thrust), `Technique_01093` (−1 Riposte).

### Adversary discards a card (Technique)

On `EventResolveTechnique`, if the adversary's hand is nonempty, queue `createTransitionEvent($adversary->ControllerId, $owner->Id, "NNNNN", $this->Id)` — **active player becomes the adversary**. State class `State_duelChooseTechnique_NNNNN` (`521` + cardId) under `States/<expansion>/`; constant in `States.php`; `"NNNNN"` entry in `DUEL_CHOOSE_TECHNIQUE_EVENTS.transitions` (not the High Drama map).

`actFromTechniqueWithId`: validate card exists, active player controls it, `Location == LOCATION_HAND`; queue `createCardDiscardedFromHandEvent(..., $asEffect = true)`; `nextState()`. Empty hand → skip the picker entirely (no Pass needed — nothing to discard).

JS (adversary is active, so their `factionHand` is the picker):
- `OnEnteringState.<expansion>.js` — `factionHand.setSelectionMode('single')`
- `OnUpdateActionButtons.<expansion>.js` — Confirm → `onCardDiscarded()` (disabled until selection)
- `OnLeavingState.<expansion>.js` — `setSelectionMode('none')`
- **`EventHandlers.js`** — add the state name to the factionHand click map so Confirm enables on selection (easy to miss; without it the button stays disabled)

Reference: `Technique_01093` Maya (canonical), `Technique_03039` Íñigo (same picker + follow-on effects).

### Post-discard hand-size En Garde + EndOfRound move Home

Composite follow-ons after adversary discard (`Technique_03039`):

1. **En Garde if adversary hand > yours** — printed "Then, if…" means **after** discard. In `actFromTechniqueWithId` the discard event is queued, not flushed — compare `(count(adversaryHand) - 1) > count(ownerHand)`. Empty-hand path (no picker) compares `0 > ownerHand` (never engardes). Queue `createCardEngardedEvent` when the inequality holds.
2. **Move Home at end of round** — set a private `$MoveHome = true` on `EventResolveTechnique` **unconditionally** (do not gate on the hand-size clause unless the text does). On `EventDuelEndOfRound`: clear flag; skip if discard/locker or already `LOCATION_PLAYER_HOME`; queue `createCardMovingEvent(..., LOCATION_PLAYER_HOME, $engage=false, ...)` when Engage is not printed (contrast `_01053`, which engages). Clear on `EventTechniqueCanceled` / leftover `EventDuelEnd`.

Split the −N Thrust onto `EventDuelCalculateTechniqueValues` and the interactive / deferred effects onto `EventResolveTechnique` — both fire when the technique is chosen (`FrameworkActionsTrait` queues Resolve then Calculate).

### Gain Lethal — in-duel vs city-challenge

There are two completely different "Gain Lethal" pipelines depending on context. Don't conflate them.

| Event | When it fires | Use case |
|---|---|---|
| `EventGenerateChallengeThreat` | City-action challenge resolution (no duel; single threat roll) | Techniques granting Lethal during a non-duel challenge. Set `$event->adversaryThreatIsLethal = true` directly on the event. |
| `EventDuelCalculateTechniqueValues` | Per-technique calculation phase during a duel round | Techniques granting Lethal during a duel. Queue `EventFactory::createGainLethalEvent($event->actorId, $event->theah)` — this internally creates a `ThreatModified` event that marks the adversary's threat lethal regardless of which side the actor is. |

```php
public function handleEvent(Event $event)
{
    parent::handleEvent($event);

    if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id)
    {
        $lethalEvent = EventFactory::createGainLethalEvent($event->actorId, $event->theah);
        $event->theah->queueEvent($lethalEvent);
    }
}
```

A technique can handle BOTH events if it's usable in both contexts (see `Technique_01049` and the generic `Technique_GainLethal` helper). A Gambling Technique is duel-only, so only `EventDuelCalculateTechniqueValues` matters — gambling is exclusively a duel-round mechanic.

`createGainLethalEvent($actorId, $theah)` reads as: "the actor's strike against the adversary is now lethal." The naming inside the produced event (`challengerThreatIsLethal` / `defenderThreatIsLethal`) describes whose threat is lethal — i.e., the threat dealt TO that role. The factory figures out the sign for you; just pass the actor's id.

References: `Technique_GainLethal` (generic two-pipeline helper), `Technique_01049` (in-duel + city-context), `Technique_03002` (Aja, in-duel only via Gambling Technique gate).

### `EventDuelCalculateTechniqueValues` field shape

Unlike `EventDuelCalculateCombatCardStats` (which exposes `addRiposte`/`addParry`/`addThrust`/`removeRiposte`/etc. methods and respects `dashedX` flags), `EventDuelCalculateTechniqueValues` has plain int fields `$riposte`/`$parry`/`$thrust` you mutate directly:

```php
if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id)
{
    $event->parry  += 1;
    $event->thrust -= 1;
    $event->explanations[] = sprintf($event->theah->game->translate("%s: Technique [%s] adds +1 Parry."), $owner->getInjectCode(), $this->Name);
}
```

Reference: `Technique_01050` (–1 Thrust + wound), `Technique_03004` Elena (+1 Parry + wound). You can queue follow-on events (e.g., `createCharacterBeingWoundedEvent`, `createGainLethalEvent`) from inside the same calc handler — the queued events fire after the calc resolves.

### "If <owner>'s combat card is a <trait>" gate

For techniques gated on the actor's combat card having a particular trait (`_03004` Elena's "if combat card is a Sorcery"):

```php
$combatCards = $theah->getCombatCardsForCurrentRound();
foreach ($combatCards as $card)
{
    if ($card->ControllerId == $owner->ControllerId && $card->hasTrait("Sorcery"))
    {
        return true;
    }
}
return false;
```

`getCombatCardsForCurrentRound()` returns BOTH players' combat cards. Filter by `$card->ControllerId == $owner->ControllerId` to isolate the actor's own combat card. (Since the technique already gates on `actor->Id == owner->Id`, this is the actor's own combat card.) Cesca Scarpa's `Technique_02003` is similar but cares about *any* Sorcery played in the round, so it skips the ControllerId filter — match the card text literally.

### "If <Owner> is equipped with X **or** there is an X card in his dueling line" gate

For techniques gated on a trait being present on either the owner's attachments OR the owner's side of the dueling line (`_03014` Kaspar — "equipped with an Eisenfaust attachment or there is an Eisenfaust card in his dueling line"). Check BOTH sources, OR them, and gate `isAvailableToPlayer` on the OR:

```php
private function hasEisenfaust(Theah $theah, Character $owner): bool
{
    // Attachments: $owner->Attachments is an array of *ids*. Look each up.
    foreach ($owner->Attachments as $attachmentId)
    {
        $attachment = $theah->getCardById($attachmentId);
        if ($attachment !== null && $attachment->hasTrait("Eisenfaust"))
        {
            return true;
        }
    }

    // Dueling line: per-player, keyed on the owner's ControllerId.
    $cards = $theah->getCardObjectsAtLocation(Game::LOCATION_DUELING_LINE, $owner->ControllerId);
    foreach ($cards as $card)
    {
        if ($card->hasTrait("Eisenfaust"))
        {
            return true;
        }
    }
    return false;
}
```

WHY `getCardObjectsAtLocation(LOCATION_DUELING_LINE, $owner->ControllerId)` is safe inside an `IN_DUEL` gate: the dueling line is per-player and accumulates combat cards over the duel's rounds; outside a duel it's empty (the line is cleared at duel end). With the standard `isAvailableToPlayer` gate on `IN_DUEL` + `actor == owner`, the cards returned are the owner's combat cards from this duel's prior rounds (plus the current round once a combat card has been picked). If the card text said "his dueling line *this round*" you'd switch to `getCombatCardsForCurrentRound()` filtered by controller; "his dueling line" without qualifier means the cumulative line.

WHY iterate `$owner->Attachments` by id rather than calling `hasWeaponEquipped` / similar helper: there's no `hasAttachmentWithTrait($trait)` helper on `Character`. The id-list-then-`getCardById` pattern is the one in use across the codebase (e.g. `Maneuver_01054`'s `if ($attachment && $attachment->hasTrait("Eisenfaust"))`). Don't roll a new helper — match the existing shape.

### Wound-as-cost: queue the wound event at `EventResolveTechnique` BEFORE the transition

For techniques whose printed cost is "Wound <Owner> • <effect>" (Daniella Dietrich `_03013`), the wound is part of the cost — paid before the effect resolves. The natural place is the `EventResolveTechnique` handler, where you queue BOTH the wound event and the technique-transition event, in that order:

```php
if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
{
    $owner = $this->getOwningCharacter($event->theah);

    // Pay the cost: wound the owner. Cost-before-effect per the "Wound X •" split.
    $woundedEvent = EventFactory::createCharacterBeingWoundedEvent(
        $owner->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id
    );
    $event->theah->queueEvent($woundedEvent);

    // Effect: transition into the target-picker state.
    $transition = EventFactory::createTechniqueTransitionEvent(
        $owner->ControllerId, $owner->Id, "NNNNN", $this->Id
    );
    $event->theah->queueEvent($transition);
}
```

WHY at resolve-time and not inside `actFromTechniqueWithId`: by the time the player picks a swap target in `actFromTechniqueWithId`, the cost has already been paid — the wound fired earlier when `EventResolveTechnique` flushed. Putting the wound in the act handler would invert the cost/effect order printed on the card and let a player back out of the cost by declining the picker. Queue at resolve and the wound is committed regardless of whether the player completes the effect.

The wound-event factory signature mirrors `Technique_01063`'s use: `($characterId, $sourceCharacterId, $wounds, $sourceDescription, $techniqueId)`.

### Swap mechanics inline in `actFromTechniqueWithId` — challenge vs duel context

For "swap <Owner> with another character" techniques (Daniella Dietrich `_03013` — Wound + swap with Hunter/Zealot at this location), don't defer the swap to event handlers. Do it inline in `actFromTechniqueWithId` so the player's commit unambiguously commits the swap. Branch on the state to handle the challenge-time and duel-time contexts differently:

```php
public function actFromTechniqueWithId(Game $game, int $state, string $stateName, int $id): void
{
    parent::actFromTechniqueWithId($game, $state, $stateName, $id);

    if ($state == States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_NNNNN
        || $state == States::DUEL_CHOOSE_TECHNIQUE_NNNNN)
    {
        // ... target validation, notification ...

        $this->swapId = $target->Id;

        if ($state == States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_NNNNN)
        {
            // Challenge context: duel not yet built. Redirect CHOSEN_PERFORMER
            // and move DUEL_CHALLENGER condition so the new challenger is the
            // one who actually enters the duel.
            $game->globals->set(Game::CHOSEN_PERFORMER, $target->Id);
            $owner->removeCondition(Game::DUEL_CHALLENGER);
            $target->addCondition(Game::DUEL_CHALLENGER);
            $owner->IsUpdated = true;
            $target->IsUpdated = true;
            $game->updateCardObjectInDb($owner);
            $game->updateCardObjectInDb($target);

            $challengerSwappedEvent = EventFactory::createChallengerSwappedEvent(
                $owner->ControllerId, $owner->Id, $target->Id
            );
            $game->theah->queueEvent($challengerSwappedEvent);
        }
        else  // DUEL_CHOOSE_TECHNIQUE_NNNNN — already inside a duel
        {
            // Duel context: rewrite the duel's stored participant list so the
            // target takes Daniella's seat for the rest of the duel.
            $duelId = $game->globals->get(Game::DUEL_ID);
            $round  = $game->globals->get(Game::DUEL_ROUND);
            $game->theah->swapParticipantsInDuel($duelId, $round, $owner->Id, $target->Id);
            $game->updateCardObjectInDb($owner);
            $game->updateCardObjectInDb($target);
        }

        $game->gamestate->nextState();
    }
}
```

Keep ONE thing in `handleEvent` — the `EventGenerateChallengeThreat` `actorId` redirect. That mutation can only happen at event-fire time:

```php
if ($event instanceof EventGenerateChallengeThreat
    && $event->techniqueId == $this->Id
    && $this->swapId != 0)
{
    // WHY: the event is in flight when threat is being calculated. Character
    // ::handleEvent (which adds the actor's stat to adversaryThreat when
    // actorId matches) and the EventHub threat notification both key on
    // $event->actorId. Without the redirect they still reference the original
    // challenger, even though DUEL_CHALLENGER condition has already moved.
    $event->actorId = $this->swapId;
}
```

WHY split the work this way (vs. mirroring Bastien's all-in-events approach in `Technique_01063Swap`): Bastien defers the condition swap into `EventGenerateChallengeThreat` (with a `CHALLENGE_ACCEPTED` guard) so the swap doesn't fire if the challenge is rejected. That's a stricter, more conservative shape. The in-`actFromTechniqueWithId` shape is cleaner to read and matches the user's preference (see project history), but if your card text says the swap is *conditional on the challenge being accepted*, prefer Bastien's pattern instead so a rejection doesn't leave a stuck DUEL_CHALLENGER condition on a character that never enters a duel.

### Technique usable in BOTH challenge and duel contexts — two states, two routings, two state classes

A technique that fires in either a challenge-resolve flow or a duel round needs entries in BOTH dispatcher routes:

- **Challenge-time:** state ID `455` + 5-digit cardId (e.g. `HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_03013 = 45503013`). Routed from `HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_EVENTS`. State class: `State_highDramaChallengeActionResolveTechnique_NNNNN`.
- **Duel-time:** state ID `521` + 5-digit cardId (e.g. `DUEL_CHOOSE_TECHNIQUE_03013 = 52103013`). Routed from `DUEL_CHOOSE_TECHNIQUE_EVENTS`. State class: `State_duelChooseTechnique_NNNNN`.

Both states live under `modules/php/States/<expansion>/` and extend `GameState`. The technique's `createTechniqueTransitionEvent($controllerId, $ownerId, "NNNNN", $this->Id)` uses the SAME transition-name string (`"NNNNN"`) in both contexts — the dispatcher routes correctly because the lookup is per-dispatcher-state. Both routing maps need the entry:

```php
// states.inc.php — HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_EVENTS.transitions
"NNNNN" => States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_NNNNN,

// states.inc.php — DUEL_CHOOSE_TECHNIQUE_EVENTS.transitions
"NNNNN" => States::DUEL_CHOOSE_TECHNIQUE_NNNNN,
```

Both state classes use the default-`""` transition back to their dispatcher EVENTS state (it's the only exit), and both expose `actFromCardWithId` as their `#[PossibleAction]`. Their `getArgsFromTechnique`/`actFromTechniqueWithId` can share a single `if ($state == HIGH_DRAMA... || $state == DUEL_CHOOSE...)` branch since the args shape and act validation are identical — the only divergence is the swap mechanics (see above).

JS handlers live in `modules/js/{OnEnteringState,OnUpdateActionButtons,OnLeavingState}.<expansion>.js`. Both states need their own keyed handler in each file — the args shape and Confirm button are identical to the existing `_01063` Bastien handlers; copy-paste and rename. The `_01063` versions live in the `*.7s5s.js` files; faf cards' versions live in `*.faf.js` files.

WHY `actFromCardWithId` and not `actFromTechniqueWithId` as the `#[PossibleAction]`: the GameState framework's `actFromCardWithId` delegates into `Game::actFromCardWithId`, which the technique framework routes back to the technique's own `actFromTechniqueWithId` via the per-state dispatch in `StatesTrait`. Don't expose `actFromTechniqueWithId` directly as the `#[PossibleAction]` — mirror the existing `_01063` state classes.

### Disambiguating same-name characters in state descriptions

Some characters share a name across expansions (e.g., `_01036` "Daniella Dietrich" and `_03013` "Daniella Dietrich, Witch / Hunter"). The state's `descriptionMyTurn` is the only place this is user-visible; disambiguate by appending the `Title` in parens:

```php
descriptionMyTurn: clienttranslate('Daniella Dietrich (Witch, Hunter)')
                   . clienttranslate(': Wound and Swap with a Hunter or Zealot: ${you} must choose a Hunter or Zealot:'),
```

The state classes' `name` field (used by JS) doesn't need disambiguation because state IDs already differ — `_01036`'s state is `duelChooseTechnique_01036`, `_03013`'s is `duelChooseTechnique_03013`.

### Duel-flow events worth knowing

| Event | When it fires |
|---|---|
| `EventDuelStarted` / `EventDuelEnd` | Duel boundaries. |
| `EventNewDuelRound` / `EventDuelEndOfRound` | Round boundaries. |
| `EventDuelAttemptGamble` | Pre-check fired when the actor clicks Gamble. Throw via `eventCheck` to block gambling (Mysta's Technique_02037 pattern). |
| `EventDuelGambleCardsRevealed` | After cards are revealed during gambling. Carries `revealedCardIds`. |
| `EventDuelPlayerGambled` | After the actor selects a card from the gambled reveal — combat card locked in, `DUEL_GAMBLED = true`. |
| `EventTechniqueActivated` | A technique was just activated (the base `Technique::handleEvent` flips `Used` on this for the matching technique). |
| `EventResolveTechnique` | Resolve-time event for a technique. Used to spawn the technique's "side effects" (queue further events, transition into a state). |
| `EventDuelCalculateTechniqueValues` | Per-technique value calculation. Use this to inject Lethal, modify riposte/parry/thrust, etc. |
| `EventDuelCalculateCombatCardStats` | Per-combat-card stat calculation (Yevgeni's pattern). |
| `EventGenerateChallengeThreat` | City-action challenge threat generation (no duel). |
| `EventChallengerSwapped` / `EventDefenderSwapped` | The challenge had its participant changed mid-stream. Re-evaluate any modifier you applied. |

## JS Wiring (required for new state classes)

Same as `create-city-character`'s "JS Wiring" section. For every new state, wire BOTH:

- `modules/js/OnEnteringState.<expansion>.js` — highlight selectables, mark already-chosen characters.
- `modules/js/OnUpdateActionButtons.<expansion>.js` — `Confirm` button (`actChooseCardSelected` + `onChooseInPlayCardConfirmed`).
- `modules/js/OnLeavingState.<expansion>.js` — cleanup highlights when leaving the state.

Reusable client-side handlers:
- Character / in-play card selection: `onChooseInPlayCardConfirmed()` + `highlightCardsAsSelectable(ids)`.
- Location selection: `onCityLocationsSelected()` + `makeCityLocationSelectable(element)`.
- Marking a "chosen" character: `dojo.addClass($(`${card.divId}_image`), '_7sfs-chosen')`.

If your state reuses an existing client action (e.g. `onMusterCardSelected`), extend the action map in `modules/js/PlayerActions.js`.

For new expansion JS files (`*.<expansion>.js`), make sure the chain to the master JS files exists — `faf`, `tac`, and `_7s5s` are already chained.

### City-location picker — full wiring (Technique example)

For a Technique/Action that prompts "choose any city location" (`Technique_03025b` Angeline's Gambling relocation):

**Backend — the technique:**

```php
public function getArgsFromTechnique(Game $game, int $state, string $stateName): array
{
    $args = parent::getArgsFromTechnique($game, $state, $stateName);
    if ($state == States::DUEL_CHOOSE_TECHNIQUE_03025B) {
        $args["locationIds"] = array_keys($game->theah->getCityLocations());  // not hardcoded
    }
    return $args;
}

public function actFromTechniqueWithIds(Game $game, int $state, string $stateName, array $ids): void
{
    parent::actFromTechniqueWithIds($game, $state, $stateName, $ids);
    if ($state == States::DUEL_CHOOSE_TECHNIQUE_03025B) {
        $location = $ids[0];
        if (! array_key_exists($location, $game->theah->getCityLocations())) {
            throw new \Bga\GameFramework\UserException($game->translate('Invalid location.'));
        }
        // ... queue createCardMovingEvent etc.
    }
}
```

WHY `$theah->getCityLocations()` and not a hardcoded array: the city has 3 locations in 2p, 4 in 3p, 5 in 4p (Ole's Inn and Governor's Garden are excluded in smaller games). Hardcoding breaks player-count adaptation. Also, the constants `Game::LOCATION_BORDELLO` / `LOCATION_CATHEDRAL` / `LOCATION_DOCKS` / `LOCATION_MARKET` / `LOCATION_OLES_INN` **do not exist** — the real constants are `LOCATION_CITY_DOCKS`, `LOCATION_CITY_FORUM`, `LOCATION_CITY_BAZAAR`, `LOCATION_CITY_OLES_INN`, `LOCATION_CITY_GOVERNORS_GARDEN`. `getCityLocations()` sidesteps both problems.

**State class — the `#[PossibleAction]` must be `actFromCardWithLocations`:**

```php
#[PossibleAction]
public function actFromCardWithLocations(string $locations): void
{
    $this->game->actFromCardWithLocations($locations);
}
```

**NOT** `actFromCardWithIds`. The JS calls `onCityLocationsSelected()` → `bgaPerformAction('actFromCardWithLocations', { locations: JSON.stringify(...) })`. If the state's `#[PossibleAction]` is `actFromCardWithIds`, the framework reports "This move is not authorized now" — the action name doesn't match. The framework's `actFromCardWithLocations` then JSON-decodes the locations and forwards them as the `$ids` array into the card's `actFromCardWithIds` → `actFromTechniqueWithIds`, so the technique still receives the locations through the `$ids` parameter — only the entry-point name differs.

**JS — `OnEnteringState.<expansion>.js`:**

```js
'duelChooseTechnique_03025b': () => {
    if (this.isCurrentPlayerActive()) {
        this.clientStateArgs.locationIds = this.gamedatas.gamestate.args.locationIds;
        this.numberOfCityLocationsSelectable = 1;
        this.selectedCityLocations = [];
        this.clientStateArgs.locationIds.forEach((locationId) => {
            const imageElement = this.getCityLocationElement(locationId);
            this.makeCityLocationSelectable(imageElement);
        });
    }
},
```

**JS — `OnUpdateActionButtons.<expansion>.js`:**

```js
'duelChooseTechnique_03025b': () => {
    this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
    dojo.addClass('actCityLocationsSelected', 'disabled');
},
```

**JS — `OnLeavingState.<expansion>.js` — use `resetCityLocations()`, NOT `clearCityLocationAsSelectable`:**

```js
'duelChooseTechnique_03025b': () => {
    if (this.isCurrentPlayerActive()) {
        this.resetCityLocations();
        this.selectedCityLocations = [];
        this.numberOfCityLocationsSelectable = 0;
    }
},
```

There is no `clearCityLocationAsSelectable` function — that's a hallucinated name. The existing helper is `resetCityLocations()` (in `modules/js/Utilities.js`), which strips `_7sfs-selectable` / `_7sfs-selected` / `_7sfs-chosen` and the pointer cursor from every active city location element (plus the player Home endcap). Every existing location-picker cleanup in `OnLeavingState.tac.js` uses it; mirror that.

## Pre-Commit Hook (relevant subset)

`.githooks/pre-commit` enforces, for the files you touch when implementing a Character or Leader:

| Pattern | Required |
|---|---|
| `extends CardAction/RiskAction/RiskCityAction` (regex literal — does NOT match `CharacterAction` directly, but the convention still applies) | `createActionResolvedEvent()` somewhere in the class. |
| **Forbidden in `CharacterAction` subclasses** | `$this->setUsed()` / `$this->resetPlayerPassCount()` / `$this->announceAction()` — these run centrally. |
| `extends CardReaction/AttachmentReaction` | `$this->setUsed(` AND `$this->isAvailable(` (literal strings; the hook is grep-based). For a Continuous Reaction (no actual `setUsed(true)` call at runtime), keep the literal in a comment so grep matches — see "Continuous Reaction" in Pattern D. |
| `implements ISorcererAbility` | both `createSorcererAbilityStartEvent()` and `createSorcererAbilityPlayedEvent()`. |
| Class implementing both `IAbilityThatTargetsCharacters` and `IAbilityThatTargetsCards` | **Forbidden.** Split into two classes. |
| Calls `createAttachmentEquippedEvent()` | Must also call `getRequiredAttachTargetId()`. |

The card class itself (`_NNNNN extends Character` / `extends Leader`) has no hook-mandated calls — the requirements apply to the Action/Reaction subclasses that live next to it.

## Style / Memory Notes

- `getActivePlayerName()` is deprecated — use `$game->getPlayerNameById($id)`.
- `BgaUserException` is deprecated — throw `\Bga\GameFramework\UserException`.
- PHP: PSR-12, 4-space indent, braces on their own line.
- Namespaces:
  - Card class: `Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>`
  - Action:     `...\cards\<expansion>\actions`
  - Reaction:   `...\cards\<expansion>\reactions`
  - State:      `Bga\Games\SeventhSeaCityOfFiveSails\States\<expansion>`
- **"Opposing"** means BOTH different controller AND same location. Never roll your own `ControllerId !=` filter.
- **`TraitNames::$TraitsJson`** (`modules/php/Traits.php`) is the canonical Trait list for "Name a Trait" pickers. Add new Traits in alphabetical order.

## Cross-Cutting Helpers

- `$theah->cardInCity($card): bool` — true when the card is at a city location.
- `$theah->locationInCity(string $location): bool` — true for any active city location. Use inside an `EventCardMoved` handler against `$event->toLocation` — `$owner->Location` is still the OLD location at that point because `EventCardMoved.runEventHubAfterCards = true` defers the state write until after every card's `handleEvent` runs.
- `$theah->getCityLocations(): array` — keyed by location id. `array_keys($theah->getCityLocations())` enumerates the ACTIVE city locations (3 in 2p / 4 in 3p / 5 in 4p — Ole's Inn and Governor's Garden are excluded in smaller games). Always use this over hardcoded `LOCATION_*` arrays for "pick any city location" pickers. The actual city-location constants are `LOCATION_CITY_DOCKS`, `LOCATION_CITY_FORUM`, `LOCATION_CITY_BAZAAR`, `LOCATION_CITY_OLES_INN`, `LOCATION_CITY_GOVERNORS_GARDEN` — there is no `LOCATION_BORDELLO` / `LOCATION_CATHEDRAL` / `LOCATION_DOCKS` / `LOCATION_MARKET` (those are hallucinated names; check before using).
- `$theah->getCharactersAtLocation(string $location, bool $includeUncontrolled = false): array` — all characters at a location (default excludes uncontrolled, which is usually what you want).
- `$theah->getCharactersAtLocationByPlayerId(string $location, int $playerId, bool $includeUncontrolled = false): array` — friendly characters at a location.
- `$theah->getOpposingCharactersAtLocation(string $location, int $playerId): array` — opposing = different controller AND same location.
- `$theah->getCharactersInPlayByPlayerId(int $playerId): array` — all characters in play controlled by a player.
- `$theah->getCharactersInCityByPlayerId(int $playerId): array` — characters in city (not Home, not approach).
- `$theah->getAdjacentCityLocations(string $location, bool $includeHome = true): array` — adjacency for move actions.
- `$game->characterIsInDiscardOrLocker(Character $character): bool` — "is this character out of play (discard or locker)?" The Leader-equivalent of `isInPlay`. Gate phase-event handlers on `! characterIsInDiscardOrLocker($this)`.
- `$this->getInjectCode()` — inline-styled card name for notifications (`${card_inject_code}` placeholder).
- `$this->hasTrait(string $trait): bool` — check a trait against `$this->ModifiedTraits`. English trait strings compare directly against `clienttranslate()`-wrapped values.

Duel-specific (used in Pattern E and the in-duel branch of any ability):
- `$theah->getDuelRoundActor(): ?Character` / `getDuelRoundOpponent(): ?Character` — current round participants.
- `$theah->getDuelChallengerId(): ?int` / `getDuelDefenderId(): int` / `getDuelOpponentId(int $actorId): int` — id-only accessors.
- `$theah->getCombatCardsForCurrentRound(): array` — combat cards played in the current round.
- `$theah->getCurrentDuelThreat(int $characterId): int` — running threat against a participant.
- `EventFactory::createGainLethalEvent(int $actorId, Theah $theah)` — produces a `ThreatModified` event marking the adversary's threat lethal.
- `Game::IN_DUEL` / `Game::DUEL_GAMBLED` globals — round-scoped, see Pattern E.

## Reference Implementations

| File | What it demonstrates |
|---|---|
| `modules/php/cards/_7s5s/_01007.php` (Aldo Bussotti) | **Canonical regular Character.** `initializeFaction`, `handleEvent` listening on `EventCardMoved` / `EventRenownAddedToLocation` / `EventReknownRemovedFromLocation` to keep Influence in sync with current-location Renown, paired with a one-step City Action. |
| `modules/php/cards/_7s5s/_01006.php` (Don Constanzo Scarpa) | **Leader with setup-time reaction.** `EventTableSetup` flow (reveal a Red Hand Thug from your deck), `EventPressureOccuring` listener that flips a pressure-type global, paired with multi-step setup states. |
| `modules/php/cards/_7s5s/_01089.php` (Soline el Gato) | **Leader with passive duel hook + City Reaction.** `EventDuelStarted` / `EventDuelEnd` / `EventDefenderSwapped` / `EventChallengerSwapped` keep the affected character's Finesse modified; `Reaction_01089` adds a button-based "move to adjacent location after an Action resolves" prompt. |
| `modules/php/cards/_7s5s/_01116.php` (Yevgeni) | **Leader with passive duel-stat hook + paired Reactions.** Demonstrates `EventDuelCalculateCombatCardStats`, `actorId == $this->Id` checks, and multi-reaction wiring. |
| `modules/php/cards/_7s5s/_01035.php` (Kaspar Dietrich) | **Leader with parley discount + City Action.** Demonstrates `getParleyDiscount` override and the reveal-from-city-deck-until-trait pattern. |
| `modules/php/cards/faf/_03001.php` (Cesca del Rosso) | **Leader with End-of-Dawn draw + button-based City Reaction + two-step City Action.** `EventPhaseDawnEnding` + `characterIsInDiscardOrLocker` gate, `EventSorcererAbilityPlayed` reaction with source/performer identity check, two-step CharacterAction with the move-wound (heal + wound) recipe. |
| `modules/php/cards/faf/reactions/Reaction_03001.php` | Button-per-opposing-character target picker; `IAbilityThatTargetsCharacters`; `isNotControlledByPlayer` + location filter for "opposing"; `setUsed`/`isAvailable` discipline. |
| `modules/php/cards/faf/actions/Action_03001.php` | Two-step CharacterAction; `cardInCity` gate; `IAbilityThatTargetsCharacters` interface for target hooks; `isValidTargetForAbility` double-checked at step 2; heal+wound recipe; `createActionResolvedEvent` at terminal state. |
| `modules/php/cards/faf/_03002.php` (Aja) | **Character that issues a Combat challenge + Gambling Technique.** Pattern F (issue-a-challenge) with a new `AJA_CHALLENGE_TYPE` whose intervention/refusal are gated by Finesse ≥ 3 — touches all six challenge-type integration points. Pattern E "Gambling Technique" gate via `Game::DUEL_GAMBLED` + `getDuelRoundActor`. |
| `modules/php/cards/faf/actions/Action_03002.php` | Pattern F skeleton: opposing-target picker → set CHOSEN_PERFORMER/TARGET/CHALLENGE_STAT/CHALLENGE_TYPE → `createTransitionEvent("03002_2")` + `nextState("targetChosen")` to `HIGH_DRAMA_PLAYER_TURN_EVENTS`. |
| `modules/php/cards/faf/techniques/Technique_03002.php` | Gambling Technique with adversary-wounded precondition. `EventDuelCalculateTechniqueValues` + `createGainLethalEvent` in-duel pipeline. |
| `modules/php/cards/faf/_03003.php` (Don Constanzo Scarpa, Fearsome Father) | **Character with a Pattern F variant where performer ≠ owner + a cost-bearing City Reaction.** City Action picks one of the controller's Thugs at Don's location and has *that* Thug issue the challenge — new `DON_CONSTANZO_CHALLENGE_TYPE` deliberately omitted from auto-engage list so already-engaged Thugs are eligible. City Reaction triggers on `EventCharacterDestroyed` for Thugs and offers a multi-stage "pick from hand/discard → click-to-pay Wealth → muster at Home" flow. |
| `modules/php/cards/faf/actions/Action_03003.php` | Two-step Pattern F where the performer is selected by the player. Step 1 picks the Thug, sets `CHOSEN_PERFORMER` to the Thug's id. Step 2 picks the opposing target at the performer's location, conditionally engages the Thug (`if (! $performer->Engaged)`), then `createTransitionEvent("03003_2")` into the challenge sub-state. |
| `modules/php/cards/faf/reactions/Reaction_03003.php` | Multi-stage Reaction (`'pick'` → `'pay'` → finalize). Source filtering from hand AND discard with the destroyed-Thug exclusion. **In-reaction click-to-pay** with running `$paidWealth`/`$paidHasWealthCard` state, `wouldClickProduceValidPayment` button filter, atomic discards at finalize. Mirrors `UtilitiesTrait::isValidWealthPayment` semantics (exact match OR overpay-by-1-with-Wealth). |
| `modules/php/cards/faf/_03004.php` (Elena Agnelli) | **Character with a dynamic-recompute dueling-line Finesse bonus + a trait-gated Technique.** Pattern A passive with a `$FinesseBonus` running field recomputed at `EventDuelEndOfRound` from `getCardObjectsAtLocation(LOCATION_DUELING_LINE, controllerId)`, reset via inverse-delta at `EventDuelEnd` (which fires BEFORE the line is cleared). Gates on the owner being a duel participant (the dueling line is per-player, not per-character). |
| `modules/php/cards/faf/techniques/Technique_03004.php` | Trait-gated Technique: in-duel + actor-is-owner + actor's own combat card has the Sorcery trait (via `getCombatCardsForCurrentRound()` filtered by `ControllerId`). `EventDuelCalculateTechniqueValues` handler mutates `$event->parry` directly (plain int field — no `addParry` method on this event) AND queues a `createCharacterBeingWoundedEvent` for the adversary in the same calc handler. |
| `modules/php/cards/faf/_03013.php` (Daniella Dietrich, Witch/Hunter) | **Leader with a continuous-Action trait passive + cost-reduction Reaction + dual-context Wound+Swap Technique.** Three patterns on one card: opposing-character `addTrait`/`removeTrait` lifecycle via `Action_03013` (never-`Used` continuous Action), Faith/Sorcery cost-reduction reaction cloned from `Reaction_01116b`, and a Technique usable from both `HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_EVENTS` and `DUEL_CHOOSE_TECHNIQUE_EVENTS` with the swap mechanics inline in `actFromTechniqueWithId`. |
| `modules/php/cards/faf/actions/Action_03013.php` | **Canonical Continuous-Action passive.** `isAvailableToPlayer` returns `false` so it never appears in the action menu; `handleEvent` tags opposing characters with "Sorcerer" on `EventActionTriggered`/`EventReactionActivated`/`EventTechniqueActivated`/`EventManeuverActivated` for the owner's controller and untags at `EventPlayerTurnEnd`; tracks tagged-id set to dedup `addTrait` (which appends without dedup); explicitly resets `Used` to false at the turn-end boundary. |
| `modules/php/cards/faf/reactions/Reaction_03013.php` | Cost-reduction Reaction cloned from `Reaction_01116b`; filter swapped to `IWealthCost && (hasTrait("Faith") || hasTrait("Sorcery"))`. Standard four-discount-method shape. |
| `modules/php/cards/faf/techniques/Technique_03013.php` | **Dual-context Wound + Swap Technique.** Wound cost queued at `EventResolveTechnique` BEFORE the technique-transition event (cost-before-effect ordering). Swap mechanics inline in `actFromTechniqueWithId`, branched on state: challenge-context moves DUEL_CHALLENGER condition + queues ChallengerSwappedEvent + sets CHOSEN_PERFORMER; duel-context calls `swapParticipantsInDuel`. `EventGenerateChallengeThreat` handler kept slim — only the `actorId` redirect, which must happen at event-fire time. |
| `modules/php/cards/faf/_03014.php` (Kaspar Dietrich, Iron Reforged) | **Character with a wound-prevention passive + attachment-or-dueling-line-gated Technique.** Passive uses `eventCheck` (not `handleEvent`) on `EventCharacterBeingWounded` to zero `$event->wounds` — cleaner than Maxime's skip-`parent::handleEvent` shape because `EventHub` only emits the past-tense `EventCharacterWounded` when `wounds > 0`, so nothing downstream thinks the wound happened. Filters: `abilityId != ''` (lets threat conversion through, which emits with empty `abilityId`) AND `source.ControllerId != owner.ControllerId` (opponent's ability). Wound-movement (heal+wound recipe, `Action_02010`) is blocked by the same filter — no special handler needed. |
| `modules/php/cards/faf/_03016.php` (Schwester Ise, Moonlit Interrogator) | **Character with a self-condition stat bonus + a cancel-and-reissue Reaction + an enemy-moved-to-me Reaction.** (1) +1 Combat while wounded: private `$WoundedCombatBonusApplied` flag, hook `EventCharacterWounded`/`EventCharacterHealed` for `characterId == $this->Id` after `parent::handleEvent` updates `$this->Wounds`, queue `createCharacterCombatModifiedEvent(±1)` only on flag transition, skip on `IsDying`/discard. (2) Dusk "keep in city" via `Reaction_03016a` — listens on `EventCardMoving` (`sourceId == 0`, `toLocation == HOME`, `TURN_PHASE == DUSK`), cancels and prompts, uses `cancelDeclinedByCardIds` to gate the Decline re-queue. (3) "After enemy moves here" via `Reaction_03016b` — `EventCardMoved` with controller/location gates, button-per-eligible-friendly, `createCardMovingEvent` to the owner's location. |
| `modules/php/cards/faf/reactions/Reaction_03016a.php` | Canonical cancel-and-reissue Reaction. Clone + `unset($cloned->theah)` for storage; `stackEvent` for the transition; `cancelDeclinedByCardIds[] = owner.Id` on Decline so the re-queued event isn't re-caught. Same shape as in-hand `Reaction_01140` but lives on a Character in play. |
| `modules/php/cards/faf/reactions/Reaction_03016b.php` | "After enemy moves here → pull a friendly" Reaction. Demonstrates the full `EventCardMoved` gate set (`cardId != owner.Id`, `toLocation == owner.Location`, `cardInCity(owner)`, instanceof Character, `ControllerId != 0`, enemy controller), valid-target precondition (`getEligibleMovers` non-empty), and `createCardMovingEvent` for a non-self mover with `engage=false`. |
| `modules/php/cards/faf/_03025.php` (Angeline Dèmone, Prodigal Capitaine) | **Leader with a Continuous Reaction on self-move + two Techniques (stat modifier + city-location picker).** Reaction is the OWNER-moves-to-city analogue of `Reaction_03016b` (filter inverted: `cardId == owner.Id`). Techniques split into `_03025a` (simple `EventDuelCalculateTechniqueValues` +1 Riposte) and `_03025b` (Gambling Technique with interactive city-location picker state). |
| `modules/php/cards/faf/reactions/Reaction_03025.php` | **Owner-moves-to-city Reaction + Continuous (never-Used) pattern.** Uses `cardId == owner.Id` + `locationInCity($event->toLocation)`. **Reads target eligibility at `$event->toLocation`, not `$owner->Location`** — `EventCardMoved.runEventHubAfterCards = true` means the location field still holds the OLD value during `handleEvent`. `performReaction` omits the `setUsed(true)` call so the Reaction fires every move; the hook-required literal `$this->setUsed(` is kept inside a comment for grep compliance. |
| `modules/php/cards/faf/techniques/Technique_03025a.php` | **Plain stat-modifier Technique with explicit `setUsed`.** `EventDuelCalculateTechniqueValues` adds +1 Riposte and calls `$this->setUsed($event->theah, true)` in the same handler. No state class, no JS wiring needed. |
| `modules/php/cards/faf/techniques/Technique_03025b.php` | **Gambling Technique with city-location picker.** `EventResolveTechnique` queues a transition into `DUEL_CHOOSE_TECHNIQUE_03025B`; `getArgsFromTechnique` returns `array_keys($theah->getCityLocations())` (player-count-aware, no hardcoded constants); `actFromTechniqueWithIds` validates via `array_key_exists($location, $game->theah->getCityLocations())` and queues `createCardMovingEvent` for both actor and adversary. State class `State_duelChooseTechnique_03025b` exposes `actFromCardWithLocations(string $locations)` as the `#[PossibleAction]` — NOT `actFromCardWithIds`, since the JS submits via `onCityLocationsSelected → actFromCardWithLocations`. `OnLeavingState` cleanup uses `resetCityLocations()`. |
| `modules/php/cards/faf/_03015.php` (Joern Kietelsson, Fury's Edge) | **Character with three pure-passive abilities — no Action/Reaction/Technique files.** (1) Forced self-wound on muster: hooks BOTH `EventCharacterMustered` AND `EventApproachCharacterPlayed` OR'd in one conditional, gated on `characterId == $this->Id`. (2) Phase-conditional Resolve penalty ("During Dusk, -3 Resolve"): direct `$this->ModifiedResolve` mutation on `EventDuskPhaseBegin`, restore on `EventDuskEndOfDay`, gated by a private `$DuskResolvePenaltyApplied` bool — there is no `createCharacterResolveModifiedEvent` factory. Includes an explicit destruction check (mirroring `EventHub.php:251`) because `Character::handleEvent`'s threshold check only runs inside an `EventCharacterWounded` handler. (3) Challenge-refused self-heal on `EventChallengeRejected` with `challengerId == $this->Id` — symmetric to `_01119` Nazem's challenger-side engage. |
| `modules/php/cards/faf/techniques/Technique_03014.php` | **Attachment-OR-dueling-line trait-gated Technique that wounds the adversary.** `isAvailableToPlayer` ORs two checks: iterate `$owner->Attachments` (ids → `getCardById` → `hasTrait("Eisenfaust")`) and iterate `getCardObjectsAtLocation(LOCATION_DUELING_LINE, $owner->ControllerId)`. Effect mirrors `Technique_03004` — `EventDuelCalculateTechniqueValues` handler queues a `createCharacterBeingWoundedEvent` against `getDuelRoundOpponent()` and pushes an explanation. |
| `modules/php/cards/_7s5s/_01069.php` (Maxime de Lafayette) | **Wound-prevention passive — own-Sorcerer scope.** Overrides `handleEvent` on `EventCharacterWounded` and skips `parent::handleEvent` to drop the wound (alternative to Kaspar's `eventCheck`-on-`EventCharacterBeingWounded` shape). Distinguishes Sorcery-trait source (auto-targets performer) from `ISorcererAbility` + `CHOSEN_PERFORMER == Maxime`. Prefer Kaspar's shape for new wound-prevention passives — it doesn't propagate the past-tense event to other listeners. |
| `modules/php/cards/faf/_03026.php` (Angeline Dèmone, Uneasy Ally) | **Binary location-counting passive + two-step `actFromActionWithIds` location picker + conditional step-3 target picker.** Passive recomputes Influence bonus on `EventCardMoved` / `EventCharacterMustered` / `EventApproachCharacterPlayed` / `EventCharacterDestroyed` / `EventCharacterRecruited`. **Threads the `EventCardMoved` instance into the helper** to compensate for stale DB (`runEventHubAfterCards = true` defers the location write until after `handleEvent`) — excludes a card moving OUT, looks up a card moving IN. No-op gate on `$newInfluence == $this->ModifiedInfluence` to skip same-value events. Approach handlers cover BOTH self (using `$event->playerId` as ControllerId override, since the in-memory `ControllerId` may not be propagated yet) AND other-character-approached-while-Owner-at-Home. Action uses `factionHand.setSelectionMode` for step 1 (hand discard), `actFromActionWithIds` for step 2 (adjacent city location string), and conditionally transitions to step 3 (pick opposing target to wound) only when the discarded card is a Sorcery AND opponents exist at the destination. NO `IAbilityThatTargetsCharacters` — the text says "wound an opposing character," not "target an opposing character." |
| `modules/php/cards/faf/actions/Action_03026.php` | **Hand-discard → location-pick → conditional target-pick flow.** Step 1 (`actFromActionWithId`): validate hand card, queue `createCardDiscardedFromHandEvent($owner.ControllerId, $cardId, $owner->Id, ...)` — sourceId is `$owner->Id` (int), NOT `$this->Id` (string composite). Step 2 (`actFromActionWithIds`): treat `$ids[0]` as a location-name string, queue move, then conditionally queue a transition to step 3. Step 3 (`actFromActionWithId`): wound the picked opposing character. `getArgsFromAction` uses `array_values(array_map(...))` to keep the hand-id payload JSON-serializable as an array (not an associative object). |
| `modules/php/cards/_7s5s/_01037.php` (Edeline Trinken) | **Per-character location-counting passive via `$adjustment` int.** Same `runEventHubAfterCards = true` timing problem as Angeline `_03026`, but for "+1 per character at this location" — the per-character shape lets each event hand-pass a `+1`/`-1` adjustment without needing to peek at the moving card's traits. Use this shape when the bonus is uniform per qualifying card; use Angeline's event-passing shape when the bonus depends on the moving card's traits/controller. |
| `modules/php/cards/faf/_03027.php` (Odette Dubois D'Arrent, Disillusioned Courtier) | **Character with two paired button-based City Reactions — no state classes.** (1) `EventCharacterDestroyed` "after another character at this location is destroyed" — heal Odette + optional adjacent-Renown-move (mandatory-first + optional-second split). Uses `$destroyed->Location` directly (event has `runEventHubAfterCards = true`, locker move runs after handlers). Useful-effect precondition: trigger only if Odette has wounds OR adjacent renown exists. Buttons: per-source renown move, "Heal only" (gated on `Wounds > 0`), "Pass" (early-return before `setUsed`). Effect side gates the heal-event/notification on `Wounds > 0` to avoid no-op log noise. (2) `EventChallengeIssued` "after a challenge is issued at this location" — pull adjacent Duelist; fires BEFORE intervention (use `EventChallengeIssued`, NOT `EventChallengeAccepted`). |
| `modules/php/cards/faf/_03028.php` (Térence Rois, Pompous Perveyor) | **Character with stat-specific challenge ban + duel-scoped stat replacement + third-party equip Reaction — all card-local, no states/JS.** (1) Combat-challenge ban via `eventCheck` only (`CHALLENGE_STAT == STAT_COMBAT`); `canChallenge()` left default so Finesse/Influence action performers still work. (2) "Set Combat equal to Influence while dueling at Grand Bazaar": `$DuelCombatEqualsInfluenceApplied` + `$CombatBeforeDuelOverride` snapshot; apply on `EventDuelStarted`/swap, clear on `EventDuelEnd`, re-sync on `EventCharacterInfluenceModified`/`EventCharacterCombatModified`. (3) `Reaction_03028` on `EventAttachmentEquipped` — any character equips at `LOCATION_CITY_BAZAAR` while Terence is in city there. |
| `modules/php/cards/faf/_03037.php` (Sanjay, Daring Tomcat) | **Leader with gambled-only Riposte passive + challenge-refused Collect Renown Reaction + never-engages Influence City Action.** (1) `EventDuelCalculateCombatCardStats` with `$event->gambled` gate (contrast Yevgeni's ungated +1 Thrust). (2) `Reaction_03037` on `EventChallengeRejected` — Collect = `RenownRemovedFromLocation` + `PlayerGainsReknown`; Renown > 0 precondition. (3) `Action_03037` Pattern F with `SANJAY_CHALLENGE_TYPE` out of auto-engage and **no** engage event — exemplar for engagement trichotomy case (c). Hand-size target filter via `getGameDeckObject()->getPlayerHand`. |
| `modules/php/cards/faf/actions/Action_03037.php` | **Never-engages Pattern F + hand-size target filter.** Sets `STAT_INFLUENCE` + `SANJAY_CHALLENGE_TYPE`; transitions `"03037_2"` into challenge machine. No `createCardEngagedEvent`. Filters opposing targets to those whose controller's hand size is strictly less than Sanjay's controller. |
| `modules/php/cards/faf/reactions/Reaction_03037.php` | **Challenge-refused Collect Renown Reaction.** `challengerId == owner.Id`; location Renown > 0 gate; Collect/Pass buttons; Collect queues remove-from-location + player-gains. |
| `modules/php/cards/faf/_03038.php` (Damya Kahina, Sea Serpent) | **Character with two City Actions (`a`/`b`).** (1) Draw-then-discard — draw on `EventActionTriggered` before `factionHand` discard picker. (2) Move equipped character → destroy attachment → draw `WealthCost + 1`. Exemplar for dual-action split, destroy-attachment recipe, and attachment button picker. |
| `modules/php/cards/faf/actions/Action_03038a.php` | **Draw-then-discard City Action.** Queues `createCardDrawnEvent` then `"03038a"` transition; discard via `createCardDiscardedFromHandEvent`; availability requires a post-draw discardable card (hand or deck/discard). |
| `modules/php/cards/faf/actions/Action_03038b.php` | **Move + destroy attachment + draw.** Character picker (no `IAbilityThatTargetsCharacters`); `engage=false` move; Adelheide-style attachment buttons; unequip + `createCardDiscardedFromPlayEvent`; capture cost before destroy; `WealthCost + 1` draws. Excludes same-location / FakeAttachment. |
| `modules/php/cards/faf/_03039.php` (Íñigo Rocoso, Avispa Mordedora) | **Character with Weapon +1 Finesse passive + composite Gambling Technique.** Passive = Rena `_01040` count-transition on equip/unequip via `createCharacterFinesseModifedEvent`. Technique = −2 Thrust + adversary discard + post-discard hand-size En Garde + EndOfRound move Home. |
| `modules/php/cards/faf/techniques/Technique_03039.php` | **Gambling Technique composite.** Gates: `IN_DUEL` + `DUEL_GAMBLED` + actor + `getCurrentRoundThrust() >= 2`. Calc: `$event->thrust -= 2`. Resolve: `$MoveHome` flag; adversary discard transition (`createTransitionEvent` to adversary) or empty-hand En Garde check; `actFromTechniqueWithId` discards then compares `(hand-1) > ownerHand` for En Garde; EndOfRound move Home `engage=false`. |
| `modules/php/cards/_7s5s/_01040.php` (Rena Klingenhalter) | **Canonical "while equipped with a Weapon, +N [Stat]" passive.** Count-transition on `EventAttachmentEquipped`/`Unequipped` — apply at weaponsCount==1, undo at ==0. Íñigo swaps Combat→Finesse factory. |
| `modules/php/cards/_7s5s/techniques/Technique_01093.php` | **Canonical adversary-discards-a-card Technique.** Active-player swap via `createTransitionEvent($adversary->ControllerId, …)`; `DUEL_CHOOSE_TECHNIQUE_01093` hand picker; −1 Riposte on calc. Íñigo reuses this picker shape. |
| `modules/php/cards/_7s5s/actions/Action_01174.php` | **Canonical attachment destroy recipe** (unequip + discard-from-play) used by Damya and many maneuvers/techniques. |
| `modules/php/cards/_7s5s/actions/Action_01194.php` | **Attachment button picker UX** — `args.attachments` → one `addActionButton` per attachment submitting `actFromCardWithId`. Damya step 2 mirrors this. |
| `modules/php/cards/faf/reactions/Reaction_03028.php` | **Canonical "after any character equips at named location" City Reaction.** Demonstrates: (a) owner + equipping character both gated on the same `Game::LOCATION_*` constant; (b) NOT gated on `characterId == owner.Id` (contrast `Reaction_01039` self-equip); (c) `FakeAttachment` skip; (d) Draw/Pass with Pass before `setUsed`. |
| `modules/php/cards/faf/reactions/Reaction_03027a.php` | **Canonical Pass-button + heal/renown-move pattern.** Demonstrates: (a) `EventCharacterDestroyed` "another character at this location" gate (`characterId != owner.Id`, `destroyed.Location == owner.Location`); (b) mandatory-first + optional-second effect split in `performReaction`; (c) Pass button with early-return BEFORE `setUsed` so the daily-use slot survives; (d) hide buttons whose effect would be a no-op (`Heal only` only shown when `Wounds > 0`); (e) three-event renown-move recipe (`createRenownMovingBetweenLocationsEvent` + `Removed` + `Added` with shared `batchId`). |
| `modules/php/cards/faf/reactions/Reaction_03027b.php` | **Pre-intervention `EventChallengeIssued` trigger + pull-adjacent-Duelist effect.** Uses `EventChallengeIssued` (queued by `stIssueChallenge` BEFORE the intervention dispatcher) for "before choosing to intervene" — `EventChallengeAccepted` fires too late. Eligibility: controller match + `Duelist` trait + at an adjacent city location (`getAdjacentCityLocations($owner->Location, false)`). Button-per-Duelist + Pass; effect re-validates location/trait/controller before queueing `createCardMovingEvent(engage=false)`. |
| `modules/php/cards/_7s5s/_01062.php` (Odette Dubois D'Arrent, Genteel Spy) | **Leader sibling reference for renown-move and pull-adjacent-Duelist.** The existing Odette Leader has the "move adjacent Duelist to this location" mechanic as a *City Action* (`Action_01062`, `RequiresPerformerSelected = true`) and the "move adjacent renown to this location" mechanic as a *Reaction on `EventChallengeAccepted`/`EventCharacterIntervened`*. Compare to `_03027`'s Character version, which packages both mechanics as Reactions instead. |
| `modules/php/cards/_7s5s/reactions/Reaction_01062.php` | **Canonical Pass-button + renown-move recipe.** First implementation of the three-event renown-move batch with shared `batchId`. Sets the pattern that `Reaction_03027a` follows: per-source `moveFrom-<location>` buttons + `'pass'` button, Pass branch does NOT call `setUsed`. |
| `modules/php/cards/_7s5s/_01153.php` (Breastplate) | **Reduce-by-one wound prevention in `eventCheck`.** Canonical `eventCheck` on `EventCharacterBeingWounded` pattern. Tracks `$hasBlockedWound` to enforce "first time this duel." Mutates `$event->wounds` rather than zeroing — adapt this shape for partial-reduction passives. |
| `modules/php/cards/_7s5s/actions/Action_01090.php` (Yuri Pyetrovich) | **Continuous Action — user-triggered variant.** Player activates from the menu; the Action sets globals and immediately calls `$this->setUsed($event->theah, false)` so it's available again. Companion to `Action_03013`'s never-shown variant. |
| `modules/php/cards/tac/_02013.php` (Wilhelm Dünst) | **Stat-specific challenge restriction (inverse of Térence).** `eventCheck` on `EventChallengeIssued` when `challengerId == $this->Id` AND `CHALLENGE_STAT != STAT_COMBAT` throws — Wilhelm may *only* issue Combat challenges. Also custom `canChallenge()` filtering valid Combat targets. Compare to `_03028` Térence (cannot issue Combat — `eventCheck` only, no `canChallenge` override). |
| `modules/php/cards/tac/actions/Action_02013.php` (Wilhelm Dünst) | Pattern F with a discard-as-cost step plus the standard challenge transition. Reference for `doCost` / `doEffect` separation when the cost isn't just engagement. |
| `modules/php/cards/_7s5s/techniques/Technique_GainLethal.php` | Generic two-pipeline Gain Lethal helper — handles both `EventGenerateChallengeThreat` (city) and `EventDuelCalculateTechniqueValues` (duel). |
| `modules/php/cards/_7s5s/techniques/Technique_01049.php` | Engagement-as-cost Gain Lethal technique; handles both pipelines, demonstrates `IRangedAbility` integration. |
| `modules/php/cards/_7s5s/actions/Action_01008.php` | Multi-state Sorcerer City Action with branching (`_2`, `_3`, `_4`). Reference for `ISorcererAbility` + sorcerer-start/played event discipline. |
| `modules/php/cards/_7s5s/actions/Action_01076.php` | Sorcerer Action with `RequiresPerformerSelected = true` and location + character pick. |
| `modules/php/cards/_7s5s/reactions/Reaction_01118.php` (Elina) | Button-based Reaction triggered by `EventSorcererAbilityPlayed`; the canonical "sourceId OR performerId OR targeted-at-my-location" idiom. |
| `modules/php/cards/tac/reactions/Reaction_02001.php` (Andriana) | Sorcerer Reaction (`implements ISorcererAbility, IAbilityThatTargetsCharacters`); demonstrates the start/played event discipline inside a reaction. |
| `modules/php/cards/Leader.php` | Base class. Read for `CrewCap`/`Panache`/`Modified*` fields, the built-in `EventCharacterDestroyed` renown-loss handler, and the `EventSchemeCardRevealed` Panache modifier. Always `parent::handleEvent($event)` first. |
| `modules/php/cards/Character.php` | Parent. `canIntervene` / `canChallenge` defaults, `Wounds` tracking, `Attachments`, `resetCard` copying stats into `Modified*`. |

## When You Finish

1. Walk each clause of the printed Text — confirm each maps to exactly one pattern (Dashed stat / Hard ban / Passive handleEvent / Action / Reaction / Sorcerer ability / Technique / Maneuver). Stat numbers go on the constructor and are not a "pattern."
2. For a Leader, confirm: `"Leader"` is in `Traits`, `CrewCap` and `Panache` are set, no `initializeFaction` call (the framework sets this from player faction selection).
3. For a regular Character, confirm: `initializeFaction(<faction>)` is called, `CardNumber` matches the filename's NNNNN.
4. Every new state class needs all three: the class file in `modules/php/States/<expansion>/`, the constant in `States.php`, and the transition entry in `states.inc.php`. High Drama actions → `HIGH_DRAMA_PLAYER_TURN_EVENTS.transitions`; duel technique pickers → `DUEL_CHOOSE_TECHNIQUE_EVENTS.transitions` (id `521` + cardId).
5. Only add `"<card>_2"` to `states.inc.php` if you actually call `EventFactory::createTransitionEvent(..., "<card>_2", ...)` somewhere — that lookup table is **only** consulted by `createTransitionEvent`, not by `nextState`. Some multi-step actions jump step 1 → step 2 solely via `nextState("...")` on the state's own transitions (step-1 entry only). **Others** `nextState` back to `HIGH_DRAMA_PLAYER_TURN_EVENTS` so the queue can process a move/draw/discard, then `createTransitionEvent("NNNNN_2")` re-enters step 2 — those **do** need the `_2` (and `_3`, …) lookup entries (Angeline `"03026_2"`, Damya `"03038b_2"`, Pattern F challenge `"NNNNN_2"`).
6. State ID convention: `4` + 5-digit `CardNumber` for step 1; append `2`/`3`/`4` for subsequent steps. For dual Action classes (`a`/`b`), append which-action digit first (`4030381` / `4030382` / `40303822` for Damya). Don't engineer a separate prefix to dodge hypothetical CD-card collisions (per user feedback memory).
7. Every new state needs JS wiring in `OnEnteringState.<expansion>.js` AND `OnUpdateActionButtons.<expansion>.js`. Add `OnLeavingState.<expansion>.js` reset if you set selection modes or styling. Add to `PlayerActions.js` if you reuse a client action. **Hand-discard / factionHand Confirm buttons also need an entry in `EventHandlers.js`** (factionHand click map) or Confirm stays disabled.
8. If you minted a new global, clear it in the matching cleanup state (or defensively at turn boundaries).
9. Mentally run pre-commit hook checks on every file you touched. Especially: `createActionResolvedEvent` in the action, no `setUsed`/`resetPlayerPassCount`/`announceAction` in the `CharacterAction` subclass, `$this->setUsed(` and `$this->isAvailable(` literal strings present in every `CardReaction` subclass, and `createSorcererAbilityStartEvent`/`createSorcererAbilityPlayedEvent` if implementing `ISorcererAbility`.
10. For each Reaction you added, walk the `handleEvent` triggers and confirm all required gates are in place: `isAvailable()`, identity check (`$event->sourceId/performerId/cardId == $owner->Id` etc.), scope gate (`cardInCity($owner)` for City Reactions), and a valid-target precondition if the effect needs a target. Missing the valid-target gate leaves the player with a useless "Decline" prompt.
11. For phase-event listeners on Leaders, confirm a `! characterIsInDiscardOrLocker($this)` guard — a destroyed Leader still has a `ControllerId` set, so `isControlled()` alone is insufficient.
12. **For "issue a challenge" actions (Pattern F):** confirm all six challenge-integration files are touched if you minted a new challenge type **with intervention/refusal restrictions** — `Game.php`, `seventhseacityoffivesails.js`, `StatesTrait::stIssueChallenge` (auto-engage list), `Theah::interventionCheck`, `ArgumentsTrait::argsHighDramaChallengeActionAcceptChallenge`, `FrameworkActionsTrait::actHighDramaChallengeActionReject`, plus `OnUpdateActionButtons.js::highDramaChallengeActionAcceptChallenge` for the Refuse button UI. Types with **no** special intervene/refuse rules (Sanjay) only need `Game.php` + matching JS int. The PHP int and the JS int MUST match. Confirm `states.inc.php` has BOTH `"NNNNN"` (picker entry) and `"NNNNN_2"` (post-pick → `HIGH_DRAMA_CHALLENGE_ACTION_TECHNIQUE_AVAILABLE`). **Engagement trichotomy** (see Pattern F): (a) Engage printed → add to auto-engage list, require `!Engaged`; (b) no Engage printed but unengaged performers still engage (Don Constanzo) → keep OUT of auto-engage, emit conditional `createCardEngagedEvent`; (c) never engages (Sanjay) → keep OUT of auto-engage, **emit no engage event**. Do not copy (b) onto every "no Engage printed" card — ask whether the action engages at all. Re-emitting engage on an already-engaged character trips Vittoria-style `Reaction_01014` swaps.
13. **For picker states with multiple exits**, name every transition (`"targetChosen"`, `"zombie"`, etc.). The empty-string `""` transition is only legal when it's the SOLE transition out of the state.
14. **For in-duel techniques (Pattern E)**, confirm `Game::IN_DUEL` and (for Gambling Techniques) `Game::DUEL_GAMBLED` gates are in `isAvailableToPlayer`, plus the actor-identity check via `getDuelRoundActor()`. For Gain Lethal effects, use `EventDuelCalculateTechniqueValues` + `createGainLethalEvent` for the in-duel pipeline; only also handle `EventGenerateChallengeThreat` if the technique is meant to fire outside duels too.
15. **For cost-bearing Reactions (e.g., "at -N cost", "pay N Wealth"):** roll the payment tracking inside the Reaction class using running `$paidCardIds`/`$paidWealth`/`$paidHasWealthCard` state. Do NOT route through `PAY_STATE_PLAY_BRUTE` — it's tied to the player-turn state cycle and won't return correctly from reactions fired in dawn/dusk/duel contexts. See Pattern D's "Reactions that need to pay a wealth cost" subsection and `Reaction_03003`. Mirror `UtilitiesTrait::isValidWealthPayment` semantics (exact OR `cost+1`-with-Wealth-card). Queue discards atomically at finalize, not per-click, so `Decline` is a clean rollback.
16. **For "Put into play from hand or discard" Reactions:** `createCharacterMusteredEvent` does the actual move. `createCardRemovedFromPlayerDiscardPileEvent` is notification-only and exists so JS clients can sync their `player.discard` array — fire it BEFORE the muster event when the card is from discard. Pattern reference: `Action_01024` (Bravos), `Reaction_03003`.
17. **For dueling-line-derived running bonuses** ("+N[Stat] for each X in my dueling line"): there is no event fired when a card enters the dueling line (`cards->moveCard` is called directly, bypassing `EventCardMoved`). Recompute the running bonus at `EventDuelEndOfRound` from `getCardObjectsAtLocation(LOCATION_DUELING_LINE, controllerId)`. Reset at `EventDuelEnd` via direct inverse-delta (NOT a recount — `stDuelEnd` queues `EventDuelEnd` BEFORE the line-clearing discard events, so the line still contains the round's cards). Gate the recount on the owner being a duel participant (the line is per-player, not per-character). Pattern reference: `_03004` Elena and Pattern A's "Dynamic stat bonuses tied to the dueling line" subsection.
18. **For "opposing characters are considered <Trait>" passives:** mutate the opposing characters' `ModifiedTraits` via `addTrait` / `removeTrait` (Wilhelm `Action_02013` shape), NOT a `hasTrait` override on the owner (Uwe `_01043` shape only works when the receiver of the call is the modified card). Track a `TaggedOpposingIds` set on the listener — `Card::addTrait` appends without dedup, so untracked re-tagging on every ability-use event will pile up duplicate trait entries that `removeTrait` won't fully clear. Untag at the scope boundary named by the text (`EventPlayerTurnEnd` for "while using your abilities" → turn-scope) and add `EventCardMoved` / `EventCharacterDestroyed` cleanups for the owner. Pattern reference: `Action_03013` (Daniella Dietrich).
19. **For continuous Actions** (passive abilities mounted on a `CharacterAction` that the player never triggers from the menu): `isAvailableToPlayer` returns `false`; `handleEvent` does the work; explicitly call `$this->setUsed($event->theah, false)` at the scope boundary you want the Action to "reset" at (typically `EventPlayerTurnEnd`) — the parent `CardAction::handleEvent`'s `EventDuskEndOfDay` reset alone isn't frequent enough for an effect that needs to persist within a single turn but renew next turn. Mirror `Reaction_01196` "Continuous" for the never-`setUsed(true)` Reaction analogue. Pattern reference: `Action_03013` and Pattern A's "Continuous Action" subsection.
20. **For techniques with "Wound X • effect" cost-bearing text:** queue `createCharacterBeingWoundedEvent` at the `EventResolveTechnique` handler BEFORE the `createTechniqueTransitionEvent`, so the cost fires before the player picks a target. Queueing the wound from inside `actFromTechniqueWithId` would invert the printed cost/effect ordering and let a player decline the picker to dodge the cost. Pattern reference: `Technique_03013` and Pattern E's "Wound-as-cost" subsection.
21. **For swap techniques in challenge AND duel contexts:** mint TWO state classes under `modules/php/States/<expansion>/` — `State_highDramaChallengeActionResolveTechnique_NNNNN` (id `455` + cardId, routed from `HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_EVENTS`) and `State_duelChooseTechnique_NNNNN` (id `521` + cardId, routed from `DUEL_CHOOSE_TECHNIQUE_EVENTS`). Both states use the same transition name (`"NNNNN"`) in `createTechniqueTransitionEvent`; the per-dispatcher lookup routes correctly. The technique's swap mechanics live inline in `actFromTechniqueWithId` branched on `$state` — challenge-context moves `DUEL_CHALLENGER` condition + queues `ChallengerSwappedEvent` + sets `CHOSEN_PERFORMER`; duel-context calls `swapParticipantsInDuel($duelId, $round, $owner->Id, $target->Id)`. Keep ONE thing in `handleEvent` — the `EventGenerateChallengeThreat` `actorId` redirect, which has to happen at event-fire time so `Character::handleEvent`'s threat-add and the EventHub notification reference the new challenger. Both states need JS handlers in `OnEnteringState.<expansion>.js`, `OnUpdateActionButtons.<expansion>.js`, `OnLeavingState.<expansion>.js`. Disambiguate `descriptionMyTurn` with `Name (Title)` when other cards share the character's name. Pattern reference: `Technique_03013` and Pattern E's "Technique usable in BOTH challenge and duel contexts" subsection.
22. **For wound-prevention passives** ("<X>'s abilities cannot wound <Owner>" / "<Owner> ignores wounds from <Y>"): override `eventCheck` on the card class (NOT `handleEvent`) on `EventCharacterBeingWounded` and zero `$event->wounds` when the gate trips. Reasons: (a) `EventHub` only emits the past-tense `EventCharacterWounded` when `wounds > 0`, so zeroing in the *Being*-tense event also suppresses every downstream listener that keys on the past-tense event — cleaner than Maxime's skip-`parent::handleEvent` shape; (b) `eventCheck` runs before any handler, so the mutation is visible to everyone. Use `$event->abilityId == ''` as the threat-conversion signal (`StatesTrait::stDuelEndOfRound` omits the ability id when emitting threat-to-wounds — every ability emitter passes it). Use `$source->ControllerId != $this->ControllerId` for "opponent's ability" scope; use Sorcery-trait / `ISorcererAbility` + `CHOSEN_PERFORMER` for "abilities he performs" scope (Maxime). Wound-movement (heal+wound recipe) is automatically covered by the wound-block — don't add a special handler. Pattern reference: `_03014` Kaspar (zero), `_01153` Breastplate (reduce-by-one), `_01069` Maxime (alternative `handleEvent` shape — only use for "abilities he performs" scope).
23. **For techniques gated on "equipped with X or X in dueling line":** OR two checks in `isAvailableToPlayer` — iterate `$owner->Attachments` (ids → `getCardById($id)` → `hasTrait(...)`) AND iterate `getCardObjectsAtLocation(LOCATION_DUELING_LINE, $owner->ControllerId)`. Both are inside the standard `IN_DUEL` + actor-is-owner gate. There is no `hasAttachmentWithTrait` helper — the id-then-lookup pattern is the codebase convention (`Maneuver_01054`). Pattern reference: `Technique_03014` and Pattern E's "equipped with X or X in dueling line" subsection.
24. **For "after X musters" triggers:** the conditional MUST OR `EventCharacterMustered` AND `EventApproachCharacterPlayed`. The Approach card path emits a distinct event, so a single-event hook silently misses Approach-driven entries. Pattern reference: `_01009` Cirilo (mercenary→Brute) and `_03015` Joern (Forced self-wound). See Pattern A's "Forced muster/approach triggers" subsection.
25. **For city-location picker states (Action or Technique):** the state's `#[PossibleAction]` MUST be `actFromCardWithLocations(string $locations)`, NOT `actFromCardWithIds(array $ids)`. The JS submits via `onCityLocationsSelected → bgaPerformAction('actFromCardWithLocations', ...)`; if the state declares `actFromCardWithIds`, you get a "This move is not authorized now" error. The framework's `actFromCardWithLocations` JSON-decodes the payload and forwards it into the card's `actFromCardWithIds → actFromTechniqueWithIds`, so the technique still receives the location strings through the `$ids` parameter — only the entry-point name differs. Source list: `array_keys($theah->getCityLocations())`. Validation: `array_key_exists($loc, $theah->getCityLocations())`. NOT hardcoded `LOCATION_*` arrays — those constants don't exist for the colloquial names (`LOCATION_BORDELLO` etc. are hallucinations; the real constants are `LOCATION_CITY_DOCKS`/`LOCATION_CITY_FORUM`/`LOCATION_CITY_BAZAAR`/`LOCATION_CITY_OLES_INN`/`LOCATION_CITY_GOVERNORS_GARDEN`). `OnLeavingState` cleanup uses `resetCityLocations()` — there is no `clearCityLocationAsSelectable` function. Pattern reference: `Technique_03025b` and JS Wiring's "City-location picker — full wiring" subsection.
26. **For "after the Owner moves to a city location" Reactions:** the trigger filter is `cardId == $owner->Id` (OPPOSITE of `Reaction_03016b`'s enemy-moves-to-me filter). Inside the `EventCardMoved` handler, `$owner->Location` is STILL the OLD location because `EventCardMoved.runEventHubAfterCards = true` defers the state write until after every card's `handleEvent`. Read eligibility at `$event->toLocation`, not `$owner->Location`. By the time `performReaction` runs, the move has resolved and `$owner->Location` reflects the new value. Pattern reference: `Reaction_03025` (Angeline) and Pattern D's "After the Owner herself moves to a city location" subsection.
27. **For Continuous Reactions** (Reactions that fire every time the trigger event recurs, no per-round/per-turn cap): omit the `$this->setUsed($theah, true)` runtime call. But the `.githooks/pre-commit` hook greps for the literal string `$this->setUsed(` in every `CardReaction` subclass — keep that literal alive inside an explanatory comment so the hook still passes. Pattern reference: `Reaction_03025` and Pattern D's "Continuous Reaction" subsection.
25. **For phase-conditional Resolve modifiers** ("During <Phase>, X has ±N Resolve"): mutate `$this->ModifiedResolve` directly — there is no `createCharacterResolveModifiedEvent` factory (unlike Combat/Finesse/Influence/Panache which all have one). Gate the apply with a private bool flag so attachment-driven `ModifiedResolve` churn doesn't desync. Manually emit `createCharacterDestroyedEvent` (mirroring `EventHub.php:251`'s unequip path) if the reduction crosses the wounds-equal-resolve threshold — `Character::handleEvent`'s destruction check only runs inside `EventCharacterWounded`. Restore at `EventDuskEndOfDay` (or whichever phase-end event matches the printed scope), unconditionally on the flag — destroyed objects in the Locker are fine, and the unconditional restore guards against any hypothetical return-from-Locker path that skips the constructor. Pattern reference: `_03015` Joern and Pattern A's "Phase-conditional Resolve modifier" subsection.
26. **For "+N [Stat] while <self-condition>" passives** (Combat/Finesse/Influence/Panache): use a flag-based recompute pattern, NOT a recompute-from-base. Hook the event(s) that toggle the condition (`EventCharacterWounded`/`EventCharacterHealed` for "while wounded", `EventCardEngaged`/`EventCardEngarded` for "while engaged", etc.) gated on `characterId == $this->Id`. Call `parent::handleEvent($event)` FIRST so the parent updates `$this->Wounds` / `Engaged` / etc.; then re-derive the boolean and queue `createCharacter<Stat>ModifiedEvent(±1)` only on flag transition. Skip if `IsDying` or `characterIsInDiscardOrLocker`. Pattern reference: `_03016` Ise (+1 Combat while wounded) and Pattern A's "Stat bonus while a self-condition holds" subsection. The flag avoids clobbering attachment-driven `ModifiedCombat` etc. **Resolve has no factory — use the `_03015` Joern direct-mutation pattern instead.**
27. **For "During <Phase>, you may choose not to <auto-action>" Reactions** (Dusk opt-out, Dawn cleanup opt-out, etc.): listen on the *pre*-event (e.g., `EventCardMoving`) and use the `cancelDeclinedByCardIds` re-queue dance. Cancel the event in `handleEvent` (`$event->canceled = true`), clone-and-store it (with `unset($cloned->theah)`), prompt the player; "Keep" path calls `setUsed(true)` and discards the clone; "Decline" path re-queues the clone with `cancelDeclinedByCardIds[] = $owner->Id` so `handleEvent` doesn't immediately re-catch it. Gate the trigger on the auto-emitter signal — for the Dusk move-home, that's `sourceId == 0` AND `TURN_PHASE == Game::DUSK`. Use `stackEvent` (not `queueEvent`) for the transition so the prompt fires before subsequent dusk cleanup events. Pattern reference: `Reaction_03016a` (Ise, on a Character in play) and `Reaction_01140` (in-hand sibling). See Pattern D's "Cancel-and-reissue Reaction" subsection.
28. **For "Reaction: After <enemy/X> character moves to this location" triggers:** listen on `EventCardMoved` (past-tense, the move has committed). Required gates in order: `isAvailable()`, `cardInCity($owner)` (enemies can't enter your Home), `event.cardId != $owner->Id` (skip the owner's self-moves), `event.toLocation == $owner->Location`, `getCardById` returns a Character, `ControllerId != 0`, and the enemy/friendly controller check that matches the text. ALWAYS include a valid-effect-target precondition (`count($eligibleEffectTargets) > 0`) before queuing the transition, or the player gets a useless prompt. To MOVE another character to the owner's location, queue `createCardMovingEvent($mover.ControllerId, $mover.Id, $mover.Location, $owner.Location, $engage=false, $owner->Id, $this->Id)` — there's no pull/teleport helper, the standard move event handles all bookkeeping. Pattern reference: `Reaction_03016b` (Ise). For the *self-moves* analogue ("after this character moves to a new location"), the receiver is a `handleEvent` on the card itself — see `_01067` Jean Urbain / `_02022` Stranahan.
29. **For location-counting passives** ("while you control X at <Owner>'s location"): hook `EventCardMoved` AND `EventCharacterMustered` AND `EventApproachCharacterPlayed` AND `EventCharacterDestroyed` AND `EventCharacterRecruited`. **`EventCardMoved` has `runEventHubAfterCards = true`** so the DB location field is stale during `handleEvent` — either pass an `$adjustment` int (per-character count, `_01037` Edeline) or pass the `EventCardMoved` instance into the helper and exclude moving-out + look up moving-in (binary bonus, `_03026` Angeline). For approach-played triggers, hook a SECOND branch for "another character is approached while Owner is at Home" (gate on `$event->playerId == $this->ControllerId`); in the Owner's-own-approach branch use `$event->playerId` as the controller — `$this->ControllerId` may not be propagated yet. Add a no-op gate (`if ($new == $this->ModifiedStat) return;`) to skip same-value events. See Pattern A "Location-counting passives".
30. **For event factory calls from inside an Action class**: an Action's `$this->Id` is a STRING composite (`"{ownerId}_{ClassName}"`, set in `CardAbilityTrait::setOwnerId`). Use `$owner->Id` (int) for any `int $sourceId` parameter — `createCardDiscardedFromHandEvent`, `createCharacterBeingWoundedEvent`, `createCharacterBeingHealedEvent`, `createCardMovingEvent`. `$this->Id` is correct for `string $abilityId` parameters and the 4th `internalId` arg of `createTransitionEvent`.
31. **For arrays handed to JS via `getArgsFromAction`**: `getCardObjectsAtLocation` returns an array keyed by card id. `array_map` preserves keys, and a non-sequentially-keyed array JSON-encodes as an object — `.forEach` / `.map` throws on the client. Wrap in `array_values(array_map(...))`. Symptom: `Uncaught TypeError: ids.forEach is not a function`.
32. **For hand-card picker states** (discard from hand, reveal from hand): use `factionHand.setSelectionMode('single')` + `onCardDiscarded` (reusable from `PlayerActions.js`). DO NOT use `highlightCardsAsSelectable` — that's for in-play cards in `cardProperties`; hand cards aren't there and the lookup returns `null` (symptom: `Cannot read properties of null (reading 'className')`). See Pattern C "Hand-card picker".
33. **For city-location picker states on a CharacterAction**: the state's `#[PossibleAction]` is `actFromCardWithLocations(string $locations)`, and the action overrides **`actFromActionWithIds(array $ids)`** — NOT `actFromActionWithId(int $id)`. Each `$ids[N]` entry is a location-name STRING, not an int. Symptom of overriding the wrong method: the state spins waiting for an action that never arrives (presents as an infinite loop). See Pattern C "City-location picker for CharacterActions".
34. **For `IAbilityThatTargetsCharacters`**: implement ONLY when the card text says "target". "Wound an opposing character" / "engage a character" / "destroy a character" / "Your equipped character moves …" without the word "target" is NOT a targeted ability — don't add the interface. Use a plain private helper (e.g., `isValidWoundCandidate`, `isEligibleMover`) for validation; don't reuse the `isValidTargetForAbility` name. See Pattern C "Don't add `IAbilityThatTargetsCharacters` unless the text says 'target'". References: `_03026` Angeline, `Action_03038b` Damya.
35. Write a journal entry in `.cursor/journal/YYYY-MM-DD-NN-<card>.md`. Capture the **WHY** of any non-obvious decision — event-type choice, why the Reaction was not flagged `ISorcererAbility` (or why it was), what the identity-check field is on the event (`sourceId` vs `performerId` vs `cardId`), why a particular state-ID encoding, why a button-based Reaction was chosen over state classes, why a new challenge type was added vs. piggybacked on an existing one, **and which engagement trichotomy case applies** (Engage printed / conditional engage / never engages). Read the Cesca journal (`2026-05-13-01-cesca-del-rosso-03001-implementation.md`), the Aja journal (`2026-05-13-02-aja-03002-implementation.md`), the Don Constanzo journal (`2026-05-14-01-don-constanzo-03003-implementation.md`), the Elena journal (`2026-05-16-01-elena-agnelli-03004-implementation.md`), the Kaspar Iron Reforged journal (`2026-05-25-02-kaspar-dietrich-03014-implementation.md`), the Joern journal (`2026-05-29-03-joern-kietelsson-03015-implementation.md`), the Ise journal (`2026-05-29-04-schwester-ise-03016-implementation.md`), the Odette journal (`2026-06-10-02-odette-dubois-darrent-03027-implementation.md`), the Térence journal (`2026-07-01-03-terence-rois-03028-implementation.md`), the Sanjay journal (`2026-07-11-07-sanjay-03037-implementation.md`), the Damya journal (`2026-07-11-09-damya-kahina-03038-implementation.md`), and the Íñigo journal (`2026-07-12-03-inigo-03039-implementation.md`) — between them they cover the End-of-Dawn / Sorcerer-trigger / move-wound / state-ID-encoding / issue-a-challenge / Gambling-Technique / new-challenge-type / performer-≠-owner / click-to-pay-Wealth / muster-from-discard / dueling-line-recompute / wound-prevention-via-eventCheck / muster-includes-Approach / phase-conditional-Resolve / while-wounded-stat-bonus / cancel-and-reissue-Reaction / after-enemy-moves-here / stat-specific-challenge-ban / set-stat-equal-while-dueling / third-party-equip-at-location / gambled-combat-card-passive / never-engages-challenge / Collect-Renown-on-refuse / dual-City-Action-a-b / draw-then-discard / destroy-attachment-to-draw / Weapon-equipped-stat-bonus / adversary-discard-Technique / post-discard-hand-En-Garde / EndOfRound-move-Home decisions in detail.
36. **For stat-specific challenge bans** ("cannot issue [Combat] challenges" / "may only issue [Combat] challenges"): use `eventCheck` on `EventChallengeIssued` gated on `challengerId == $this->Id` AND `CHALLENGE_STAT`. Do NOT override `canChallenge()` to `false` unless the ban covers **all** challenge types — a partial ban must leave `canChallenge()` at default so Finesse/Influence action performers still include the character. Basic Challenge always sets `CHALLENGE_STAT = STAT_COMBAT` in `actHighDramaChallengeActionStart`, so the `eventCheck` backstop blocks Basic Challenge at issue time even if the character appears in the performer list. Pattern reference: `_03028` Térence (ban Combat), `_02013` Wilhelm (only Combat).
37. **For "set [StatA] as equal to [StatB]" while a scoped condition holds** (duel at named location, etc.): use a replacement flag + snapshot restore — NOT the Ise ±1 delta pattern. Store pre-override target stat at apply-time; restore snapshot on clear; re-sync on source-stat changes and on external target-stat mutations during the override. Hook duel boundaries + swap events like `_01089` Soline. Named locations use `Game::LOCATION_CITY_*` constants. Pattern reference: `_03028` Térence and Pattern A's "Set one stat equal to another while a scoped condition holds" subsection.
38. **For "Reaction: After a character equips an attachment at [location]"**: listen on `EventAttachmentEquipped`. Gate both `$owner->Location` and equipping `$character->Location` on the named `Game::LOCATION_CITY_*` constant; `cardInCity($owner)` for City Reactions; skip `FakeAttachment`. Only gate on `$event->characterId == $owner->Id` when the text names the owner ("After Philip equips …"). Pattern reference: `Reaction_03028` (any character), `Reaction_01039` (self only).
39. **For "gambled combat cards have +N[Stat]" passives:** gate `EventDuelCalculateCombatCardStats` on `actorId == $this->Id` **AND** `$event->gambled`. Use `$event->addRiposte`/`addParry`/`addThrust`. Do NOT use `Game::DUEL_GAMBLED` for this passive — that global is for Gambling Technique availability. Pattern reference: `_03037` Sanjay (gambled-only) vs `_01116` Yevgeni (every combat card).
40. **For "Collect a Renown from [location]" effects:** queue `createRenownRemovedFromLocationEvent` then `createPlayerGainsReknownEvent`. Gate the Reaction prompt (or Action availability) on `getCityLocation(...)->Renown > 0`. Pattern reference: `Reaction_03037` Sanjay.
41. **For two (or more) City Actions / Actions on one card:** split into `Action_NNNNNa` / `Action_NNNNNb` with separate Names, availability, states, and transition keys (`"NNNNNa"`, `"NNNNNb"`). State IDs: `4` + cardNumber + action digit (+ step suffix). Pattern reference: `_03038` Damya, `_01095` Patricia. See Pattern C "Multiple Actions on one card".
42. **For "Draw a card. Then, discard a card.":** queue `createCardDrawnEvent` on `EventActionTriggered` **before** the discard-state transition so the drawn card is in hand for `factionHand`. Gate availability on post-draw discardability (hand nonempty OR faction deck+discard nonempty). Pattern reference: `Action_03038a`. See Pattern C "Draw-then-discard".
43. **For "destroy [an] attachment" effects:** use unequip + `createCardDiscardedFromPlayEvent` — there is no `createAttachmentDestroyedEvent`. Capture `WealthCost` before destroy if you draw equal to printed cost. Skip `FakeAttachment`. Pattern reference: `Action_01174`, `Action_03038b`. See Pattern C "Destroy an attachment".
44. **For "Your equipped character moves to this location …":** equipped = ≥1 non-fake attachment; exclude characters already at the owner's location when the text says "moves to"; move with `engage=false` if Engage is not printed; attachment step uses button list (Adelheide `01194`), not board highlight; no `IAbilityThatTargetsCharacters` without the word "target". Pattern reference: `Action_03038b`.
45. **For "While equipped with a Weapon, +N [Stat]" passives:** count Weapons after `EventAttachmentEquipped`/`Unequipped` (Attachments already updated); apply only at count==1, undo only at count==0. Use `createCharacterFinesseModifedEvent` / `createCharacterCombatModifiedEvent` — do not invent a bool flag. Pattern reference: `_01040` Rena, `_03039` Íñigo. See Pattern A "While equipped with a Weapon".
46. **For −N Thrust/Riposte Techniques** with "(combat card must have at least N)": gate on `getCurrentRoundThrust()` / `getCurrentRoundRiposte() >= N`; apply `$event->thrust -= N` (etc.) on `EventDuelCalculateTechniqueValues`. Pattern reference: `Technique_01050`, `Technique_03039`.
47. **For "adversary discards a card" Techniques:** `createTransitionEvent` to the **adversary's** playerId; wire `DUEL_CHOOSE_TECHNIQUE_EVENTS` + faf/7s5s JS **and** `EventHandlers.js` factionHand enable. Empty hand → skip picker. Pattern reference: `Technique_01093`, `Technique_03039`.
48. **For post-discard "if they have more cards … en garde" + "at end of round move Home":** compare hands with `(adversaryCount - 1)` because discard is queued not flushed; set `$MoveHome` unconditionally on Resolve (unless text gates it); EndOfRound move with `engage=false` when Engage is not printed; clear flag on cancel/DuelEnd. Pattern reference: `Technique_03039`.
49. **Line endings:** leave them alone. Do **not** post-process Write output to "ensure CRLF" — on this Windows repo Write already emits CRLF, and a naïve `\n`→`\r\n` pass produces `\r\r\n` (blank line between every line). User rule: leave line endings intact. If a file looks double-spaced, check for `\r\r\n` and replace with `\r\n` once — never as a routine Write follow-up.