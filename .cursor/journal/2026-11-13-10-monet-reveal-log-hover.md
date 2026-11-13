# Monet Reaction_04023 reveal log hover hydration

## Report
User: Action_04023 (actually Reaction_04023 Monet) puts revealed attachments in the game log, but tags aren't hydrated — hover shows only "Uppman's Jacket (Attachment)" instead of the card tooltip.

## Root cause
`handleReveal` notified with implode of `getInjectCode()` only. Faction-deck cards are never in opponents' `cardProperties`. Text hover preference (type 2) looks up `cardProperties ?? logCardCache`; miss → fallback `"${cardName} (${type})"`.

Discard path already passed `"card" => getPropertyArray`. Reveal/equip did not. Reaction_04042 comment even called out Monet's implode pattern and the need for property arrays.

## Fix
- Reveal notify: add `"cards" => [...]getPropertyArray...]` (same as EventHub gamble reveal). JS `format_string_recursive_with_injection` scans arrays into logCardCache.
- Equip notify: add top-level `card_{id}` property arrays for attachment + character (Reaction_04042 shape).
- Regression: assert reveal notify args.cards ids/types.

## WHY cards[] not only inject codes
Inject codes make bold names + image path for image-hover prefs. Text tooltips need full property objects. Without seeding, everyone who uses text hover gets the stub.

## Follow-up: sunk messages also unhydrated
Same stub on "sunk X to the bottom of their Faction Deck." Monet only queues `EventCardAddedToFactionDeck`; the hub notify had inject code only.

Fixed in EventHub for both FactionDeck and CityDeck add/sink notifies (`"card" => getPropertyArray`). Shared path — fixes Otto/Gustavo/etc. sinks too.

## Tests
`php tests/run.php tests\regression\bas\Reaction_04023_Test.php`
`php tests/run.php tests\regression\framework\EventHub_Board_Test.php`

## Note
Kaj 01180 / Otto 01038 *reveal* log messages still lack cards[] — same class of bug if someone reports those. Sink side is fixed centrally now.
