window.addEventListener('load', function (event) {
    // Init filter
    var filtrableList = new Vue({
        el: 'body',
        data: {
            files: [],
            query: ''
        },
        created: function () {
            if (data && 'files' in data) {
                data.files.forEach(function (file, index) {
                    this.files.$set(index, {
                        name: file.filename,
                        extension: file.extension,
                        size: file.filesize,
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
        }
    });
});