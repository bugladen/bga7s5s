# Risk create wiki pages

## Task
User wants beginner-facing GitHub-push-ready wiki pages on creating a complete Risk class set. Place in `misc/wiki/create/risk`, push to GitHub wiki. Use create-risk skill + other create wikis as templates. Remove existing Risk stub.

## Context inherited
2026-09-20 CityAttachment wiki (`07`) established hub + 12-page map, PUBLISH.md, Home links. Risk skill is the densest of the create skills (Patterns A–E with many A.n / C.n / D.n subpatterns).

## Plan / WHY
Mirror Scheme/FactionAttachment page altitude for beginners — hub + What Is / Card Class / Classify + one page per ability family + Wiring / Checklist / Examples / Glossary.

Deliberately split **City Actions** vs **Actions** (separate pages) because the performer-pool trap (City vs Home+city) is the #1 beginner landmine on Risks, and challenges mostly live on City Actions. Folded challenge correlators into City Actions rather than a separate Challenge page (unlike Character/Scheme) — Risks rarely have non-City challenge Actions, and one less page keeps the map closer to FactionAttachment.

Teaching walking examples: Arrogant `_03008`, Follow the Thread `_03009`, Glorious `_03033` — cover City Action+Maneuver, plain Action+Maneuver, Forced+Maneuver without drowning beginners in A.8–A.10 edge cases. Those edges live in Classify traps + Examples table + Checklist.

Stub `Implementing-A-Risk-Card.md` replaced in place (same wiki page name Home already linked).

## Status
Done. Pushed wiki commit `05acd1b` to `bga7s5s.wiki` master.

## What shipped
13 pages under `misc/wiki/create/risk/`:
1. `Implementing-A-Risk-Card.md` — hub (replaces stub)
2. What Is / Card Class / Classify
3. City Actions / Actions / Maneuvers / Reactions
4. Passives Forced And Discounts
5. Wiring / Checklist / Examples / Glossary

Also: PUBLISH.md map; FactionAttachment + Scheme hubs cross-link Risk guide.

Local source remains in main repo under `misc/wiki/create/` (untracked until user commits game repo). Wiki clone at `wiki-tmp/` after push.

## Feel
Skill is huge; wiki deliberately thinner. Biggest beginner landmines called out repeatedly: City Action vs Action performer pools, no RiskCityReaction, Target interfaces only when printed "target", hand `==` guard for Reactions, calc vs resolve on Maneuvers, CHALLENGE_TYPE correlator (no actionId on challenge events), Action-only discounts must not invent Maneuvers.
