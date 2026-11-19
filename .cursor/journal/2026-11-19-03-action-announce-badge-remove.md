# In-hand action announce — remove name badge

## Ask
Action name in in-hand announce log is a hoverable badge; hover shows broken image.
Remove the badge from action names.

## WHY it broke
`CardAction::announceAction` (and `LocationAction::announceAction`) used
`[${action}]`. Client `logInject` has a legacy regex `[name(image)]`. Action
Names with parentheses — e.g. `"Issue Combat Challenge (Non-Leader)"` — match
as name=`Issue Combat Challenge `, image=`Non-Leader` → tippy img with a
nonsense path → broken image.

Even without parens, brackets made the name look like a card inject badge.

## Fix
Drop the brackets: `${action}` plain text. Owner inject code still badges the
card (with hydrated `card` property array from 2026-10-03).

Same change on LocationAction — identical template, same regex trap.

## Not touched
Other `[${technique_name}]` / `[${maneuver_name}]` / `[${trait}]` log strings
have the same footgun if those strings ever contain `()`. Left alone — user
only asked about action announce names.

## Files
- `modules/php/cards/actions/CardAction.php`
- `modules/php/theah/actions/LocationAction.php`
