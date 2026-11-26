# tac queryCard sweep (Patricia/_05Serenissima fiction)

Mechanical pass over modules/php/cards/tac: own-character `hasTrait` in ability/cost gates now passes the owning card as `$queryCard` (`$this->getOwningCard($theah)` hoisted per method; `$this` for equip checks in _02005/_02006/_02027/_02056; `$ivy` in Reaction_02042).

WHY: Patricia's `hasTrait` only invents traits when queryCard is non-null + same controller. See 2026-11-26-01 journal.

Judgement calls:
- Action_02029: private helper `getDiplomatsAtLocation` got a `?Card $owner` param (counts other diplomats for the location-claim check too — consistent with fiction).
- Reaction_02058: also passed owner to the `Leader` gate (Patricia prints Leader so no-op, but same rule). Action_02014 Leader same.
- Technique_02026a/b: queryCard = `getOwningCard` (the attachment), host check stays on `getOwningCharacter`.
- Action_02036a: only performer gates (:72/:85); opponent `!Pirate` filters + isValidTarget untouched.

Skipped (deliberately): opponent filters (02001, 02013, 02020, 02023, 02036a, 02049, Maneuver_02049, 02057 Brute target); negative gate _02016; static/passive card text (_02022 Musketeer/Diplomat, _02024 Musketeer scheme passive); deck-card trait checks (02001 Sorcery, 02013 Relic/Faith, 02045 Dar Matushki/Poluchatel, Technique_02034/02043b, 02033); attachment-trait checks (02011, 02020, 02047); host-is-self checks (Action_02002, Technique_02012, Reaction_02012: host character is never Patricia so queryCard is a no-op).

Tests: `php tests/run.php` 6151 passed, 0 failed (one transient error on first run, gone on rerun — other agents editing concurrently). No new test added for tac-specific Patricia cases.
