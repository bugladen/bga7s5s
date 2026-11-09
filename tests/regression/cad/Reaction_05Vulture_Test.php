<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04017;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04027;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Vulture;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions\Reaction_05Vulture;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlayerDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_05Vulture_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_05Vulture';
    }

    /**
     * Destroy pipeline mid-flight: unequip stash while IsDying, attachment already in discard,
     * then CharacterDestroyed at Vulture's location.
     *
     * @return array{world: TestWorld, vulture: _05Vulture, reaction: Reaction_05Vulture, ally: GenericCharacter, foe: GenericCharacter, bow: _04017}
     */
    private function sceneReadyToSalvage(int $wealthCost = 0): array
    {
        $world = new TestWorld();
        $vulture = $world->placeCharacter(new _05Vulture(), Game::LOCATION_CITY_DOCKS, 1);
        // WHY: Equip hosts are same-location only — ally must share Gusev's site.
        $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $bow = $world->placeCard(new _04017(), Game::LOCATION_CITY_DOCKS, 2);
        $bow->WealthCost = $wealthCost;
        $bow->AttachedToId = $foe->Id;
        $foe->Attachments = [$bow->Id];
        /** @var Reaction_05Vulture $reaction */
        $reaction = $vulture->getReactions()[0];

        $foe->IsDying = true;
        $unequip = new EventAttachmentUnequipped();
        $unequip->playerId = 2;
        $unequip->characterId = $foe->Id;
        $unequip->attachmentId = $bow->Id;
        $world->fireOn($vulture, $unequip);

        $bow->AttachedToId = 0;
        $bow->Location = $world->game->getPlayerDiscardDeckName(2);
        $foe->Attachments = [];

        return compact('world', 'vulture', 'reaction', 'ally', 'foe', 'bow');
    }

    private function destroy(TestWorld $world, _05Vulture $vulture, GenericCharacter $foe): void
    {
        $destroyed = new EventCharacterDestroyed();
        $destroyed->characterId = $foe->Id;
        $destroyed->playerId = 2;
        $world->fireOn($vulture, $destroyed);
    }

    public function tests(): array
    {
        return [
            // WHY: Attachments leave before Destroy — stash only when host IsDying at this location.
            'offers after a character at this location is destroyed with a salvageable attachment' => function () {
                ['world' => $world, 'vulture' => $vulture, 'foe' => $foe] = $this->sceneReadyToSalvage(0);
                $this->destroy($world, $vulture, $foe);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered');
                Assert::same($vulture->Id, $transitions[0]->sourceId, 'Vulture');
            },

            'does not offer when the destroyed character is elsewhere or has no stash' => function () {
                $world = new TestWorld();
                $vulture = $world->placeCharacter(new _05Vulture(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_FORUM, 2);
                /** @var Reaction_05Vulture $reaction */
                $reaction = $vulture->getReactions()[0];

                $destroyed = new EventCharacterDestroyed();
                $destroyed->characterId = $foe->Id;
                $world->fireOn($vulture, $destroyed);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'different location / no stash');
            },

            'does not stash unequips unless the host is dying' => function () {
                $world = new TestWorld();
                $vulture = $world->placeCharacter(new _05Vulture(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $bow = $world->placeCard(new _04017(), Game::LOCATION_CITY_DOCKS, 2);
                $bow->AttachedToId = $foe->Id;
                $foe->Attachments = [$bow->Id];

                $unequip = new EventAttachmentUnequipped();
                $unequip->characterId = $foe->Id;
                $unequip->attachmentId = $bow->Id;
                $world->fireOn($vulture, $unequip);

                $bow->Location = $world->game->getPlayerDiscardDeckName(2);
                $destroyed = new EventCharacterDestroyed();
                $destroyed->characterId = $foe->Id;
                $world->fireOn($vulture, $destroyed);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'non-dying unequip ignored');
            },

            // WHY: Click-to-pay inside the Reaction (Tomas) — never PAY_STATE_EQUIP_ATTACHMENT.
            // Take control + remove from discard + equip (Teens), 0-cost skips pay stage.
            'takes control and equips a zero-cost attachment to a chosen character' => function () {
                ['world' => $world, 'vulture' => $vulture, 'reaction' => $reaction, 'ally' => $ally, 'foe' => $foe, 'bow' => $bow] = $this->sceneReadyToSalvage(0);
                $this->destroy($world, $vulture, $foe);
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'equip_' . $ally->Id);

                Assert::same(1, $bow->ControllerId, 'control taken');
                Assert::same($bow->Id, $world->theah->queuedOfType(EventCardRemovedFromPlayerDiscardPile::class)[0]->cardId, 'out of discard');
                Assert::same($ally->Id, $world->theah->queuedOfType(EventAttachmentEquipping::class)[0]->characterId, 'equip target');
                Assert::true($reaction->Used, 'spent');
            },

            'pays wealth then equips when the attachment costs Wealth' => function () {
                ['world' => $world, 'vulture' => $vulture, 'reaction' => $reaction, 'ally' => $ally, 'foe' => $foe, 'bow' => $bow] = $this->sceneReadyToSalvage(1);
                $pay = $world->placeCard(new _04027(), Game::LOCATION_HAND, 1);
                $this->destroy($world, $vulture, $foe);
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'equip_' . $ally->Id);
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'requeued into pay');
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pay-' . $pay->Id);
                Assert::same($pay->Id, $world->theah->queuedOfType(EventCardDiscardedFromHand::class)[0]->cardId, 'paid');
                Assert::same($ally->Id, $world->theah->queuedOfType(EventAttachmentEquipping::class)[0]->characterId, 'equipped');
                Assert::true($reaction->Used, 'spent');
            },

            'pass does not spend the reaction or take the attachment' => function () {
                ['world' => $world, 'vulture' => $vulture, 'reaction' => $reaction, 'foe' => $foe, 'bow' => $bow] = $this->sceneReadyToSalvage(0);
                $this->destroy($world, $vulture, $foe);
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');
                Assert::false($reaction->Used, 'pass does not spend');
                Assert::same(2, $bow->ControllerId, 'control unchanged');
                Assert::count(0, $world->theah->queuedOfType(EventAttachmentEquipping::class), 'no equip');
            },

            'rejects equipping to a controlled character at a different location' => function () {
                ['world' => $world, 'vulture' => $vulture, 'reaction' => $reaction, 'foe' => $foe] = $this->sceneReadyToSalvage(0);
                $elsewhere = $world->placeCharacter(new GenericCharacter('Elsewhere'), Game::LOCATION_CITY_FORUM, 1);
                $this->destroy($world, $vulture, $foe);
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'equip_' . $elsewhere->Id);
                Assert::count(0, $world->theah->queuedOfType(EventAttachmentEquipping::class), 'elsewhere rejected');
                Assert::false($reaction->Used, 'not spent on illegal pick');
            },
        ];
    }
}
