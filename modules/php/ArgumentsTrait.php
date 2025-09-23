<?php

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * SeventhSeaCityOfFiveSails implementation : © Edward Mittelstedt bugbucket@comcast.net
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 */

 namespace Bga\Games\SeventhSeaCityOfFiveSails;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CardAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;

trait ArgumentsTrait
{
    /**
     * Game state arguments, example content.
     *
     * This method returns some additional information that is very specific to the `playerTurn` game state.
     *
     * @return array
     * @see ./states.inc.php
     */

    public function argsEmpty(): array
    {
        return [];
    }    

    public function argAvailableDecks(): array
    {
        require('includes/starterdecks.inc.php');
        
        $starter_decks = json_decode($this->starter_decks);        
        $decks = array_map(function($deck) { 
            return [ 
                "id" => $deck->id,
                "name" => $deck->name
            ]; 
        }, $starter_decks->decks);

        return ["availableDecks" => $decks];
    }

    public function argsPlanningPhaseResolveSchemes_01016_2(): array
    {
        //Return all the Red Hand Thug cards in player's deck
        if ($this->getCurrentPlayerId() == $this->getActivePlayerId()) 
        {
            $playerId = $this->getActivePlayerId();
            $location = $this->getPlayerFactionDeckName($playerId);
            $deck = $this->cards->getCardsInLocation($location);
            $thugs = [];
            foreach ($deck as $deckCard) {
                $card = $this->getCardObjectFromDb($deckCard['id']);
                if (in_array("Red Hand", $card->Traits) && in_array("Thug", $card->Traits)) 
                {
                    $thugs[] = $card->getPropertyArray($this);
                }
            }            

            return [
                "_private" => [
                    "active" => [
                        "thugs" => $thugs
                    ]
                ]
            ];
        }

        return [];
    }

    public function argsPlanningPhaseResolveSchemes_01016_3(): array
    {
        $id = $this->globals->get(GAME::CHOSEN_CARD);
        $card = $this->getCardObjectFromDb($id);
        return [
            "card" => $card->getPropertyArray($this)
        ];
    }

    public function argsPlanningPhaseResolveSchemes_01125_3(): array
    {
        return [
            "location" => $this->globals->get(GAME::CHOSEN_LOCATION)
        ];
    }

    public function argsPlanningPhaseResolveSchemes_01144_2(): array
    {
        return [
            "location" => $this->globals->get(GAME::CHOSEN_LOCATION)
        ];
    }

    public function argsPlanningPhaseEnd_01098_2(): array
    {
        $id = $this->globals->get(GAME::CATS_EMBARGO);
        $card = $this->getCardObjectFromDb($id);
        return [
            "card" => $card->getPropertyArray($this)
        ];
    }

    public function argsHighDramaBeginning_01144(): array{
        return [
            "discount" => $this->globals->get(GAME::DISCOUNT)
        ];
    }

    public function argsPlanningPhaseResolveSchemes_01152_3(): array
    {
        return [
            "location" => $this->globals->get(GAME::CHOSEN_LOCATION)
        ];
    }

    public function argPlayerTurn(): array
    {
        $this->theah->buildCity();
        $playerId = (int)$this->getActivePlayerId();

        return [
            '_private' => [
                'active' => [
                    "canChallenge" => $this->theah->playerCanBasicChallenge($playerId),
                    "canClaim" => $this->theah->playerCanClaim($playerId),
                    "canEquip" => $this->theah->playerCanEquip($playerId),
                    "canMove" => $this->theah->playerCanMove($playerId),
                    "canRecruit" => $this->theah->playerCanRecruit($playerId),
                    "hasInPlayActions" => $this->theah->playerHasInPlayActions($playerId),
                    "hasInHandActions" => $this->theah->playerHasInHandActions($playerId),
                ]
            ]
        ];
    }

    public function argsHighDramaMoveActionChoosePerformer(): array
    {
        $playerId = (int)$this->getActivePlayerId();
        $this->theah->buildCity();

        $characters = $this->theah->getCharactersInPlayByPlayerId($playerId);
        
        //Filter out those characters that are engaged
        $characters = array_values(array_filter($characters, function($character) { return $character->Engaged == false; }));

        //Select only the Ids of the characters
        $characterIds = array_map(function($character) { return $character->Id; }, $characters);

        return [
            "ids" => $characterIds
        ];
    }

    public function argsHighDramaMoveActionChooseLocation(): array
    {
        $playerId = (int)$this->getActivePlayerId();
        $this->theah->buildCity();

        $performerId = $this->globals->get(GAME::CHOSEN_CARD);
        $performer = $this->theah->getCharacterById($performerId);
        $currentLocation = $performer->Location;

        $locations = $this->theah->getAdjacentCityLocations($currentLocation);

        return [
            "performerId" => $performerId,
            "locations" => $locations            
        ];
    }

    public function argsHighDramaRecruitActionChoosePerformer(): array
    {
        $playerId = (int)$this->getActivePlayerId();
        $this->theah->buildCity();

        $characters = $this->theah->getCharactersInCityByPlayerId($playerId);

        $charactersThatCanReruit = [];
        foreach ($characters as $character) {
            $charactersAtLocation = $this->theah->getCharactersAtLocation($character->Location, $includeUncontrolled = true);
            $mercenariesAtLocation = array_filter($charactersAtLocation, fn($character) => ! $character->isControlled() && $character->hasTrait("Mercenary"));
            if (count($mercenariesAtLocation) > 0) {
                $charactersThatCanReruit[] = $character;
            }
        }

        return [
            "ids" => array_map(fn($character) => $character->Id, $charactersThatCanReruit)
        ];

    }

    public function argsHighDramaRecruitActionParley(): array
    {
        $performerId = $this->globals->get(GAME::CHOSEN_PERFORMER);

        return [
            "performerId" => $performerId,
        ];
    }    

    public function argsHighDramaRecruitActionChooseMercenary(): array
    {
        $performerId = $this->globals->get(GAME::CHOSEN_PERFORMER);
        $performer = $this->theah->getCharacterById($performerId);
        $discount = $this->globals->get(GAME::DISCOUNT);

        $args = [];
        $args["performerId"] = $performerId;
        $args["discount"] = $discount;

        $characters = $this->theah->getCharactersAtLocation($performer->Location, $includeUncontrolled = true);
        $characters = array_values(array_filter($characters, fn($character) => ! $character->isControlled() && $character->hasTrait("Mercenary")));
        $args["characterIds"] = array_map(fn($character) => $character->Id, $characters);

        return $args;
    }

    public function argsHighDramaRecruitActionPayForMercenary(): array
    {
        $performerId = $this->globals->get(GAME::CHOSEN_PERFORMER);
        $recruitId = $this->globals->get(GAME::CHOSEN_CARD);
        $discount = $this->globals->get(GAME::DISCOUNT);        

        return [
            "performerId" => $performerId,
            "recruitId" => $recruitId,
            "discount" => $discount,
            "recruitType" => $this->globals->get(Game::RECRUIT_TYPE)
        ];
    }

    public function argsHighDramaEquipActionChoosePerformer(): array
    {
        $playerId = (int)$this->getActivePlayerId();
        $this->theah->buildCity();

        $charactersInCity = $this->theah->getCharactersInCityByPlayerId($playerId);

        $handHasAttachments = $this->handHasAttachments($playerId);
        $charactersThatCanEquip = [];
        foreach ($charactersInCity as $character) {
            $attachmentsAtLocation = $this->theah->getAvailableAttachmentsAtLocation($character->Location);
            if (count($attachmentsAtLocation) > 0 || $handHasAttachments) {
                $charactersThatCanEquip[] = $character;
            }
        }

        $charactersAtHome = $this->theah->getCharactersAtHome($playerId);
        foreach($charactersAtHome as $character) {
            if ($handHasAttachments) {
                $charactersThatCanEquip[] = $character;
            }
        }

        //Select only the Ids of the characters
        $characterIds = array_map(function($character) { return $character->Id; }, $charactersThatCanEquip);

        return [
            "_private" => [
                "active" => [
                    "ids" => $characterIds
                ]
            ],
        ];
    }

    public function argsHighDramaEquipActionChooseAttachmentFromHand(): array
    {
        $playerId = (int)$this->getActivePlayerId();
        $performerId = $this->globals->get(GAME::CHOSEN_PERFORMER);
        $attachmentsInHand = $this->getAttachmentsInHand($playerId);

        return [
            "_private" => [
                "active" => [
                    "performerId" => $performerId,
                    "attachmentsInHand" => array_map(function($attachment) { return $attachment->Id; }, $attachmentsInHand),
                ]
            ],
        ];
    }

    public function argsHighDramaEquipActionPayForAttachmentFromHand(): array
    {
        $performerId = $this->globals->get(GAME::CHOSEN_PERFORMER);
        
        $attachmentId = $this->globals->get(GAME::CHOSEN_CARD);
        $attachment = $this->getCardObjectFromDb($attachmentId);

        return [
            "_private" => [
                "active" => [
                    "performerId" => $performerId,
                    "chosenAttachmentId" => $attachmentId,
                    "chosenAttachment" => $attachment->getPropertyArray($this),
                    "discount" => $this->globals->get(GAME::DISCOUNT)
                ]
            ],
        ];
    }

    public function argsHighDramaEquipActionChooseAttachmentFromPlay(): array
    {
        $this->theah->buildCity();
        $performerId = $this->globals->get(GAME::CHOSEN_PERFORMER);
        $performer = $this->theah->getCharacterById($performerId);

        $attachmentsInPlay = [];
        if ($performer->Location != Game::LOCATION_PLAYER_HOME) 
        {
            $attachmentsInPlay = $this->theah->getAvailableAttachmentsAtLocation($performer->Location);
        }

        return [
            "performerId" => $performerId,
            "attachmentsInPlay" => array_map(function($attachment) { return $attachment->Id; }, $attachmentsInPlay),
        ];
    }

    public function argsHighDramaEquipActionPayForAttachmentFromPlay(): array
    {
        $performerId = $this->globals->get(GAME::CHOSEN_PERFORMER);
        $attachmentId = $this->globals->get(GAME::CHOSEN_CARD);

        return [
            "_private" => [
                "active" => [
                    "performerId" => $performerId,
                    "chosenAttachmentId" => $attachmentId,
                    "discount" => $this->globals->get(GAME::DISCOUNT),
                    "equipType" => $this->globals->get(Game::EQUIP_TYPE)
                ]
            ],
        ];
    }

    public function argsHighDramaEquipActionChooseAttachmentLocation(): array
    {
        $this->theah->buildCity();
        $playerId = (int)$this->getActivePlayerId();
        $performerId = $this->globals->get(GAME::CHOSEN_PERFORMER);
        $performer = $this->theah->getCharacterById($performerId);

        $attachmentsInHand = $this->getAttachmentsInHand($playerId);
        $attachmentsInPlay = [];
        if ($performer->Location != Game::LOCATION_PLAYER_HOME) 
        {
            $attachmentsInPlay = $this->theah->getAvailableAttachmentsAtLocation($performer->Location);
        }
        
        return [
            "_private" => [
                "active" => [
                    "equipType" => $this->globals->get(Game::EQUIP_TYPE),
                    "performerId" => $performerId,
                    "attachmentsInHand" => array_map(function($attachment) { return $attachment->Id; }, $attachmentsInHand),
                    "attachmentsInPlay" => array_map(function($attachment) { return $attachment->Id; }, $attachmentsInPlay),
                ]
            ],
        ];
    }

    public function argsHighDramaClaimActionChoosePerformer(): array
    {
        $playerId = (int)$this->getActivePlayerId();
        $this->theah->buildCity();

        $characters = $this->theah->getCharactersInCityByPlayerId($playerId);
        
        //Filter out those characters that are engaged
        $characters = array_values(array_filter($characters, fn($character) => !$character->Engaged ));

        //Filter out those characters that have a dashed Influence
        $characters = array_values(array_filter($characters, fn($character) => !$character->DashedInfluence));

        //Select the Ids of the characters
        $characterIds = array_map(function($character) { return $character->Id; }, $characters);

        return [
            "ids" => $characterIds
        ];
    }

    public function argsHighDramaInPlayActionChooseAction(): array
    {
        $this->theah->buildCity();
        return [
            "_private" => [
                "active" => [
                    "actions" => $this->theah->getInPlayActionsAvailableToPlayer($this->getActivePlayerId())
                ]
            ]
        ];

    }

    public function argsHighDramaInPlayActionChoosePerformer(): array
    {
        $playerId = (int)$this->getActivePlayerId();
        $this->theah->buildCity();

        $actionId = $this->globals->get(Game::CHOSEN_ACTION);
        $action = $this->theah->getInPlayActionById($actionId);
        $owner = $action->getOwningCard($this->theah);
        
        $performers = array_values($action->getPerformersForAction($playerId, $this->theah));
        
        //Select the Ids of the performers
        $performerIds = array_map(function($performer) { return $performer->Id; }, $performers);

        return [
            "ids" => $performerIds,
            "actionCardId" => $owner?->Id
        ];
    }

    public function argsHighDramaInHandActionChooseAction(): array
    {
        $this->theah->buildCity();
        $playerId = $this->getActivePlayerId();

        return [
            "_private" => [
                "active" => [
                    "actions" => $this->theah->getInHandActionIdsAvailableToPlayer($playerId),
                    "ids" => $this->theah->getInHandActionCardIdsAvailableToPlayer($playerId),
                ]
            ]
        ];

    }

    public function argsHighDramaInHandActionChoosePerformer(): array
    {
        $playerId = (int)$this->getActivePlayerId();
        $this->theah->buildCity();

        $actionId = $this->globals->get(Game::CHOSEN_ACTION);
        $action = $this->theah->getInHandActionById($actionId);

        $owner = $action->getOwningCard($this->theah);
        $performers = array_values($action->getPerformersForAction($playerId, $this->theah));
        
        //Select the Ids of the performers
        $performerIds = array_map(fn($performer) => $performer->Id, $performers);

        return [
            "_private" => [
                "active" => [
                    "ids" => $performerIds,
                    "actionCardId" => $owner->Id
                ]
            ]
        ];
    }

    public function argsHighDramaInHandActionPay(): array
    {
        $playerId = (int)$this->getActivePlayerId();
        $this->theah->buildCity();

        $performerId = $this->globals->get(GAME::CHOSEN_PERFORMER);        
        $actionId = $this->globals->get(Game::CHOSEN_ACTION);
        $action = $this->theah->getInHandActionById($actionId);

        $owner = $action->getOwningCard($this->theah);

        return [
            "_private" => [
                "active" => [
                    "performerId" => $performerId,
                    "chosenActionId" => $actionId,
                    "choseActionCardId" => $owner->Id,
                    "requiresPerformerSelected" => $action->RequiresPerformerSelected,
                    "discount" => $this->globals->get(GAME::DISCOUNT)
                ]
            ],
        ];
    }

    public function argsHighDramaChallengeActionChoosePerformer(): array
    {
        $playerId = (int)$this->getActivePlayerId();
        $this->theah->buildCity();

        $characters = $this->theah->getCharactersInCityByPlayerId($playerId);
        
        //Filter those characters that can challenge
        $characters = array_filter($characters, fn($character) => $character->canChallenge() && ! $character->Engaged);

        $charactersThatCanChallenge = [];
        foreach ($characters as $character)
        {
            $opponents = $this->theah->getCharactersAtLocation($character->Location);
            $opponents = array_filter($opponents, fn($opponent) => $opponent->isNotControlledByPlayer($playerId));
            if (count($opponents) > 0)
            {
                $charactersThatCanChallenge[] = $character;
            }
        }
        
        //Select the Ids of the characters
        $characterIds = array_map(fn($character) => $character->Id, $charactersThatCanChallenge);

        return [
            "ids" => $characterIds
        ];
    }

    public function argsHighDramaChallengeActionChooseTarget(): array
    {
        $playerId = (int)$this->getActivePlayerId();
        $this->theah->buildCity();
        $performerId = $this->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $this->theah->getCharacterById($performerId);

        $charactersAtLocation = $this->theah->getCharactersAtLocation($performer->Location);
        //
        $charactersAtLocation = array_values(array_filter($charactersAtLocation, fn($character) => $character->ControllerId && $character->ControllerId != $playerId));
        $ids = array_map(fn($character) => $character->Id, $charactersAtLocation);

        return [
            "challengeType" => $this->globals->get(Game::CHALLENGE_TYPE),
            "performerId" => $performerId,
            "ids" => $ids
        ];
    }

    public function argsHighDramaChallengeActionActivateTechnique(): array
    {
        $playerId = (int)$this->getActivePlayerId();
        $this->theah->buildCity();
        $performerId = $this->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $this->theah->getCharacterById($performerId);

        $targetId = $this->globals->get(Game::CHOSEN_TARGET);
        $target = $this->theah->getCharacterById($targetId);

        return [
            "performerId" => $performerId,
            "targetId" => $targetId,
            "techniques" => $this->theah->getAvailableCharacterTechniques($performer)
        ];
    }

    public function argsHighDramaChallengeActionAcceptChallenge(): array
    {
        $playerId = (int)$this->getActivePlayerId();
        $this->theah->buildCity();
        $performerId = $this->globals->get(Game::CHOSEN_PERFORMER);
        $targetId = $this->globals->get(Game::CHOSEN_TARGET);
        $target = $this->theah->getCharacterById($targetId);

        //Get a list of characters that could intervene
        $charactersAtLocation = $this->theah->getCharactersAtLocation($target->Location);
        //Characters must be controlled by the player and not be the target
        $charactersAtLocation = array_filter($charactersAtLocation, 
            fn($character) => $character->ControllerId && $character->ControllerId == $playerId && $character->Id != $targetId);
        
        //Get characters that can intervene
        $charactersCanIntervene = array_values(array_filter($charactersAtLocation, fn($character) => $character->canIntervene() && ! $character->Engaged));

        return [
            "performerId" => $performerId,
            "targetId" => $targetId,
            "ids" => array_map(fn($character) => $character->Id, $charactersCanIntervene),
            "challengeType" => $this->globals->get(Game::CHALLENGE_TYPE)
        ];

    }

    public function argsChooseDuelAction(): array
    {
        $this->theah->buildCity();
        $playerId = $this->getActivePlayerId();
        $duelId = $this->globals->get(Game::DUEL_ID);
        $round = $this->globals->get(Game::DUEL_ROUND);
        
        //How many times has the player gambled this duel?
        $sql = "SELECT count(gambled) FROM duel_round where duel_id = $duelId and player_id = {$playerId}";
        $gamblesCount = $this->getUniqueValueFromDB($sql);

        $sql = "SELECT count(*) FROM duel_round_combat_card where duel_id = $duelId AND round = $round";
        $combatCardsCount = $this->getUniqueValueFromDB($sql);

        $sql = "SELECT count(*) FROM duel_round_maneuver where duel_id = $duelId AND round = $round";
        $playedManeuversCount = $this->getUniqueValueFromDB($sql);

        $sql = "SELECT count(*) FROM duel_round_technique where duel_id = $duelId AND round = $round and technique_is_main = 1";
        $playedTechniquesCount = $this->getUniqueValueFromDB($sql);

        $sql = "SELECT * FROM duel_round where duel_id = $duelId AND round = $round";
        $round = $this->getObjectListFromDB($sql)[0];

        $actor = $this->theah->getCharacterById($round['actor_id']);
        $gamblesLeft = $actor->ModifiedFinesse - $gamblesCount;

        $characterManeuevers = $this->theah->getAvailableCharacterManeuvers($actor);
        $techniques = $this->theah->getAvailableCharacterTechniques($actor);

        $duelType = $this->globals->get(Game::DUEL_TYPE);
        if ($duelType == Game::VLADISLAV_DUEL_TYPE)
        {
            return [
                "_private" => [
                    "active" => [
                        "maneuversAvailable" => false,
                        "techniquesAvailable" => false,
                        "gambleAvailable" => false,
                        "gamblesLeft" => 0,
                        "combatCardAvailable" => false,
                        "endDuelAvailable" => true
                    ]
                ],
            ];
            }
        else
        {
            return [
                "_private" => [
                    "active" => [
                        "maneuversAvailable" => count($characterManeuevers) > 0 && $playedManeuversCount == 0,
                        "techniquesAvailable" => count($techniques) > 0 && $playedTechniquesCount == 0,
                        "gambleAvailable" => $gamblesLeft > 0 && $round['gambled'] == null && $combatCardsCount == 0,
                        "gamblesLeft" => $gamblesLeft,
                        "combatCardAvailable" => $combatCardsCount == 0,
                        "endDuelAvailable" => false
                    ]
                ],
            ];
            }

    }

    public function argsChooseDuelTechnique(): array
    {
        $this->theah->buildCity();

        $actor = $this->theah->getDuelRoundActor();
        $techniques = $this->theah->getAvailableCharacterTechniques($actor);
        return [
            "techniques" => $techniques
        ];

    }

    public function argsDuelUseManeuverFromCombatCard(): array
    {
        $cardId = $this->globals->get(Game::CHOSEN_CARD);
        $gambled = $this->globals->get(Game::DUEL_GAMBLED, false);

        $card = $this->getCardObjectFromDb($cardId);
        if ($card instanceof IHasManeuvers)
            $maneuvers = $card->getManeuversArray($this);

        return [
            "_private" => [
                "active" => [
                    "cardId" => $cardId,
                    "maneuvers" => $maneuvers,
                    "gambled" => $gambled,
                    "card" => $card->getPropertyArray($this)
                ]
            ]
        ];

    }

    public function argsDuelPayForManeuverFromCombatCard(): array     
    {
        $cardId = $this->globals->get(Game::CHOSEN_CARD);
        $gambled = $this->globals->get(Game::DUEL_GAMBLED, false);
        $card = $this->getCardObjectFromDb($cardId);
        return [
            "_private" => [
                "active" => [
                    "combatCardId" => $cardId,
                    "cost" => $this->globals->get(Game::CHOSEN_CARD_COST),
                    "discount" => $this->globals->get(Game::DISCOUNT),
                    "gambled" => $gambled,
                    "card" => $card->getPropertyArray($this)
                ]
            ]
        ];
    }

    public function argsDuelChooseGambleCard(): array
    {
        $playerId = $this->getActivePlayerId();
        $deckCards = $this->getCardsOnTopOfPlayerFactionDeck($playerId, 2);
        $cards = [];
        foreach ($deckCards as $deckCard) {
            $card = $this->getCardObjectFromDb($deckCard['id']);
            $cards[] = $card->getPropertyArray($this);
        }

        return [
            "_private" => [
                "active" => [
                    "cards" => $cards
                ]
            ]
        ];
    }

    public function argsForState(): array
    {
        $this->theah->buildCity();

        $sourceId = $this->globals->get(Game::TRANSITION_SOURCE_ID);
        $internalId = $this->globals->get(Game::TRANSITION_INTERNAL_ID);
        $state = $this->gamestate->state_id();
        $stateName = $this->gamestate->state()['name'];

        if ($sourceId == Game::THEAH_ID)
        {
            $args = $this->theah->argsFromReaction($state, $stateName, $internalId);
        }
        else
        {
            $card = $this->theah->getCardById($sourceId);
            $args = $card->argsFromCard($this, $state, $stateName, $internalId);
        }        

        return [
            "args" => $args
        ];       
        
    }

    public function argsForStatePrivate(): array
    {
        $this->theah->buildCity();

        $sourceId = $this->globals->get(Game::TRANSITION_SOURCE_ID);
        $internalId = $this->globals->get(Game::TRANSITION_INTERNAL_ID);
        $state = $this->gamestate->state_id();
        $stateName = $this->gamestate->state()['name'];

        if ($sourceId == Game::THEAH_ID)
        {
            $args = $this->theah->argsFromReaction($state, $stateName, $internalId);
        }
        else
        {
            $card = $this->theah->getCardById($sourceId);
            $args = $card->argsFromCard($this, $state, $stateName, $internalId);
        }        

        return [
            "_private" => [
                "active" => [
                    "args" => $args
                ]
            ]
        ];       
        
    }
}