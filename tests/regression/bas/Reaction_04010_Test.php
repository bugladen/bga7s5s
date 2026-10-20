<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04006;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04010;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04010;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelGambleCardsRevealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_04010_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_04010';
    }

    public function tests(): array
    {
        return [
            // WHY: The reaction is on the revealed deck card. A stuck Used flag is cleared
            // because faction decks miss dusk. Controller 0 falls back to OwnerId.
            // Use adds Influence to the reveal count and stamps the parry controller.
            // Pass does not spend it.
            'a revealed unravel offers a sorcerer, then reveals more and marks the round' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _04010(), 'Deck-1', 0);
                $risk->OwnerId = 1;
                $actor = $world->placeCharacter(new GenericCharacter('Sorcerer', ['Sorcerer']), Game::LOCATION_CITY_DOCKS, 1);
                $actor->ModifiedInfluence = 1;
                $world->theah->duelActor = $actor;
                /** @var Reaction_04010 $reaction */
                $reaction = $risk->getReactions()[0];
                $reaction->Used = true;
                $extra = $world->placeCard(new _04006(), 'Deck-1', 1);
                $world->game->topFactionCards = [
                    ['id' => $risk->Id],
                    ['id' => $world->placeCard(new _04006(), 'Deck-1', 1)->Id],
                    ['id' => $extra->Id],
                ];
                $world->game->globals->set(Game::GAMBLE_REVEAL_COUNT, 2);

                $revealed = new EventDuelGambleCardsRevealed();
                $revealed->actorId = $actor->Id;
                $revealed->revealedCardIds = [$risk->Id];
                $revealed->theah = $world->theah;
                $reaction->handleEvent($revealed);
                Assert::false($reaction->Used, 'stuck used was cleared');
                Assert::same('04010', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'show the cards first');

                $reaction->actFromReactionWithId($world->game, States::DUEL_GAMBLE_REVEALED_04010, '', 1);
                Assert::same(3, $world->game->globals->get(Game::GAMBLE_REVEAL_COUNT), 'influence added');
                Assert::same(1, $world->game->globals->get(Game::UNRAVEL_THE_THREAD_CONTROLLER_ID), 'owner via OwnerId');
                Assert::true($reaction->Used, 'used');
                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityPlayed::class), 'played');
                Assert::same('use', $world->game->gamestate->transitions[0], 'back through revealed');

                $actor->ModifiedTraits = ['Hero'];
                $reaction->Used = false;
                $world->theah->takeQueuedEvents();
                $reaction->handleEvent($revealed);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'not a sorcerer');

                $actor->ModifiedTraits = ['Sorcerer'];
                $reaction->actFromReactionPass($world->game, States::DUEL_GAMBLE_REVEALED_04010);
                Assert::false($reaction->Used, 'pass');
                Assert::same('pass', $world->game->gamestate->transitions[1], 'pass transition');
            },
        ];
    }
}
