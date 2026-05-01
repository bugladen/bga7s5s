# Wealth Trait — Allow Overpay By 1

## Rule change
Cards with the **Wealth** trait (worth 2) may now overpay a cost by exactly 1. So a single Wealth card can pay a 1-cost; two Wealth cards can pay a 3-cost; etc. Plain cards (worth 1 each) must still total exactly the cost — overpay is only legal when at least one Wealth-trait card is in the payment.

## Why
The user requested it. The previous rule enforced `totalWealth == cost` for all payments, which made some Wealth-card lines awkward (e.g. couldn't burn a single Wealth card to pay a 1-cost without an extra "swing" cost-1 card). Wealth-only overpay-by-1 keeps non-Wealth payments tight (no free disposal of cards) while letting Wealth carry the extra.

## Implementation
Added `FrameworkActionsTrait::isValidWealthPayment(int $totalWealth, int $cost, bool $hasWealthCard): bool` — public so card-class payment validators (which run via `$game->...`) can also call it.

```php
if ($totalWealth == $cost) return true;
if ($hasWealthCard && $totalWealth == $cost + 1) return true;
return false;
```

Updated **10** payment-validation sites:

`FrameworkActionsTrait.php`:
- `actRecruitMercenary`
- `actHighDramaEquipAttachment`
- `actPayForInHandAction`
- `actPayForBrute`
- `actDuelPayForManeuverFromCombatCard`
- `actPayForReaction`

Card-specific validators (each does its own discount math, so they kept their own loops):
- `cards/_7s5s/maneuvers/Maneuver_01113.php`
- `cards/_7s5s/actions/Action_01113.php`
- `cards/_7s5s/actions/Action_01167.php`
- `cards/_7s5s/actions/Action_01180.php`

Each loop now also tracks `$hasWealthCard` (set true the first time `hasTrait("Wealth")` returns true), and the final `if ($totalWealth != $cost)` was replaced with `if (!$game->isValidWealthPayment(...))` (or `$this->...` inside the trait).

## Why "Wealth-only" overpay
The only way to overpay by exactly 1 with non-Wealth cards is to have a 0-cost target — and 0-cost targets after discount really should be free, not "pay 1 anyway". Gating overpay on `$hasWealthCard` prevents accidentally letting players burn a single 1-wealth card on an already-free play.

## Client side
JS does **not** enforce exact cost — it only displays the running wealth count via `$('faction_hand_info')`. The Confirm button is always enabled in pay states (no JS gate to relax). So no JS changes were needed; the new rule takes effect via the PHP validators only.

## Things I noticed
- Six of the ten sites were near-identical copy-paste; the four card-specific ones diverge slightly (different `$ids` var name, different translate calls, mix of `\BgaUserException` vs `UserException`). I kept the variable naming/exception type each site already used to minimize diff.
- The Bravos/Thug edge case in `actPayForInHandAction` is preserved unchanged.
- No tests in this repo — relied on `php -l` for syntax check.
