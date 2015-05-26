window.addEventListener('load', function (event) {
    // Init filter
    var filtrableList = new Vue({
        el: 'body',
        data: {
            files: [],
            query: ''
        }
    });

    var filesList = document.querySelector('#files-list');

    if (filesList) {
        var files = filesList.querySelectorAll('.file.static');
        var filesLength = files.length;
        var index;

        for (index = 0; index < filesLength; index++) {
            var file = files[index];
            
            filtrableList.files.$set(index, {
                name: file.dataset.filename,
                extension: file.dataset.extension,
                size: file.dataset.filesize,
                iconClass: file.dataset.iconClass,
                previewLink: file.dataset.previewLink,
                downloadLink: file.dataset.downloadLink
            });
            
            file.remove();
        }

        filesList.classList.add('compiled');
    }
});