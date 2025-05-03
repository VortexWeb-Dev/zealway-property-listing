<div class="text-center mb-3">
    <input type="file" class="d-none" id="photos" name="photos[]" accept="image/*" multiple>
    <label for="photos" class="dropzone d-block">
        <div class="cursor-pointer p-12 flex justify-center bg-white border border-gray-300 rounded-xl" data-hs-file-upload-trigger="">
            <div class="text-center">
                <span class="inline-flex justify-center items-center size-16 bg-gray-100 text-gray-800 rounded-full">
                    <svg class="shrink-0 size-6" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="17 8 12 3 7 8"></polyline>
                        <line x1="12" x2="12" y1="3" y2="15"></line>
                    </svg>
                </span>
                <div class="mt-4 flex flex-wrap justify-center text-sm leading-6 text-gray-600">
                    <span class="pe-1 font-medium text-gray-800">Drop your file here or</span>
                    <span class="bg-white font-semibold text-blue-600 hover:text-blue-700 rounded-lg decoration-2 hover:underline focus-within:outline-none focus-within:ring-2 focus-within:ring-blue-600 focus-within:ring-offset-2">browse</span>
                </div>
                <p class="mt-1 text-xs text-gray-400">Pick a file up to 10MB.</p>
            </div>
        </div>
    </label>
    <p class="text-left text-xs text-red-500 font-semibold mt-2 hidden" id="photosMessage"></p>
</div>
<div id="photoPreviewContainer" class="photoPreviewContainer"></div>
<input type="hidden" id="selectedImages" name="selectedImages" />

<script>
    document.addEventListener('DOMContentLoaded', function() {
        let imageLinks = []; // Base64 URLs for preview
        let selectedFiles = []; // File objects for upload
        const previewContainer = document.getElementById('photoPreviewContainer');
        const selectedImagesInput = document.getElementById('selectedImages');
        const dropzone = document.querySelector('.dropzone');

        // Drag-and-drop event listeners
        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault(); // Prevent default to allow drop
            dropzone.querySelector('div').classList.add('bg-gray-200'); // Visual feedback
        });

        dropzone.addEventListener('dragleave', () => {
            dropzone.querySelector('div').classList.remove('bg-gray-200'); // Remove visual feedback
        });

        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.querySelector('div').classList.remove('bg-gray-200'); // Reset visual feedback
            const files = Array.from(e.dataTransfer.files); // Get dropped files
            handleFiles(files); // Process dropped files
        });

        function addSwapy() {
            const swapy = Swapy.createSwapy(previewContainer, {
                animation: 'spring',
                swapMode: 'hover'
            });

            swapy.onSwapEnd((event) => {
                imageLinks = [];
                event.slotItemMap.asMap.forEach((item) => {
                    let element = document.querySelector(`[data-swapy-item="${item}"]`);
                    imageLinks.push(element.querySelector('img').src);
                });
                updateSelectedImagesInput();
            });
        }

        document.getElementById("photos").addEventListener("change", function(event) {
            const files = Array.from(event.target.files);
            handleFiles(files); // Process selected files
        });

        // Common function to handle files (from input or drop)
        function handleFiles(files) {
            if (files.length < 3) {
                document.getElementById("photosMessage").classList.remove('hidden');
                document.getElementById("photosMessage").textContent = `Please select at least 3 images. You have selected ${files.length}.`;
                return;
            }

            selectedFiles = [];
            imageLinks = [];
            document.getElementById("photosMessage").classList.remove('hidden');
            document.getElementById("photosMessage").classList.remove('text-red-500');
            document.getElementById("photosMessage").classList.add('text-blue-500');
            document.getElementById("photosMessage").textContent = `Loading images...`;

            files.forEach((file) => {
                if (file.size >= 10 * 1024 * 1024) {
                    document.getElementById("photosMessage").classList.remove('hidden');
                    document.getElementById("photosMessage").classList.add('text-red-500');
                    document.getElementById("photosMessage").textContent = `The file "${file.name}" is too large (10MB or greater).`;
                } else if (!selectedFiles.some((f) => f.name === file.name)) {
                    selectedFiles.push(file);
                }
            });

            updatePhotoPreview();
        }

        function updatePhotoPreview() {
            imageLinks = []; // Reset imageLinks
            const promises = selectedFiles.map((file) => {
                return new Promise((resolve) => {
                    const reader = new FileReader();
                    reader.readAsDataURL(file);
                    reader.onload = function(e) {
                        imageLinks.push(e.target.result);
                        resolve();
                    };
                });
            });

            Promise.all(promises).then(() => {
                previewImages(imageLinks);
            });
        }

        function previewImages(imageLinks) {
            previewContainer.innerHTML = "";
            let row = document.createElement('div');
            row.classList.add('shuffle-row');

            imageLinks.forEach((link, i) => {
                if (i % 3 === 0 && i !== 0) {
                    previewContainer.appendChild(row);
                    row = document.createElement('div');
                    row.classList.add('shuffle-row');
                }

                const slot = document.createElement('div');
                slot.classList.add('slot');
                slot.setAttribute('data-swapy-slot', i + 1);

                const item = document.createElement('div');
                item.classList.add('item');
                item.setAttribute('data-swapy-item', String.fromCharCode(97 + i));

                const image = document.createElement('div');
                const img = document.createElement('img');
                img.src = link;

                image.appendChild(img);
                item.appendChild(image);
                slot.appendChild(item);

                const removeBtn = document.createElement("button");
                removeBtn.innerHTML = "×";
                removeBtn.classList.add("position-absolute", "top-0", "end-0", "btn", "btn-sm", "btn-danger", "m-1");
                removeBtn.style.zIndex = "1";
                removeBtn.onclick = function() {
                    selectedFiles.splice(i, 1); // Remove corresponding file
                    imageLinks.splice(i, 1); // Remove corresponding link
                    previewImages(imageLinks); // Re-render preview
                    updateSelectedImagesInput();
                };

                item.appendChild(removeBtn);
                row.appendChild(slot);
            });

            previewContainer.appendChild(row);
            addSwapy();
            document.getElementById("photosMessage").classList.add('hidden');
            updateSelectedImagesInput();
        }

        function updateSelectedImagesInput() {
            selectedImagesInput.value = JSON.stringify(imageLinks);
        }
    });
</script>