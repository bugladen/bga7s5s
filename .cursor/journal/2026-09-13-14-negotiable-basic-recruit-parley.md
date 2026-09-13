# Negotiable gates basic Recruit parley

## What
City characters with `Negotiable = false` can still be recruited with the basic
Recruit Action, but you cannot Parley when paying for them.

## Why the flag did nothing
`CityCharacter::$Negotiable` defaulted false and was mirrored to JS as
`card.negotiable`. The choose-mercenary UI already hid the Influence discount
on `!negotiable` cards. The server never read the flag:

- `stHighDramaRecruitActionParleyable` only looked at the *performer*
  (engaged / Mercenary trait)
- Parley Yes/No happens *before* the mercenary is chosen
- Discount was applied from `PERFORMER_PARLEYED` regardless of the target

So you could Parley, engage the performer, then recruit a non-Negotiable
mercenary at the discounted rate. Frontend cost chip said otherwise.

## Approach (kept parley-before-target)
Did **not** reorder to choose-mercenary-then-parley. Cirilo / Kaspar / Filling
the Ranks enter CHOOSE_MERCENARY or PAY by skipping the parley states. Reordering
would have offered Parley on those "lose Negotiable" paths unless we added
recruit-type skips everywhere.

Instead, three gates on the existing order:

1. **Skip the prompt** when no uncontrolled Negotiable mercenary is at the
   performer's location (plus the old engaged/Mercenary performer checks).
2. **After Yes**, args only list Negotiable mercenaries; choosing a
   non-Negotiable throws. Back still undoes engage.
3. **`stRecruitComputeDiscount`** only passes `parleying=true` into
   `getParleyDiscount` if the chosen card is actually Negotiable.

Cirilo (`CIRILO_RECRUIT_TYPE`) is unchanged — it never offers Parley.

## Kaspar (Action_01035)
His City Action has its own Parley / No Parley state (`01035_4`). That is
*after* the mercenary is known, so skip is easy:

- Recruit + Negotiable → `01035_4` as before
- Recruit + not Negotiable → `recruitNoParley` straight to pay, discount 0
- Pay Back: Negotiable → `01035_4`; not → `01035_3` (must not land on Parley buttons)

Kaspar's printed exception is only "can parley even while engaged." It does
not override Negotiable. `01035_4` still rejects Parley if someone forces it.

## Penya
User updated `_03cd01` text: **he is not Negotiable.** Flag stays unset
(defaults `false` on `CityCharacter`). Do **not** re-add `$this->Negotiable = true`
from the old printed reminder or the 2026-04-26 Penya journal — that text is stale.

## Don't "fix"
Leave parley-before-mercenary. It exists so the choose-mercenary UI can show
discounted costs on every Negotiable card at once. Reordering is a larger UX
change and would collide with Cirilo/Kaspar entry points.
