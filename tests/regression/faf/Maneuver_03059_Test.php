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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03056;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03058;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03059;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Maneuver_03059_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_03059';
    }

    public function tests(): array
    {
        return [
            // WHY: Needs at least one card in the adversary deck or discard. The revealed
            // card's printed Parry or Thrust is the bonus. Non-Academics replace; one leftover
            // goes on top. Cancel clears the bonus before calculate.
            'reveals one card and adds its printed thrust' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _03059(), Game::LOCATION_HAND, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $maneuver = $risk->getManeuvers()[0];
                $world->theah->duelActor = $actor;
                $world->theah->duelOpponent = $foe;

                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'empty deck');
                $world->game->deckLocationCounts['Deck-2'] = 2;
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'deck has cards');

                $peeked = $world->placeCard(new _03058(), 'Deck-2', 2);
                $other = $world->placeCard(new _03056(), 'Deck-2', 2);
                $world->game->topFactionCards = [['id' => $peeked->Id], ['id' => $other->Id]];

                $activated = new EventManeuverActivated();
                $activated->theah = $world->theah;
                $activated->maneuverId = $maneuver->Id;
                $maneuver->handleEvent($activated);
                Assert::same('03059', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'look');

                $threw = false;
                try {
                    $maneuver->actFromManeuverWithId($world->game, States::DUEL_RESOLVE_MANEUVER_03059, 'x', 999);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'not looked at');

                $maneuver->actFromManeuverWithId($world->game, States::DUEL_RESOLVE_MANEUVER_03059, 'x', $peeked->Id);
                Assert::same('03059_2', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'stat');

                $threw = false;
                try {
                    $maneuver->actFromManeuverWithId($world->game, States::DUEL_RESOLVE_MANEUVER_03059_2, 'x', 9);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'bad stat');

                $maneuver->actFromManeuverWithId($world->game, States::DUEL_RESOLVE_MANEUVER_03059_2, 'x', 2);
                Assert::same($other->Id, $world->game->deckInserts[0]['id'], 'replaced');
                Assert::true($world->game->deckInserts[0]['onTop'], 'on top');

                $calc = new EventDuelCalculateManeuverValues();
                $calc->theah = $world->theah;
                $calc->maneuverId = $maneuver->Id;
                $maneuver->handleEvent($calc);
                Assert::same(2, $calc->thrust, 'printed thrust');
                Assert::same(0, $calc->parry, 'not parry');

                $cancel = new EventManeuverCanceled();
                $cancel->theah = $world->theah;
                $cancel->maneuverId = $maneuver->Id;
                $maneuver->handleEvent($cancel);
                $calc->thrust = 0;
                $calc->explanations = [];
                $maneuver->handleEvent($calc);
                Assert::same(0, $calc->thrust, 'cleared');
            },

            'an academic may sink the unchosen cards; anyone else may not' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _03059(), Game::LOCATION_HAND, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $maneuver = $risk->getManeuvers()[0];
                $world->theah->duelActor = $actor;
                $world->theah->duelOpponent = $foe;
                $first = $world->placeCard(new _03058(), 'Deck-2', 2);
                $second = $world->placeCard(new _03056(), 'Deck-2', 2);
                $third = $world->placeCard(new _03059(), 'Deck-2', 2);
                $fourth = $world->placeCard(new _03058(), 'Deck-2', 2);
                $world->game->globals->set(Game::CHOSEN_CARD, json_encode([
                    $first->getPropertyArray($world->game),
                    $second->getPropertyArray($world->game),
                    $third->getPropertyArray($world->game),
                    $fourth->getPropertyArray($world->game),
                ]));
                $reveal = new \ReflectionProperty($maneuver, 'RevealedCardId');
                $reveal->setAccessible(true);
                $reveal->setValue($maneuver, $first->Id);
                $adversary = new \ReflectionProperty($maneuver, 'AdversaryPlayerId');
                $adversary->setAccessible(true);
                $adversary->setValue($maneuver, 2);

                $threw = false;
                try {
                    $maneuver->actFromManeuverWithIds($world->game, States::DUEL_RESOLVE_MANEUVER_03059_3, 'x', [$second->Id]);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'not academic');

                $actor->ModifiedTraits = ['Academic', 'Hero'];
                $maneuver->actFromManeuverWithIds($world->game, States::DUEL_RESOLVE_MANEUVER_03059_3, 'x', [$second->Id]);
                Assert::same($second->Id, $world->game->deckInserts[0]['id'], 'sunk');
                Assert::false($world->game->deckInserts[0]['onTop'], 'bottom');
                Assert::same('03059_4', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'reorder the last');
            },
        ];
    }
}
