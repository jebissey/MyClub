const leanSheepSVG = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjgwIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxnPjxlbGxpcHNlIGN4PSI1MCIgY3k9IjQwIiByeD0iMjQiIHJ5PSIxOCIgZmlsbD0iI2U4ZDhjNCIvPjxjaXJjbGUgY3g9IjM1IiBjeT0iMzUiIHI9IjE0IiBmaWxsPSIjZTlkOGM0Ii8+PGNpcmNsZSBjeD0iNjgiIGN5PSIzNSIgcj0iMTIiIGZpbGw9IiNlOGQ4YzQiLz48ZWxsaXBzZSBjeD0iNzgiIGN5PSIzNyIgcng9IjEwIiByeT0iOCIgZmlsbD0iI2M4YWE4OCIvPjxjaXJjbGUgY3g9IjgyIiBjeT0iMzUiIHI9IjMiIGZpbGw9IiMzMzMiLz48Y2lyY2xlIGN4PSI4MiIgY3k9IjQwIiByPSIyIiBmaWxsPSIjMzMzIi8+PHJlY3QgeD0iNDAiIHk9IjU1IiB3aWR0aD0iNSIgaGVpZ2h0PSIxNCIgZmlsbD0iI2E4ODg3MCIgcng9IjIiLz48cmVjdCB4PSI1OCIgeT0iNTUiIHdpZHRoPSI1IiBoZWlnaHQ9IjE0IiBmaWxsPSIjYTg4ODcwIiByeD0iMiIvPjwvZz48L3N2Zz4=';
const fatSheepSVG = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTQwIiBoZWlnaHQ9IjEwMCIgdmlld0JveD0iMCAwIDE0MCAxMDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGcgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoNDAsMTUpIj4KICA8ZWxsaXBzZSBjeD0iNjAiIGN5PSI0NSIgcng9IjM4IiByeT0iMzAiIGZpbGw9IiNmZmYiLz4KICA8ZWxsaXBzZSBjeD0iNjAiIGN5PSI0NSIgcng9IjM1IiByeT0iMjciIGZpbGw9IiNlOGQ4YzQiLz4KICA8Y2lyY2xlIGN4PSIzMCIgY3k9IjQwIiByPSIxOCIgZmlsbD0iI2U4ZDhjNCIvPgogIDxjaXJjbGUgY3g9IjE4IiBjeT0iNDIiIHI9IjQiIGZpbGw9IiMzMzMiLz4KICA8Y2lyY2xlIGN4PSIxOCIgY3k9IjQyIiByPSIxIiBmaWxsPSIjZmZmIi8+CiAgPHJlY3QgeD0iNDUiIHk9IjY1IiB3aWR0aD0iOCIgaGVpZ2h0PSIxOCIgZmlsbD0iI2E4ODg3MCIgcng9IjQiLz4KICA8cmVjdCB4PSI2NSIgeT0iNjUiIHdpZHRoPSI4IiBoZWlnaHQ9IjE4IiBmaWxsPSIjYTg4ODcwIiByeD0iNCIvPgogIDxwYXRoIGQ9Ik0gMzUgMjUgQyAzNyAyNSAzOCAyMyAzOCAyMyBDIDM4IDIzIDQwIDI1IDQwIDI1IiBmaWxsPSJub25lIiBzdHJva2U9IiM2NjYiIHN0cm9rZS13aWR0aD0iMiIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIi8+CiAgPHBhdGggZD0iTSA1MCAyNSBDIDUyIDI1IDU1IDIzIDU1IDIzIEMgNTUgMjMgNTcgMjUgNTcgMjUiIGZpbGw9Im5vbmUiIHN0cm9rZT0iIzY2NiIgc3Ryb2tlLXdpZHRoPSIyIiBzdHJva2UtbGluZWNhcD0icm91bmQiLz4KICA8cGF0aCBkPSJNIDY1IDM1IEMgNjUgMzUgNzAgMzAgNzUgMzAgQyA4MCAzMCA4NSA0MCA4NSA0NSBDIDg1IDUwIDgwIDU1IDc1IDU1IEMgNzAgNTUgNjUgNTAgNjUgNDUiIGZpbGw9IiNlOGQ4YzQiLz4KICA8Y2lyY2xlIGN4PSI3NSIgY3k9IjM1IiByPSI0IiBmaWxsPSIjYjg5ODgwIi8+CiAgPHBhdGggZD0iTSA2MiAzOCBMIDY1IDQwIEwgNjggMzgiIGZpbGw9Im5vbmUiIHN0cm9rZT0iI2U4ZDhjNCIgc3Ryb2tlLXdpZHRoPSIyLjUiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIvPgo8L2c+PC9zdmc+';

let container = document.getElementById('sheep-container');

container.className = 'container mt-4';
container.innerHTML = `
  <div class="row text-center justify-content-center">
    ${Array.from({ length: 7 }).map((_, i) => `
      <div class="col sheep-col ${i < 3 ? 'bergerie' : i > 3 ? 'pre' : 'separation'
    }" data-index="${i}">
        ${i < 3
        ? `<img src="${leanSheepSVG}" alt="Mouton maigre" class="sheep" data-type="lean">`
        : i > 3
            ? `<img src="${fatSheepSVG}" alt="Mouton gras" class="sheep" data-type="fat">`
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
}

function shakeSheep(sheep) {
    if (!sheep) return;
    sheep.animate(
        [
            { transform: 'translateX(0)' },
            { transform: 'translateX(-8px)' },
            { transform: 'translateX(8px)' },
            { transform: 'translateX(0)' },
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
