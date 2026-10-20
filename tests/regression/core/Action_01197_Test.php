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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01197;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01197;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01197_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01197';
    }

    /**
     * Kalla + ally with attachment at same location.
     *
     * @return array{0:_01197,1:Action_01197,2:Character,3:Attachment}
     */
    private function scene(TestWorld $world): array
    {
        $kalla = $world->placeCharacter(new _01197(), Game::LOCATION_CITY_DOCKS, 1);
        $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
        $att = $this->equip($world, new _01049(), $ally);
        /** @var Action_01197 $action */
        $action = $kalla->getActions()[0];
        return [$kalla, $action, $ally, $att];
    }

    private function equip(TestWorld $world, Attachment $attachment, Character $host): Attachment
    {
        $placed = $world->placeCard($attachment, $host->Location, $host->ControllerId);
        $placed->AttachedToId = $host->Id;
        $host->Attachments[] = $placed->Id;
        return $placed;
    }

    public function tests(): array
    {
        return [
            'is a CharacterAction' => function () {
                Assert::instanceOf(CharacterAction::class, new Action_01197(), 'CharacterAction');
            },

            // WHY (journal 2026-04-08-08): need ≥2 friendlies AND ≥1 with attachments.
            'available with two friendlies and an attachment at Kalla\'s location' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable with only one friendly at the location' => function () {
                $world = new TestWorld();
                $kalla = $world->placeCharacter(new _01197(), Game::LOCATION_CITY_DOCKS, 1);
                $this->equip($world, new _01049(), $kalla);
                /** @var Action_01197 $action */
                $action = $kalla->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'solo');
            },

            'unavailable when no friendly has attachments' => function () {
                $world = new TestWorld();
                $kalla = $world->placeCharacter(new _01197(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_01197 $action */
                $action = $kalla->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no att');
            },

            'unavailable when Kalla is uncontrolled' => function () {
                $world = new TestWorld();
                [$kalla, $action] = $this->scene($world);
                $kalla->ControllerId = 0;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'uncontrolled');
            },

            'trigger queues transition 01197' => function () {
                $world = new TestWorld();
                [$kalla, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one');
                Assert::same('01197', $transitions[0]->transition, 'name');
                Assert::same($kalla->Id, $transitions[0]->sourceId, 'source');
            },

            'args step 1 lists friendlies with attachments' => function () {
                $world = new TestWorld();
                [$kalla, $action, $ally] = $this->scene($world);

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01197,
                    'highDramaPlayerTurn_01197'
                );

                Assert::same($kalla->Id, $args['performerId'], 'performer');
                Assert::same([$ally->Id], $args['targetCharacterIds'], 'sources');
            },

            'args step 2 lists attachments on the chosen source' => function () {
                $world = new TestWorld();
                [, $action, $ally, $att] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $ally->Id);

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01197_2,
                    'highDramaPlayerTurn_01197_2'
                );

                Assert::same($ally->Id, $args['chosenCharacterId'], 'source');
                Assert::count(1, $args['attachments'], 'one att');
                Assert::same($att->Id, $args['attachments'][0]['id'], 'id');
            },

            'args step 3 excludes the source character' => function () {
                $world = new TestWorld();
                [$kalla, $action, $ally, $att] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $ally->Id);
                $world->game->globals->set(Game::CHOSEN_CARD, $att->Id);

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01197_3,
                    'highDramaPlayerTurn_01197_3'
                );

                Assert::same([$kalla->Id], $args['targetCharacterIds'], 'dest = Kalla only');
                Assert::same($att->Name, $args['attachmentName'], 'name');
            },

            'act step 1 stores source and transitions characterChosen' => function () {
                $world = new TestWorld();
                [, $action, $ally] = $this->scene($world);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01197,
                    'x',
                    $ally->Id
                );

                Assert::same($ally->Id, $world->game->globals->get(Game::CHOSEN_PERFORMER), 'source');
                Assert::same(['characterChosen'], $world->game->gamestate->transitions, 'next');
            },

            'act step 2 stores attachment and transitions attachmentChosen' => function () {
                $world = new TestWorld();
                [, $action, $ally, $att] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $ally->Id);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01197_2,
                    'x',
                    $att->Id
                );

                Assert::same($att->Id, $world->game->globals->get(Game::CHOSEN_CARD), 'att');
                Assert::same(['attachmentChosen'], $world->game->gamestate->transitions, 'next');
            },

            'act step 3 queues AttachmentMoved and ActionResolved' => function () {
                $world = new TestWorld();
                [$kalla, $action, $ally, $att] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $ally->Id);
                $world->game->globals->set(Game::CHOSEN_CARD, $att->Id);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01197_3,
                    'x',
                    $kalla->Id
                );

                $moves = $world->theah->queuedOfType(EventAttachmentMoved::class);
                Assert::count(1, $moves, 'moved');
                Assert::same($att->Id, $moves[0]->attachmentId, 'att');
                Assert::same($ally->Id, $moves[0]->fromCharacterId, 'from');
                Assert::same($kalla->Id, $moves[0]->toCharacterId, 'to');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same(['characterChosen'], $world->game->gamestate->transitions, 'done');
            },

            // WHY (journal 2026-04-08-08): UI filters source; server must still refuse same-as-source.
            'act step 3 refuses destination equal to source' => function () {
                $world = new TestWorld();
                [, $action, $ally, $att] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $ally->Id);
                $world->game->globals->set(Game::CHOSEN_CARD, $att->Id);

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01197_3,
                        'x',
                        $ally->Id
                    );
                } catch (UserException | \BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'same source');
                Assert::count(0, $world->theah->queuedEvents, 'nothing');
            },

            'act step 1 refuses an opposing character' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $this->equip($world, new _01049(), $foe);

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01197,
                        'x',
                        $foe->Id
                    );
                } catch (UserException | \BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'foe');
            },
        ];
    }
}
