<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04047;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04053;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04053;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_04053_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_04053';
    }

    public function tests(): array
    {
        return [
            // WHY: Only an opponent ability wound, and the attachment must be en garde and
            // equipped. Empty abilityId is a duel-threat wound. Ignore engages this card
            // and spends. Pass requeues the wound and skips the next copy of it.
            // A card-level ability id falls back to the source controller.
            'ignores an opponent ability wound by engaging' => function () {
                $world = new TestWorld();
                $armor = $world->placeCard(new _04053(), Game::LOCATION_CITY_DOCKS, 1);
                $bearer = $world->placeCharacter(new GenericCharacter('Bearer'), Game::LOCATION_CITY_DOCKS, 1);
                $bearer->ModifiedFinesse = 2;
                $armor->AttachedToId = $bearer->Id;
                $source = $world->placeCard(new _04047(), Game::LOCATION_HAND, 2);
                /** @var Reaction_04053 $reaction */
                $reaction = $armor->getReactions()[0];

                $wound = new EventCharacterBeingWounded();
                $wound->characterId = $bearer->Id;
                $wound->sourceId = $source->Id;
                $wound->abilityId = $source->getActions()[0]->Id;
                $wound->wounds = 1;
                $world->fireOn($armor, $wound);
                Assert::true($wound->canceled, 'intercepted');
                Assert::same('reaction', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'offered');

                $reaction->performReaction($world->game, 0, '', 'ignoreWound');
                Assert::true($reaction->Used, 'spent');
                Assert::same($armor->Id, $world->theah->queuedOfType(EventCardEngaged::class)[0]->cardId, 'engages the armor');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'wound is not taken');

                $reaction->Used = false;
                $armor->Engaged = false;
                $world->theah->takeQueuedEvents();
                $wound->canceled = false;
                $world->fireOn($armor, $wound);
                $reaction->performReaction($world->game, 0, '', 'pass');
                Assert::false($reaction->Used, 'pass');
                $requeued = $world->theah->queuedOfType(EventCharacterBeingWounded::class)[0];
                $world->theah->takeQueuedEvents();
                $world->fireOn($armor, $requeued);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'skip the requeue');

                $wound->canceled = false;
                $wound->abilityId = '';
                $world->fireOn($armor, $wound);
                Assert::false($wound->canceled, 'empty ability is not an ability');

                $wound->abilityId = (string) $source->Id;
                $source->ControllerId = 0;
                $world->fireOn($armor, $wound);
                Assert::false($wound->canceled, 'uncontrolled source');

                $source->ControllerId = 1;
                $world->fireOn($armor, $wound);
                Assert::false($wound->canceled, 'your own card');

                $armor->Engaged = true;
                $wound->abilityId = $source->getActions()[0]->Id;
                $source->ControllerId = 2;
                $world->fireOn($armor, $wound);
                Assert::false($wound->canceled, 'armor is engaged');

                $armor->Engaged = false;
                $wound->abilityId = (string) $source->Id;
                $world->fireOn($armor, $wound);
                Assert::true($wound->canceled, 'card-level ability uses the source controller');
            },
        ];
    }
}
