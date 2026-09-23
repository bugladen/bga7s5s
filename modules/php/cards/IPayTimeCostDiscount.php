<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

/**
 * Pay-time cost discounts that Activate on EventEnteringPayState and scope to a
 * DiscountedCardId (Yevgeni Reaction_01116b, Daniella Reaction_03013, …).
 *
 * Multi-step equip UIs that let the player Back and pick a different card must
 * retarget / clear via these methods so the -1 follows the new target without
 * leaking onto later unrelated payments.
 */
interface IPayTimeCostDiscount
{
    public function isDiscountActive(): bool;

    public function retargetDiscountedCard(int $cardId): void;

    public function clearActiveDiscount(): void;
}
