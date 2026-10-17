<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03025;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques\Technique_03025b;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Technique_03025b_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_03025b';
    }

    /**
     * @return array{0:_03025,1:Technique_03025b,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $angeline = $world->placeCharacter(new _03025(), Game::LOCATION_CITY_DOCKS, 1);
        $adversary = $world->placeCharacter(new GenericCharacter('Adversary'), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $angeline;
        $world->theah->duelOpponent = $adversary;
        $world->game->globals->set(Game::IN_DUEL, true);
        $world->game->globals->set(Game::DUEL_GAMBLED, true);
        /** @var Technique_03025b $technique */
        $technique = $angeline->getTechniques()[1];
        return [$angeline, $technique, $adversary];
    }

    public function tests(): array
    {
        return [
            // WHY: Gambling, she is the actor, and the adversary is still in play.
            // Destinations are city locations only. The adversary moves, then she does.
            // Neither move engages. Resolve spends the technique before the pick.
            'moves both participants to a city location' => function () {
                $world = new TestWorld();
                [$angeline, $technique, $adversary] = $this->scene($world);
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'available');
                $world->game->globals->set(Game::DUEL_GAMBLED, false);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not gambled');
                $world->game->globals->set(Game::DUEL_GAMBLED, true);

                $world->game->discardOrLockerByCardId[$adversary->Id] = true;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'adversary gone');
                $world->game->discardOrLockerByCardId[$adversary->Id] = false;

                $resolve = new EventResolveTechnique();
                $resolve->theah = $world->theah;
                $resolve->techniqueId = $technique->Id;
                $resolve->playerId = 1;
                $technique->handleEvent($resolve);
                Assert::true($technique->Used, 'spent on resolve');
                Assert::same('03025b', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'picker');

                $args = $technique->getArgsFromTechnique($world->game, States::DUEL_CHOOSE_TECHNIQUE_03025B, 'x');
                Assert::true(in_array(Game::LOCATION_CITY_FORUM, $args['locationIds'], true), 'forum');
                Assert::false(in_array(Game::LOCATION_PLAYER_HOME, $args['locationIds'], true), 'home is not a city location');

                $threw = false;
                try {
                    $technique->actFromTechniqueWithIds($world->game, States::DUEL_CHOOSE_TECHNIQUE_03025B, 'x', [Game::LOCATION_PLAYER_HOME]);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'home rejected');
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'nothing queued');

                $technique->actFromTechniqueWithIds($world->game, States::DUEL_CHOOSE_TECHNIQUE_03025B, 'x', [Game::LOCATION_CITY_BAZAAR]);
                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::same($adversary->Id, $moves[0]->cardId, 'adversary first');
                Assert::same($angeline->Id, $moves[1]->cardId, 'then Angeline');
                Assert::same(Game::LOCATION_CITY_BAZAAR, $moves[0]->toLocation, 'destination');
                Assert::false($moves[0]->engage, 'adversary unengaged');
                Assert::false($moves[1]->engage, 'she unengaged');
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
            },
        ];
    }
}
