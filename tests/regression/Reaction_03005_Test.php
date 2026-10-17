<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03005;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03005;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeRejected;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_03005_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_03005';
    }

    /**
     * @return array{0:_03005,1:Reaction_03005,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $scheme = $world->placeCard(new _03005(), Game::LOCATION_PLAYER_HOME, 1);
        $hand = $world->placeCharacter(new GenericCharacter('Blade', ['Red Hand', 'Duelist']), Game::LOCATION_CITY_DOCKS, 1);
        /** @var Reaction_03005 $reaction */
        $reaction = $scheme->getReactions()[0];
        return [$scheme, $reaction, $hand];
    }

    public function tests(): array
    {
        return [
            'is a card reaction' => function () {
                Assert::instanceOf(CardReaction::class, new Reaction_03005(), 'reaction');
            },

            'offers when your Red Hand challenge is refused at a claimable city location' => function () {
                $world = new TestWorld();
                [$scheme, $reaction, $hand] = $this->scene($world);
                $event = new EventChallengeRejected();
                $event->theah = $world->theah;
                $event->challengerId = $hand->Id;
                $reaction->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered');
                Assert::same($scheme->Id, $transitions[0]->sourceId, 'source');
                Assert::true($scheme->IsUpdated, 'location latched');
                $buttons = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(['claim', 'pass'], $buttons, 'buttons');
            },

            'an opponent Red Hand, a character without the trait, or an unclaimable location does not offer' => function () {
                $world = new TestWorld();
                [, $reaction, $hand] = $this->scene($world);
                $hand->ControllerId = 2;
                $event = new EventChallengeRejected();
                $event->theah = $world->theah;
                $event->challengerId = $hand->Id;
                $reaction->handleEvent($event);
                Assert::count(0, $world->theah->queuedEvents, 'opponent');

                $hand->ControllerId = 1;
                $hand->removeTrait($world->game, 'Red Hand');
                $reaction->handleEvent($event);
                Assert::count(0, $world->theah->queuedEvents, 'not Red Hand');

                $hand->addTrait($world->game, 'Red Hand', true);
                $world->theah->setLocationCanBeClaimed(Game::LOCATION_CITY_DOCKS, false);
                $reaction->handleEvent($event);
                Assert::count(0, $world->theah->queuedEvents, 'cannot be claimed');
            },

            // WHY: getCityLocation throws for Home. The following null check never runs.
            'a Home Red Hand throws instead of declining quietly' => function () {
                $world = new TestWorld();
                [, $reaction, $hand] = $this->scene($world);
                $hand->Location = Game::LOCATION_PLAYER_HOME;
                $event = new EventChallengeRejected();
                $event->theah = $world->theah;
                $event->challengerId = $hand->Id;
                $threw = false;
                try {
                    $reaction->handleEvent($event);
                } catch (\Exception $e) {
                    $threw = str_contains($e->getMessage(), 'not found');
                }
                Assert::true($threw, 'home location');
                Assert::count(0, $world->theah->queuedEvents, 'no offer');
            },

            'claim queues the location claim and spends; pass clears the latch without spending' => function () {
                $world = new TestWorld();
                [$scheme, $reaction, $hand] = $this->scene($world);
                $event = new EventChallengeRejected();
                $event->theah = $world->theah;
                $event->challengerId = $hand->Id;
                $reaction->handleEvent($event);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');
                Assert::false($reaction->Used, 'pass');
                Assert::count(0, $world->theah->queuedOfType(EventLocationClaimed::class), 'no claim');

                $reaction->handleEvent($event);
                $world->theah->setLocationCanBeClaimed(Game::LOCATION_CITY_DOCKS, false);
                $reaction->performReaction($world->game, 0, $reaction->Id, 'claim');
                Assert::false($reaction->Used, 'became unclaimable');
                Assert::count(0, $world->theah->queuedOfType(EventLocationClaimed::class), 'still no claim');

                $world->theah->setLocationCanBeClaimed(Game::LOCATION_CITY_DOCKS, true);
                $reaction->handleEvent($event);
                $reaction->performReaction($world->game, 0, $reaction->Id, 'claim');
                $claims = $world->theah->queuedOfType(EventLocationClaimed::class);
                Assert::same(1, $claims[0]->playerId, 'controller');
                Assert::same(null, $claims[0]->performerId, 'no performer');
                Assert::same(Game::LOCATION_CITY_DOCKS, $claims[0]->location, 'docks');
                Assert::true($reaction->Used, 'spent');
                unset($scheme);
            },
        ];
    }
}
