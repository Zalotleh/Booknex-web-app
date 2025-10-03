class GenericTable {
    tableId = '';
    table = '';
    tbody = '';

    columns = [];
    data = [];
    onSave = () => {};
    onDelete = () => {};
    loadType = 'row';
    onModalLoad = () => {};
    addNewButtonSelector = null;
    saveButtonSelector = null;

    constructor(options) {
        this.tableId = options.tableId;
        this.columns = options.columns;
        this.data = options.data;
        this.onSave = options.onSave;
        this.onDelete = options.onDelete;
        this.loadType = options.loadType;
        this.onModalLoad = options.onModalLoad;
        this.addNewButtonSelector = options.addNewButtonSelector;
        this.saveButtonSelector = options.saveButtonSelector;

        this.table = $(`#${this.tableId}`);
        this.tbody = this.table.find('tbody');

        // Initialize the table with data
        this.refreshTable();

        if (this.addNewButtonSelector) {
            this.initAddNewAction();
        }

        // Bind table events
        this.bindEvents();
    }

    initAddNewAction() {
        this.addNewButtonSelector.on('click', () => {
            if (this.loadType !== 'modal' || !this.onModalLoad) {
                this.addNewRow();
                return;
            }

            this.onModalLoad(null);
        });

        if (this.loadType === 'modal' && this.onModalLoad) {
            this.saveButtonSelector.on('click', () => {
                const data = {};

                this.columns.forEach(col => {
                    data[col.key] = $(`#${col.key}`).val();
                });

                this.onSave(data);
            });
        }
    }

    updateTableWithRow(row) {
        if (!row) {
            return;
        }

        const existingIndex = this.data.findIndex(item => Number(item.id) === Number(row.id));

        if (existingIndex !== -1) {
            this.data[existingIndex] = row;
        } else {
            this.data.unshift(row);
        }

        this.refreshTable();
    }

    refreshTable() {
        this.tbody.empty();

        this.data.forEach(item => {
            this.tbody.append(this.createNonEditableRow(item));
        });
    }

    createEditableRow(data = {}) {

        const tr = $('<tr>');
        tr.data('id', data.id || 0);

        this.columns.forEach(col => {
            const td = $('<td>');
            const value = data[col.key];

            if (col.editable) {
                if (col.type === 'select' && col.options) {
                    const select = $('<select>', {
                        class: 'form-control',
                        'data-field': col.key
                    });
                    col.options.forEach(option => {
                        const optionElement = $('<option>', {
                            value: option.value,
                            text: option.label,
                            selected: data[col.key] === option.value
                        });
                        select.append(optionElement);
                    });
                    td.append(select);
                } else {

                    const input = $('<input>', {
                        type: 'text',
                        class: 'form-control',
                        'data-field': col.key,
                        value: value
                    });
                    td.append(input);
                }
            } else {
                td.text(value || '');
            }
            tr.append(td);
        });

        // Add action buttons
        const actionsTd = $('<td>');
        actionsTd.append(`
            <button type="button" class="btn btn-default btn-xs save-row">
                <i class="fa fa-check"></i>
            </button>
            <button type="button" class="btn btn-default btn-xs cancel-row">
                <i class="fa fa-times"></i>
            </button>
        `);
        tr.append(actionsTd);

        return tr;
    }

    createNonEditableRow(data) {
        const tr = $('<tr>');
        tr.data('id', data.id);

        // Store all data in the row
        Object.keys(data).forEach(key => {
            tr.data(key, data[key]);
        });

        this.columns.forEach(col => {
            const value = col.fn ? col.fn(data[col.key]) : data[col.key];
            const td = $('<td>').html(value ?? '');

            tr.append(td);
        });

        // Add action buttons
        const actionsTd = $('<td>');
        actionsTd.append(`
            <button type="button" class="btn btn-default btn-xs edit-row">
                <i class="fa fa-pencil-alt"></i>
            </button>
            <button type="button" class="btn btn-default btn-xs delete-row">
                <i class="fa fa-trash"></i>
            </button>
        `);
        tr.append(actionsTd);

        return tr;
    }

    bindEvents() {
        // Save row
        this.table.on('click', '.save-row', (e) => {
            const tr = $(e.target).closest('tr');
            const data = {
                id: tr.data('id')
            };

            // Collect data from editable fields
            this.columns.forEach(col => {
                if (col.editable) {
                    data[col.key] = tr.find(`[data-field="${col.key}"]`).val();
                } else {
                    data[col.key] = tr.data(col.key);
                }
            });

            this.onSave && this.onSave(data);
        });

        // Cancel editing
        this.table.on('click', '.cancel-row', (e) => {
            const tr = $(e.target).closest('tr');
            const id = tr.data('id');

            if (id === 0) {
                tr.remove();
            } else {
                const originalData = this.data.find(item => item.id === id);
                if (originalData) {
                    tr.replaceWith(this.createNonEditableRow(originalData));
                }
            }
        });

        // Edit row
        this.table.on('click', '.edit-row', (e) => {
            if (this.loadType === 'modal' && this.onModalLoad) {
                const row = $(e.currentTarget).closest('tr');

                if (!row) {
                    return;
                }

                const rowData = this.columns.reduce((acc, col) => {
                    acc[col.key] = row.data(col.key);
                    return acc;
                }, {});

                this.onModalLoad(rowData);

                return;
            }

            const tr = $(e.target).closest('tr');
            const data = {};

            this.columns.forEach(col => {
                data[col.key] = tr.data(col.key);
            });

            tr.replaceWith(this.createEditableRow(data));
        });

        // Delete row
        this.table.on('click', '.delete-row', (e) => {
            const tr = $(e.target).closest('tr');
            const id = tr.data('id');

            if (this.onDelete) {
                booknetic.confirm(booknetic.__('are_you_sure_want_to_delete'), 'danger', 'trash', () => {
                    this.onDelete(id, () => {
                        this.data = this.data.filter(item => item.id !== id);
                        this.refreshTable();
                    });
                });
            }
        });
    }

    addNewRow() {
        this.tbody.prepend(this.createEditableRow());
    }

    setData(response) {
        this.data = response;
        this.refreshTable();
    }
}
