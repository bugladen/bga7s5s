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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04027;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04039;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05RedTape;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions\Action_05RedTape;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_05RedTape_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_05RedTape';
    }

    /**
     * Risk in hand (player 1), Academic + engaged foe with hand cards (player 2) at the Docks.
     *
     * @return array{
     *   world: TestWorld,
     *   risk: Risk,
     *   action: Action_05RedTape,
     *   performer: GenericCharacter,
     *   foe: GenericCharacter,
     *   handCards: list<\Bga\Games\SeventhSeaCityOfFiveSails\cards\Card>
     * }
     */
    private function scene(): array
    {
        $world = new TestWorld();
        $risk = $world->placeCard(new _05RedTape(), Game::LOCATION_HAND, 1);
        $performer = $world->placeCharacter(
            new GenericCharacter('Scholar', ['Academic']),
            Game::LOCATION_CITY_DOCKS,
            1
        );
        $foe = $world->placeCharacter(new GenericCharacter('Clerk'), Game::LOCATION_CITY_DOCKS, 2);
        $foe->Engaged = true;

        $handCards = [];
        for ($i = 0; $i < 5; $i++)
        {
            $handCards[] = $world->placeCard(new _04039(), Game::LOCATION_HAND, 2);
        }

        /** @var Action_05RedTape $action */
        $action = $risk->getActions()[0];
        $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

        return compact('world', 'risk', 'action', 'performer', 'foe', 'handCards');
    }

    public function tests(): array
    {
        return [
            'is a Castille Risk with Bureaucracy/Duress and a single Academic Action' => function () {
                $card = new _05RedTape();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::instanceOf(IRiskThatTargetsCharacters::class, $card, 'Cesca');
                Assert::same(0, $card->WealthCost, 'cost');
                Assert::same(1, $card->Riposte, 'riposte');
                Assert::same(1, $card->Parry, 'parry');
                Assert::same(1, $card->Thrust, 'thrust');
                Assert::true(in_array('Bureaucracy', $card->Traits, true), 'Bureaucracy not Beaurocracy');
                Assert::true(in_array('Duress', $card->Traits, true), 'Duress');
                Assert::count(1, $card->getActions(), 'one action');
                Assert::instanceOf(RiskAction::class, $card->getActions()[0], 'RiskAction');
            },

            // WHY: Academic Action (not City) — RiskAction admits home performers. Target pool
            // needs an opposing engaged character whose controller has hand cards.
            'is only offered to an Academic with an opposing engaged foe whose controller has cards' => function () {
                ['world' => $world, 'action' => $action, 'performer' => $performer, 'foe' => $foe] = $this->scene();

                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'baseline');

                $plain = $world->placeCharacter(new GenericCharacter('Thug'), Game::LOCATION_CITY_DOCKS, 1);
                $performers = $action->getPerformersForAction(1, $world->theah);
                $ids = array_map(fn($c) => $c->Id, $performers);
                Assert::true(in_array($performer->Id, $ids, true), 'academic');
                Assert::false(in_array($plain->Id, $ids, true), 'non-Academic greyed');

                $homeAcademic = $world->placeCharacter(
                    new GenericCharacter('Home Scholar', ['Academic']),
                    Game::LOCATION_PLAYER_HOME,
                    1
                );
                // Home academic has no opposing engaged at Home — greyed despite Academic trait.
                $performers = $action->getPerformersForAction(1, $world->theah);
                $ids = array_map(fn($c) => $c->Id, $performers);
                Assert::false(in_array($homeAcademic->Id, $ids, true), 'home academic no opposing');

                $foe->Engaged = false;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'en garde foe');
                $foe->Engaged = true;

                $discard = $world->game->getPlayerDiscardDeckName(2);
                foreach ($world->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, 2) as $card)
                {
                    $card->Location = $discard;
                }
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'empty hand');
            },

            'queues the character chooser on ActionTriggered' => function () {
                ['world' => $world, 'risk' => $risk, 'action' => $action] = $this->scene();

                $triggered = new EventActionTriggered();
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $world->fireOn($risk, $triggered);
                Assert::same('05RedTape', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'chooser');
            },

            'target must be opposing engaged at the performer location with cards in hand' => function () {
                ['world' => $world, 'action' => $action, 'performer' => $performer, 'foe' => $foe] = $this->scene();

                $ids = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_05REDTAPE, '')['ids'];
                Assert::same([$foe->Id], array_values($ids), 'only engaged foe');

                [$ok] = $action->isValidTargetForAbility($world->game, $performer);
                Assert::false($ok, 'own character');

                $ready = $world->placeCharacter(new GenericCharacter('Ready'), Game::LOCATION_CITY_DOCKS, 2);
                $ready->Engaged = false;
                [$ok] = $action->isValidTargetForAbility($world->game, $ready);
                Assert::false($ok, 'not engaged');

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_05REDTAPE, '', $foe->Id);
                Assert::same($foe->Id, (int)$world->game->globals->get(Game::CHOSEN_TARGET), 'target stored');
                Assert::count(1, $world->theah->queuedOfType(EventCharacterTargeted::class), 'targeting hook');
                Assert::same('characterChosen', end($world->game->gamestate->transitions), 'to EVENTS');
            },

            // WHY: Hand > 4 → hand owner chooses which four (not random). Cancel skips chooser.
            'opens the hand-owner reveal chooser only if targeting survives when hand has more than four' => function () {
                ['world' => $world, 'risk' => $risk, 'action' => $action, 'foe' => $foe] = $this->scene();
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);

                $canceled = new EventCharacterTargeted();
                $canceled->abilityId = $action->Id;
                $canceled->targetId = $foe->Id;
                $canceled->canceled = true;
                $world->fireOn($risk, $canceled);
                Assert::count(0, $action->revealedCardIds, 'no reveal on cancel');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no chooser on cancel');

                $targeted = new EventCharacterTargeted();
                $targeted->abilityId = $action->Id;
                $targeted->targetId = $foe->Id;
                $targeted->canceled = false;
                $world->fireOn($risk, $targeted);

                Assert::count(0, $action->revealedCardIds, 'not auto-revealed when hand > 4');
                $transition = $world->theah->queuedOfType(EventTransition::class)[0];
                Assert::same('05RedTape_2', $transition->transition, 'hand-owner reveal chooser');
                Assert::same(2, $transition->playerId, 'active = hand owner');

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_05REDTAPE_2, '');
                Assert::same(4, $args['cardsToReveal'], 'exactly four');
            },

            'hand owner reveals four chosen cards then opens the sink chooser for the owning player' => function () {
                ['world' => $world, 'action' => $action, 'foe' => $foe, 'handCards' => $handCards] = $this->scene();
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);
                $world->game->activePlayerId = 2;

                $pick = [
                    $handCards[0]->Id,
                    $handCards[1]->Id,
                    $handCards[2]->Id,
                    $handCards[4]->Id,
                ];
                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_05REDTAPE_2,
                    '',
                    $pick
                );

                Assert::same($pick, $action->revealedCardIds, 'chosen four sticky');
                $transition = $world->theah->queuedOfType(EventTransition::class)[0];
                Assert::same('05RedTape_3', $transition->transition, 'sink chooser');
                Assert::same(1, $transition->playerId, 'active = owning player');
                Assert::same('cardsRevealed', end($world->game->gamestate->transitions), 'to EVENTS');

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_05REDTAPE_3, '');
                Assert::same(4, count($args['cards']), 'chooseList cards');

                $threw = false;
                try {
                    $action->actFromActionWithIds(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_05REDTAPE_2,
                        '',
                        [$handCards[0]->Id, $handCards[1]->Id, $handCards[2]->Id]
                    );
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'not exactly four rejected');
            },

            'sinks the chosen revealed card to the owner faction deck bottom and resolves' => function () {
                ['world' => $world, 'action' => $action, 'foe' => $foe, 'handCards' => $handCards] = $this->scene();
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);

                $pick = $handCards[0];
                $action->revealedCardIds = [$pick->Id, $handCards[1]->Id];

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_05REDTAPE_3, '', $pick->Id);

                Assert::same(
                    $world->game->getPlayerFactionDeckName(2),
                    $pick->Location,
                    'sunk to owner deck'
                );
                Assert::true(
                    count(array_filter(
                        $world->game->deckInserts,
                        fn($row) => $row['id'] === $pick->Id && $row['onTop'] === false
                    )) > 0,
                    'bottom insert'
                );
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same('cardChosen', end($world->game->gamestate->transitions), 'back to EVENTS');
                Assert::count(0, $action->revealedCardIds, 'cleared');

                $threw = false;
                try {
                    $action->revealedCardIds = [$handCards[2]->Id];
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_05REDTAPE_3,
                        '',
                        $handCards[3]->Id
                    );
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'non-revealed rejected');
            },

            // WHY: ≤4 cards → no hand-owner choice; auto-reveal and open sink for owning player.
            'auto-reveals a short hand and opens the sink chooser' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _05RedTape(), Game::LOCATION_HAND, 1);
                $world->placeCharacter(new GenericCharacter('Scholar', ['Academic']), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Clerk'), Game::LOCATION_CITY_DOCKS, 2);
                $foe->Engaged = true;
                $only = $world->placeCard(new _04027(), Game::LOCATION_HAND, 2);
                /** @var Action_05RedTape $action */
                $action = $risk->getActions()[0];

                $targeted = new EventCharacterTargeted();
                $targeted->abilityId = $action->Id;
                $targeted->targetId = $foe->Id;
                $world->fireOn($risk, $targeted);

                Assert::same([$only->Id], $action->revealedCardIds, 'reveal what is there');
                $transition = $world->theah->queuedOfType(EventTransition::class)[0];
                Assert::same('05RedTape_3', $transition->transition, 'sink not hand chooser');
                Assert::same(1, $transition->playerId, 'owning player sinks');
            },
        ];
    }
}
