define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {

var jstpl_player_board = `
<div class="cp_board">
    <div id="\${id}-score-reknown" class="score-reknown">\${reknown}</div>
    <div id="\${id}-score-crewcap" class="crew-cap score-crew-cap">\${crewcap}</div>
    <div id="\${id}-score-panache" class="panache score-panache">\${panache}</div>
    <div id="\${id}-score-seal-first-player" class="first-player-hidden"></div>
    <div id="\${id}-score-seal"></div>
</div>
`;

var jstpl_home=`
<div id="\${id}" class="home-container home-\${faction}">
    <div class="home-panel">
        <div id="\${id}-crewcap" class="crew-cap home-crew-cap">\${crewcap}</div>
        <div id="\${id}-discard" class="home-discard"></div>
        <div id="\${id}-panache" class="panache home-panache">\${panache}</div>
        <div id="\${id}-locker" class="home-locker"></div>
        <div></div>
        <div class="seal-home seal-\${faction}-home"></div>
        <div>
        <div class="home-player-color" style="--player-color:#\${player_color}"></div>
        <div id="\${id}-scheme-anchor"></div>
        <div id="\${id}-first-player"></div>
        </div>
    </div>
    <div id="\${id}-home-anchor" class="home-endcap"></div>
</div>
`;

var jstpl_character=`
<div id="\${id}">
    <div class="card home-\${faction}" style="--card_image:url('\${image}')">
        <div class="card-resolve">\${resolve}</div>
        <div id="\${id}-wealth-cost" class="card-wealth-cost city-character-wealth-cost">\${cost}</div>
        <div class="card-stat-box card-combat-box">
            <div class="card-combat-value">\${combat}</div>
            <div class="card-combat-image"></div>
        </div>
        <div class="card-stat-box card-finesse-box">
            <div class="finesse-value card-finesse-value">\${finesse}</div>
            <div class="card-finesse-image"></div>
        </div>
        <div class="card-stat-box card-influence">
            <div class="card-influence-value">\${influence}</div>
            <div class="card-influence-image"></div>
        </div>
        <div id="\${id}-player-color" style="--player-color:#\${player_color}" class="character-player-color"></div>
    </div>
</div>
`;

var jstpl_card_attachment=`
<div id="\${id}">
    <div class="card home-\${faction}" style="--card_image:url('\${image}')">
        <div class="card-resolve">\${resolve}</div>
        <div id="\${id}-wealth-cost" class="card-wealth-cost">\${cost}</div>
        <div class="card-stat-box card-combat-box">
            <div class="card-combat-value attachment-combat-value">\${combat}</div>
            <div class="card-combat-image"></div>
        </div>
        <div class="card-stat-box card-finesse-box">
            <div class="card-finesse-value attachment-finesse-value">\${finesse}</div>
            <div class="card-finesse-image"></div>
        </div>
        <div class="card-stat-box card-influence">
            <div class="card-influence-value attachment-influence-value">\${influence}</div>
            <div class="card-influence-image"></div>
        </div>
    </div>
</div>
`;

var jstpl_card_event=`
<div id="\${id}">
    <div class="card" style="--card_image:url('\${image}')">
    </div>
</div>
`;

var jstpl_card_scheme=`
<div id="\${id}">
    <div class="scheme" style="--card_image:url('\${image}')"></div>
    <div class="card-stat-box scheme-initiative-box">
        <div class="scheme-initiative-value">\${initiative}</div>
        <div class="scheme-initiative-image"></div>
    </div>
    <div class="card-stat-box scheme-panache-box">
        <div class="scheme-panache-value">\${panache}</div>
        <div class="scheme-panache-image"></div>
    </div>
</div>
`;

var jstpl_reknown_chip = `
<div id="\${id}" class="reknown-chip card-reknown-chip">\${amount}</div>
`;

var jstpl_generic_chip = `
<div id="\${id}" class="\${class}"></div>
`;

});
