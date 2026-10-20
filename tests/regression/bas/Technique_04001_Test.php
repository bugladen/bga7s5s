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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04001;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04006;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\techniques\Technique_04001;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Technique_04001_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_04001';
    }

    /**
     * @return array{0:TestWorld,1:_04001,2:Technique_04001}
     */
    private function ready(bool $withDeck = true): array
    {
        $world = new TestWorld();
        $benci = $world->placeCharacter(new _04001(), Game::LOCATION_CITY_DOCKS, 1);
        /** @var Technique_04001 $technique */
        $technique = $benci->getTechniques()[0];
        if ($withDeck) {
            $top = $world->placeCard(new _04006(), 'Deck-1', 1);
            $second = $world->placeCard(new _04006(), 'Deck-1', 1);
            $world->game->topFactionCards = [['id' => $top->Id], ['id' => $second->Id]];
        }
        return [$world, $benci, $technique];
    }

    public function tests(): array
    {
        return [
            // WHY: Challenge activation has no duel round. Actor==owner is only required
            // inside a duel. An empty deck is hidden rather than a useless look.
            'offered outside a duel when the deck has cards, and only to the actor inside one' => function () {
                [$world, $benci, $technique] = $this->ready();
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'challenge');

                $world->game->globals->set(Game::IN_DUEL, true);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'no actor');
                $world->theah->duelActor = $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_CITY_DOCKS, 1);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'other actor');
                $world->theah->duelActor = $benci;
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'he is acting');

                $world->game->topFactionCards = [];
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'empty deck');
            },

            // WHY: Resolve before Accept must not peek. In-duel Resolve is the effect.
            // Challenge look waits for GENERATE_THREAT with CHALLENGE_ACCEPTED, and a
            // preview dry-run must not queue the UI.
            'in-duel resolve looks, and a refused or previewed challenge does not' => function () {
                [$world, $benci, $technique] = $this->ready();
                $resolve = new EventResolveTechnique();
                $resolve->techniqueId = $technique->Id;
                $resolve->inDuel = false;
                $resolve->theah = $world->theah;
                $technique->handleEvent($resolve);
                Assert::true($technique->Used, 'spent on resolve');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no look yet');

                $world->theah->takeQueuedEvents();
                $preview = new EventGenerateChallengeThreat();
                $preview->techniqueId = $technique->Id;
                $preview->preview = true;
                $world->game->globals->set(Game::CHALLENGE_ACCEPTED, true);
                $preview->theah = $world->theah;
                $technique->handleEvent($preview);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'preview');

                $refused = new EventGenerateChallengeThreat();
                $refused->techniqueId = $technique->Id;
                $refused->preview = false;
                $world->game->globals->set(Game::CHALLENGE_ACCEPTED, false);
                $refused->theah = $world->theah;
                $technique->handleEvent($refused);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'refused');

                $accepted = new EventGenerateChallengeThreat();
                $accepted->techniqueId = $technique->Id;
                $world->game->globals->set(Game::CHALLENGE_ACCEPTED, true);
                $accepted->theah = $world->theah;
                $technique->handleEvent($accepted);
                $look = $world->theah->queuedOfType(EventTransition::class)[0];
                Assert::same('04001', $look->transition, 'challenge look');
                Assert::same(Event::HIGHEST_PRIORITY, $look->priority, 'before other resolve effects');
                Assert::same($benci->Id, $look->sourceId, 'benci');
            },

            // WHY: Pass sinks none. 0 left is done, 1 left is inserted on top, 2+ reorder.
            // A sink is an immediate bottom insert. A short reorder throws before inserts.
            'pass, sink, and reorder follow the leftover count' => function () {
                [$world, , $technique] = $this->ready();
                $topId = $world->game->topFactionCards[0]['id'];
                $secondId = $world->game->topFactionCards[1]['id'];
                $cards = json_decode($world->game->globals->get(Game::CHOSEN_CARD, '[]'));
                $world->game->globals->set(Game::CHOSEN_CARD, json_encode([
                    $world->game->getCardObjectFromDb($topId)->getPropertyArray($world->game),
                    $world->game->getCardObjectFromDb($secondId)->getPropertyArray($world->game),
                ]));

                $technique->actFromTechniquePass($world->game, States::DUEL_CHOOSE_TECHNIQUE_04001);
                Assert::same('reorder', $world->game->gamestate->transitions[0], 'two remain');
                Assert::count(0, $world->game->deckInserts, 'pass inserts nothing');

                $technique->actFromTechniqueWithIds($world->game, States::DUEL_CHOOSE_TECHNIQUE_04001, '', [$topId]);
                Assert::same($topId, $world->game->deckInserts[0]['id'], 'sunk');
                Assert::false($world->game->deckInserts[0]['onTop'], 'bottom');
                Assert::same($secondId, $world->game->deckInserts[1]['id'], 'leftover replaced');
                Assert::true($world->game->deckInserts[1]['onTop'], 'on top');
                Assert::same('done', $world->game->gamestate->transitions[1], 'one left is done');

                $world->game->deckInserts = [];
                $world->game->globals->set(Game::CHOSEN_CARD, json_encode([
                    $world->game->getCardObjectFromDb($topId)->getPropertyArray($world->game),
                    $world->game->getCardObjectFromDb($secondId)->getPropertyArray($world->game),
                ]));
                $threw = false;
                try {
                    $technique->actFromTechniqueWithIds(
                        $world->game,
                        States::DUEL_CHOOSE_TECHNIQUE_04001_2,
                        '',
                        [$topId]
                    );
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'must order every remaining card');
                Assert::count(0, $world->game->deckInserts, 'no insert after a short list');
                Assert::count(0, $cards ?? [], 'decoded placeholder');
            },
        ];
    }
}
