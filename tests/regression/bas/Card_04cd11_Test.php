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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityEventCard;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04cd11;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingHealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskPhaseBegin;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_04cd11_Test extends TestCase
{
    public function name(): string
    {
        return '_04cd11 Let Bygones Be Bygones';
    }

    public function tests(): array
    {
        return [
            'is a camaraderie revelry city event' => function () {
                $card = new _04cd11();
                Assert::instanceOf(CityEventCard::class, $card, 'CityEventCard');
                Assert::same(11, $card->CityCardNumber, 'city number');
                Assert::true($card->hasTrait('Camaraderie'), 'Camaraderie');
                Assert::true($card->hasTrait('Revelry'), 'Revelry');
            },

            // WHY: Turn order is getCollectionFromDB player rows. Only players with a
            // character at this location are prompted. The card must be in the city.
            'dusk prompts each player who has a character here' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _04cd11(), Game::LOCATION_CITY_FORUM, 0);
                $mine = $world->placeCharacter(new GenericCharacter('Mine'), Game::LOCATION_CITY_FORUM, 1);
                $world->placeCharacter(new GenericCharacter('Away'), Game::LOCATION_CITY_DOCKS, 2);
                $theirs = $world->placeCharacter(new GenericCharacter('Theirs'), Game::LOCATION_CITY_FORUM, 2);

                $dusk = new EventDuskPhaseBegin();
                $world->fireOn($card, $dusk);
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(2, $transitions, 'both players');
                Assert::same(1, $transitions[0]->playerId, 'turn order');
                Assert::same(2, $transitions[1]->playerId, 'second');
                Assert::same('04cd11', $transitions[0]->transition, 'state');
                Assert::same($card->Id, $transitions[0]->sourceId, 'card');

                $args = $card->argsFromCard($world->game, States::DUSK_PHASE_BEGIN_04CD11, '', '');
                Assert::same([$mine->Id], $args['ids'], 'active player characters');
                $world->game->activePlayerId = 2;
                $args = $card->argsFromCard($world->game, States::DUSK_PHASE_BEGIN_04CD11, '', '');
                Assert::same([$theirs->Id], $args['ids'], 'the other player');
            },

            'dusk with nobody here, or the card outside the city, does not prompt' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _04cd11(), Game::LOCATION_CITY_FORUM, 0);
                $dusk = new EventDuskPhaseBegin();
                $world->fireOn($card, $dusk);
                Assert::count(0, $world->theah->queuedEvents, 'empty location');

                $world->placeCharacter(new GenericCharacter('Mine'), Game::LOCATION_CITY_FORUM, 1);
                $card->Location = Game::LOCATION_CITY_DISCARD;
                $world->fireOn($card, $dusk);
                Assert::count(0, $world->theah->queuedEvents, 'discarded');
            },

            'the choice must be one of your characters here, then they heal and stay' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _04cd11(), Game::LOCATION_CITY_FORUM, 0);
                $mine = $world->placeCharacter(new GenericCharacter('Mine'), Game::LOCATION_CITY_FORUM, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_FORUM, 2);

                $threw = false;
                try {
                    $card->actFromCardWithId($world->game, States::DUSK_PHASE_BEGIN_04CD11, '', '', $foe->Id);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'not yours');
                Assert::false($foe->hasCondition(Game::LET_BYGONES_BE_BYGONES), 'no stamp');

                $card->actFromCardWithId($world->game, States::DUSK_PHASE_BEGIN_04CD11, '', '', $mine->Id);
                Assert::true($mine->hasCondition(Game::LET_BYGONES_BE_BYGONES), 'stamped');
                $heal = $world->theah->queuedOfType(EventCharacterBeingHealed::class)[0];
                Assert::same($mine->Id, $heal->characterId, 'healed');
                Assert::same(1, $heal->wounds, 'one');
                Assert::same((string) $card->Id, $heal->abilityId, 'this card');
                Assert::same(null, $world->game->gamestate->transitions[0], 'bare nextState');
            },

            // WHY: The stay-home check is on EventCardMoved. The condition is removed
            // before the throw, so a second attempt is not blocked by this card.
            'a stamped character cannot move home, and the stamp is spent by the attempt' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _04cd11(), Game::LOCATION_CITY_FORUM, 0);
                $mine = $world->placeCharacter(new GenericCharacter('Mine'), Game::LOCATION_CITY_FORUM, 1);
                $mine->addCondition(Game::LET_BYGONES_BE_BYGONES);

                $home = new EventCardMoved();
                $home->cardId = $mine->Id;
                $home->toLocation = Game::LOCATION_PLAYER_HOME;
                $home->theah = $world->theah;
                $threw = false;
                try {
                    $card->eventCheck($home);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'blocked');
                Assert::false($mine->hasCondition(Game::LET_BYGONES_BE_BYGONES), 'spent');

                $threw = false;
                try {
                    $card->eventCheck($home);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::false($threw, 'second move is not this card');

                $mine->addCondition(Game::LET_BYGONES_BE_BYGONES);
                $elsewhere = new EventCardMoved();
                $elsewhere->cardId = $mine->Id;
                $elsewhere->toLocation = Game::LOCATION_CITY_DOCKS;
                $elsewhere->theah = $world->theah;
                $card->eventCheck($elsewhere);
                Assert::true($mine->hasCondition(Game::LET_BYGONES_BE_BYGONES), 'other destinations keep it');
            },
        ];
    }
}
