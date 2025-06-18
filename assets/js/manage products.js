
        function saveDraft() {
            // Add a hidden input for draft action
            const form = document.getElementById('productForm');
            const draftInput = document.createElement('input');
            draftInput.type = 'hidden';
            draftInput.name = 'save_as_draft';
            draftInput.value = '1';
            form.appendChild(draftInput);
            
            // Submit the form
            form.submit();
        
    
    // Image preview for newly uploaded files
    document.getElementById('product_images').addEventListener('change', function(e) {
        const container = document.getElementById('imagePreviewContainer');
        const files = e.target.files;

        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            if (!file.type.match('image.*')) continue;

            const reader = new FileReader();
            reader.onload = function(e) {
                const previewItem = document.createElement('div');
                previewItem.className = 'image-preview-item mb-3';
                previewItem.innerHTML = `
                <img src="${e.target.result}" class="img-thumbnail" style="max-height: 100px;">
                <div class="d-flex justify-content-between mt-2">
                    <div class="form-check">
                        <input class="form-check-input primary-image" type="radio" name="primary_image" 
                               value="new_${i}">
                        <label class="form-check-label">Primary</label>
                    </div>
                    <button type="button" class="btn btn-sm btn-danger remove-image">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            `;
                container.appendChild(previewItem);

                // Add remove functionality
                previewItem.querySelector('.remove-image').addEventListener('click', function() {
                    container.removeChild(previewItem);
                });
            };
            reader.readAsDataURL(file);
        }
    });

    // Delete existing image
    document.querySelectorAll('.delete-image').forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this image?')) {
                const imageId = this.getAttribute('data-image-id');
                const previewItem = this.closest('.image-preview-item');

                // Create hidden input to mark image for deletion
                const deleteInput = document.createElement('input');
                deleteInput.type = 'hidden';
                deleteInput.name = 'deleted_images[]';
                deleteInput.value = imageId;
                document.getElementById('productForm').appendChild(deleteInput);

                // Remove preview
                previewItem.remove();
            }
        });
    });
        }