class grid {
    constructor(wrapper, method) {
        this.wrapper = wrapper;
        this.table = this.getTable();
        this.pagination = this.getPagination();
        this.method = method;
        this.setActions();
    }

    getTable() {
        return this.wrapper.querySelector('.dsg-table');
    }

    getPagination() {
        return this.wrapper.querySelector('.dsg-pagination');
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
    }

    paginationActionsHandler(e) {
        console.log(e.target.tagName);
        if (e.target.tagName == 'BUTTON') {
            const btn = e.target;
            const current = parseInt(btn.parentNode.querySelector('.current').innerText);
            const page = parseInt(btn.innerText);

            const data = this.prepareData();

            if (!isNaN(page)) {
                data.page = page;
            }else {
                switch(btn.innerText) {
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
        }else {
            this.sendUpdateRequest(data);
        }
    }
}
