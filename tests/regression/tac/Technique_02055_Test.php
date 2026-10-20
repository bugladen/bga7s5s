<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01010;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02055;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02056;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02055;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToFactionDeck;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Technique_02055_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_02055';
    }

    /**
     * @return array{0:_02055,1:Technique_02055,2:_01010,3:_02056}
     */
    private function scene(TestWorld $world, bool $attach = true): array
    {
        $actor = $world->placeCharacter(new _01010(), Game::LOCATION_CITY_DOCKS, 1);
        $dame = $world->placeCard(new _02055(), Game::LOCATION_CITY_DOCKS, 1);
        $gauche = $world->placeCard(new _02056(), Game::LOCATION_CITY_DOCKS, 1);
        if ($attach) {
            $dame->AttachedToId = $actor->Id;
            $actor->addAttachment($world->theah, $dame);
            $gauche->AttachedToId = $actor->Id;
            $actor->addAttachment($world->theah, $gauche);
        }
        $adversary = $world->placeCharacter(new GenericCharacter('Adversary'), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $adversary;
        $world->game->globals->set(Game::IN_DUEL, true);
        /** @var Technique_02055 $technique */
        $technique = $dame->getTechniques()[0];
        return [$dame, $technique, $actor, $gauche];
    }

    public function tests(): array
    {
        return [
            'state constant' => function () {
                Assert::same(52102055, States::DUEL_CHOOSE_TECHNIQUE_02055, 'state');
            },

            // WHY: Copy ignores the source technique's own availability. It refuses Dame
            // itself (by class, not just this instance) and anything already a temporary copy.
            'available when the attached actor has some other technique' => function () {
                $world = new TestWorld();
                [$dame, $technique, $actor] = $this->scene($world);
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'available');

                $world->game->globals->set(Game::IN_DUEL, false);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not a duel');
                $world->game->globals->set(Game::IN_DUEL, true);

                Assert::false($technique->isAvailableToPlayer(2, $world->theah), 'not the actor controller');

                $dame->AttachedToId = 0;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not attached');
                $dame->AttachedToId = $actor->Id;

                $onlyDame = new TestWorld();
                $host = $onlyDame->placeCharacter(new GenericCharacter('Plain'), Game::LOCATION_CITY_DOCKS, 1);
                $bare = $onlyDame->placeCard(new _02055(), Game::LOCATION_CITY_DOCKS, 1);
                $bare->AttachedToId = $host->Id;
                $host->addAttachment($onlyDame->theah, $bare);
                $onlyDame->theah->duelActor = $host;
                $onlyDame->game->globals->set(Game::IN_DUEL, true);
                Assert::false($bare->getTechniques()[0]->isAvailableToPlayer(1, $onlyDame->theah), 'nothing to copy');
            },

            'args list actor and attachment techniques, skipping Dame and temporary copies' => function () {
                $world = new TestWorld();
                [, $technique, $actor, $gauche] = $this->scene($world);
                $temp = clone $actor->getTechniques()[0];
                $temp->IsTemporaryCopy = true;
                $temp->Id = $actor->Id . '_temp';
                $actor->addTechnique($temp, $world->game, false);
                $world->game->activePlayerId = 2;

                $args = $technique->getArgsFromTechnique($world->game, States::DUEL_CHOOSE_TECHNIQUE_02055, 'duelChooseTechnique_02055');
                $ids = array_column($args['techniques'], 'id');
                Assert::same([
                    $actor->getTechniques()[0]->Id,
                    $gauche->getTechniques()[0]->Id,
                ], $ids, 'Sarafina and Main Gauche');
            },

            // WHY: Faction decks are omitted from buildCity, so a sunk Dame never sees DuelEnd.
            // Used is cleared in memory before the sink, and only while a host exists.
            // Activate records the Dame as ownerId even though the copy lives on the actor.
            // The sink player is OwnerId; unequip uses ControllerId.
            'copying a technique sinks Dame on the bottom of the faction deck' => function () {
                $world = new TestWorld();
                [$dame, $technique, $actor] = $this->scene($world);
                $dame->OwnerId = 1;
                $dame->ControllerId = 2;
                $technique->Used = true;
                $source = $actor->getTechniques()[0];

                $technique->actFromTechniqueWithIds(
                    $world->game,
                    States::DUEL_CHOOSE_TECHNIQUE_02055,
                    'duelChooseTechnique_02055',
                    [$source->Id]
                );

                $copyId = $actor->Id . '_copy_' . $source->ClassId;
                $copy = $actor->getTechniqueById($copyId);
                Assert::true($copy !== null, 'copied onto the actor');
                Assert::true($copy->IsTemporaryCopy, 'temporary');
                Assert::same($actor->Id, $copy->OwnerId, 'copy owner');
                Assert::false($technique->Used, 'cleared before sink');
                Assert::same($copyId, $world->game->globals->get(Game::CHOSEN_TECHNIQUE), 'chosen');
                Assert::false($world->game->globals->get(Game::CHOSEN_TECHNIQUE_IS_MAIN), 'not main');
                Assert::same($copyId, $world->game->globals->get(Game::TRANSITION_INTERNAL_ID), 'internal');

                $activated = $world->theah->queuedOfType(EventTechniqueActivated::class);
                $resolve = $world->theah->queuedOfType(EventResolveTechnique::class);
                $values = $world->theah->queuedOfType(EventDuelCalculateTechniqueValues::class);
                Assert::true($activated[0]->copied, 'marked copied');
                Assert::same($dame->Id, $activated[0]->ownerId, 'activated owner is Dame');
                Assert::same($copyId, $activated[0]->techniqueId, 'copy id');
                Assert::same($actor->ControllerId, $resolve[0]->playerId, 'actor controller');
                Assert::same($actor->Id, $resolve[0]->actorId, 'actor');
                Assert::same($world->theah->duelOpponent->Id, $resolve[0]->adversaryId, 'adversary');
                Assert::same($copyId, $values[0]->techniqueId, 'values use the copy');

                $unequip = $world->theah->queuedOfType(EventAttachmentUnequipped::class);
                $removed = $world->theah->queuedOfType(EventCardRemovedFromPlay::class);
                $sink = $world->theah->queuedOfType(EventCardAddedToFactionDeck::class);
                Assert::same(2, $unequip[0]->playerId, 'unequip uses controller');
                Assert::same($actor->Id, $unequip[0]->characterId, 'host');
                Assert::same($dame->Id, $unequip[0]->attachmentId, 'dame');
                Assert::same($dame->Location, $removed[0]->toLocation, 'removed from current location');
                Assert::same(1, $sink[0]->playerId, 'sink uses owner');
                Assert::false($sink[0]->onTop, 'bottom of the deck');
                Assert::same(['cardChosen'], $world->game->gamestate->transitions, 'named transition');
            },

            // WHY: The chooser filters copyable techniques. Confirm clones whatever id it is given,
            // including Dame's own technique.
            'confirm does not re-check that the chosen technique is copyable' => function () {
                $world = new TestWorld();
                [$dame, $technique, $actor] = $this->scene($world);
                $technique->actFromTechniqueWithIds(
                    $world->game,
                    States::DUEL_CHOOSE_TECHNIQUE_02055,
                    'duelChooseTechnique_02055',
                    [$technique->Id]
                );
                $copy = $actor->getTechniqueById($actor->Id . '_copy_Technique_02055');
                Assert::true($copy !== null, 'Dame copied herself');
                Assert::same($dame->Id, $world->theah->queuedOfType(EventCardAddedToFactionDeck::class)[0]->cardId, 'still sinks');
            },

            'an unattached Dame still copies, and does not clear Used or sink' => function () {
                $world = new TestWorld();
                [, $technique, $actor] = $this->scene($world, false);
                $technique->Used = true;
                $source = $actor->getTechniques()[0];
                $technique->actFromTechniqueWithIds(
                    $world->game,
                    States::DUEL_CHOOSE_TECHNIQUE_02055,
                    'duelChooseTechnique_02055',
                    [$source->Id]
                );
                Assert::true($technique->Used, 'Used stays');
                Assert::true($actor->getTechniqueById($actor->Id . '_copy_' . $source->ClassId) !== null, 'copied');
                Assert::count(0, $world->theah->queuedOfType(EventCardAddedToFactionDeck::class), 'no sink');
                Assert::same(['cardChosen'], $world->game->gamestate->transitions, 'still leaves');
            },

            // WHY: isAvailable() is checked before isAvailableToPlayer, so a Used Dame that
            // was sunk and redrawn would never heal there. Re-equip clears Used directly.
            'equipping a used Dame clears Used' => function () {
                $world = new TestWorld();
                [$dame, $technique] = $this->scene($world);
                $technique->Used = true;
                $dame->IsUpdated = false;
                $event = new EventAttachmentEquipped();
                $event->attachmentId = $dame->Id;
                $event->theah = $world->theah;
                $technique->handleEvent($event);
                Assert::false($technique->Used, 'healed');
                Assert::true($dame->IsUpdated, 'persisted');

                $technique->Used = true;
                $dame->IsUpdated = false;
                $event->attachmentId = 999;
                $technique->handleEvent($event);
                Assert::true($technique->Used, 'other attachment');
                Assert::false($dame->IsUpdated, 'untouched');
            },

            'resolve opens the copy chooser for the controller' => function () {
                $world = new TestWorld();
                [$dame, $technique] = $this->scene($world);
                $event = new EventResolveTechnique();
                $event->techniqueId = $technique->Id;
                $event->theah = $world->theah;
                $technique->handleEvent($event);
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::same('02055', $transitions[0]->transition, 'name');
                Assert::same($dame->ControllerId, $transitions[0]->playerId, 'controller');
                Assert::same($dame->Id, $transitions[0]->sourceId, 'source');
                Assert::same($technique->Id, $transitions[0]->internalId, 'technique');
            },
        ];
    }
}
