<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericLeader;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03001;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03001;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_03001_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_03001';
    }

    /**
     * @return array{0:_03001,1:Reaction_03001,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $cesca = $world->placeCharacter(new _03001(), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        /** @var Reaction_03001 $reaction */
        $reaction = $cesca->getReactions()[0];
        return [$cesca, $reaction, $foe];
    }

    private function played(TestWorld $world, _03001 $cesca, int $targetId = 0): EventSorcererAbilityPlayed
    {
        $event = new EventSorcererAbilityPlayed();
        $event->theah = $world->theah;
        $event->playerId = 1;
        $event->sourceId = $cesca->Id;
        $event->performerId = $cesca->Id;
        $event->abilityId = 'sorcery';
        $event->targetId = $targetId;
        return $event;
    }

    public function tests(): array
    {
        return [
            'is a character-targeting card reaction' => function () {
                $reaction = new Reaction_03001();
                Assert::instanceOf(CardReaction::class, $reaction, 'card reaction');
                Assert::instanceOf(IAbilityThatTargetsCharacters::class, $reaction, 'targets');
            },

            'offers after Cesca performs or sources a Sorcerer ability with an opposing character present' => function () {
                $world = new TestWorld();
                [$cesca, $reaction, $foe] = $this->scene($world);
                $reaction->handleEvent($this->played($world, $cesca));
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered');
                Assert::same($cesca->ControllerId, $transitions[0]->playerId, 'her controller');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'reaction');

                $buttons = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::true(in_array("wound-{$foe->Id}", $buttons, true), 'wound button');
                Assert::true(in_array('pass', $buttons, true), 'pass');
            },

            'a Leader is a legal wound, and someone else performing sorcery is not' => function () {
                $world = new TestWorld();
                [$cesca, $reaction, $foe] = $this->scene($world);
                $foe->Location = Game::LOCATION_CITY_FORUM;
                $leader = $world->placeCharacter(new GenericLeader(), Game::LOCATION_CITY_DOCKS, 2);
                $reaction->handleEvent($this->played($world, $cesca));
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'leader counts');

                $world->theah->takeQueuedEvents();
                $other = $this->played($world, $cesca);
                $other->sourceId = $leader->Id;
                $other->performerId = $leader->Id;
                $reaction->handleEvent($other);
                Assert::count(0, $world->theah->queuedEvents, 'not her ability');
            },

            'Home with no queued arrival does not offer' => function () {
                $world = new TestWorld();
                [$cesca, $reaction] = $this->scene($world);
                $cesca->Location = Game::LOCATION_PLAYER_HOME;
                $reaction->handleEvent($this->played($world, $cesca));
                Assert::count(0, $world->theah->queuedEvents, 'home');
            },

            // WHY: EventCardMoved is queued after the sorcery event. Cesca's Location is still
            // the old one, and the pulled character is not co-located yet. Only EventCardMoved
            // counts; EventCardMoving is invisible to the DB lookup.
            'a queued move to the city, or a target queued onto Cesca, still offers' => function () {
                $world = new TestWorld();
                [$cesca, $reaction, $foe] = $this->scene($world);
                $cesca->Location = Game::LOCATION_PLAYER_HOME;
                $arrival = new EventCardMoved();
                $arrival->cardId = $cesca->Id;
                $arrival->fromLocation = Game::LOCATION_PLAYER_HOME;
                $arrival->toLocation = Game::LOCATION_CITY_DOCKS;
                $arrival->initiatingPlayerId = 1;
                $world->theah->queueEvent($arrival);

                $reaction->handleEvent($this->played($world, $cesca));
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'queued city arrival');

                $world->theah->takeQueuedEvents();
                $cesca->Location = Game::LOCATION_CITY_DOCKS;
                $foe->Location = Game::LOCATION_CITY_FORUM;
                $pull = new EventCardMoved();
                $pull->cardId = $foe->Id;
                $pull->fromLocation = Game::LOCATION_CITY_FORUM;
                $pull->toLocation = Game::LOCATION_CITY_DOCKS;
                $pull->initiatingPlayerId = 1;
                $world->theah->queueEvent($pull);
                $reaction->handleEvent($this->played($world, $cesca, $foe->Id));
                $buttons = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::false(in_array("wound-{$foe->Id}", $buttons, true), 'buttons still read current location');
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'queued co-location still offers');
            },

            'rejects a friendly character and wounds a legal one' => function () {
                $world = new TestWorld();
                [$cesca, $reaction, $foe] = $this->scene($world);
                [$own] = $reaction->isValidTargetForAbility($world->game, $cesca);
                Assert::false($own, 'own');
                [$ok] = $reaction->isValidTargetForAbility($world->game, $foe);
                Assert::true($ok, 'foe');

                $reaction->performReaction($world->game, 0, $reaction->Id, "wound-{$foe->Id}");
                $wound = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::same($foe->Id, $wound[0]->characterId, 'target');
                Assert::same($cesca->Id, $wound[0]->sourceId, 'source');
                Assert::same(1, $wound[0]->wounds, 'one');
                Assert::same($reaction->Id, $wound[0]->abilityId, 'ability');
                Assert::true($reaction->Used, 'spent');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'pass does not spend the reaction' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');
                Assert::false($reaction->Used, 'pass');
                Assert::count(0, $world->theah->queuedEvents, 'no wound');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'an unknown wound id throws' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $threw = false;
                try {
                    $reaction->performReaction($world->game, 0, $reaction->Id, 'wound-999');
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'missing');
                Assert::false($reaction->Used, 'not spent');
            },
        ];
    }
}
