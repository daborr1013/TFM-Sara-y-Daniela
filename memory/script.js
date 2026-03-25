const symbols = ['🐌​', '🐛', '🦙', '🐢', '🐸', '🦕', '🐡​', '🐒​'];
const totalPairs = symbols.length;

let cards = [];
let flippedCards = [];
let pairsFound = 0;

function shuffle(array) {
    return array.slice().sort(() => Math.random() - 0.5);
}

cards = shuffle([...symbols, ...symbols]).map((symbol, index) => ({ }));

function createCard(symbol) {
    const card = document.createElement('div');
    card.classList.add('card');

    const symbolContainer = document.createElement('div');
    symbolContainer.classList.add('symbol');
    symbolContainer.textContent = symbol;

    card.appendChild(symbolContainer);

    card.addEventListener('click', () => flipCard(card) );
    return card;
}

function flipCard(card) {
    if (flippedCards.length < 2 
        && !flippedCards.includes(card) 
        && !card.classList.contains('flipped')) {
        card.classList.add('flipped');
        flippedCards.push(card);
    }

    if (flippedCards.length === 2) {
        setTimeout(checkMatch, 1000);
    }
}

function checkMatch() {
    const [card1, card2] = flippedCards;
    const symbol1 = card1.textContent; 
    const symbol2 = card2.textContent;

    if (symbol1 === symbol2) {
        card1.removeEventListener('click', flipCard);
        card2.removeEventListener('click', flipCard);
        pairsFound++;

    if (pairsFound === totalPairs){
        alert('¡WOW! ¡Has encontrado todas las parejas!');
        }
    } else {
        card1.classList.remove('flipped');
        card2.classList.remove('flipped');
    }

    flippedCards = [];
}

function initializeGame() {
    cards = shuffle([...symbols, ...symbols]);
    pairsFound = 0;
    flippedCards = [];

    const gameContainer = document.querySelector('.memory-game');
    gameContainer.innerHTML = ' ';

    cards.forEach(symbol => {
        const cardElement = createCard(symbol);
        gameContainer.appendChild(cardElement);
    });
}

initializeGame();