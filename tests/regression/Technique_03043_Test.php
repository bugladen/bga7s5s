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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03036;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03043;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03044;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques\Technique_03043;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Technique_03043_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_03043';
    }

    /**
     * @return array{0:_03043,1:Technique_03043,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $host = $world->placeCharacter(new GenericCharacter('Host', ['Scoundrel']), Game::LOCATION_CITY_DOCKS, 1);
        $mask = $world->placeCard(new _03043(), Game::LOCATION_CITY_DOCKS, 1);
        $mask->AttachedToId = $host->Id;
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $host->ModifiedFinesse = 3;
        $foe->ModifiedFinesse = 1;
        $world->theah->duelActor = $host;
        $world->theah->duelOpponent = $foe;
        $world->game->globals->set(Game::IN_DUEL, true);
        $world->game->globals->set(Game::DUEL_GAMBLED, true);
        /** @var Technique_03043 $technique */
        $technique = $mask->getTechniques()[0];
        return [$mask, $technique, $foe];
    }

    public function tests(): array
    {
        return [
            // WHY: Gambling, actor is the equipped character, Finesse strictly greater,
            // and the adversary has a hand. Equal Finesse is not greater.
            'requires a gambled actor with greater Finesse and a hand to look at' => function () {
                $world = new TestWorld();
                [, $technique, $foe] = $this->scene($world);
                $world->placeCard(new _03036(), Game::LOCATION_HAND, 2);
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'available');
                $foe->ModifiedFinesse = 3;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'equal finesse');
                $foe->ModifiedFinesse = 1;
                $world->game->globals->set(Game::DUEL_GAMBLED, false);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not gambled');
                $world->game->globals->set(Game::DUEL_GAMBLED, true);
                $world->theah->duelActor = $foe;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not the equipped character');
            },

            // WHY: One revealed attachment is discarded without a picker. None revealed
            // as attachments ends the technique. Two attachments hand the choice to the adversary.
            'one revealed attachment is discarded; two wait for the adversary' => function () {
                $world = new TestWorld();
                [$mask, $technique] = $this->scene($world);
                $attachment = $world->placeCard(new _03044(), Game::LOCATION_HAND, 2);
                $world->placeCard(new _03036(), Game::LOCATION_HAND, 2);

                $resolve = new EventResolveTechnique();
                $resolve->theah = $world->theah;
                $resolve->techniqueId = $technique->Id;
                $technique->handleEvent($resolve);
                Assert::same($mask->ControllerId, $world->game->globals->get(Game::MULTI_STATE_INITIATING_PLAYER), 'everyone sees the reveal');
                Assert::same($mask->Id, $world->theah->queuedOfType(EventTransition::class)[0]->sourceId, 'the mask, not the character');
                Assert::true($technique->Used, 'spent on resolve');

                $technique->stateFromTechnique($world->game, States::DUEL_CHOOSE_TECHNIQUE_03043_2, 'x');
                Assert::same($attachment->Id, $world->theah->queuedOfType(EventCardDiscardedFromHand::class)[0]->cardId, 'the only attachment');
                Assert::same(['done'], $world->game->gamestate->transitions, 'no picker');

                $world->theah->takeQueuedEvents();
                $world->game->gamestate->transitions = [];
                foreach ($world->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, 2) as $card) {
                    if (! ($card instanceof \Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment)) {
                        $card->Location = Game::LOCATION_CITY_DOCKS;
                    }
                }
                $second = $world->placeCard(new _03044(), Game::LOCATION_HAND, 2);
                $attachment->Location = Game::LOCATION_HAND;
                $technique->handleEvent($resolve);
                $technique->stateFromTechnique($world->game, States::DUEL_CHOOSE_TECHNIQUE_03043_2, 'x');
                Assert::same([2], $world->game->gamestate->activePlayerChanges, 'adversary chooses');
                Assert::same(['discard'], $world->game->gamestate->transitions, 'discard');

                $args = $technique->getArgsFromTechnique($world->game, States::DUEL_CHOOSE_TECHNIQUE_03043_3, 'x');
                Assert::count(2, $args['cardIds'], 'both attachments');
                $world->game->activePlayerId = 2;
                $technique->actFromTechniqueWithId($world->game, States::DUEL_CHOOSE_TECHNIQUE_03043_3, 'x', $second->Id);
                Assert::same($second->Id, $world->theah->queuedOfType(EventCardDiscardedFromHand::class)[0]->cardId, 'chosen');

                $threw = false;
                try {
                    $risk = $world->placeCard(new _03036(), Game::LOCATION_HAND, 2);
                    $technique->actFromTechniqueWithId($world->game, States::DUEL_CHOOSE_TECHNIQUE_03043_3, 'x', $risk->Id);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'not one of the revealed attachments');
            },

            'no revealed attachments, and an empty hand, discard nothing' => function () {
                $world = new TestWorld();
                [, $technique] = $this->scene($world);
                $world->placeCard(new _03036(), Game::LOCATION_HAND, 2);
                $world->placeCard(new _03036(), Game::LOCATION_HAND, 2);
                $resolve = new EventResolveTechnique();
                $resolve->theah = $world->theah;
                $resolve->techniqueId = $technique->Id;
                $technique->handleEvent($resolve);
                $technique->stateFromTechnique($world->game, States::DUEL_CHOOSE_TECHNIQUE_03043_2, 'x');
                Assert::count(0, $world->theah->queuedOfType(EventCardDiscardedFromHand::class), 'no attachments');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },
        ];
    }
}
