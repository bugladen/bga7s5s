# 11 — Example Risks to copy

← [[10 — Finish checklist|Risk Guide Finish Checklist]] · [[Index|Implementing a Risk Card]] · Next: [[12 — Glossary|Risk Guide Glossary And Helpers]]

**Mirror first, invent never.** Find the closest printed shape below and open those PHP files while you work.

## Teaching set (start here)

| Card | Id | Why copy it |
|---|---|---|
| **Arrogant** | `_03008` | City Action Combat challenge + Gambling Maneuver (Influence compare). Shared challenge chooser. Great first combined Risk. |
| **Follow the Thread** | `_03009` | Plain **Action** location chooser (home performers OK) + Strega Maneuver. GameState + JS trio. |
| **Glorious** | `_03033` | Forced on the Risk class + pure-resolve Gambling Maneuver (`>=` Influence). |
| **Subtle** | `_03012` | Single-stage RiskReaction with pay; flips `CHALLENGE_STAT` after pay. |
| **Well-Equipped** | `_01061` | Action + Maneuver; Maneuver conditional draw. |

## City Actions

| Card | Id | Demonstrates |
|---|---|---|
| Legendary Reputation | `_01083` | Custom challenge type (Leader-only intervention) |
| Cornered | `_03021` | Engage cost + refuse/intervene side effects via `CHALLENGE_TYPE` correlator |
| La Voix des Sans Voix | `_03034` | Diplomat engage + En garde friendly + may heal/draw |
| Astute | `_03056` | Opponent claims + you move Renown (pronoun trap) |
| Censure | `_03057` | Engage + Influence challenge + auto-claim on refuse |
| Courageous | `_03058` | Duelist headcount If → Combat challenge |
| Matushka's Song | `_03060` | May engage / ignore costs + heal another (no "Target") |
| Ambitious | `_03067` | Leader City Action: wound + pressure + claim + locker |
| Leverage | `_03071` | Opponent-controlled location → engage opposing + Leader Action discount |
| Sabotage | `_03072` | Target destroy engaged attachments + engage remaining |
| Regroup | `_01059` | Simple move-to-adjacent City Action (legacy state template) |
| Taunt | `_01115` | City Action + Maneuver; `IRiskThatTargetsCharacters` |
| Provoking the Pack | `_03011` | Friendly-target City Action + Gambling Maneuver |
| Bloody Entrance | `_03032` | Sorcerer Action: wound + move any location + locked extra action |

## Plain Actions

| Card | Id | Demonstrates |
|---|---|---|
| Curious | `_03045` | Claim-control adjacent move (± wound) — not enemy-character filter |
| Commanding | `_03020` | Leader Action (no performer chooser) + cancel Reaction |

## Maneuvers

| Card | Id | Demonstrates |
|---|---|---|
| Master of Valroux Style | `_01084` | Duelist Maneuver + combat-card discount + cancelable sticky state |
| Mireli's Revision | `_01135` | Choice-at-activation (+Parry or wound/−Thrust) |
| Superstitious | `_03024` | Pure-calc choice (+Parry or +Thrust) |
| Second Wind | `_03023` | Suppress threat→wound; carry threat forward |
| Overzealous | `_03022` | Final Strike with chooser (end-of-round wiring) |
| Valroux Exemplar | `_03036` | Finesse discount + dueling-line count Riposte + conditional discard |
| Proper Drama | `_03047` | Dual a/b: choose their gamble card / cannot gamble |
| Wily | `_03048` | Gambled discount + move-all-threat Riposte |
| Comforting | `_03070` | Discard excess threat vs adversary duel-stat |
| Insightful | `_03059` | Adversary-deck peek → reveal for Parry/Thrust |
| Hop on Board | `_03069` | Swap participant; dual plain + Gambling |
| Loyal (Maneuver half) | `_03035` | Multi-step C.3: wound other + Riposte/Thrust choice |

## Reactions

| Card | Id | Demonstrates |
|---|---|---|
| Manipulative | `_03010` | Multi-stage cross-player choice after pay |
| Confusion | `_03068` | City Reaction on pass; opponent must move Home→City (buttons, no JS) |
| Passionate | `_03046` | Dual intervene→engarde Reactions (Duelist + Pirate) |
| Loyal (Reaction half) | `_03035` | Pressure +1 via new `PRESSURE_TYPE` |
| Altruistic | `_03031` | Effect-event redirect (would wound/move/engage) |

## Forced / discounts only

| Card | Id | Demonstrates |
|---|---|---|
| Victorious | `_03073` | Forced draw on adversary destroyed + Gambling +Thrust |
| Appealing / Bleed Out | `_01159` / `_01160` | Action-only Leader cost discount (no Maneuver) |

## How to use a mirror

1. Open the reference `_NNNNN.php` and its Action / Maneuver / Reaction files.
2. Copy structure (interfaces, event types, transition names, JS hooks).
3. Change card ids, names, gates, and numbers to match **your** print.
4. Delete branches your card does not have — do not leave dead pattern code.

## Next

Lookup helpers and jargon → [[12 — Glossary and helpers|Risk Guide Glossary And Helpers]]
