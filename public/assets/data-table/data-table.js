const initDataTables = async (callbackFn) => {
    const dataTables = document.querySelectorAll('div[data-dataTable-settings]');

    dataTables.forEach(function (dataTableWrapperNode) {
        const settings = JSON.parse(dataTableWrapperNode.getAttribute('data-dataTable-settings'));

        const searchInput = dataTableWrapperNode.querySelector('input[type="search"]');
        const dataTable = dataTableWrapperNode.querySelector('table');

        if (!searchInput) {
            return;
        }
        if (!dataTable) {
            return;
        }

        fetch(settings.url).then(async function (response) {
            const dataRows = await response.json();
            const $scrollElement = dataTableWrapperNode.querySelector('.scroll-area');

            const clusterize = new Clusterize({
                rows: filterOnActiveRows(dataRows),
                scrollElem: $scrollElement,
                contentElem: dataTable.querySelector('tbody'),
                no_data_class: 'clusterize-loading',
                callbacks: {
                    clusterChanged: () => {
                        callbackFn();
                    }
                }
            });

            let sortOnPrevious = null;
            let sortAsc = false;
            const sortableColumns = dataTable.querySelectorAll('thead tr th[data-dataTable-sort]');
            sortableColumns.forEach(element => {
                element.addEventListener('click', () => {
                    const sortOn = element.getAttribute('data-dataTable-sort');
                    if (sortOn === sortOnPrevious) {
                        sortAsc = !sortAsc;
                    }
                    sortOnPrevious = sortOn;
                    // Highlight sorting icons.
                    sortableColumns.forEach(el => el.querySelector('.sorting-icon').setAttribute('aria-sort', 'none'))
                    element.querySelector('.sorting-icon').setAttribute('aria-sort', sortAsc ? 'ascending' : 'descending');
                    // Do the actual sort.
                    dataRows.sort((a, b) => {
                        if (a.sort[sortOn] < b.sort[sortOn]) return sortAsc ? -1 : 1;
                        if (a.sort[sortOn] > b.sort[sortOn]) return sortAsc ? 1 : -1;
                        return 0;
                    });
                    // Update the rows.
                    clusterize.update(filterOnActiveRows(dataRows));
                    $scrollElement.scrollTop = 0;
                });
            });

            searchInput.addEventListener('keyup', e => {
                clusterize.update(filterOnActiveRows(applySearchAndFiltersToDataRows(dataRows, dataTableWrapperNode)));
            });

            const filters = dataTableWrapperNode.querySelectorAll('[data-dataTable-filter][data-dataTable-filter-value]');
            filters.forEach(element => {
                element.addEventListener('click', () => {
                    clusterize.update(filterOnActiveRows(applySearchAndFiltersToDataRows(dataRows, dataTableWrapperNode)));
                    $scrollElement.scrollTop = 0;
                });
            });

            dataTableWrapperNode.querySelector('[data-dataTable-reset]').addEventListener('click', () => {
                location.reload();
            });
        });
    });
};

const applySearchAndFiltersToDataRows = function (dataRows, $dataTableNode) {
    const $searchInput = $dataTableNode.querySelector('input[type="search"]');
    const searchValue = $searchInput.value;

    const $activeFilters = $dataTableNode.querySelectorAll('[data-dataTable-filter][data-dataTable-filter-value]:checked');

    const filters = [];
    $activeFilters.forEach(element => {
        const filterName = element.getAttribute('data-dataTable-filter');
        filters[filterName] = element.getAttribute('data-dataTable-filter-value').toLowerCase();
    });

    for (let i = 0; i < dataRows.length; i++) {
        const filterables = dataRows[i].filterables;
        const searchables = dataRows[i].searchables.toLowerCase();
        dataRows[i].active = !(searchables.indexOf(searchValue) === -1);

        for (const filter in filters) {
            const filterValue = filters[filter];
            dataRows[i].active = dataRows[i].active && filter in filterables && filterables[filter].toLowerCase() === filterValue
        }
    }

    return dataRows;
};

const filterOnActiveRows = function (rows) {
    return rows.filter((row) => row.active).map((row) => row.markup);
}