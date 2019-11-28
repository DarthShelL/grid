class grid {
    constructor(table, method) {
        this.table = table;
        this.method = method;
        this.setActions();
    }

    setActions() {
        this.table.addEventListener('click', this.actionsHandler.bind(this));

        const filter_inputs = this.table.querySelectorAll('input.dsg-filter');
        for (const filter of filter_inputs) {
            filter.addEventListener('change', this.filterActionHandler.bind(this));
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

    updateBody(resp) {
        this.table.querySelector('tbody').innerHTML = resp;
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
                grid.updateBody(resp);
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

    update() {
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

        console.log(data);
        this.sendUpdateRequest(data);
    }
}
