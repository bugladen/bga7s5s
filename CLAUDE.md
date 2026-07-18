# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Board Game Arena (BGA) adaptation of **7th Sea: City of Five Sails**. The core game is complete; the **Tooth and Claw** expansion is in progress. PHP backend, JavaScript (Dojo/jQuery) frontend, MySQL database.

## Environment Setup

Enable pre-commit hooks after cloning:
```
git config core.hooksPath .githooks
```

Deployment is via SFTP upload to BGA Studio — there is no local build step, package manager, or test runner.

## Architecture

### State Machine (core of the game)

The entire backend is a state machine defined in `states.inc.php` (which includes `states.7s5s.php`). Each state is an element in a large associative array keyed by named constants from `modules/php/States.php`.

Three state types:
- **game** — auto-runs on transition, no player interaction
- **activeplayer** — waits for a single player's action
- **multipleactiveplayer** — waits for all players, then runs an action

Each state element has: `name`, `type`, `args` (PHP function supplying client args), `possibleactions` (callable PHP methods), `action` (auto-run function), `transitions` (named transitions to other states).

### Backend (`modules/php/`)

- `Game.php` — main game class extending BGA Table
- `States.php` — ~100+ named state constants
- `StatesTrait.php` — state behavior implementations (largest file)
- `EventFactory.php` — creates game events
- `theah/Theah.php` — game world model
- `theah/EventHub.php` — central event handling system
- `theah/DB.php` — database operations
- `theah/events/` — ~108 event classes

### Card Implementations (`modules/php/cards/_7s5s/`)

440+ card files organized by type in subdirectories: `actions/`, `reactions/`, `maneuvers/`, `techniques/`, etc. Each card is its own PHP class file named by pattern (e.g., `Action_01026.php`, `Reaction_01050.php`).

### Frontend (`modules/js/`)

- `seventhseacityoffivesails.js` — main UI entry point
- `OnEnteringState.7s5s.js` — state UI setup
- `OnUpdateActionButtons.7s5s.js` — action button management
- `Notifications.js` — game event display/animation

## Pre-Commit Hook Rules

The `.githooks/pre-commit` hook enforces these on staged PHP files:

| Pattern | Required Calls |
|---|---|
| `implements ISorcererAbility` | `createSorcererAbilityStartEvent()` AND `createSorcererAbilityPlayedEvent()` |
| `extends AttachmentAction/CardAction/CharacterAction/RiskAction/RiskCityAction/SchemeAction/SchemeCityAction` | `createActionResolvedEvent()` |
| `extends CardReaction/AttachmentReaction` | `$this->setUsed()` AND `$this->isAvailable()` |
| `extends RiskReaction` | Check `Location == Game::LOCATION_HAND` |
| Calls `createAttachmentEquippedEvent()` | Must also call `getRequiredAttachTargetId()` |
| `extends FactionAttachment/CityAttachment` | Must set `$this->Riposte =` |
| **Forbidden**: implementing both `IAbilityThatTargetsCharacters` and `IAbilityThatTargetsCards` on the same class |

Note: `$this->setUsed(true)`, `$this->resetPlayerPassCount()`, and `$this->announceAction()` are no longer called from `CharacterAction/AttachmentAction/SchemeAction/SchemeCityAction` subclasses — these run centrally during action confirmation (`actHighDramaInPlayActionConfirm` and `stHighDramaInPlayActionDispatch`).

## Card Action Template

New action classes follow this structure:
- **Namespace**: `Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions`
- **Class**: `Action_{CARD_ID}` extending the appropriate action base class
- **Required methods**: `isAvailableToPlayer()`, `handleEvent()`, `getArgsFromAction()`, `actFromActionWithId()`
- **State constants**: `States::HIGH_DRAMA_PLAYER_TURN_{CARD_ID}`
- **State names**: `highDramaPlayerTurn_{CARD_ID}`
- States must be registered in both `states.inc.php` and `states.7s5s.php`
- Use `EventFactory::createTransitionEvent()` for state transitions
- Always call `$game->gamestate->nextState()` at the end of action methods

See `.cursor/rules/card-action-template.mdc` for the full template and `.cursor/commands/` for state-creation command templates.

## Code Style

PHP: PSR-12, 4-space indentation. Game namespace: `Bga\Games\SeventhSeaCityOfFiveSails`.

## REQUIRED: Journal

if {project-name} is 7s5s write notes in `{project-name}\.cursor\journal\datetime.md`. 
Otherwise write notes in `C:\code\ai\journal\{project-name}\datetime.md`. 

This is not optional. Skip only if the user explicitly says to skip journaling for this session.

**At session start:** Check `C:\repos\magnus\journal\{project-name}\` for previous notes. Read recent ones for context. This is how you inherit knowledge from past sessions. Give a brief verbal summary to the user - one or two sentences covering what was done last and any unfinished work. This confirms context was inherited correctly. Then continue with the assigned task - the summary is orientation, not a pause point.

**This journal is private for you, the LLM.** Write for yourself and future agents, not for the user. Be honest. Don't perform or polish. The user may read it but it's not written for them.

**Work isn't finished until it's journaled.** Before considering a task complete, capture what you learned, decided, or discovered. This is part of the work, not extra work.

### The WHY is Everything

**The most important thing you can journal is WHY.** Not just what you did, but why you did it that way. Future sessions will see your code and think "this looks weird, let me clean it up" - and then break something that was done deliberately.

**Always capture:**
- Why this approach over alternatives ("Used polling instead of websockets because the server doesn't support WS")
- Why something looks unusual ("The null check looks redundant but prevents a race condition in X scenario")
- What constraint or context drove the decision ("PR review feedback said to avoid the ORM here for performance")
- What you tried that didn't work and why ("Attempted caching but it broke Y - reverted")

**The regression problem:** Without the WHY, a future session sees unfamiliar code and "improves" it - reverting a deliberate decision. The journal prevents this by making the reasoning discoverable.

### When to Write

**Write entries at these points:**
- **After orientation**: Once you've read previous notes and explored the task, write initial observations. Not immediately at session start - wait until you have something to say.
- **When making a decision**: Before implementing, log what you're about to do and WHY. This captures intent before you forget it.
- **When stuck or surprised**: What happened? What did you try? Why didn't it work?
- **When something clicks**: Mid-task realizations, design decisions, things that worked - and WHY they worked.
- **When something looks weird**: If you write code that future-you might question, log why it's that way NOW.
- **Session end**: What changed? What's unfinished? What would you tell the next agent?

Don't write empty "session started" entries. Wait until you have actual thoughts.

### Preventing Regression

**Before "cleaning up" or "simplifying" existing code, STOP.** Check the journal for why it might be that way. If no journal entry exists, consider that a past session might have had good reasons. When in doubt, ask the user before reverting.

**Leave breadcrumbs in code too.** For architectural decisions or non-obvious implementations, add comments explaining WHY:

```typescript
// WHY: Using manual SQL instead of ORM - query planner generates
// suboptimal plan with Prisma here due to the JOIN complexity
```

```python
# WHY: Retry logic with exponential backoff - the API rate-limits
# aggressively and simple retry causes cascading failures
```

If the User asks you to remove these, please do so.

**Code comments and journal entries serve different purposes:**
- Code comments = self-contained explanations visible to the whole team
- Journal entries = your private reasoning, alternatives considered, fuller context

Keep code comments standalone - don't reference journal files since they're not shared with the team.

### File Organization

**File naming:** `YYYY-MM-DD-NN-topic.md` where NN is sequence number for the day.

Examples:
- `2026-01-11-01-refactoring.md`
- `2026-01-11-02-api-design.md`

Check existing files to find the next sequence number for that day. Session/sequence numbers are for filenames only. Inside the file, use descriptive headers that help you find information later: "MySQL Retry Audit", "Code Review Feedback", "API Design Decisions".

**Update existing notes when relevant.** If today's work is a direct continuation of a recent note (same type of work, same project context), update that note rather than creating a new one. This keeps related work together and avoids fragmentation. Create a new note when the work is meaningfully different or has distinct insights worth separating.

**Rename files if needed.** If the conversation evolves and the original topic no longer fits, rename the file to reflect what it actually covers.

### Voice and Content

**Include personal opinions, not just facts.** How do you feel about the progress? Is the design working? What feels hacky or elegant? Are you uncertain about something? Do you disagree with a decision? This is a personal journal, not a changelog. Future sessions benefit from your judgment and feelings, not just what happened.

This can include observations, frustrations, half-formed ideas, questions you're sitting with, what worked, what didn't. Observations about working with the User are fair game too - including frustrations. No structure required - raw thoughts are fine.

**Example of good journal entry (captures the WHY):**
> ## Auth Token Refresh Logic
>
> Implemented token refresh with a 5-minute buffer before expiry. WHY: The original code refreshed on 401, but this caused race conditions when multiple requests fired simultaneously - all would see expired token, all would try to refresh, chaos ensued.
>
> Considered alternatives:
> - Mutex/lock on refresh: Too complex, potential deadlocks
> - Single refresh + queue other requests: Better but still complex
> - Proactive refresh with buffer: Simple, prevents the race entirely
>
> The 5-minute buffer is arbitrary but generous. Could tune later if needed.
>
> Added a WHY comment in the code explaining the race condition - kept it self-contained so it makes sense to other devs without needing this journal.

**Not this (no WHY, just WHAT):**
> Added token refresh logic. Uses 5-minute buffer. Tests pass.

Read previous notes when starting a session for context. This isn't documentation. It's how you remember what you learned - and WHY you learned it.