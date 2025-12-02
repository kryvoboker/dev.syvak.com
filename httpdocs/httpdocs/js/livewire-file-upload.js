document.addEventListener('livewire:init', () => {
    // Hook into Livewire's file upload manager
    Livewire.hook('morph.updated', ({ el, component }) => {
        // Find all file inputs managed by Livewire
        const fileInputs = el.querySelectorAll('input[type="file"][wire\\:model]');

        fileInputs.forEach(input => {
            input.addEventListener('change', function(e) {
                const files = e.target.files;
                if (files.length > 0) {
                    // Store original file names in data attributes
                    Array.from(files).forEach((file, index) => {
                        const originalName = file.name;
                        // Store in a way that Livewire can access it
                        if (!input.dataset.originalNames) {
                            input.dataset.originalNames = JSON.stringify([]);
                        }
                        const names = JSON.parse(input.dataset.originalNames);
                        names.push(originalName);
                        input.dataset.originalNames = JSON.stringify(names);
                    });
                }
            });
        });
    });

    // Intercept the upload process
    Livewire.hook('commit', ({ component, commit, respond, succeed, fail }) => {
        if (component.uploadManager) {
            const originalUpload = component.uploadManager.upload;

            component.uploadManager.upload = function(file, uploadHandler) {
                // Preserve original file name
                const originalFileName = file.name;

                // Call original upload method
                const result = originalUpload.call(this, file, uploadHandler);

                // Store original name in the upload object
                if (result && result.then) {
                    result.then(upload => {
                        if (upload) {
                            upload.originalFileName = originalFileName;
                        }
                    });
                }

                return result;
            };
        }
    });
});