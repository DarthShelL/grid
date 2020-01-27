class grid {
    constructor(wrapper, method) {
        this.wrapper = wrapper;
        this.table = this.getTable();
        this.pagination = this.getPagination();
        this.actionPanel = this.getActionPanel();
        this.method = method;
        this.setActions();
    }

    getTable() {
        return this.wrapper.querySelector('.dsg-table');
    }

    getPagination() {
        return this.wrapper.querySelector('.dsg-pagination');
    }

    getActionPanel() {
        return this.wrapper.querySelector('.dsg-action-panel');
    }

    setActions() {
        this.table.addEventListener('click', this.actionsHandler.bind(this));

        // filters
        const filter_inputs = this.table.querySelectorAll('input.dsg-filter');
        for (const filter of filter_inputs) {
            filter.addEventListener('change', this.filterActionHandler.bind(this));
        }

        // pagination
        this.getPagination().addEventListener('click', this.paginationActionsHandler.bind(this));

        // action panel
        this.getActionPanel().addEventListener('click', this.actionPanelActionsHandler.bind(this));
    }

    adjustTemplateRowSize() {
        const nRowChildren = document.querySelector('.dsg-template-row').children;
        const oRowChildren = this.getTable().querySelector('tbody tr').children;

        for (let i = 0; i < oRowChildren.length; i++) {
            const n = nRowChildren[i];
            const o = oRowChildren[i];

            n.width = getComputedStyle(o, null).width;
        }
    }

    toggleTemplateRow() {
        const row = document.querySelector('.dsg-template-row');
        this.adjustTemplateRowSize();
        if (row.classList.contains('hidden')) {
            row.classList.remove('hidden');
        } else {
            row.classList.add('hidden');
        }
    }

    saveTemplateRow() {
        const grid = this;
        // get all cells
        const cells = this.getTable().querySelectorAll('.dsg-template-cell');
        console.log(cells);

        let data = {};

        for (const cell of cells) {
            const attr = cell.getAttribute('data-attribute');
            const input = cell.children[0];
            let value = input.value;

            if (input.tagName == 'SELECT' && value == '-') {
                value = '';
            }
            data['nrv_' + attr] = encodeURI(value);
        }

        data.nr_flag = true;
        data.dsgrid_update = true;
        console.log({data});

        //send data
        this.customRequest(data, function (resp) {
            try {
                const r = JSON.parse(resp);
                let msg = '';
                for (const error in r) {
                    msg += r[error] + '\n';
                }
                alert(msg);
                return;
            } catch (e) {
            }
            const data = grid.prepareData();
            grid.update(data)
        })
    }

    customRequest(data, callback) {
        const grid = this;
        const request = new XMLHttpRequest();
        request.open(grid.method, grid.method == 'GET' ? document.location.pathname + '?' + grid.makeURLParams(data) : '', true);
        request.onload = function () {
            if (this.status >= 200 && this.status < 400) {
                // Success!
                const resp = this.responseText;
                if (callback) {
                    callback(resp);
                }
            } else {
                // We reached our target server, but it returned an error
            }
        };

        request.onerror = function () {
            // There was a connection error of some sort
        };

        if (this.method == 'POST') {
            request.setRequestHeader('Content-Type', 'application/json; charset=UTF-8');
            request.send(JSON.stringify(data));
        } else {
            request.send();
        }
    }

    actionPanelActionsHandler(e) {
        switch (e.target.tagName) {
            case 'A':
                if (e.target.classList.contains('add-row-btn')) {
                    e.preventDefault();
                    this.toggleTemplateRow();
                }
                break;
        }
    }

    paginationActionsHandler(e) {
        if (e.target.tagName === 'BUTTON') {
            const btn = e.target;
            const current = parseInt(btn.parentNode.querySelector('.current').innerText);
            const page = parseInt(btn.innerText);

            const data = this.prepareData();

            if (!isNaN(page)) {
                data.page = page;
            } else {
                switch (btn.innerText) {
                    case '>':
                        data.page = current + 1;
                        break;
                    case '<':
                        data.page = current - 1;
                        break;
                    case '>>':
                        const last = btn.getAttribute('data-last');
                        data.page = last;
                        break;
                    case '<<':
                        data.page = 1;
                        break;
                }
            }

            this.update(data);
        }
    }

    filterActionHandler(e) {
        const input = e.target;
        this.update();
    }

    clearSort() {
        const headerCells = this.table.querySelectorAll('thead th');

        for (const cell of headerCells) {
            cell.removeAttribute('data-sort');
        }
    }

    actionsHandler(e) {
        switch (e.target.tagName) {
            case 'TH':
                const headerCell = e.target;
                //clear sort from other cells
                if (headerCell.hasAttribute('data-sort')) {
                    const currentSort = headerCell.getAttribute('data-sort');
                    this.clearSort();
                    const sort = {
                        ASC: 'DESC',
                        DESC: 'ASC'
                    };
                    headerCell.setAttribute('data-sort', sort[currentSort]);
                } else {
                    this.clearSort();
                    headerCell.setAttribute('data-sort', 'ASC');
                }
                this.update();
                break;
            case 'TD':
                break;
            case 'A':
                if (e.target.classList.contains('remove-btn')) {
                    e.preventDefault();

                    let decision = confirm('You are about to delete record. Are you sure?');

                    if (!decision)
                        return;

                    const btn = e.target;
                    const id = btn.getAttribute('data-id');

                    const data = this.prepareData();
                    data.rr_flag = true;
                    data.rrid = id;

                    //send data
                    this.update(data);
                }
                if (e.target.classList.contains('save-row-btn')) {
                    e.preventDefault();

                    this.saveTemplateRow();
                }
                break;
            case 'DIV':
                if (e.target.classList.contains('dsg-ie-value')) {
                    const vDiv = e.target;
                    const cell = vDiv.parentNode;
                    const cellInitialWidth = parseInt(getComputedStyle(cell, null).width);
                    const iDiv = cell.querySelector('.dsg-ie-editable');
                    const input = iDiv.children[0];
                    const value = input.value;

                    //switch
                    vDiv.classList.add('hidden');
                    iDiv.classList.remove('hidden');
                    cell.style.width = cellInitialWidth + 'px';
                    input.style.width = cellInitialWidth + 'px';
                    input.focus();

                    const kdHandler = function (e) {
                        if (e.key == 'Escape') {
                            //switch back
                            vDiv.classList.remove('hidden');
                            iDiv.classList.add('hidden');
                            input.value = value;
                            cell.style.width = 'auto';
                        }
                    }
                    const outHandler = function (e) {
                        //switch back
                        vDiv.classList.remove('hidden');
                        iDiv.classList.add('hidden');
                        input.value = value;
                        cell.style.width = 'auto';
                    }
                    const changeHandler = function (e) {
                        //collect data
                        const data = this.prepareData();
                        data.inline_edit = true;
                        data.edit_id = iDiv.getAttribute('data-id');
                        data.edit_attribute = iDiv.getAttribute('data-attribute');
                        data.edit_value = encodeURI(input.value);

                        //send data
                        this.update(data);
                    }

                    iDiv.addEventListener('keydown', kdHandler, {once: true});
                    input.addEventListener('focusout', outHandler, {once: true});
                    input.addEventListener('change', changeHandler.bind(this), {once: true});
                }
                break;
        }
    }

    getSort() {
        const headerCells = this.table.querySelectorAll('thead th');

        for (const cell of headerCells) {
            if (cell.hasAttribute('data-sort')) {
                return {
                    attribute: cell.getAttribute('data-attribute'),
                    order: cell.getAttribute('data-sort')
                };
            }
        }

        return false;
    }

    updateGrid(resp) {
        try {
            const r = JSON.parse(resp);
            let msg = '';
            for (const error in r) {
                msg += r[error] + '\n';
            }
            alert(msg);
            return;
        } catch (e) {
        }

        let temp = document.createElement("div");
        temp.innerHTML = resp;

        let body = temp.querySelector('table.dsg-table tbody').innerHTML;
        this.updateBody(body);

        let pagination = temp.querySelector('.dsg-pagination').innerHTML;
        this.updatePagination(pagination);
    }

    updateBody(body) {
        this.table.querySelector('tbody').innerHTML = body;
    }

    updatePagination(pagination) {
        this.getPagination().innerHTML = pagination;
    }

    makeURLParams(data) {
        const searchParams = new URLSearchParams();

        for (const param in data) {
            searchParams.append(param, data[param]);
        }

        return searchParams.toString();
    }

    sendUpdateRequest(data) {
        const grid = this;
        const request = new XMLHttpRequest();
        request.open(grid.method, grid.method == 'GET' ? document.location.pathname + '?' + grid.makeURLParams(data) : '', true);
        request.onload = function () {
            if (this.status >= 200 && this.status < 400) {
                // Success!
                const resp = this.responseText;
                grid.updateGrid(resp);
            } else {
                // We reached our target server, but it returned an error
            }
        };

        request.onerror = function () {
            // There was a connection error of some sort
        };

        if (this.method == 'POST') {
            request.setRequestHeader('Content-Type', 'application/json; charset=UTF-8');
            request.send(JSON.stringify(data));
        } else {
            request.send();
        }
    }

    getFilters() {
        const filter_inputs = this.table.querySelectorAll('input.dsg-filter');
        const filters = [];
        for (const filter of filter_inputs) {
            if (filter.value.toString().trim() !== '') {
                filters.push({
                    attribute: filter.getAttribute('data-attribute'),
                    type: filter.getAttribute('data-type'),
                    expression: filter.value
                })
            }
        }

        return filters;
    }

    prepareData() {
        const data = {dsgrid_update: true};
        //get sort
        const sort = this.getSort();
        if (sort) {
            data.order_by = sort.attribute;
            data.order_direction = sort.order;
        }
        //get filters
        const filters = this.getFilters();

        for (let i = 0; i < filters.length; i++) {
            data['fa_' + i] = filters[i].attribute;
            data['ft_' + i] = filters[i].type;
            data['fv_' + i] = filters[i].expression;
        }

        return data;
    }

    update(data) {
        if (typeof data == "undefined") {
            this.sendUpdateRequest(this.prepareData());
        } else {
            this.sendUpdateRequest(data);
        }
    }
}
