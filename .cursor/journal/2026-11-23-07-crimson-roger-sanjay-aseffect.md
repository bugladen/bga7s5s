# Rumors of the Crimson Roger discard missed Sanjay

## Bug
Castille plays Action_02036a; opponent discards to stay in city. Sanjay (01097) Reaction should offer a draw ("After an opponent discards a card due to your effect") and did not.

## Cause
`createCardDiscardedFromHandEvent` defaults `asEffect=false`. Action_02036a only passed owner/card/sourceId. Reaction_01097 gates on `$event->asEffect`, so the offer never queued.

WHY this is an effect, not a payment: card text is "Move … unless their controller discards" — the discard is the consequence of resolving the scheme ability (same as Rosa 02033, which already passes `asEffect=true`). Payments are costs you choose to pay for your own ability (`asPayment=true`, source often 0).

## Fix
Action_02036a discard call: `asPayment=false, asPlayed=false, asEffect=true`. Assert in Action_02036a_Test.

## Note
Other TAC discards (02001 Andriana, 02011 Katain, 02013 Wilhelm) also omit asEffect — same Sanjay hole if those force an opponent discard. Not in this report; leave for a follow-up sweep unless user asks.
