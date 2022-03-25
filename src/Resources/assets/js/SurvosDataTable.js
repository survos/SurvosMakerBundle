// 'use strict';

/*  When the class is wrapped in this, not sure how to pass everything.
(function(window, $, Routing, swal) {
    class SurvosDataTable {
})(window, jQuery, Routing, swal);
*/

const $ = require('jquery'); // problematic!!!
let _ = global._;



console.warn('As expected, this is the SurvosDataTable.js in base-bundle/public/js!!');

require('datatables.net-bs5');
require('datatables.net-scroller-bs5');
// require('datatables.net-buttons-bs5');
require('datatables.net-select-bs5');

// hacks for linking this
import "core-js/stable";
import "regenerator-runtime/runtime";
xx



var commonButtons = {
    'json': {
        text: '<i class="fas fa-share"></i>',
        action: function (e, dt, node, config) {
            $(this).target = "_blank";
            let dtUrl = dt.ajax.url();
            var newWindow = window.open(dtUrl);
            newWindow.focus()
            return false;

            // var tableData = tables.table( $(this).parents('table') );
            // there's got to be a way to get $this datatable!
            // let url = $this.table().ajax.url();
            // let url = $sitesTable.ajax.url();
            // window.open(url);
            // return false;
        }
    },
    'selectVisible': {
        text: "<i class='fas fa-check'></i>Visible",
        key: {
            key: 'v',
            altKey: true
        }, action: function (e, dt, button, config) {
            dt.rows({page: 'current'}).select();
        }
    },
    'refresh': {
        text: '<i title="Refresh" class="fas fa-sync"></i>',
        key: {
            key: 'r',
            altKey: true
        },
        action: function (e, dt, button, config) {
            let tableId = dt.table().node().getAttribute('id');
            let t = dt.table();
            console.log(tableId, e, dt);
            console.log(t.ajax.ur);
            dt.clear().draw();
            t.ajax.reload();
        }
    },
};

export default class SurvosDataTable {
    constructor($el, columns, options) {
        if (!options) {
            options = {};
        }
        this.el = $el; // the <table> element, with an id and some data- attributes
        this.dataTableElement = false; // the DataTable object after it's been rendered

        this.columns = columns;
        this.searchField = options.searchField || 'name';

        this.url = $el.data('jsonLdUrl'); // ??
        if (this.url === 'undefined') {
            console.error('missing jsonLdUrl');
        }
        this.buttons = options.buttons ? options.buttons : [
            commonButtons['json'],
            commonButtons['refresh'],
            commonButtons['selectVisible']
        ];
        // @todo: add custom buttons

        console.log("Setting up " + $el.attr('id') + ' with ' + this.url);

        this.debug = false;

    }


    initFooter() {
        var footer = this.el.find('tfoot');
        if (footer.length > 0) {
            return; // do not initiate twice
        }

        var handleSelect = function (column) {
            var select = $('<select class="form-control"></select>');
            var createOptions = function(items) {
                select.empty();
                select.append('<option value="">Select option</option>');
                _.each(items, function (label, value) {
                    select.append('<option value="'+value+'">'+label+'</option>');
                });
            };

            if (column.filter.choices) {
                createOptions(column.filter.choices);
            } else if (column.filter.choices_url) {
                survosApp.apiRequest({url: column.filter.choices_url}).then(createOptions);
            }

            return select;
        };
        var handleInput = function (column) {
            var input = $('<input class="form-control" type="text">');
            input.attr('placeholder', column.filter.placeholder || column.data );
            return input;
        };

        this.debug && console.log('adding footer');
        var tr = $('<tr>');
        var that = this;
        console.log(this.columns);
        this.columns.forEach( (column, index) => {
            console.log(column, index);
            var td = $('<td>');
            if (column.filter !== undefined) {
                var el;
                if (column.filter === true || column.filter.type === 'input') {
                    el = handleInput(column);
                } else if (column.filter.type === 'select') {
                    el = handleSelect(column);
                }
                that.handleFieldSearch(this.el, el, index);

                td.append(el);
            }
            tr.append(td);
        });
        footer = $('<tfoot>');
        footer.append(tr);
        console.log(footer);
        this.el.append(footer);

        // see http://live.datatables.net/giharaka/1/edit for moving the footer to below the header
    }

    removeTableSelection($tableEl) {
        this.debug && console.log('removeTableSelection');
        var rows = $tableEl.DataTable().rows();
        if (rows.deselect) {
            rows.deselect();
        }
    }

    handleFieldSearch($table, $field, columnIndex) {
        var that = this;

        function getValue($el) {
            if ($el.is(':checkbox')) {
                return $el.filter(':checked').val();
            } else if ($el.is('select')) {
                return $el.val();
            } else {
                return $el.val();
            }
        }
        var filter =  ()  => {
            this.removeTableSelection($table);
            var value = getValue($field);
            console.log(value);
            $table.dataTable().api().column(columnIndex).search(value).draw();
        };
        $field.on('change', filter);
        if (getValue($field)) {
            filter();
        }
    }

    initDataTableWidgets() {
        let $table = this.el;
        var info = $table.dataTable().api().page.info();

        var card = $table.closest('.card'); // go up to the card
        var cardHeader = card.find('.card-header ');
        var buttons = card.find('.js-dt-buttons').first(); // the buttons div, defined in dom:
        // $("#source").appendTo("#destination");
        // destination.appendChild(source);
        // cardHeader.appendChild(buttons);
        buttons.detach();
        buttons.appendTo(cardHeader);
        console.warn('Buttons should now be in the card header.', cardHeader, buttons);
        // cardHeader.prepend(buttons);
        card.find('[data-totals="rows"]').html(info.recordsDisplay);
        card.find('[data-totals="progress"]').html(Math.round((info.start / info.recordsDisplay) * 100));
        this.debug && console.log('page info', info);
    }

    getAccessToken() {
        return $('body').data('accessToken');
    }

    dataTableParamsToApiPlatformParams(params) {
        var apiData = {
            page: 1
        };

        if (params.length) {
            apiData.itemsPerPage = params.length;
        }

        // this is the global search, should really be elasticsearch!  Or it could be the primary text field, like title, defined in the table, search-field
        if (params.search && params.search.value) {
            // @todo: what is the configured search field?

            apiData[this.searchField] = params.search.value;
            console.error(`searching by ${this.searchField} field only`, params, apiData);
        }

        params.columns.forEach(function(column, index) {
            if (column.search && column.search.value) {
                console.error(column);
                let value = column.search.value;
                // check the first character for a range filter operator

                // data is the column field, at least for right now.
                apiData[column.data] = value;
            }
        });

        if (params.start) {
            // was apiData.page = Math.floor(params.start / params.length) + 1;
            apiData.page = Math.floor(params.start / apiData.itemsPerPage) + 1;
        }

        // add our own filters
        // apiData['marking'] = ['fetch_success'];

        return apiData;
    }

    apiRequest(options, apiData) {

        if (typeof options === 'string') {
            options = {
                url: options
            };
        }

        // this was _.defaults(), changed to remove the _ dependency, BUT this may be problematic!!
        //  must be a better way!  https://www.sitepoint.com/es6-default-parameters/
        $.extend(options, {
            headers: {},
            dataType: 'json',
        });
        $.extend(options.headers, {
            Authorization: 'Bearer ' + this.getAccessToken(),
            Accept: 'application/ld+json'
        });

        /*
        console.log('------',apiData.itemsPerPage, params.length);
        if (params.start) {
            apiData.page = Math.floor(params.start / params.length) + 1;
        }
        */

        return (params, callback, settings) => {
            console.warn(params, callback, settings);
            // this is the data sent to API platform!

            options.data = this.dataTableParamsToApiPlatformParams(params);
            // this.debug &&
            console.log(params, options.data);
            console.log(`DataTables is requesting ${params.length} records starting at ${params.start}`, options.data);
            console.log(params, options.url, JSON.stringify(options.data));


            var jqxhr = $.ajax(options)
                .fail(function (jqXHR, textStatus, errorThrown) {
                    console.error(textStatus, errorThrown);
                })
                // use .dataFilter instead?  At some point, we should get rid of jQuery and use fetch.
                .done(  (hydraData, textStatus, jqXHR) => {

                    // get the next page from hydra
                    let next = hydraData["hydra:view"]['hydra:next'];
                    var total = hydraData['hydra:totalItems'];
                    var itemsReturned = hydraData['hydra:member'].length;
                    let apiOptions = options.data;

                    if (params.search.value) {
                        console.log(`dt search: ${params.search.value}`);
                    }

                    console.log(`dt request: ${params.length} starting at ${params.start}`);

                    let first = (apiOptions.page-1) * apiOptions.itemsPerPage;
                    let d = hydraData['hydra:member'];
                    // we could get rid of the first part if the starting_at is not at the beginning.
                    // d = d.slice(0, params.length - first);
                    //this.debug &&
                    // this one could be a partial, just json, etc.  Also we don't need it if it's on a page boundary  Usually okay on the first call
                    if ( next && (params.start > 0) ) { // && itemsReturned !== params.length
                        $.ajax({
                            url: next,
                            Accept: 'application/ld+json'
                        }).done( json =>
                        {
                            d = d.concat(json['hydra:member']);
                            this.debug && console.log(d.map( obj => obj.id ));
                            if (this.debug && console && console.log) {
                                console.log(`  ${itemsReturned} (of ${total}) returned, page ${apiOptions.page}, ${apiOptions.itemsPerPage}/page first: ${first} :`, d);
                            }
                            d = d.slice(params.start - first, (params.start - first) + params.length);

                            itemsReturned = d.length;

                            console.log(`2-page callback with ${total} records (${itemsReturned} items)`);
                            console.log(d);
                            callback({
                                draw: params.draw,
                                data: d,
                                recordsTotal: total,
                                recordsFiltered: total // was itemsReturned,
                            });

                            // console.log(params, hydraData, total);
                            // could check hydra:view to see if it's partial
                        });
                    } else {
                        console.log(`D${params.draw} Single page callback with ${itemsReturned} of ${total} records`);
                        console.warn(callback);
                        callback({
                            draw: params.draw,
                            data: d,
                            recordsTotal: total,
                          //  recordsFiltered: itemsReturned,
                            recordsFiltered: total,
                        });

                    }

                    // likely need caching, since in most cases we'll need two requests


                });

            /*
                jqxhr.on('xhr.dt', function ( e, settings, json, xhr ) {
                    console.log(e, settings, json);
                    // Note no return - manipulate the data directly in the JSON object.
                } );
            */

            // return jqxhr;
        }
    }


    dt() {
        return this.dataTableElement;
    }

    render() {
        console.warning('calling render()');
        $('<div class="loading">Loading</div>').appendTo('body');

        // var url = this.el.data('ajax'); // or options?
        $.ajaxSetup({ cache: true});

        // console.log(this.buttons);

        this.dataTableElement = this.el.DataTable({
            ajax: this.apiRequest({
                url: this.url
            }),
            orderCellsTop: true,
            rowId: 'id', // id, @id is also a candidate, it's a string rather than an int
            columns: this.columns,
            columnDefs: [{
                "targets": '_all',
                "defaultContent": "~~"
            }],
            initComplete: (settings, json) => {
                this.initDataTableWidgets();
                console.log('initComplete');
                $('div.loading').remove();
            },

            serverSide: true,
            processing: true,
            paging: true,
            scrollY: '50vh', // vh is percentage of viewport height, https://css-tricks.com/fun-viewport-units/
            // scrollY: true,
            deferRender: true,
            // displayLength: 10000, // not sure how to adjust the 'length' sent to the server
            pageLength: 15,
            dom: '<"js-dt-buttons"B><"js-dt-info"i>ftp',
            buttons: this.buttons,
            scroller: {
                // rowHeight: 20,
                displayBuffer: 20,
                loadingIndicator: true,
            }
        });
        this.debug && console.warn(this.el.attr('id') + ' rendered!');
        this.debug && console.log(this.url); // , this.el.data());
    }

}
