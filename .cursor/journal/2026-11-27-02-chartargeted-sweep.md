# EventCharacterTargeted emitter sweep

User asked to sweep the IAbilityThatTargetsCharacters gap after Vantage Point / Cloud Bottle.

## Inventory
- 126 implement `IAbilityThatTargetsCharacters`
- Started at ~16 in-file emitters; mid-session at **27** (gap 99, of which ~31 challenge-shaped are Framework-covered)
- Challenge-shaped → `FrameworkActionsTrait` challenge picker already emits CharacterTargeted

## Approach
Emitter-side only (Bleed Out / Vantage Point seat). Do not widen Terraria.
Gate effects on surviving `EventCharacterTargeted` with shared `batchId`.
Costs (discard/wealth) stay **outside** the targeting batch so cancel does not refund.

## Fixed this session
| Card | Shape |
|------|-------|
| Reaction_04020 Vantage Point | engage-or-wound (reported bug) |
| Action_05Vulture | simple wound |
| Action_04cd29 Tijani | simple wound + Finesse recheck |
| Action_01015 | destroy+wound both gated |
| Action_01049 Flintlock | engage-or-wound |
| Action_01156 Matchlock | discard cost outside batch; effects gated |
| Action_01012, Action_01019 | agents |
| Action_03001 Cesca | opposing target pick (_2) |
| Reaction_03001, Reaction_02001, Reaction_04058 | agents |

## Caveats learned
- Matchlock discard must NOT share CharacterTargeted batchId
- 01015 destroy performer must gate with the wound (not irreversible before hook)
- Engage-or-wound: act = CharacterTargeted only; survive opens choice / auto-wounds
- Skill B.5 said skip CharacterTargeted for Action_04018 — wrong for Cloud Bottle; fix when reaching that card

## Self-only (lower priority)
Action_02010 moves wounds between own characters — Cloud Bottle opposing-initiator gate would not offer.

## Unfinished
Next wave agent working through more HIGH opposing-target emitters (01152a/b, 04018, 05William, etc.). Full sweep not done.

## Action_01049 / Action_01156 detail (agent: engage-or-wound AttachmentActions)
- 01049: pick (state 01049) now only notifies + `eventCheck` + queues `EventCharacterTargeted` (batchId only), then `nextState("characterChosen")`. `handleEvent` on surviving hook (abilityId == this->Id, !canceled) runs the old branch: engaged -> wound + engage flintlock (shared batch) + ranged + resolved; else sets CHOSEN_TARGET and queues `01049_2` transition (shared batch). Stage 01049_2 untouched. WHY CHOSEN_TARGET moved into handleEvent: a canceled hook must not leave a stale target behind.
- 01156: pick (state 01156_2) queues discard cost (unbatched, not tied to cancel; card is already parked so it MUST be discarded or it strands in Purgatory) then `EventCharacterTargeted`. handleEvent continues: engaged -> wound (shared batch) + ranged + resolved; else CHOSEN_TARGET + `01156_3` transition (shared batch). Katain effects-only copy still skips discard. 01156_3 untouched.
- RangedAbilityPlayed / ActionResolved deliberately NOT batched (same as 01160 / Reaction_04020) so a racing deleteEventBatch does not strip the resolve.
- Messages: the old "selected as target" strings moved/split � act emits a short "targeting" line; handleEvent emits the engaged / "must decide" lines. New clienttranslate strings in 01156 (and 01049 keeps the plain "selected as the target" one; its "already Engaged" notify is dropped).
- Caveat I'm unsure about: on canceled hook nothing queues ActionResolved (matches 01160). If the engine needs ActionResolved to leave the action state, a cancel could stall; Cloud Bottle's own cancel path presumably handles that, but untested end-to-end.
- Tests: Action_01049_Test / Action_01156_Test updated (pick = Targeted only, survive continues, canceled queues nothing, other abilityId ignored). Ran with Card_01049/01156/Technique_01049: 39/39. Note `php tests/run.php <file>` on Windows needs backslash paths.
- Mistake: I ran `git stash`/`stash pop` briefly while other agents had dirty files � popped clean, but don't do that in a shared tree.
- Full runner at the time: Reaction_04058 "redirects the wound onto an opposing character" FAIL � not mine, another agent's in-flight change.

## Sweep 2: 01012, 01019, 03001 (Action + Reaction), 02001, 04058 — all fixed
Same shape everywhere: pick/perform queues ONLY `createCharacterTargetedEvent(controller, target, sourceCardId, this->Id)` with a `getNextEventBatchId()` batchId + `eventCheck`; `handleEvent` on `EventCharacterTargeted && abilityId == this->Id && !canceled` emits the effects, wound/heal/destroy sharing `$event->batchId`. None were self-only (every one picks an opposing character, or any character at the location for 01019), so no skips.

- **Action_01012 (Sibella)**: self-wound, SorcererAbilityStart, target wound, SorcererAbilityPlayed, ActionResolved all moved to the survive hook. WHY self-wound gated: canceled action must not leave Sibella paying a pure cost (same reasoning as destroy in 01015). Self + target wounds share the batch.
- **Action_01019 (Buratino)**: `unEquipAllAttachments` + destroy + wound + resolve gated (same as 01015). Target can be any character at location incl. own, so it's a real targeting hook.
- **Action_03001 (Cesca)**: first-state Strega pick untouched. State `_2` opposing pick -> CharacterTargeted only. Survive: heal Strega + wound target (shared batch) + ActionResolved. WHY re-check Strega at survive: she is read from `CHOSEN_CARD`; if her wound is gone by then (or she left), heal+wound are skipped together so the target isn't wounded for free, but the action still resolves. Tested.
- **Reaction_03001 (Cesca City Reaction)**: performReaction -> CharacterTargeted; wound on survive. `setUsed` still happens at pick (cost is paid by choosing).
- **Reaction_02001 (Andriana)**: SorcererStart + wound + SorcererPlayed all on the survive hook (Start/Played gated so a canceled reaction doesn't emit a dangling Start). Target is read from `event->targetId`, so clearing `CharacterId` at pick is fine.
- **Reaction_04058 (Shield Rite redirect)**: the careful one. Redirect now happens in the `EventRiskReactionTriggered` (post-pay) branch: queue CharacterTargeted, `setUsed`, but do NOT reset saved state. Survive hook (matched by `abilityId` + `chosenTargetId == targetId`) emits Start, releases the cloned intercepted wound onto the target (new optional `$batchId` arg on `releaseEvent`), emits Played. State (`characterWoundedEvent`, `performerId`, `chosenTargetId`, `isCescaCopy`) is reset in the hook on BOTH survive and canceled — otherwise canceled targeting would leave stale sticky state. WHY not re-release the original wound on cancel: the redirect already replaced it; the redirected wound is the thing Cloud Bottle countered. Decline / invalid-target paths unchanged (still re-release onto performer). Cesca-copy path flows through the same hook (synthetic wound, isCescaCopy reset after).
  - Unverified assumption: a paid Risk still receives handleEvent after pay (Theah loops `$this->cards`, "including hands"). Reaction_04020 relies on the same, so I followed it.

## Tests
Updated Action_01012/01019/03001, Reaction_03001/02001/04058 suites: pick = Targeted only; survive = effects w/ shared batch; cancel = nothing (04058: state reset); other abilityId ignored. 03001 also covers Strega-lost-wound. Full suite: 6193 passed, 0 failed. Terraria untouched.

Left in tree (not mine): `tests/_tmp_target_gap.php` untracked.

## Classification (explore agent 16af9c74) — remaining after wave 1
67 non-challenge non-emitters at scan time (11 dropped mid-scan as other agents fixed them).

**HIGH (38)** — opposing/any target with wound/move/engage/destroy — fix first:
- wound: Action_01194, Maneuver_02057, Reaction_05FunkyCheese, Reaction_05Quack
- engage-or-wound: Action_01148, Action_02020, Action_01171, Action_01026, Action_04027, Action_04049
- engage: Action_01041, 01041v2, 01044, 01029, 01034, 01097, 01105, 01152b, 01017, Maneuver_05Stash
- move: Action_04011, 04028, 04032, 04042, 05William, Reaction_05Teapot, Action_03020, 03054, 02023, 02025, 02036a, 01020, 01055, 01092, 01115, 01172, Reaction_01037
- destroy-attachment: Action_03072

**MED (13)** — softer / pointer / beneficial-but-opposing-legal: Action_04018 (fix B.5 skip!), 01030, 03056, 02008, 05Quack, 04038, 05Serenissima, Reaction_05Stash, Action_02051, 01081, 01152a, Maneuver_03022, Action_03061

**LOW (16)** — self-only / beneficial / unclear: skip for Cloud Bottle wave (own-initiator gate). Include Action_02010, 04005, 03011, 03034, 01052, 01068, 01085, 01133, Maneuver_01051, Maneuver_03035, Reaction_01016, Action_01176, 01175, 01091, 01161, 04059.

Priority order for next waves: HIGH wound → engage-or-wound → engage/move → MED (esp. 04018 B.5 doc fix) → LOW last if at all.

## Follow-up: Action_04027 stale CHOSEN_TARGET
Engage-wave agent fixed 04027 but left `CHOSEN_TARGET` set at pick. Moved set into CharacterTargeted survive handler (01049 seat) so cancel does not leave a sticky target. Checklist B.5 already REQUIRES CharacterTargeted (04018).

## Wound/engage-or-wound wave (cc1f9518) — verified
Fixed Quack/FunkyCheese/02057/01171/02020/01148; skipped 01194/01026 (already done). Full suite 6227 pass.
- Quack seat: CharacterTargeted survive **before** `isAvailable()` because `setUsed` at pick.
- 01148: `mercenaryChosen` → EVENTS hub; survive queues `01148_4` hop.
Emitters mid-session recount: **46** of 126. Remaining HIGH mostly move/engage.

## HIGH move wave (7a515867) — done
All 21 listed HIGH move/engage files fixed. Full suite **6266** pass. Emitters ~**67**/126.
Open: cost-gating inconsistency (01015-style gated costs vs 01026/01194 unbatched-at-pick); cancel hand-off untested (no ActionResolved on cancel — same as 01160).
MED remaining (11): 01030, 03056, 02008, 05Quack Action, 04038, 05Serenissima, Reaction_05Stash, 02051, 01081, Maneuver_03022, 03061. MED wave launched.

## MED wave (053ff7dd) — done
All 11 MED fixed. Full suite **6274** pass. Final recount: **78** emitters / 126 implementers; **31** challenge-shaped (Framework covers); **17** other-gap = LOW self-only/beneficial + `BasicChallengeAction`.

### Sweep status for Cloud Bottle
HIGH + MED opposing/any-target emitters done. LOW deferred (own-initiator / self-only — Cloud Bottle opposing gate would not offer).

### Deferred / inconsistent (do not "clean up" without deciding a rule)
1. Cost gating: 01015-style (gate destroy/wound cost with targeting) vs 01026/01194/01152/02051 (cost at pick, unbatched)
2. Locker: Serenissima gates Locker send; 02051 does not (park/strand risk)
3. Action_02008 dangling SorcererStart if targeting canceled after stage 1
4. Cancel hand-off: no ActionResolved on cancel (01160 seat) — untested end-to-end in live engine

## Sweep 3: HIGH engage/move batch (01041, 01041v2, 01044, 01029, 01034, 04049 fixed; 04027 + 04018 already done by another agent)
Same seat as 01160: pick queues `createCharacterTargetedEvent` (batchId, eventCheck) only; `handleEvent` on `abilityId == this->Id && !canceled` runs effects with shared batchId; ActionResolved unbatched (01160 convention).

- **01041 / 01041v2 (Rosine)**: pick -> targeting only. Survive: engage target (+ move Home if Sorcerer, shared batch) + ActionResolved. Engage-your-performer cost is in announceAction, already outside.
- **01029**: pick -> targeting. Survive: engage + resolved.
- **01034 (Wrath of the Don)**: performer wound is the "Wound your performer •" COST -> queued at pick UNBATCHED (cancel must not refund). Survive: set CHOSEN_TARGET + queue `01034_2` Transition (shared batch). WHY CHOSEN_TARGET moved into survive: a canceled hook must not leave stale target (same as 01049). `_2` stage untouched.
- **01044 (Armed and Marshaled, SchemeCity)**: DELIBERATE DEVIATION — targeting fires at the `_3` Engage/Home CONFIRM, not at the `_2` character pick. WHY: `_2 -> _3` is a direct state transition with a Back button; firing at `_2` would need a new EVENTS-hub transition (`01044_3`) and Back from `_3` would let the player re-target and re-trigger "when targeted" reactions. Avoided the state-machine rewrite. Attachment engage (cost) queued at confirm, unbatched. Engage-vs-Home choice stashed in card-local global `action01044Manipulation` (1/2) because the survive hook has no other way to know it (same pattern as Action_04cd09 COST_MODE); cleared on BOTH survive and cancel. If a future session wants hook-at-`_2`, it must add the hub key + drop Back from `_3`.
- **04049 (Point of Order)**: pick -> targeting only (notify stays). Survive: set CHOSEN_TARGET; already-Engaged -> `04049_3` auto-move Transition else `04049_2` Engage/Decline prompt (to target controller, shared batch). `_2`/`_3` stages untouched.
- **04027 / 04018**: another agent landed code + tests while I was working (saw their diff + `Action_04027_Test`/`Action_04018_Test` modified). Skipped. NOTE: my third StrReplace on 04027 added a duplicate `use EventCharacterTargeted` — I removed it; no other 04027 edit from me. 04027 still sets CHOSEN_TARGET at pick (their choice; not stale-safe vs. cancel, unlike my 04049/01034 — flagging, not changing).
- **checklist.md item 56 (B.5)**: replaced "Skip EventCharacterTargeted unless Vittoria sync needed" with REQUIRED + how it's gated (04018 shape).

Tests: updated Action_01041/01041v2/01044/01029/01034/04049 suites (pick = targeting only; survive = effects w/ shared batch; cancel = nothing; other abilityId ignored; 01034 cost outside batch; 01044 cancel clears stashed choice). Ran touched suites + Card_ suites: 61/61 (backslash paths). Did not run the full runner (other agents mid-edit in tree). Terraria untouched; no git stash; nothing committed.

Unverified: on cancel nothing queues ActionResolved (matches 01160) — action state hand-off on cancel is still untested end-to-end; for 01034/01044 the cost (wound / attachment engage) stays paid on cancel.

## Sweep 4: HIGH wound + engage-or-wound batch (05Quack, 05FunkyCheese, 02057, 01148, 02020, 01171; 01194 + 01026 skipped)
Same seat as 01160/Vulture. Pick queues ONLY `createCharacterTargetedEvent` (batchId, eventCheck); `handleEvent` on `abilityId == this->Id && !canceled` runs effects with the shared batchId. ActionResolved unbatched (01160 convention).

- **Skipped 01194 (Adelheide) + 01026 (For the Family)**: another agent landed identical-shape code AND tests while I was working (saw their diff mid-edit; my first StrReplace failed on 01194 because the file had changed under me). My third edit on 01194 added a duplicate `use EventCharacterTargeted` - removed it. Their design: attachment unequip/discard (01194) and destroy-performer (01026) = paid COST, stays unbatched at pick; effect + ActionResolved gated. I agree with it (same reasoning as Matchlock discard). Re-ran their suites: pass.
- **Reaction_05Quack / Reaction_05FunkyCheese**: GOTCHA - `handleEvent` began with `if (!isAvailable()) return` and `setUsed` happens at pick, so the survive hook would have been silently dropped. Put the `EventCharacterTargeted` branch BEFORE the isAvailable gate (same reason Reaction_03001 scopes isAvailable to its trigger branch). Tests set `Used = true` before delivering the hook to lock that in. Do not "tidy" that ordering.
  - FunkyCheese: self-wound on Leichenberg is GATED with the target wound (shared batch, self first). WHY: it's not a bullet-separated cost, it's half of a dual-wound effect; canceled targeting must not leave him wounded for nothing (same call as 01012 Sibella).
- **Maneuver_02057**: both wounds (Brute re-read at survive) share the batch. No ActionResolved (maneuver never had one).
- **Action_01171**: trivial copy of 01026 shape (engage, or Home if already Engaged).
- **Action_02020 (Yield)**: targeting fires at the ATTACHMENT pick (state `_2`), not the character pick (state `02020`). WHY: engage-attachment is the paid cost and lives in `_2`; firing there keeps cost unbatched/outside, and avoids touching the `characterChosen -> _2` transition. If canceled the cost stays paid (matches 01194/01034). Survive: already Engaged -> wound; else `02020_2` Transition to opponent's choose engage/wound state (batched) + ActionResolved. `_3` unchanged.
- **Action_01148 (Marooned) - needed a state-graph change**: pick state `01148` used `mercenaryChosen -> 01148_2` directly (game state, would run before any queued event). Changed `State_highDramaPhase01148` `mercenaryChosen` to `HIGH_DRAMA_PLAYER_TURN_EVENTS`. Survive hook sets CHOSEN_TARGET (moved from pick so cancel leaves no stale target) and queues Transition `01148_4` (batched). REUSED the existing EVENTS-hub key `01148_4` because it already routes to `01148_2` (the loop entry); a fresh key would be cleaner but means touching states.inc.php for no behavior gain - revisit if confusing. The per-card discard + engage/wound loop (_3/_4) is NOT gated: discards are paid after targeting survived once, and re-targeting per loop would be wrong.
- Cancel on all of these queues nothing, so no ActionResolved (still the unverified engine-handoff caveat from earlier sweeps). For 01148 specifically a cancel returns to the EVENTS hub with the action unresolved.

Tests: updated Action_01148/02020/01171, Maneuver_02057, Reaction_05Quack/05FunkyCheese suites (pick = Targeted only; survive = effects w/ shared batch; cancel/foreign abilityId = nothing). Touched suites + Card_ suites (backslash paths): 127/127. Full runner at end: 6227 passed, 0 failed. Terraria untouched; no git stash; nothing committed. Left `tests/_tmp_target_gap.php` alone.

## Sweep 5 (this agent): 01152a, 01152b, 01026, 01194, 04018, 04027, 04028
Interleaved with another agent editing the same tree (Sweep 3/4 entries above also claim 01194/01026/04018/04027 — see "collision" note). Final tree state: exactly one `EventCharacterTargeted` import/handler/emit per file (verified by grep), all suites green.

Same seat as 01160/Vulture: pick queues ONLY `createCharacterTargetedEvent` (batchId + eventCheck); effects in `handleEvent` on `abilityId == this->Id && !canceled`; ActionResolved unbatched; costs stay outside.

- **01152a / 01152b (Scheme City, wound performer + En Garde/Engage target)**: performer wound = COST, stays at pick unbatched (always applies, even if the targeting is canceled; the old code comments already called the wound "the cost"). Engarded/Engaged effect + ActionResolved gated; already-en-garde / already-engaged still skip the effect (not refuse) — kept the existing "legal target, take the wound" design. Engarded vs Engaged distinction preserved (Engaged on 01152a's target was a noop before).
- **01026 (Destroy performer, manipulate)**: destroy performer = COST at pick (incl. `unEquipAllAttachments`). Engage-or-send-Home decision moved to survive time (target `Engaged` re-read). Uses `$owner->ControllerId` (the Risk) instead of the performer's because the performer is already destroyed by then.
- **01194 (Adelheide)**: unequip + discard attachment (asEffect=false) = COST at pick. Wound + ActionResolved gated.
- **04018 (B.5 — the one the skill wrongly said to skip)**: nothing is a cost. Pick -> targeting + notify only. Survive: performer move (batched), ActionResolved, `$PlayersToDiscard`, `04018_2` Transition (or the "nobody must discard" notify). Performer read from CHOSEN_PERFORMER, destination from the target's CURRENT location at survive time. `characterChosen` already routed to the EVENTS hub so no state change. Skill doc checklist item 56 already says REQUIRED (landed concurrently); I also rewrote `pattern-b.md` item 11 ("Skip unless Vittoria") because it contradicted it.
- **04027**: pick -> targeting. Survive -> Transition `04027_2` to target's controller (engage-or-decline prompt). Other agent additionally moved `CHOSEN_TARGET` set into the survive branch (stale-target-safe) — good call, kept.
- **04028 — DELIBERATE DEVIATION**: targeting fires at the `_2` destination CONFIRM, not at the character pick. WHY: character pick goes straight to the `_2` GameState (with Back); firing at the pick would need a new hub key and a canceled/backed-out targeting would be stranded. Same trade-off as 01044. Destination rides `CHOSEN_LOCATION` global so the survive hook knows it. Both moves share the batch. If anyone wants hook-at-pick, add `04028_2` to the EVENTS hub and drop Back.
- Skipped from the candidate list (not touched this pass): 01148, 01034 (already done by the other agent), 05Serenissima/05William/05Teapot/05Quack (not read), 02020, 03054 (02020 done by other agent; 03054 not evaluated — wounds own performer, likely LOW/self).

Tests: updated Action_01152a/01152b/01026/01194/04018/04027/04028 (pick = Targeted only; survive = effects + shared batch; cancel = nothing; foreign abilityId ignored). Touched Action_ + Card_ suites green. Full runner NOT run (other agent mid-edit). Terraria untouched; nothing committed.

Collision note: early on a `Cannot use ... EventCharacterTargeted ... already in use` fatal appeared in Action_04027.php — two agents both adding the import. Gone on re-run. Re-grep imports after any parallel sweep.

## Sweep 6 (this agent): remaining HIGH move/engage/destroy batch - 21 files fixed, 0 skipped
Targets were 21 files from the user's list; every one verified to lack `createCharacterTargetedEvent` first (04028 / 01152b already emit, left alone). Emitter count 49 -> 70 (rg -l createCharacterTargetedEvent modules/php). Full runner at end: 6266 passed, 0 failed, 0 errors. Terraria untouched; no git stash; nothing committed.

Same seat as 01160/04028: pick queues ONLY `createCharacterTargetedEvent` (batchId + eventCheck); `handleEvent` on `abilityId == this->Id && !canceled` runs effects with the shared batchId; ActionResolved unbatched (01160 convention); `if ($event instanceof EventCharacterTargeted ...)` guarded by exactly one import per file (re-grepped: emit=1 import=1 everywhere, php -l clean).

**Simple single-pick (pick -> targeting; survive -> effect + resolved):** Action_04011, 03020, 01092, 01115 (move Home / to performer), 01097, 01105 (engage). 01115/01105 read CHOSEN_PERFORMER at survive (global is still set while the hook dispatches).
**Destroy performer "•" cost - GATED (01015/01019 seat), not unbatched:** Action_01020 (Dante), Action_01017 (Alcee). WHY: user brief cites 01015 seat; destroying a Brute for a canceled hook is a pure lost cost, same as 01019 Buratino. NOTE this is the opposite call from 01026/01194/01152 (cost left at pick, unbatched) landed by earlier agents - the tree is now inconsistent on "bullet = cost" handling for destroy. Engage-artifact in 05William is gated the same way. If a later session wants one rule, decide it centrally; I did not flip the earlier ones.
**Two-stage destination (targeting at the FINAL destination confirm, destination in CHOSEN_LOCATION - 04028 deviation):** Action_01055, 02023, 02025, 04042. WHY: character pick routes straight into a `_2` GameState with a Back button; firing at the pick would leave a stale targeting event on Back, and a hub key would be needed. Survive reads CHOSEN_PERFORMER / CHOSEN_LOCATION. 01055: RangedAbilityPlayed gated + unbatched (01049). 02025: both moves + the 3 Renown events share the batch; Renown source = performer location at survive. 04042 notify reworded "targets X, moving them to Y" (the old "moves" line would lie on cancel).
**Pick -> prompt stage (CHOSEN_TARGET set on survive, prompt Transition batched, 01049/04027 seat):** Action_02036a (Scheme, `02036_2` discard-or-Home), Action_04032 (Giacinto, `04032_2` reveal/pass). State graphs already routed to the EVENTS hub (`targetChosen`), so no states.inc.php change. Downstream reveal/discard/move stages not re-targeted.
**Action_03054:** performer wound + Resolve pressure already happen in the earlier stage = paid cost outside; only the final target pick (wound + Home move; willDie computed at survive) is gated.
**Action_03072:** attachments snapshot moved into survive (`resolveTargetedCharacter`).
**Action_01172 (Sorcerer City Action):** Start, performer wound (the "•"), move, ActionResolved, Played ALL gated; kept the existing "everything in one batch, ActionResolved included" Torsten shape but the batch is now the targeting event's batchId. This is the one place ActionResolved is batched - that predates me (Torsten cancel strips the batch) and I preserved it.
**Reaction_01037 (Edeline):** setUsed at pick -> survive branch is placed BEFORE the isAvailable-scoped duel branches (Quack seat). Test sets Used before delivering the hook.
**Reaction_05Teapot (Risk reaction, ISorcererAbility):** 04058 seat. Post-pay branch (EventRiskReactionTriggered) queues CharacterTargeted + setUsed but does NOT reset sticky state (performerId/toLocation/chosenTargetId); the hook branch (abilityId + chosenTargetId == targetId, NOT behind isAvailable) emits Start/move/Played on survive and resets state on BOTH survive and cancel. Invalid-target early-out path unchanged. The `chosenTargetId != 0` guard replaced 04058's `!== null` because Teapot's ids are `int = 0`.
**Maneuver_05Stash:** engage only; no ActionResolved (maneuver never had one). Adversary discard-or-decline stage not part of the targeting.
**Action_05William:** new test file `tests/regression/cad/Action_05William_Test.php` (none existed). Targeting at the `_2` target pick; Artifact engage + move + resolved gated; CHOSEN_CARD (the artifact) re-read at survive.

Tests: updated Action_04011/04032/04042/03020/03054/03072/02023/02025/02036a/01017/01020/01055/01092/01097/01105/01115/01172, Reaction_01037/05Teapot, Maneuver_05Stash; added Action_05William. Each: pick = Targeted only; survive = effects + shared batch; cancel = nothing; foreign abilityId ignored (Teapot/02036a/04032 also assert sticky state not left behind on cancel). Unverified end-to-end (still): on cancel nothing queues ActionResolved, so the HD action hand-off after a canceled hook is untested in the real engine - for every new emitter, same caveat as Sweeps 3-5.

### Remaining HIGH (not reached): none from the user's 21-file list. I did not re-audit the full 126-card classification this session, so MED/LOW leftovers are whatever the earlier sweep notes above list. Next session: diff `rg -l IAbilityThatTargetsCharacters` against `rg -l createCharacterTargetedEvent` (70 emitters now) to get the real remainder.


## Sweep 7 (MED wave, this agent): 11 files fixed, 0 skipped
Each verified to lack `createCharacterTargetedEvent` first. Emitter count among `IAbilityThatTargetsCharacters` implementers: **79** (rg impl=137 incl. interface/trait/framework files). Full runner: **6274 passed, 0 failed**. Terraria untouched; no git stash; nothing committed.

Same seat as 01160: pick queues `createCharacterTargetedEvent` (batchId + eventCheck); effects in `handleEvent` on `abilityId == this->Id && !canceled`; ActionResolved unbatched (01160 convention). One import + one emit per file (grepped).

- **Action_01030 (Pull the Strand)**: state `01030` `""` already -> EVENTS hub. Survive: CHOSEN_TARGET set, engage performer + Sorcerer start + `01030_2` Transition, all on the shared batch. WHY engage gated: pure cost if canceled (01015 seat). `01030_2` / pressure untouched.
- **Action_03056**: pick -> targeting only; `resolveTargeted()` on survive sets CHOSEN_TARGET / CHOSEN_LOCATION (moved off the pick so cancel leaves nothing stale), queues claim (target controller) + `03056_2` (batched) or ActionResolved. Cannot-claim notify also moved to survive.
- **Action_02008 (Fate's Kiss)**: targeting at the `_2` character pick (no Back there). `moveCard` to PERMANENTLY_HIDDEN + clone creation + equip + Played + Resolved moved into `placeRiskUnderTarget()` on survive. WHY: those are immediate mutations; if run at the pick a canceled hook would still strand the Risk in the hidden pile. **Sorcerer START deliberately left at stage 1 (before target known) - a canceled hook leaves a dangling Start with no Played.** Flagged, not changed.
- **Action_05Quack (Action)**: heal + `05Quack_2` discard-stage Transition on survive; CHOSEN_TARGET set on survive. Test for "wrong player discards" now delivers the hook first.
- **Action_04038**: engarde + heal + Resolved on survive; Engaged/Wounds re-read at survive.
- **Action_05Serenissima**: the Locker send of the discard card is the "•" cost; I GATED it with the effect (01015 seat) because the card is still in the discard pile (nothing strands). If the card left the discard before survive: skip effect, still ActionResolved. Engarde permission `eventCheck` kept at the PICK (dry-run on a throwaway event) and a fresh Engarde is built on survive - WHY: an eventCheck veto throws a UserException; thrown inside a survive hook it would crash dispatch instead of telling the player.
- **Reaction_05Stash**: Teapot/04058 seat. New `public int $chosenTargetId`. Pick (chooseCharacter stage): dry-run eventCheck, targeting, `setUsed`, stage = '' but keep `pressureLocation` + `chosenTargetId`. Survive branch is a separate `if` NOT behind `isAvailable()` (setUsed already ran - Quack seat); matched by abilityId + stored target; builds Engarde (batched); resets `chosenTargetId`/`pressureLocation` on BOTH survive and cancel.
- **Action_02051 (Voice of the Gods)**: **DELIBERATE ASYMMETRY.** The Risk's own `createCardSentToLockerEvent` stays at pick, unbatched, ungated (Matchlock 01156 seat): the Risk is parked in Purgatory and the Locker send is the only thing that moves it out, so a canceled hook must not strand it. Engage performer / Start / Engarde / Played / Resolved gated. If you want one rule for "send this card to Locker" costs decide centrally.
- **Action_01081**: both Engardes (target + performer) are half of the printed effect, gated together; Resolved after.
- **Maneuver_03022 (Final Strike)**: targeting fires for ANY legal target incl. already En Garded (still a legal target); survive re-reads Engaged -> Engarde or "already En Garded" notify. `DUEL_END_OF_ROUND_03022` `""` -> `DUEL_END_OF_ROUND_EVENTS` hub, so the hook is delivered there. Pass path (no characters) unchanged / no targeting.
- **Action_03061**: pick keeps an eventCheck dry-run of the move (veto surfaces to player); survive rebuilds the move with destination = performer's CURRENT location (`buildMoveEvent`). Notify reworded "targets X" (old "moves X to Y's location" would lie on cancel; new string is a plain clienttranslate).

Tests: updated Action_01030/03056/02008/05Quack/04038/05Serenissima/02051/01081/03061, Maneuver_03022, Reaction_05Stash. Each: pick = Targeted only (+ locker for 02051); canceled = nothing, no stale state; foreign abilityId ignored; survive = effects share the batch (77). Touched + sibling Card_ suites all green, then full runner 6274.

Unverified (unchanged caveat): on cancel nothing queues ActionResolved, so the HD action hand-off after a canceled hook is untested end-to-end. For 05Quack/05Serenissima/04038/03061/03056/01081/02051/01030/02008 a cancel leaves the action state unresolved the same way 01160 does.

### Remaining after this wave
No MED left. `rg -l IAbilityThatTargetsCharacters` minus emitters still lists ~58 files: ~31 challenge-shaped (FrameworkActionsTrait emits - covered), plus the LOW (self-only/beneficial) list already recorded above (02010, 04005, 03011, 03034, 01052, 01068, 01085, 01133, Maneuver_01051, Maneuver_03035, Reaction_01016, 01176, 01175, 01091, 01161, 04059) and Reaction_05Terraria (off limits). Not re-audited individually this session.