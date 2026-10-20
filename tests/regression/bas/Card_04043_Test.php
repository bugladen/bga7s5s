<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04043;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterFinesseModifed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDefenderSwapped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelStarted;

class Card_04043_Test extends TestCase
{
    public function name(): string
    {
        return '_04043 Tomoe Sango';
    }

    public function tests(): array
    {
        return [
            'is a fuso samurai' => function () {
                $card = new _04043();
                Assert::instanceOf(Character::class, $card, 'Character');
                Assert::same(4, $card->Resolve, 'resolve');
                Assert::same(3, $card->Combat, 'combat');
                Assert::same(2, $card->Finesse, 'finesse');
                Assert::same(1, $card->Influence, 'influence');
                Assert::true($card->hasTrait('Duelist'), 'Duelist');
                Assert::true($card->hasTrait('Samurai'), 'Samurai');
                Assert::true($card->hasTrait('Fusō'), 'Fusō');
                Assert::count(1, $card->getReactions(), 'reaction');
            },

            // WHY: The -1 is only on her adversary, and it is queued rather than written
            // onto ModifiedFinesse. A floored 0 skips the event but still stamps the
            // condition so a later rise can absorb it. Destroy/duel end/blank restore.
            // The pending global survives a recreate. A spectator duel does nothing.
            'lowers only her adversary and restores when the duel ends' => function () {
                $world = new TestWorld();
                $sango = $world->placeCharacter(new _04043(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $other = $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->duelActor = $sango;
                $world->theah->duelOpponent = $foe;

                $started = new EventDuelStarted();
                $started->challengerId = $other->Id;
                $started->defenderId = $foe->Id;
                $world->fireOn($sango, $started);
                Assert::same(0, $sango->AffectedCharacterId, 'spectator');

                $started->challengerId = $sango->Id;
                $started->defenderId = $foe->Id;
                $world->fireOn($sango, $started);
                Assert::same($foe->Id, $sango->AffectedCharacterId, 'adversary');
                Assert::true($foe->hasCondition(Game::TOMOE_SANGO_CONDITION), 'stamped');
                Assert::same($foe->Id, $world->game->globals->get(Game::TOMOE_SANGO_PENDING_DEBUFF_CHARACTER_ID), 'pending global');
                $lower = $world->theah->queuedOfType(EventCharacterFinesseModifed::class)[0];
                Assert::same($foe->ModifiedFinesse - 1, $lower->NewFinesse, 'queued -1');

                $world->theah->takeQueuedEvents();
                $world->fireOn($sango, $started);
                Assert::count(0, $world->theah->queuedOfType(EventCharacterFinesseModifed::class), 'does not stack');

                $swap = new EventDefenderSwapped();
                $swap->oldDefenderId = $foe->Id;
                $swap->newDefenderId = $other->Id;
                $world->fireOn($sango, $swap);
                Assert::false($foe->hasCondition(Game::TOMOE_SANGO_CONDITION), 'old adversary');
                Assert::true($other->hasCondition(Game::TOMOE_SANGO_CONDITION), 'new adversary');
                Assert::same($other->Id, $sango->AffectedCharacterId, 'transferred');

                $world->fireOn($sango, new EventDuelEnd());
                Assert::same(0, $sango->AffectedCharacterId, 'duel end');
                Assert::false($other->hasCondition(Game::TOMOE_SANGO_CONDITION), 'cleared');
                Assert::same(0, $world->game->globals->get(Game::TOMOE_SANGO_PENDING_DEBUFF_CHARACTER_ID), 'global cleared');

                $foe->ModifiedFinesse = 0;
                $world->theah->takeQueuedEvents();
                $world->fireOn($sango, $started);
                Assert::true($foe->hasCondition(Game::TOMOE_SANGO_CONDITION), 'zero still stamps');
                Assert::false($sango->FinessePenaltyAbsorbed, 'no event to absorb');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterFinesseModifed::class), 'floor skips the -1');

                $foe->ModifiedFinesse = 1;
                $rise = new EventCharacterFinesseModifed();
                $rise->CharacterId = $foe->Id;
                $world->fireOn($sango, $rise);
                Assert::true($sango->FinessePenaltyAbsorbed, 'later rise absorbs');
                Assert::same($foe->ModifiedFinesse - 1, $world->theah->queuedOfType(EventCharacterFinesseModifed::class)[0]->NewFinesse, 'absorbed -1');

                $sango->addCondition(Game::FATES_SILENCE_CONDITION);
                $sango->onAbilitiesBlanked($world->theah);
                Assert::same(0, $sango->AffectedCharacterId, 'blank clears');
                Assert::false($foe->hasCondition(Game::TOMOE_SANGO_CONDITION), 'blank removes the stamp');
            },
        ];
    }
}
