//New datatable library to reduce boilerplate code.
//This library should store all the states and actions related to a datatable. Which includes: searching, filtering, ordering, pagination and exportation.
//As well as rendering the HTML content of the table. Some components such as export button can be defined outside this library to enable greater flexibility.
//Components at the time: Search Input, Advanced Filter Modal, Date Filter modal, Export Button
class Table {
    dataTable;
    endpoint;
    params = {
        currentPage: 0,
        orderBy: null,
        searchKey: "",
        sort: "desc",
        dateFilter: {
            type: "today",
            from: "",
            to: "",
        }
    }

    constructor(container) {
    }

    load() {
    }

    draw() {
    }

    export() {
    }
}