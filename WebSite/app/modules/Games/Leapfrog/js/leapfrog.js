const leanSheepImage = '/app/images/leanSheep.png';
const fatSheepImage = '/app/images/fatSheep.png';

const i18n = window.leapfrogI18n ?? {};
const container = document.getElementById('sheep-container');
const counterElement = document.querySelector('#gameboard-container h2');
const sessionId = window.sessionId ?? 'no-session';
let moves = 0;

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML.replace(/"/g, '&quot;');
}

container.className = 'container mt-4';
container.innerHTML = `
  <div class="row text-center justify-content-center">
    ${Array.from({ length: 7 }).map((_, i) => `
      <div class="col sheep-col ${i < 3 ? 'bergerie' : i > 3 ? 'pre' : 'separation'}" data-index="${i}">
        ${i < 3
            ? `<img src="${leanSheepImage}" alt="${escapeHtml(i18n.leanSheep)}" class="sheep" data-type="lean" style="cursor: pointer;">`
            : i > 3
                ? `<img src="${fatSheepImage}" alt="${escapeHtml(i18n.fatSheep)}" class="sheep" data-type="fat" style="cursor: pointer;">`
                : ''
        }
      </div>
    `).join('')}
  </div>
`;

function getCell(index) {
    return document.querySelector(`.sheep-col[data-index="${index}"]`);
}

function moveSheep(fromIndex, toIndex) {
    const fromCell = getCell(fromIndex);
    const toCell = getCell(toIndex);
    if (!fromCell || !toCell) return;
    const sheep = fromCell.querySelector('.sheep');
    if (!sheep) return;

    const fromRect = fromCell.getBoundingClientRect();
    const toRect = toCell.getBoundingClientRect();
    const dx = (toRect.left + toRect.width / 2) - (fromRect.left + fromRect.width / 2);

    sheep.style.transition = 'transform 0.35s ease';
    sheep.style.transform = `translateX(${dx}px)`;

    setTimeout(() => {
        toCell.appendChild(sheep);
        sheep.style.transition = '';
        sheep.style.transform = '';
    }, 360);

    updateCounter();
    setTimeout(() => {
        if (isGameOver()) showRestartButton(checkWinOrLose());
    }, 400);
    log(`Moved sheep from ${fromIndex} to ${toIndex} at movement ${moves}`);
}

function shakeSheep(sheep) {
    if (!sheep) return;
    sheep.animate(
        [
            { transform: 'translateY(0)' },
            { transform: 'translateY(-8px)' },
            { transform: 'translateY(8px)' },
            { transform: 'translateY(0)' },
        ],
        { duration: 300, iterations: 1 }
    );
}

function getIndex(element) {
    const cell = element.closest('.sheep-col');
    return cell ? Number(cell.dataset.index) : -1;
}

function getType(sheep) {
    return sheep.dataset.type;
}

function isGameOver() {
    const cells = Array.from(document.querySelectorAll('.sheep-col'));

    for (let i = 0; i < cells.length; i++) {
        const sheep = cells[i].querySelector('.sheep');
        if (!sheep) continue;

        const type = getType(sheep);
        const direction = type === 'lean' ? 1 : -1;
        const next = i + direction;
        const jump = i + 2 * direction;

        const nextCell = getCell(next);
        const jumpCell = getCell(jump);

        if (nextCell && !nextCell.querySelector('.sheep')) return false;
        if (nextCell && nextCell.querySelector('.sheep') && jumpCell && !jumpCell.querySelector('.sheep')) return false;
    }
    return true;
}

function updateCounter() {
    moves++;
    counterElement.textContent = (moves > 1 ? i18n.movesOther : i18n.movesOne)
        .replace('{count}', String(moves));
}

function checkWinOrLose() {
    const leftCells = [getCell(0), getCell(1), getCell(2)];

    const fatsAtLeft = leftCells.filter(c => {
        const s = c.querySelector('.sheep');
        return s && getType(s) === 'fat';
    }).length;

    return fatsAtLeft === 3 ? 'won' : 'lost';
}

function showRestartButton(result) {
    const div = document.createElement('div');
    div.className = 'text-center mt-3';

    div.innerHTML = `
        <h3>${result === 'won' ? '🎉 ' + escapeHtml(i18n.won) : '❌ ' + escapeHtml(i18n.lost)}</h3>
        <button class="btn btn-primary mt-2" onclick="location.reload()">${escapeHtml(i18n.replay)}</button>
    `;
    document.getElementById('gameboard-container').appendChild(div);
    log(`Game over: ${result} after ${moves} moves`);
}

function log(message) {
    fetch('/api/leapfrog', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ message: `Session ${sessionId}: ${message}` })
    })
        .then(response => response.json())
        .then(result => {
            if (!result.success) {
                console.error('Failed to log the move: ' + result.message);
            }
        })
        .catch(error => {
            console.error('Failed to log the move: ' + error.message);
        });
}

container.addEventListener('click', (e) => {
    const sheep = e.target.closest('.sheep');
    if (!sheep) return;

    const from = getIndex(sheep);
    if (from === -1) return;

    const sheepType = getType(sheep);
    const direction = sheepType === 'lean' ? 1 : -1;
    const next = from + direction;
    const jump = from + 2 * direction;
    const nextCell = getCell(next);
    const jumpCell = getCell(jump);
    if (nextCell && !nextCell.querySelector('.sheep')) {
        moveSheep(from, next);
        return;
    }
    if (
        nextCell && nextCell.querySelector('.sheep') &&
        jumpCell && !jumpCell.querySelector('.sheep')
    ) {
        moveSheep(from, jump);
        return;
    }
    shakeSheep(sheep);
});