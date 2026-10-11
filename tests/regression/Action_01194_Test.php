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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01194;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01194;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01194_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01194';
    }

    /**
     * Adelheide in city with one attachment and an opposing character.
     *
     * @return array{0:_01194,1:Action_01194,2:Attachment,3:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $adelheide = $world->placeCharacter(new _01194(), Game::LOCATION_CITY_DOCKS, 1);
        $att = $this->equip($world, $adelheide, new _01049());
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        /** @var Action_01194 $action */
        $action = $adelheide->getActions()[0];
        return [$adelheide, $action, $att, $foe];
    }

    private function equip(TestWorld $world, Character $host, Attachment $attachment): Attachment
    {
        $placed = $world->placeCard($attachment, $host->Location, $host->ControllerId);
        $placed->AttachedToId = $host->Id;
        $host->Attachments[] = $placed->Id;
        return $placed;
    }

    public function tests(): array
    {
        return [
            'is a CharacterAction that targets characters' => function () {
                $action = new Action_01194();
                Assert::instanceOf(CharacterAction::class, $action, 'CharacterAction');
                Assert::instanceOf(IAbilityThatTargetsCharacters::class, $action, 'targets');
            },

            'available with attachment and opposing character in city' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable without an attachment' => function () {
                $world = new TestWorld();
                $adelheide = $world->placeCharacter(new _01194(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Action_01194 $action */
                $action = $adelheide->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no attachment');
            },

            'unavailable without an opposing character at location' => function () {
                $world = new TestWorld();
                $adelheide = $world->placeCharacter(new _01194(), Game::LOCATION_CITY_DOCKS, 1);
                $this->equip($world, $adelheide, new _01049());
                /** @var Action_01194 $action */
                $action = $adelheide->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'alone');
            },

            'unavailable at Home' => function () {
                $world = new TestWorld();
                $adelheide = $world->placeCharacter(new _01194(), Game::LOCATION_PLAYER_HOME, 1);
                $this->equip($world, $adelheide, new _01049());
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_PLAYER_HOME, 2);
                /** @var Action_01194 $action */
                $action = $adelheide->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home');
            },

            'unavailable when uncontrolled' => function () {
                $world = new TestWorld();
                $adelheide = $world->placeCharacter(new _01194(), Game::LOCATION_CITY_DOCKS, 0);
                $this->equip($world, $adelheide, new _01049());
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Action_01194 $action */
                $action = $adelheide->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'uncontrolled');
            },

            'trigger queues transition 01194' => function () {
                $world = new TestWorld();
                [$adelheide, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one');
                Assert::same('01194', $transitions[0]->transition, 'name');
                Assert::same($adelheide->Id, $transitions[0]->sourceId, 'source');
            },

            'args step 1 list attachment buttons' => function () {
                $world = new TestWorld();
                [$adelheide, $action, $att] = $this->scene($world);

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01194,
                    'highDramaPlayerTurn_01194'
                );

                Assert::same($adelheide->Id, $args['characterId'], 'character');
                Assert::count(1, $args['attachments'], 'one');
                Assert::same($att->Id, $args['attachments'][0]['id'], 'id');
                Assert::same($att->Name, $args['attachments'][0]['name'], 'name');
            },

            'args step 2 list opposing character ids at location' => function () {
                $world = new TestWorld();
                [$adelheide, $action, , $foe] = $this->scene($world);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01194_2,
                    'highDramaPlayerTurn_01194_2'
                );

                Assert::same($adelheide->Id, $args['characterId'], 'character');
                Assert::same([$foe->Id], $args['targetCharacterIds'], 'opposing only');
                Assert::false(in_array($ally->Id, $args['targetCharacterIds'], true), 'ally omitted');
            },

            'step 1 choosing an attachment records CHOSEN_CARD' => function () {
                $world = new TestWorld();
                [, $action, $att] = $this->scene($world);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01194,
                    'highDramaPlayerTurn_01194',
                    $att->Id
                );

                Assert::same($att->Id, $world->game->globals->get(Game::CHOSEN_CARD), 'chosen');
                Assert::same(['attachmentChosen'], $world->game->gamestate->transitions, 'next');
            },

            // WHY (journal 2026-04-01-09): cost discard (asEffect=false) then wound; ActionResolved.
            'step 2 discards attachment, wounds target, ActionResolved' => function () {
                $world = new TestWorld();
                [$adelheide, $action, $att, $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_CARD, $att->Id);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01194_2,
                    'highDramaPlayerTurn_01194_2',
                    $foe->Id
                );

                $unequip = $world->theah->queuedOfType(EventAttachmentUnequipped::class);
                Assert::count(1, $unequip, 'unequip');
                Assert::same($att->Id, $unequip[0]->attachmentId, 'attachment');
                Assert::same($adelheide->Id, $unequip[0]->characterId, 'host');

                // FactionAttachment _01049 discards via EventCardDiscardedFromPlay, not city pile.
                $discards = $world->theah->queuedOfType(EventCardDiscardedFromPlay::class);
                Assert::count(1, $discards, 'discard');
                Assert::same($att->Id, $discards[0]->cardId, 'card');
                Assert::false($discards[0]->asEffect, 'cost');

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'wound');
                Assert::same($foe->Id, $wounds[0]->characterId, 'target');
                Assert::same($adelheide->Id, $wounds[0]->sourceId, 'source');
                Assert::same(1, $wounds[0]->wounds, '1 wound');

                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same(['characterChosen'], $world->game->gamestate->transitions, 'done');
                Assert::count(0, $world->theah->queuedOfType(EventCardAddedToCityDiscardPile::class), 'not city');
            },

            'step 1 refuses an attachment Adelheide does not have' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $otherHost = $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_CITY_DOCKS, 1);
                $otherAtt = $this->equip($world, $otherHost, new _01049());

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01194,
                        'x',
                        $otherAtt->Id
                    );
                } catch (UserException | \BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'not hers');
            },

            'step 2 refuses a friendly character' => function () {
                $world = new TestWorld();
                [, $action, $att] = $this->scene($world);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $world->game->globals->set(Game::CHOSEN_CARD, $att->Id);

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01194_2,
                        'x',
                        $ally->Id
                    );
                } catch (UserException | \BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'friendly');
                Assert::count(0, $world->theah->queuedEvents, 'nothing');
            },

            'step 2 refuses a character at another location' => function () {
                $world = new TestWorld();
                [, $action, $att] = $this->scene($world);
                $far = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_FORUM, 2);
                $world->game->globals->set(Game::CHOSEN_CARD, $att->Id);

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01194_2,
                        'x',
                        $far->Id
                    );
                } catch (UserException | \BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'wrong location');
            },

            'isValidTargetForAbility rejects friendly and far targets' => function () {
                $world = new TestWorld();
                [, $action, , $foe] = $this->scene($world);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $far = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_FORUM, 2);

                [$ok] = $action->isValidTargetForAbility($world->game, $foe);
                Assert::true($ok, 'foe ok');
                [$okAlly] = $action->isValidTargetForAbility($world->game, $ally);
                Assert::false($okAlly, 'ally');
                [$okFar] = $action->isValidTargetForAbility($world->game, $far);
                Assert::false($okFar, 'far');
            },
        ];
    }
}
