# In-hand action announce log hover (opponents)

## Ask
Log entry for in-hand action announcement doesn't hydrate hover image/text for
opponents; works for the player who announced.

## Root cause
`CardAction::announceAction` notified with `owner_inject_code` only — no card
property array. Client `format_string_recursive_with_injection` seeds
`logCardCache` from notify args that have `id` + `type`. Acting player already
has the hand Risk in `cardProperties`, so their tooltip resolves. Opponents
never saw that card object, so text hover fell back / failed to hydrate.

WHY this shows on in-hand and not in-play: in-play owners are already public in
everyone's `cardProperties`. Hand cards are private until a notify carries them.

## Fix
Include `'card' => $owner->getPropertyArray($game)` on the announce notify.
Same pattern as `EventCombatCardAnnounced` and the subsequent "played Risk"
notify in `actPayForInHandAction`.

## Note
Announce runs *before* the "played" notify in `actPayForInHandAction`, so even
though "played" already seeds the cache, the announce log span is already
`tt_processed` by then — seeding on announce is required, not redundant.

## File
- `modules/php/cards/actions/CardAction.php`
