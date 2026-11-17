<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05GaryFlipped;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions\Reaction_05GaryFlipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_05GaryFlipped_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_05GaryFlipped';
    }

    public function tests(): array
    {
        return [
            // WHY: Printed trigger is any opposing character (enemy at Rena's
            // location), not her duel adversary / IN_DUEL. Claim is her location
            // at that moment, including a location an opponent already controls.
            // Pass does not spend the reaction.
            'claims her location when an opposing character is destroyed' => function () {
                $world = new TestWorld();
                $rena = $world->placeCharacter(new _05GaryFlipped(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $elsewhere = $world->placeCharacter(new GenericCharacter('Elsewhere'), Game::LOCATION_CITY_FORUM, 2);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Reaction_05GaryFlipped $reaction */
                $reaction = $rena->getReactions()[0];

                $world->game->setControllerForLocation(Game::LOCATION_CITY_DOCKS, 2);

                $destroyed = new EventCharacterDestroyed();

                $destroyed->characterId = $ally->Id;
                $world->fireOn($rena, $destroyed);
                Assert::count(0, $this->reactionOffers($world), 'own character');

                $destroyed->characterId = $elsewhere->Id;
                $world->fireOn($rena, $destroyed);
                Assert::count(0, $this->reactionOffers($world), 'enemy elsewhere');

                // WHY: Destroying the Leader queues endOfGame. That is not this reaction.
                $destroyed->characterId = $rena->Id;
                $world->fireOn($rena, $destroyed);
                Assert::count(0, $this->reactionOffers($world), 'Rena herself');
                $world->theah->takeQueuedEvents();

                $destroyed->characterId = $foe->Id;
                $rena->Location = Game::LOCATION_PLAYER_HOME;
                $foe->Location = Game::LOCATION_PLAYER_HOME;
                $world->fireOn($rena, $destroyed);
                Assert::count(0, $this->reactionOffers($world), 'home');

                $rena->Location = Game::LOCATION_CITY_DOCKS;
                $foe->Location = Game::LOCATION_CITY_DOCKS;
                $world->theah->setLocationCanBeClaimed(Game::LOCATION_CITY_DOCKS, false);
                $world->fireOn($rena, $destroyed);
                Assert::count(0, $this->reactionOffers($world), 'cannot be claimed');

                $world->theah->setLocationCanBeClaimed(Game::LOCATION_CITY_DOCKS, true);
                $world->fireOn($rena, $destroyed);
                $offer = $this->reactionOffers($world)[0];
                Assert::same('reaction', $offer->transition, 'offered');
                Assert::same($rena->Id, $offer->sourceId, 'Rena');

                $reaction->performReaction($world->game, 0, '', 'pass');
                Assert::false($reaction->Used, 'pass does not spend');
                Assert::count(0, $world->theah->queuedOfType(EventLocationClaimed::class), 'pass does not claim');

                $world->theah->takeQueuedEvents();
                $world->fireOn($rena, $destroyed);
                $reaction->performReaction($world->game, 0, '', 'claim');
                Assert::true($reaction->Used, 'spent');
                $claim = $world->theah->queuedOfType(EventLocationClaimed::class)[0];
                Assert::same(Game::LOCATION_CITY_DOCKS, $claim->location, 'docks');
                Assert::same(1, $claim->playerId, 'Rena\'s controller');
                Assert::same($rena->Id, $claim->performerId, 'Rena performs');

                $reaction->Used = false;
                $world->theah->takeQueuedEvents();
                $world->fireOn($rena, $destroyed);
                $world->theah->setLocationCanBeClaimed(Game::LOCATION_CITY_DOCKS, false);
                $threw = false;
                try {
                    $reaction->performReaction($world->game, 0, '', 'claim');
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'claim went illegal');
                Assert::false($reaction->Used, 'throw does not spend');
            },
        ];
    }

    /** @return list<EventTransition> */
    private function reactionOffers(TestWorld $world): array
    {
        return array_values(array_filter(
            $world->theah->queuedOfType(EventTransition::class),
            fn(EventTransition $event) => $event->transition === 'reaction'
        ));
    }
}
