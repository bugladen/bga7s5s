<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03036;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03047;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03047a;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelGambleCardsRevealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Maneuver_03047a_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_03047a';
    }

    /**
     * @return array{0:_03047,1:Maneuver_03047a,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _03047(), Game::LOCATION_DUELING_LINE, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Scoundrel', ['Scoundrel']), Game::LOCATION_CITY_DOCKS, 1);
        $adversary = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $adversary;
        /** @var Maneuver_03047a $maneuver */
        $maneuver = $risk->getManeuvers()[0];
        return [$risk, $maneuver, $actor, $adversary];
    }

    public function tests(): array
    {
        return [
            // WHY: The lock starts on resolve and steals the chooser only after cards are
            // revealed, and only for that adversary. It ends when their round ends, when
            // our controller becomes the actor, when the maneuver is canceled, or when the
            // duel ends. Choosing clears it before the normal gamble chooser runs.
            'chooses the adversary combat card if they gamble next round' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, $actor, $adversary] = $this->scene($world);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'scoundrel');
                $actor->addTrait($world->game, 'Hero', true);
                $actor->removeTrait($world->game, 'Scoundrel');
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'not a scoundrel');
                $actor->addTrait($world->game, 'Scoundrel', true);

                $calc = new EventDuelCalculateManeuverValues();
                $calc->theah = $world->theah;
                $calc->maneuverId = $maneuver->Id;
                $maneuver->handleEvent($calc);
                Assert::same(1, $calc->riposte, 'one riposte');

                $resolve = new EventResolveManeuver();
                $resolve->theah = $world->theah;
                $resolve->maneuverId = $maneuver->Id;
                $maneuver->handleEvent($resolve);
                Assert::true($maneuver->ChooseAdversaryGambleCombatCard, 'armed');
                Assert::same($adversary->Id, $maneuver->BlockedAdversaryCharacterId, 'that adversary');

                $revealed = new EventDuelGambleCardsRevealed();
                $revealed->theah = $world->theah;
                $revealed->actorId = $actor->Id;
                $maneuver->handleEvent($revealed);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'not our gamble');

                $revealed->actorId = $adversary->Id;
                $maneuver->handleEvent($revealed);
                Assert::same('03047', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'we choose');
                Assert::same($risk->ControllerId, $world->theah->queuedOfType(EventTransition::class)[0]->playerId, 'our controller');

                $top = $world->placeCard(new _03036(), Game::LOCATION_HAND, 2);
                $world->game->topFactionCards = [['id' => $top->Id]];
                $world->game->globals->set(Game::GAMBLE_REVEAL_COUNT, 1);
                $world->theah->duelActor = $adversary;
                $args = $maneuver->getArgsFromManeuver($world->game, States::DUEL_CHOOSE_GAMBLE_CARD_03047, 'x');
                Assert::same($top->Id, $args['cards'][0]['id'], 'revealed card');

                $maneuver->actFromManeuverWithId($world->game, States::DUEL_CHOOSE_GAMBLE_CARD_03047, 'x', $top->Id);
                Assert::same([$top->Id], $world->game->gambleCardsChosen, 'handed to the chooser');
                Assert::false($maneuver->ChooseAdversaryGambleCombatCard, 'one-shot');
            },

            'the lock clears at the end of their round, our next round, cancel, or the duel' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor, $adversary] = $this->scene($world);
                $resolve = new EventResolveManeuver();
                $resolve->theah = $world->theah;
                $resolve->maneuverId = $maneuver->Id;
                $maneuver->handleEvent($resolve);

                $end = new EventDuelEndOfRound();
                $end->theah = $world->theah;
                $end->actorId = $adversary->Id;
                $maneuver->handleEvent($end);
                Assert::false($maneuver->ChooseAdversaryGambleCombatCard, 'their round ended');

                $maneuver->handleEvent($resolve);
                $round = new EventDuelNewRound();
                $round->theah = $world->theah;
                $round->actorId = $actor->Id;
                $maneuver->handleEvent($round);
                Assert::false($maneuver->ChooseAdversaryGambleCombatCard, 'our controller is the actor');

                $maneuver->handleEvent($resolve);
                $cancel = new EventManeuverCanceled();
                $cancel->theah = $world->theah;
                $cancel->maneuverId = $maneuver->Id;
                $maneuver->handleEvent($cancel);
                Assert::false($maneuver->ChooseAdversaryGambleCombatCard, 'canceled');

                $maneuver->handleEvent($resolve);
                $duelEnd = new EventDuelEnd();
                $duelEnd->theah = $world->theah;
                $maneuver->handleEvent($duelEnd);
                Assert::false($maneuver->ChooseAdversaryGambleCombatCard, 'duel ended');
            },
        ];
    }
}
