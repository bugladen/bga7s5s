<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04006;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterFinesseModifed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterHealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelStarted;

class Card_04006_Test extends TestCase
{
    public function name(): string
    {
        return "_04006 Assassin's Garb";
    }

    public function tests(): array
    {
        return [
            'is attire with parry 1 and thrust 4' => function () {
                $card = new _04006();
                Assert::instanceOf(FactionAttachment::class, $card, 'FactionAttachment');
                Assert::same(1, $card->Parry, 'parry');
                Assert::same(4, $card->Thrust, 'thrust');
                Assert::true($card->DashedRiposte, 'dashed riposte');
                Assert::same(0, $card->FinesseModifier, 'no printed finesse');
                Assert::true($card->hasTrait('Stealth'), 'Stealth');
            },

            'equips only to a duelist, spy, or assassin' => function () {
                $world = new TestWorld();
                $garb = $world->placeCard(new _04006(), Game::LOCATION_HAND, 1);
                $hero = $world->placeCharacter(new GenericCharacter('Hero'), Game::LOCATION_CITY_DOCKS, 1);
                Assert::false($garb->canAttachTo($hero), 'plain hero');
                $equipping = new EventAttachmentEquipping();
                $equipping->attachmentId = $garb->Id;
                $equipping->characterId = $hero->Id;
                $equipping->theah = $world->theah;
                $threw = false;
                try {
                    $garb->eventCheck($equipping);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'rejected');

                $duelist = $world->placeCharacter(new GenericCharacter('Duelist', ['Duelist']), Game::LOCATION_CITY_DOCKS, 1);
                Assert::true($garb->canAttachTo($duelist), 'duelist');
            },

            // WHY: The +1 Finesse is duel-scoped and only while the adversary is wounded.
            // Unequip uses the event character, because AttachedToId is already cleared.
            // A heal that has not updated Wounds yet drops the bonus.
            'finesse lasts only while the adversary is wounded in this duel' => function () {
                $world = new TestWorld();
                $garb = $world->placeCard(new _04006(), Game::LOCATION_CITY_DOCKS, 1);
                $host = $world->placeCharacter(new GenericCharacter('Duelist', ['Duelist']), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $garb->AttachedToId = $host->Id;
                $foe->Wounds = 1;
                $world->game->globals->set(Game::IN_DUEL, true);
                $world->theah->duelActor = $host;
                $world->theah->duelOpponent = $foe;

                $started = new EventDuelStarted();
                $started->theah = $world->theah;
                $garb->handleEvent($started);
                Assert::true($garb->AdversaryWoundedFinesseBonusApplied, 'applied');
                Assert::same(2, $world->theah->queuedOfType(EventCharacterFinesseModifed::class)[0]->NewFinesse, 'plus one');

                $explanations = [];
                Assert::same(1, $garb->getNumberOfGambleCardsToReveal($world->theah, $host, $explanations), 'extra reveal');

                $heal = new EventCharacterHealed();
                $heal->characterId = $foe->Id;
                $heal->wounds = 1;
                $heal->characterHandled = false;
                $heal->theah = $world->theah;
                $garb->handleEvent($heal);
                Assert::false($garb->AdversaryWoundedFinesseBonusApplied, 'heal cleared it');

                $garb->AdversaryWoundedFinesseBonusApplied = true;
                $garb->AttachedToId = 0;
                $world->theah->takeQueuedEvents();
                $unequip = new EventAttachmentUnequipped();
                $unequip->attachmentId = $garb->Id;
                $unequip->characterId = $host->Id;
                $unequip->theah = $world->theah;
                $garb->handleEvent($unequip);
                Assert::false($garb->AdversaryWoundedFinesseBonusApplied, 'unequip');
                Assert::same($host->Id, $world->theah->queuedOfType(EventCharacterFinesseModifed::class)[0]->CharacterId, 'host from the event');

                $ended = new EventDuelEnd();
                $ended->theah = $world->theah;
                $garb->AdversaryWoundedFinesseBonusApplied = true;
                $garb->handleEvent($ended);
                Assert::false($garb->AdversaryWoundedFinesseBonusApplied, 'duel end');
            },
        ];
    }
}
