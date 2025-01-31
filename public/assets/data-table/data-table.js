const initDataTables = async () => {
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
                        if (dataTable.querySelectorAll('[data-dataTable-summable]').length > 0) {
                            const sums = calculateSummables(dataRows);
                            Object.keys(sums).forEach(summable => {
                                const summableNode = dataTable.querySelector('[data-dataTable-summable="' + summable + '"]');
                                summableNode.innerHTML = numberFormat(sums[summable], 0, ',', ' ');
                            });
                        }

                        document.dispatchEvent(new CustomEvent('dataTableClusterWasChanged', {
                            bubbles: true,
                            cancelable: false,
                        }));
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
                        if (b.sort[sortOn] === undefined)
                            return -1;
                        if (a.sort[sortOn] === undefined)
                            return 1;

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
    const searchValue = $searchInput.value.toLowerCase();

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

const calculateSummables = function (dataRows) {
    const sums = [];
    dataRows.filter((row) => row.active).map((row) => row.summables).forEach((summables => {
        Object.keys(summables).forEach(summable => {
            if (sums[summable] === undefined) {
                sums[summable] = 0;
            }
            sums[summable] += summables[summable];
        });
    }));

    return sums;
}

const filterOnActiveRows = function (rows) {
    return rows.filter((row) => row.active).map((row) => row.markup);
}

const numberFormat = function (number, decimals, decPoint, thousandsSep) {
    number = (number + '').replace(/[^0-9+\-Ee.]/g, '')
    const n = !isFinite(+number) ? 0 : +number
    const prec = !isFinite(+decimals) ? 0 : Math.abs(decimals)
    const sep = typeof thousandsSep === 'undefined' ? ',' : thousandsSep
    const dec = typeof decPoint === 'undefined' ? '.' : decPoint
    let s = ''

    const toFixedFix = function (n, prec) {
        if (('' + n).indexOf('e') === -1) {
            return +(Math.round(n + 'e+' + prec) + 'e-' + prec)
        } else {
            const arr = ('' + n).split('e')
            let sig = ''
            if (+arr[1] + prec > 0) {
                sig = '+'
            }
            return (+(Math.round(+arr[0] + 'e' + sig + (+arr[1] + prec)) + 'e-' + prec)).toFixed(prec)
        }
    }

    // @todo: for IE parseFloat(0.55).toFixed(0) = 0;
    s = (prec ? toFixedFix(n, prec).toString() : '' + Math.round(n)).split('.')
    if (s[0].length > 3) {
        s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep)
    }
    if ((s[1] || '').length < prec) {
        s[1] = s[1] || ''
        s[1] += new Array(prec - s[1].length + 1).join('0')
    }

    return s.join(dec)
}