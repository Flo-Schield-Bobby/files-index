window.addEventListener('load', function (event) {
    // Init filter
    var filtrableList = new Vue({
        el: 'body',
        data: {
            files: [],
            sortKey: '',
            type: '',
            query: '',
            reversed: {}
        },
        created: function () {
            if (data && 'files' in data) {
                data.files.forEach(function (file, index) {
                    this.files.$set(index, {
                        name: file.filename,
                        extension: file.extension,
                        size: file.filesize,
                        type: file.filetype,
                        iconClass: file.iconClass,
                        previewLink: file.previewLink,
                        downloadLink: file.downloadLink
                    });
                }, this);

                window.data = undefined;

                var dataScript = document.getElementById('data-script');

                if (dataScript) {
                    dataScript.remove();
                }
            }
        },
        compiled: function () {
            this.reversed.$add('type', false);
            this.reversed.$add('name', false);
        },
        methods: {
            onFilterClick: function (event) {
                event.preventDefault();
                
                if (this.type == event.target.dataset.filter) {
                    this.type = '';
                } else {
                    this.type = event.target.dataset.filter;
                }
            },
            sortBy: function (key) {
                this.sortKey = key;
                this.reversed[key] = !this.reversed[key];
            }
        }
    });
});