if (document.readyState != 'loading') {
    initGrids();
} else {
    document.addEventListener('DOMContentLoaded', initGrids);
}

function initGrids() {
    const tables = document.querySelectorAll('.dsg-table-wrapper');
    for (let table of tables) {
        new grid(table, 'GET');
    }
}
