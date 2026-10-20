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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\SchemeCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03030;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03030;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_03030_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_03030';
    }

    /**
     * @return array{0:_03030,1:Action_03030,2:GenericCharacter,3:GenericCharacter,4:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $scheme = $world->placeCard(new _03030(), Game::LOCATION_PLAYER_HOME, 1);
        $diplomat = $world->placeCharacter(new GenericCharacter('Diplomat', ['Diplomat', 'Hero']), Game::LOCATION_CITY_DOCKS, 1);
        $duelist = $world->placeCharacter(new GenericCharacter('Duelist', ['Duelist', 'Hero']), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->game->globals->set(Game::CHOSEN_PERFORMER, $diplomat->Id);
        /** @var Action_03030 $action */
        $action = $scheme->getActions()[0];
        return [$scheme, $action, $diplomat, $duelist, $foe];
    }

    public function tests(): array
    {
        return [
            'is a city action on a scheme' => function () {
                $action = new Action_03030();
                Assert::instanceOf(SchemeCityAction::class, $action, 'scheme city action');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            // WHY: The performer is an unengaged Diplomat. A Duelist who can challenge and
            // an opposing character must share that location. The trigger remembers the
            // Diplomat in CHOSEN_CARD because CHOSEN_PERFORMER later becomes the Duelist.
            // Challenge type is not set until the target is chosen. No action resolved.
            'the Diplomat engages, then the Duelist challenges' => function () {
                $world = new TestWorld();
                [$scheme, $action, $diplomat, $duelist, $foe] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
                Assert::same([$diplomat->Id], array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah)), 'diplomat');

                $diplomat->Engaged = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged diplomat');
                $diplomat->Engaged = false;

                $triggered = new EventActionTriggered();
                $triggered->theah = $world->theah;
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $action->handleEvent($triggered);
                Assert::same($diplomat->Id, $world->game->globals->get(Game::CHOSEN_CARD), 'diplomat preserved');
                Assert::same($diplomat->Id, $world->theah->queuedOfType(EventCardEngaged::class)[0]->cardId, 'engaged');
                Assert::same('03030', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'duelist step');
                Assert::same(null, $world->game->globals->get(Game::CHALLENGE_TYPE), 'type not set yet');

                $world->theah->takeQueuedEvents();
                $world->game->gamestate->transitions = [];
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03030, 'x', $duelist->Id);
                Assert::same($duelist->Id, $world->game->globals->get(Game::CHOSEN_PERFORMER), 'duelist is now the performer');
                Assert::same(['duelistChosen'], $world->game->gamestate->transitions, 'duelist');
                Assert::same(null, $world->game->globals->get(Game::CHALLENGE_TYPE), 'still not set');

                [$ok] = $action->isValidTargetForAbility($world->game, $foe);
                Assert::true($ok, 'opposing, same location');
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03030_2, 'x', $foe->Id);
                Assert::same($foe->Id, $world->game->globals->get(Game::CHOSEN_TARGET), 'target');
                Assert::same(Game::STAT_COMBAT, $world->game->globals->get(Game::CHALLENGE_STAT), 'combat');
                Assert::same(Game::SWORN_SWORDS_CHALLENGE_TYPE, $world->game->globals->get(Game::CHALLENGE_TYPE), 'sworn swords');
                $transition = $world->theah->queuedOfType(EventTransition::class)[0];
                Assert::same('03030_2', $transition->transition, 'challenge flow');
                Assert::same($duelist->Id, $transition->sourceId, 'the duelist, not the scheme');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'the challenge flow resolves it');
                Assert::same(['duelistChosen', 'targetChosen'], $world->game->gamestate->transitions, 'target chosen');
                unset($scheme);
            },

            'a civilian performer throws before engaging, and an uncontrolled target is rejected' => function () {
                $world = new TestWorld();
                [, $action, $diplomat, $duelist, $foe] = $this->scene($world);
                $diplomat->removeTrait($world->game, 'Diplomat');
                $triggered = new EventActionTriggered();
                $triggered->theah = $world->theah;
                $triggered->actionId = $action->Id;
                $threw = false;
                try {
                    $action->handleEvent($triggered);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'not a diplomat');
                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'not engaged');

                $diplomat->addTrait($world->game, 'Diplomat', true);
                $foe->ControllerId = 0;
                $world->game->globals->set(Game::CHOSEN_CARD, $diplomat->Id);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $duelist->Id);
                [$ok] = $action->isValidTargetForAbility($world->game, $foe);
                Assert::false($ok, 'uncontrolled');
            },

            'accepted Sworn Swords challenges add one threat to the actor' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $threat = new EventGenerateChallengeThreat();
                $threat->theah = $world->theah;
                $threat->actorThreat = 0;
                $world->game->globals->set(Game::CHALLENGE_TYPE, Game::NORMAL_CHALLENGE_TYPE);
                $action->handleEvent($threat);
                Assert::same(0, $threat->actorThreat, 'other challenge');

                $world->game->globals->set(Game::CHALLENGE_TYPE, Game::SWORN_SWORDS_CHALLENGE_TYPE);
                $action->handleEvent($threat);
                Assert::same(1, $threat->actorThreat, 'plus one');
            },
        ];
    }
}
