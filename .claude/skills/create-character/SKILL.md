---
name: create-character
description: Implement or finish a Character or Leader card (modules/php/cards/<expansion>/_NNNNN.php where the class directly extends Character or Leader). Use this skill whenever the user asks you to implement, finish, scaffold, or wire up a Character/Leader card, or when they reference a faction-deck character whose class extends Character (not CityCharacter) and has unimplemented Text. Triggers on phrases like "implement this character", "implement this leader", "finish _NNNNN" (when it extends Character or Leader), "wire up the City Action on Cesca", "wire up the Reaction on this Leader", or natural-language descriptions of a non-city-deck character (lives in a player's faction deck or is a Leader).
---

# Creating a Character or Leader

This skill covers cards that directly extend `Character` (regular faction-deck characters) or `Leader` (which itself extends `Character`). These cards live in a player's faction deck (or are placed at game start as the player's Leader) — they are **not** in the city deck.


## How to use this skill (progressive disclosure)

1. Confirm the stub `extends Character` or `extends Leader` (not `CityCharacter` — use `create-city-character` for those).
2. Classify each printed Text clause with the **Pick the Right Ability Shape** table below.
3. **Read only the companion files that match** — do not load every pattern file.
4. Mirror a code exemplar from [references.md](references.md) rather than inventing.
5. Before finishing, run the deep checklist in [checklist.md](checklist.md).

### Companion files

| File | Read when |
|---|---|
| [pattern-a.md](pattern-a.md) | Passive / Forced / `handleEvent` / `eventCheck` |
| [pattern-c.md](pattern-c.md) | Action / City Action / pressure / multi-step |
| [pattern-f.md](pattern-f.md) | Issuing a challenge from a City Action |
| [pattern-d.md](pattern-d.md) | Reaction / City Reaction / Continuous |
| [pattern-e.md](pattern-e.md) | Technique / Maneuver / Gambling Technique |
| [wiring.md](wiring.md) | JS state hooks, pre-commit, style |
| [helpers.md](helpers.md) | Theah/Game helpers |
| [references.md](references.md) | card id → what it demonstrates |
| [checklist.md](checklist.md) | full finish / regression checklist |

When in doubt, mirror a reference rather than invent.

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
- **`initializeFaction()` on Leaders is optional.** The framework sets faction from player selection at setup. Base-game Leaders often omit it (`_01006`, `_01089`, `_01116`); **FAF Leaders commonly call it** (`_03001` Cesca, `_03037` Sanjay, `_03049` Ekaterina — faction spelling **`Ussura`**, not "Usurra"). Either works at runtime; when finishing a FAF Leader scaffold, keep whatever the scaffold has and fix spelling.
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
| **"During <Phase>, <Owner> has -N Resolve"** (or any phase-conditional **Resolve** modifier) | There is no `createCharacterResolveModifiedEvent` factory — Resolve is not on the event-driven stat list. Directly mutate `$this->ModifiedResolve` on the phase-begin event, gated by a private bool flag (because attachments also mutate `ModifiedResolve` independently), and restore on the phase-end event. Manually emit `createCharacterDestroyedEvent` if the reduction crosses the wounds-equal-resolve threshold (the engine's destruction check only runs inside an `EventCharacterWounded` handler). **Also emit `characterResolveModified` client notif** (chip stays flat otherwise — Finesse/Combat/Influence EventHub notifs update UI; Resolve has none). See Pattern A "Phase-conditional Resolve modifier" below. Reference: `_03015` Joern; client-sync sibling `_04002` Danilo. **For Combat/Finesse/Influence/Panache, use the matching `createCharacter<Stat>ModifiedEvent` factory instead** — they're event-driven the way Resolve isn't. |
| **"When <Owner>'s challenge is refused, <effect>"** / **"When a challenge to <Owner> is refused …"** | If the text is a **Forced** / plain passive (no player choice): `handleEvent` on `EventChallengeRejected`. If the text is a **`<b>Reaction:</b>`**: Pattern D button Reaction on the same event. Fields: `$event->challengerId` (issued), `$event->targetId` (refused). Identity gate matches whichever role the text names. Reference: `_03015` Joern (passive self-heal), `Reaction_01116a` Yevgeni (Reaction En Garde), `Reaction_03037` Sanjay (Reaction Collect Renown). |
| **"<Owner>'s gambled combat cards have +N[Riposte/Parry/Thrust]"** | Pattern A on `EventDuelCalculateCombatCardStats`: gate `actorId == $this->Id` **AND** `$event->gambled`. Call `$event->addRiposte(N)` (etc.). WHY `$event->gambled` not `Game::DUEL_GAMBLED` alone: the calculate-stats event already carries the authoritative per-round flag from `duel_round.gambled` (includes Roll-the-Bones paths). Contrast Yevgeni `_01116` (every combat card, no gambled gate). See Pattern A "Gambled combat-card stat bonus" below. |
| **"Collect a Renown from <Owner's / this> location"** (Reaction or Action effect) | `createRenownRemovedFromLocationEvent` + `createPlayerGainsReknownEvent`. Valid-target gate: `getCityLocation(...)->Renown > 0` before prompting. Reference: `Reaction_03037` Sanjay, `Action_02035` (pressure-success Collect). |
| **"When an opponent collects Renown from this location, they collect one fewer. (Remaining Renown stays.)"** | Pattern A `eventCheck` on the card class — **not** a Reaction. Two Collect pipelines differ (Plunder Take→Gains→Removed vs ability Removed→Gains). `EventPlayerGainsReknown` has **no location field** — never reduce every Gains. Do **not** blindly reduce every `EventRenownRemovedFromLocation` (Moves also emit Removed+Added). See Pattern A "Opponent collects one fewer Renown". Reference: `_03049` Ekaterina. |
| **"After <Owner>'s location is claimed, she may move …"** (unlabelled After…may) | Pattern D **Continuous** Reaction on `EventLocationClaimed` when `event.location == owner.Location`. Gate on **any** claimer unless the text says "opponent" / "you" — base-game `_01117` Reaction is opponent-only; Leader FAF Ekaterina is any-claimer. Effect = move-self-to-any-city buttons. See Pattern D "After the Owner's location is claimed". Reference: `Reaction_03049`, Continuous sibling `Reaction_03025`. |
| **"<Owner> has +N [Stat] while wounded"** (or any "while <condition-on-self>" stat bonus) | Pattern A passive with a private bool flag (e.g., `$WoundedCombatBonusApplied`). Hook `EventCharacterWounded` AND `EventCharacterHealed` with `characterId == $this->Id`, call `parent::handleEvent` first (so `$this->Wounds` is up-to-date), then re-derive the boolean and queue `createCharacter<Stat>ModifiedEvent(±1)` only on flag transition. Skip if `IsDying` or in discard/locker. See Pattern A "Stat bonus while a self-condition holds" below. Reference: `_03016` Ise. |
| **"While <Owner> is equipped with a <b>Weapon</b>, he gains +N[Stat]"** | Pattern A on `EventAttachmentEquipped` / `EventAttachmentUnequipped` gated on `characterId == $this->Id`. Count Weapons in `$this->Attachments` **after** the event (Attachments already reflects the new set). Apply +N only when count transitions to `1`; undo only when count transitions to `0`. Do **not** invent a bool flag — the count-transition is the established shape and survives Offhand / multi-Weapon edge cases. Use `createCharacterFinesseModifedEvent` / `createCharacterCombatModifiedEvent` (note Finesse factory typo `Modifed`). Reference: `_01040` Rena (+1 Combat), `_03039` Íñigo (+1 Finesse). |
| **"Set [StatA] as equal to [StatB]"** while a scoped condition holds (e.g., "while participating in a duel at [The Grand Bazaar]") | Pattern A passive with a **replacement** flag + stored pre-override snapshot — NOT the ±1 delta pattern. Apply on condition start (`EventDuelStarted` + location/participant gates, plus `EventDefenderSwapped`/`EventChallengerSwapped` for mid-duel entry/exit), clear on condition end (`EventDuelEnd`), re-sync target stat whenever source stat changes (`EventCharacterInfluenceModified` → update Combat) or external sources mutate the target stat away from the link (`EventCharacterCombatModified` with `NewCombat != ModifiedInfluence`). Store `$CombatBeforeDuelOverride` at apply-time; restore that snapshot on clear (NOT recompute-from-base — attachments may change mid-condition). See Pattern A "Set one stat equal to another while a scoped condition holds" below. Reference: `_03028` Térence. |
| **"During <Phase>, you may choose not to <auto-action on Owner>"** (opt-out of an auto-emitted event) | Pattern D Reaction listening on the *pre*-event (e.g., `EventCardMoving` for the Dusk move-home) with `sourceId == 0` (auto-emitter signal) + a phase gate (`TURN_PHASE == Game::DUSK`) + the `cancelDeclinedByCardIds` re-queue dance. Cancel the event, clone it, prompt the player; on "Keep" call `setUsed(true)`; on "Decline" re-queue the clone with `cancelDeclinedByCardIds[] = owner->Id` so the reaction doesn't immediately re-catch it. See Pattern D's "Cancel-and-reissue Reaction" subsection. Reference: `Reaction_03016a` (Ise Dusk opt-out), `Reaction_01140` (in-hand RiskReaction sibling). |
| **"<Stat> increases by N"** / **"<Stat> is reduced by N"** | Queue `createCharacter<Stat>ModifiedEvent` (e.g., `createCharacterInfluenceModifiedEvent`). See `_01007` Aldo for renown-driven Influence modification. |
| **"<Owner> has +N[Stat] for each X in her dueling line"** (or any duel-line-derived count) | Pattern A passive with a running `$<Stat>Bonus` field on the card. Recompute at `EventDuelEndOfRound` (the only clean boundary — there is no event fired when a card enters the dueling line; `cards->moveCard` is called directly). Reset at `EventDuelEnd` *before* the line is cleared. Gate on the owner being a duel participant (the dueling line is per-player, not per-character). See Pattern A "Dynamic stat bonuses tied to the dueling line" below. Reference: `_03004` Elena. |
| **"While you control X at <Owner>'s location, she has +N [Stat]"** (any location-counting passive) | Pattern A passive that hooks `EventCardMoved` (and `EventCharacterMustered` / `EventApproachCharacterPlayed` / `EventCharacterDestroyed` / `EventCharacterRecruited`). **`EventCardMoved` fires BEFORE the DB location update** (`runEventHubAfterCards = true`), so `getCharactersAtLocation` returns the *pre-move* state. Either pass an explicit `+1`/`-1` adjustment (per-character count — `_01037`) or thread the event into the helper to exclude the moving-out card and look up the moving-in card (binary "any qualifying member" bonus — `_03026`). Add a no-op gate `if ($new == $this->ModifiedStat) return;` to skip same-value events. See Pattern A "Location-counting passives" below. |
| **"While <Owner> is opposed by N or more wounded characters, +M [Stat]"** | Pattern A hybrid: location-counting of **opposing** characters **plus** wound-state. Use Ise flag ±1 (not absolute `Combat + bonus` — attachments also mutate Combat). Hook moves/muster/approach/destroy/recruit **and** `EventCharacterWounded`/`EventCharacterHealed` at the location. **Home → count 0** — `LOCATION_PLAYER_HOME` is shared across players, so `getOpposingCharactersAtLocation(HOME)` falsely counts enemies at *their* Homes. For wound events on *other* characters, if `!$event->characterHandled` yet, apply the event's wound delta when counting. See Pattern A "Opposed by N+ wounded". Reference: `_04001` Benci. |
| **"Opponents' abilities cannot wound (or move wounds to) <Owner>"** / "<Owner> ignores wounds from X" | Override `eventCheck` on the card class and zero `$event->wounds` on `EventCharacterBeingWounded`. Distinguish ability-emitted wounds (non-empty `abilityId`) from threat-conversion wounds (empty `abilityId`). See Pattern A "Wound-prevention passive" below. Reference: `_03014` Kaspar (opponent's-ability scope), `_01069` Maxime (own-Sorcerer scope), `_01153` Breastplate (in-duel reduction-by-one). |
| **`<b>Action:</b>`** / **`<b>City Action:</b>`** | Implement `IHasActions`, `use ActionTrait`, create `actions/Action_NNNNN.php` extending `CharacterAction`. State class(es) + JS wiring per Pattern C. **"City Action" only differs by the `cardInCity` gate** in `isAvailableToPlayer`. |
| **Two (or more) distinct City Actions / Actions on one card** | Split into separate classes `Action_NNNNNa`, `Action_NNNNNb`, … each with its own Name, availability, states, and transition keys (`"NNNNNa"`, `"NNNNNb"`). Wire all in `$this->Actions = [...]`. State IDs append `1`/`2` (e.g. `4030381` / `4030382`); a multi-step **b** uses `40303822` for step 2. Reference: `_03038` Damya, `_01095` Patricia. |
| **"Draw a card. Then, discard a card."** | Queue `createCardDrawnEvent` in `EventActionTriggered` **before** `createTransitionEvent` into the discard state — printed order is Draw → Discard, and the client needs the drawn card in `factionHand` before the picker. Hand picker = Pattern C `factionHand` (not `highlightCardsAsSelectable`). Availability: `cardInCity` + player will have ≥1 hand card after draw (hand nonempty **OR** faction deck + discard nonempty — empty-everything hangs the discard state). Reference: `Action_03038a`. |
| **"Your equipped character moves to this location. Then, destroy their attachment to draw …"** | Two-step Pattern C. "Equipped" = your character with ≥1 non-`FakeAttachment`. Strict "moves to" → exclude characters already at the owner's location (and thus the owner herself when she is there). No "target" in text → **no** `IAbilityThatTargetsCharacters`. Move with `engage=false` when Engage is not printed. Attachment step: button list (Adelheide `01194`), not board highlight. Destroy = unequip + `createCardDiscardedFromPlayEvent`; capture `WealthCost` **before** destroy; then queue `WealthCost + 1` draws. Parenthetical "must be destroyed to draw" → no Pass on the attachment step. Reference: `Action_03038b`. |
| **"Move <Owner> to your Leader's location. Then, en garde your attachment there."** | Pattern C. Availability: `cardInCity`, Leader exists, Owner ≠ Leader location, ≥1 Engaged non-Fake attachment that will be at the destination (Owner's engaged attachments travel with him + engaged attachments already on your characters at the Leader's location). Queue `createCardMovingEvent(..., $engage=false)` then `createTransitionEvent("NNNNN")` into an Adelheide-style attachment **button** picker; En Garde via `createCardEngardedEvent` (not Engaged). WHY require Engaged: En Garde on a ready attachment is a no-op. See Pattern C "Move to Leader then En Garde attachment". Reference: `Action_03051`. |
| **"Your other characters at this location gain: Technique: …"** (location Technique aura) | Pattern A on the **card class** (not a Reaction). Mirror Jean `_01067` / Stranahan `_02022`: grant on `EventCharacterRecruited` / `EventCardMoved` (aura source moves in, ally moves in); remove on leave / aura source destroyed / aura source moves out. Home excluded. Grant with `setId("Technique_NNNNN")` then `setOwnerId($character->Id)`; remove via `getTechniqueByClassId("Technique_NNNNN")`. Trait filter only when text names a trait (Musketeers); "other characters" = every other controlled character, **not** the aura source. See Pattern A "Location Technique grant aura". Reference: `_03051` Yepikhodov, `_01067` Jean. |
| **"Your <Trait>s at Home and <Owner>'s location gain +N[Stat] and +M Resolve"** (location trait **stat** aura) | Pattern A on the card class — **not** a Technique grant. Track `$BuffedIds` on the aura source; sync apply/remove on move/muster/approach/recruit/destroy. Finesse/Combat/Influence via factories; **Resolve via direct `ModifiedResolve ±1` + `characterResolveModified` notif** (chip stays flat without it). Home is **in** scope when printed ("at Home and …") — use `getCharactersAtHomeByPlayerId`, never `getCharactersAtLocation(HOME)`. Contrast Jean Technique aura (Home excluded). See Pattern A "Location trait-stat aura". Reference: `_04002` Danilo. |
| **"Destroy [an] attachment"** (effect, any context) | Canonical recipe: `createAttachmentUnequippedEvent` → `eventCheck` → queue; then `createCardDiscardedFromPlayEvent(..., $asEffect = true)`. Do **not** invent `createAttachmentDestroyedEvent` — it does not exist. Reference: `Action_01174`, `Maneuver_01142`, `Action_03038b`. |
| **"Issue a [stat] challenge to target …"** (any flavor) | CharacterAction that sets `CHOSEN_PERFORMER`/`CHOSEN_TARGET`/`CHALLENGE_STAT`/`CHALLENGE_TYPE` and queues a transition into the challenge sub-state machine. See Pattern F. **Engagement is a trichotomy** — do not assume "no Engage printed" means Don Constanzo conditional-engage; some actions never engage (Sanjay `_03037`). |
| **"If another character intervenes, wound them or draw a card"** (post-intervene choice on own challenge) | Pattern F follow-up. Mint a `*_CHALLENGE_TYPE` to key `EventCharacterIntervened` (even with **no** intervene/refuse restrictions). Engage printed → trichotomy (a) + auto-engage list. On intervene: store intervener id, `createTransitionEvent("NNNNN_3")` — map that key on **`HIGH_DRAMA_CHALLENGE_ACTION_GENERATE_THREAT_EVENTS`** (intervene is processed there, not PLAYER_TURN_EVENTS). Choice state buttons → return to GENERATE_THREAT_EVENTS. Auto-wound-only sibling is Cornered `_03021` (no choice state). See Pattern F "Intervene follow-up choice". Reference: `Action_04002` Danilo, `_03021` Cornered. |
| **"<Owner> cannot refuse challenges issued by … with greater [Stat]." / "When <Owner> issues a challenge, characters with greater [Stat] cannot refuse."** | **Character-scoped refuse** — do **not** mint a new `CHALLENGE_TYPE`. Helper on the card class comparing `Modified*` stats (strict `>` for "greater"); wire `actHighDramaChallengeActionReject` + args flag + JS Refuse disable + zombie Accept-when-blocked. See Pattern F "Character-scoped refuse restriction". Reference: `_03050` Daichi. Contrast Aja/Épée (type-owned challenges). |
| **"… If their controller has fewer cards in hand than you, …"** (hand-size gate on targets) | Filter opposing targets (and `isAvailableToPlayer`) by comparing `count($game->getGameDeckObject()->getPlayerHand($controllerId))`. Prefer filtering at availability so the action never offers a dead pick. Reference: `Action_03037` Sanjay. |
| **"Your <Trait> at this location issues a challenge"** (performer ≠ owner) | Two-step Pattern F: step 1 picks the *performer*, step 2 picks the target at the *performer's* location. Engagement follows the trichotomy (Don Constanzo = conditional engage; never-engages variants emit no engage). See Pattern F's "Performer ≠ action owner" subsection. Reference: `Action_03003`. |
| **"Engage <Owner> • Pressure this location with [Stat]. You succeed even if tied. …"** | Pattern C pressure Action. Set `PRESSURING_PLAYER`, `CHOSEN_PERFORMER`, `PRESSURE_TYPE = NORMAL`, `PRESSURE_STAT`, mint a **new** `*_PRESSURE_TYPE` bit flag + OR it into `UtilitiesTrait::pressureLocation`'s win-ties list, `createCardEngagedEvent` (Engage printed), `createPressureOccuringEvent`, transition `"pressureLocation"`. Handle `EventLocationPressureResult` for the success effect. Do **not** reuse another card's win-ties flag. See Pattern C "Pressure (win ties)". Reference: `Action_03040` Soline, `Action_01075` Tabard. |
| **"If successful, claim it or engage an opposing character"** (post-pressure choice) | On `EventLocationPressureResult` success: if claimable OR unengaged opposing exist → `createTransitionEvent("NNNNN")` into a choice state; else notify + `createActionResolvedEvent`. Choice UI: Claim button (`actFromCardWithId({id: 0})`) + character highlight/Confirm for engage. **No Pass** when the text is mandatory "or" (contrast `Action_01105`'s optional engage Pass). No `IAbilityThatTargetsCharacters` without "target". See Pattern C "Claim or engage after pressure". Reference: `Action_03040`. |
| **`<b>Reaction:</b>`** / **`<b>City Reaction:</b>`** | Implement `IHasReactions`, `use ReactionTrait`, create `reactions/Reaction_NNNNN.php` extending `CardReaction`. Button-based reactions need **no** state class, **no** `states.inc.php` edits, **no** JS wiring. See Pattern D. |
| **"At the beginning of Dusk, you may look at the top N cards of the City Deck. If you do, sink one and replace the others in any order."** | Pattern D **Continuous Reaction** on `EventDuskPhaseBegin`: Look/Pass buttons, then a private chooseList sink state and private reorder state. Snapshot the top cards when Look is chosen; sink with `createCardAddedToCityDeckEvent(..., false)` (NOT city discard); auto-finish if ≤1 card remains. Use `argsForStatePrivate`. See Pattern D "Beginning-of-Dusk private look, sink, and reorder". Reference: `Reaction_03052`. |
| **"Reaction: After <enemy/opposing> character moves to this location • <effect>"** | Pattern D on `EventCardMoved` (past-tense). Gates: `event.cardId != owner.Id`, `event.toLocation == owner.Location`, `cardInCity(owner)`, `$character instanceof Character`, `ControllerId != 0`, **and** enemy controller (`!= owner.ControllerId`). Pair with a valid-effect-target precondition. See Pattern D's "After a character moves to this location" subsection. Reference: `Reaction_03016b` (Ise). |
| **"Reaction: After a character moves to this location • <effect>"** (no "enemy"/"opposing") | Same `EventCardMoved` gates as above **except omit the enemy-controller check** — allies arriving also trigger. Still skip Owner self-moves, non-Characters, and `ControllerId == 0`. Reference: `Reaction_03040` Soline. For the *self-moves* analogue ("after this character moves to a new location"), see `_01067` Jean Urbain and `_02022` Stranahan. |
| **"Move <Owner> to any City location"** (Reaction effect) | Button-based Pattern D — one button per city location **except** current (`array_keys($theah->getCityLocations())` filtered). Pass declines without `setUsed`. Queue `createCardMovingEvent(..., $engage=false, …)` when Engage is not printed. No state class / no JS. Adjacent-only variant uses `getAdjacentCityLocations` instead (`Reaction_01089`). Reference: `Reaction_03040` (any city), `Reaction_01089` (adjacent). |
| **"Move another character you control to this location"** (effect) | Queue `createCardMovingEvent($character.ControllerId, $character.Id, $character.Location, $owner.Location, $engage, $owner.Id, $this->Id)` for the chosen mover. Eligible movers = `getCharactersInPlayByPlayerId($owner.ControllerId)` minus the owner herself minus characters already at her location. Don't use any pull/teleport helper — there isn't one; the standard move event handles all the bookkeeping. Reference: `Reaction_03016b` (other character to here), `Reaction_01039` (move self to adjacent). |
| **"Reaction: … at -N cost"** / **"… pay N Wealth"** | Pattern D Reaction with **in-reaction click-to-pay** wealth tracking. Don't use `PAY_STATE_PLAY_BRUTE` — it's tied to the player-turn state cycle. See Pattern D's "Reactions that need to pay a wealth cost" subsection. Reference: `Reaction_03003`. |
| **"Reaction: Put a different X into play from your hand or discard pile"** | Pattern D Reaction. Filter eligibles separately from `LOCATION_HAND` and `getPlayerDiscardDeckName(...)`, exclude the just-destroyed card by id. `createCharacterMusteredEvent` does the actual move; `createCardRemovedFromPlayerDiscardPileEvent` is notification-only (fire it before the muster so JS clients sync correctly). Reference: `Reaction_03003`, `Action_01024` (Bravos). |
| **"Reaction: After a character equips an attachment at [location]"** | Pattern D Reaction on `EventAttachmentEquipped`. Gate: `cardInCity($owner)`, `$owner->Location == <named location>`, equipping `$character->Location == <named location>`, skip `$attachment->FakeAttachment`. Trigger is **any character** equipping there — do NOT gate on `$event->characterId == $owner->Id` unless the text names the owner ("After Philip equips …"). Contrast: `Reaction_01039` (Philip self-equip → move self). Draw/move/etc. effects use Draw/Pass or Pass-only buttons per Pattern D. Reference: `Reaction_03028` (any character at Grand Bazaar), `Reaction_01146a` (scheme owner equips Weapon). |
| **`<b>Sorcerer …</b>`** (Sorcerer Action / Sorcerer Reaction) | The Action/Reaction class additionally `implements ISorcererAbility`. **Must** call `createSorcererAbilityStartEvent()` and `createSorcererAbilityPlayedEvent()` (pre-commit hook enforces). See `Action_01076` and `Reaction_02001`. |
| **`<b>Technique:</b>` / `<b>Maneuver:</b>`** | The Character lineage already brings `TechniqueTrait`. Add `IHasManeuvers` + `ManeuverTrait` for maneuvers. Implement under `cards/<expansion>/techniques/` or `cards/<expansion>/maneuvers/`. See Pattern E. |
| **`<b>Gambling Technique:</b>`** | Same as Technique, but `isAvailableToPlayer` additionally gates on `Game::DUEL_GAMBLED` (actor gambled for their combat card this round). See Pattern E. |
| **"Gambling Technique: Look at your adversary's hand."** | Pattern E private acknowledge state. Gate on `IN_DUEL` + `DUEL_GAMBLED` + actor-is-owner; hide when adversary hand is empty. On Resolve, transition back to the **owner's** controller, return hand properties only through `argsForStatePrivate`, render chooseList read-only, and provide Done/Pass. Do NOT use Technique `_03043`'s multiplayer public-reveal flow. See Pattern E "Privately look at adversary's hand". Reference: `Technique_03052`. |
| **"Technique: Look at the top N cards of your deck. You may sink one or both / any and return the others in any order."** | Pattern E duel Technique. Snapshot top N into `Game::CHOSEN_CARD`; `createTechniqueTransitionEvent("NNNNN")` (HIGHEST_PRIORITY). Sink step + optional reorder step (`nextState("reorder")` → `DUEL_CHOOSE_TECHNIQUE_NNNNN_2`). **Immediate** `insertCardOnExtremePosition(..., false)` for sinks (queued sink events race top-inserts). Auto-replace when ≤1 remains. `argsForStatePrivate` for Look. Pass = sink none. Hide when deck empty. Mirror Action_04cd15 flow; City Deck sibling is Reaction_03052. **Must** wire `EventHandlers.js` `onChooseCardClicked` for multi-select sink enable **and** `addSortTagToCard` on the reorder state — without it, reorder numbers never appear. See Pattern E "Look / sink / reorder own Faction Deck". Reference: `Technique_04001`, `Action_04cd15`. |
| **"… return the others in the same order"** (no reorder) | Same look/sink Technique but **omit** the reorder state — only sink selected cards to the bottom; remaining stay in place (Technique_01010 adversary-deck shape). Parse "same order" vs "any order" literally before copying 04cd15. |
| **"-N[Thrust/Riposte/Parry] • … (Your combat card must have at least N …)"** | Gate availability with `getCurrentRoundThrust()` / `getCurrentRoundRiposte()` / etc. `>= N`. Apply the −N on `EventDuelCalculateTechniqueValues` (`$event->thrust -= N`). Parenthetical is the printed cost clarification — same gate shape as `Technique_01050` (−1 Thrust) / `Technique_01093` (−1 Riposte). |
| **"The adversary discards a card"** (Technique effect) | On `EventResolveTechnique`, if adversary hand nonempty: `createTransitionEvent($adversary->ControllerId, …, "NNNNN", …)` into `DUEL_CHOOSE_TECHNIQUE_NNNNN` (active player = adversary). `actFromTechniqueWithId` validates hand ownership + queues `createCardDiscardedFromHandEvent(..., $asEffect = true)`. Empty hand → skip picker. JS: `factionHand` single-select + `onCardDiscarded` + **also** wire `EventHandlers.js` so Confirm enables on selection. Reference: `Technique_01093` Maya, `Technique_03039` Íñigo. |
| **"Then, if they have more cards in hand than you, en garde <Owner>"** (after adversary discard) | Compare hands **after** the discard. In `actFromTechniqueWithId` the discard is queued not flushed — use `(adversaryHandCount - 1) > ownerHandCount`. Empty-hand path compares `0 > owner` (never engardes). Queue `createCardEngardedEvent`. Reference: `Technique_03039`. |
| **"At the end of the round, move <Owner> Home"** (Technique follow-on) | Private `$MoveHome` flag set on `EventResolveTechnique` (unconditional once the technique resolves — do not gate on the hand-size En Garde clause unless the text does). On `EventDuelEndOfRound`: clear flag, skip if discard/locker or already Home, queue `createCardMovingEvent(..., Game::LOCATION_PLAYER_HOME, $engage=false, …)` when Engage is not printed (contrast `_01053` which engages). Clear on `EventTechniqueCanceled` / `EventDuelEnd`. Reference: `Technique_03039`, move-deferral sibling `Technique_01036`. |
| **"+1[Parry]. You may engage an Artifact equipped to <Owner> for +2[Parry] instead."** | Pattern E. Base +1 is always legal (`IN_DUEL` + actor-is-owner — **no** Artifact gate on availability). On `EventResolveTechnique`: if no unengaged Artifact → set `$ParryBonus = 1`; else `createTechniqueTransitionEvent` into `DUEL_CHOOSE_TECHNIQUE_NNNNN`. Choice: `id: 0` = +1, attachment id = `createCardEngagedEvent` + `$ParryBonus = 2`. Apply on `EventDuelCalculateTechniqueValues`. Clear bonus on `EventTechniqueCanceled`. See Pattern E "Optional engage Artifact for upgraded Parry". Reference: `Technique_03049`, engage-picker sibling `Technique_02011` (Katain — mandatory engage, no base option). |
| **"If <Owner>'s combat card is a <b>Flourish</b> or <b>Sorcery</b> • +N[Stat]"** | Pattern E combat-card trait gate with an **OR of traits**. Same `getCombatCardsForCurrentRound()` + `ControllerId` filter as Elena; accept if either `hasTrait` matches. Reference: `Technique_03050` Daichi, single-trait sibling `Technique_03004` Elena. |
| **"Engage <named character>'s attachment • Copy the effects of a Technique on that attachment"** (granted / third-party copy) | Pattern E. Availability: `IN_DUEL` + actor-is-owner (the **granted** character) + named aura source at same location with ≥1 unengaged non-Fake attachment that has copyable techniques. On Resolve → `createTechniqueTransitionEvent` into technique-button picker. On pick: `createCardEngagedEvent` on the technique's owning attachment, then Dame `02055` clone/activate/resolve/calc. **Do not** filter source techniques with `isAvailableToPlayer` — those gates assume the attachment's character is the duel actor. Identify the named character by `ExpansionName` + `CardNumber` (avoid `instanceof` circular require from Technique → card). See Pattern E "Engage named character's attachment and copy a Technique". Reference: `Technique_03051`, copy sibling `Technique_02055`. |

## Finish (short)

1. Walk each printed Text clause → exactly one pattern (see shape table). Parse literal wording traps before copying a mirror.
2. Constructor: `initializeFaction` (non-Leader), matching `CardNumber`, Resolve/Combat/Finesse/Influence (+ `DashedX`), Traits in `TraitNames::$TraitsJson`. Leaders also set `CrewCap` / `Panache`.
3. Ability files in `actions/` / `reactions/` / `techniques/` / `maneuvers/` as needed.
4. Wire states + JS when you add card-specific sub-states — see [wiring.md](wiring.md). Button Reactions usually need neither.
5. Satisfy pre-commit literals (ActionResolved / ManeuverCanceled / setUsed+isAvailable / Sorcerer start+played / attachment Riposte when relevant).
6. `php -l` on touched PHP.

**Deep checklist:** [checklist.md](checklist.md)

