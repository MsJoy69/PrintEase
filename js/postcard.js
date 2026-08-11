// =======================
// CANCELLATION GUARD LOGIC
// =======================
let targetUrl = '';
let postcardFiles = []; // Use a separate array for postcard files

// --- AUTO-FIX: Prevent GET Request Issues on Page Load ---
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('postcardForm');
    if (form) {
        form.setAttribute('method', 'POST');
        form.setAttribute('enctype', 'multipart/form-data');
    }
    // Set initial state for custom size
    toggleCustomSize();
    // Set min value for quantity
    const qtyInput = document.getElementById('quantity');
    if(qtyInput && !qtyInput.value) {
        qtyInput.value = "1"; // Changed from 25
    }
});

function isFormDirty() {
    const sizeEl = document.getElementById("size");
    const paperTypeEl = document.getElementById("paperType");
    const finishEl = document.getElementById("finish");
    const quantityEl = document.getElementById("quantity");
    const customSizeEl = document.getElementById("customSize");

    if (sizeEl && sizeEl.value && sizeEl.value !== 'Select Size' && sizeEl.value !== '') return true;
    if (paperTypeEl && paperTypeEl.value && paperTypeEl.value !== 'Select' && paperTypeEl.value !== '') return true;
    if (finishEl && finishEl.value && finishEl.value !== 'Select' && finishEl.value !== '') return true;
    if (quantityEl && parseInt(quantityEl.value, 10) > 1) return true; // Changed from 25
    if (sizeEl && sizeEl.value === 'Custom Size' && customSizeEl && customSizeEl.value.trim() !== '') return true;
    if (postcardFiles && postcardFiles.length > 0) return true;

    return false;
}

function checkCancellation(url) {
    if (typeof event !== 'undefined') event.preventDefault();
    else if(window.event) window.event.preventDefault();

    const modal = document.getElementById('cancelOrderModal');

    if (isFormDirty()) {
        targetUrl = url;
        restoreCancelModalContent();
        if (modal) {
            modal.style.display = 'flex';
            setTimeout(() => modal.classList.add('is-active'), 10);
        }
    } else {
        window.location.href = url;
    }
}

function closeModal() {
    const modal = document.getElementById('cancelOrderModal');
    if (modal) {
            modal.classList.remove('is-active');
            setTimeout(() => modal.style.display = 'none', 300);
    }
}

function continueNavigation() {
    closeModal();
    if (targetUrl) {
        window.location.href = targetUrl;
    }
}

function restoreCancelModalContent() {
    const modal = document.getElementById('cancelOrderModal');
    if (!modal) return;
    const content = modal.querySelector('.modal-content');
    if (content) {
        // Check if it's already the correct modal
        if (content.querySelector('h3').textContent === "Cancel Order Confirmation") return;

        // Restore default cancellation content
        content.innerHTML = `
            <span class="modal-close" onclick="closeModal()">&times;</span>
            <h3>Cancel Order Confirmation</h3>
            <p>You have started an order. Do you want to <strong>cancel</strong> and <strong>leave</strong> this page? All entered details will be lost.</p>
            <div class="modal-buttons">
                <button id="modalContinueBtn" class="modal-btn continue" onclick="continueNavigation()">YES, CANCEL ORDER</button>
                <button class="modal-btn stay" onclick="closeModal()">NO, CONTINUE ORDERING</button>
            </div>
        `;
    }
}

window.onclick = function(event) {
    const modal = document.getElementById('cancelOrderModal');
    if (event.target === modal) {
        closeModal();
    }
}


// =======================
// POSTCARD ORDER
// =======================

const postcardFileInput = document.getElementById('fileInput');
const postcardUploadBox = document.getElementById('uploadBox');

// Create error element
const postcardError = document.createElement('p');
postcardError.style.color = "#d9534f";
postcardError.style.fontSize = "14px";
postcardError.style.marginTop = "8px";
postcardError.style.display = "none";
if (postcardUploadBox) postcardUploadBox.insertAdjacentElement("afterend", postcardError);

if (postcardFileInput) {
    postcardFileInput.addEventListener('change', e => {
        const files = Array.from(e.target.files);
        const validFiles = [];
        const maxSize = 5 * 1024 * 1024; // 5MB

        postcardError.style.display = "none";
        postcardError.textContent = "";

        const readPromises = files.map(file => {
            return new Promise(resolve => {
                if (file.size > maxSize) {
                    resolve({ error: `${file.name} exceeds 5MB file limit.` });
                    return;
                }
                if (file.type && file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = e => resolve({ file, dataUrl: e.target.result });
                    reader.readAsDataURL(file);
                } else {
                    resolve({ file, dataUrl: null });
                }
            });
        });

        Promise.all(readPromises).then(results => {
            for (const r of results) {
                if (r.error) {
                    if (!postcardError.textContent) {
                        postcardError.textContent = `❌ ${r.error}`;
                        postcardError.style.display = "block";
                    }
                    continue;
                }
                if (r.file) {
                    validFiles.push(r);
                }
            }
            
            // Get current main image src BEFORE adding new files
            const currentMain = document.getElementById('mainPostcardPreview');
            const currentMainSrc = currentMain ? currentMain.src : null;

            postcardFiles = postcardFiles.concat(validFiles);
            renderPostcardPreviews();

            // Restore main image src if it existed
            if (currentMain && currentMainSrc) {
                currentMain.src = currentMainSrc;
            }
        });
    });
}

// =======================
// RENDER POSTCARD PREVIEWS (with Remove Button)
// =======================
function renderPostcardPreviews() {
    if (!postcardUploadBox) return;

    // Force visibility
    postcardUploadBox.style.display = 'block';
    postcardUploadBox.style.height = 'auto';

    if (!postcardFiles || postcardFiles.length === 0) {
        postcardUploadBox.textContent = "Accepted file types: .png, .jpg, .pdf, .ai, .psd (Max: 5MB each)";
        return;
    }

    // 1. Slider Wrapper (Dashed Box)
    const wrapper = document.createElement('div');
    wrapper.className = 'slider-wrapper';
    wrapper.style.position = 'relative';
    wrapper.style.overflow = 'hidden';
    // wrapper.style.width = '100%';
    // wrapper.style.maxWidth = '100%'; // Let CSS handle this
    wrapper.style.height = '220px';
    wrapper.style.margin = '0 auto';
    wrapper.style.border = '2px dashed #999';
    wrapper.style.borderRadius = '8px';
    wrapper.style.display = 'flex';
    wrapper.style.alignItems = 'center';
    wrapper.style.justifyContent = 'center';
    wrapper.style.background = '#f9f9f9';

    // 2. Slider Container
    const container = document.createElement('div');
    container.className = 'slider-container';
    container.style.display = 'flex';
    container.style.transition = 'transform 0.45s ease';
    container.style.height = '100%';

    postcardFiles.forEach((entry, idx) => {
        const slide = document.createElement('div');
        slide.style.flex = '0 0 100%';
        slide.style.display = 'flex';
        slide.style.alignItems = 'center';
        slide.style.justifyContent = 'center';
        slide.style.height = '100%';

        const contentWrapper = document.createElement('div');
        contentWrapper.style.position = 'relative';
        contentWrapper.style.display = 'inline-block';

        // Create Remove Button
        const removeBtn = document.createElement('button');
        removeBtn.innerHTML = '&times;';
        removeBtn.style.position = 'absolute';
        removeBtn.style.top = '-10px';
        removeBtn.style.right = '5px';
        removeBtn.style.background = '#d9534f';
        removeBtn.style.color = 'white';
        removeBtn.style.border = 'none';
        removeBtn.style.borderRadius = '50%';
        removeBtn.style.width = '24px';
        removeBtn.style.height = '24px';
        removeBtn.style.cursor = 'pointer';
        removeBtn.style.display = 'flex';
        removeBtn.style.alignItems = 'center';
        removeBtn.style.justifyContent = 'center';
        removeBtn.style.fontSize = '16px';
        removeBtn.style.fontWeight = 'bold';
        removeBtn.style.boxShadow = '0 2px 4px rgba(0,0,0,0.2)';
        removeBtn.style.zIndex = '20';

        // Remove Logic
        removeBtn.onclick = (e) => {
            e.stopPropagation();
            e.preventDefault();
            postcardFiles.splice(idx, 1); // Remove from array
            renderPostcardPreviews(); // Re-render
        };

        if (entry.dataUrl) {
            const img = document.createElement('img');
            img.src = entry.dataUrl;
            img.alt = `Preview ${idx + 1}`;
            img.style.maxWidth = '200px';
            img.style.maxHeight = '180px';
            img.style.objectFit = 'contain';
            img.style.boxShadow = '0 2px 5px rgba(0,0,0,0.1)';
            img.style.cursor = 'pointer';
            img.style.display = 'block';
            
            img.onclick = function() {
                changePreview(img); // Link to main preview changer
            };
            
            contentWrapper.appendChild(img);
        } else {
            // Show icon and name for non-image files
            const p = document.createElement('div');
            let iconClass = "fa-solid fa-file"; // default
            if(entry.file.name.endsWith('.pdf')) iconClass = "fa-solid fa-file-pdf";
            if(entry.file.name.endsWith('.ai')) iconClass = "fa-solid fa-file-image";
            if(entry.file.name.endsWith('.psd')) iconClass = "fa-solid fa-file-image";
            
            p.innerHTML = `<i class="${iconClass}" style="font-size: 40px; color: #555;"></i><br><br>${entry.file.name}`;
            p.style.textAlign = 'center';
            p.style.color = '#555';
            p.style.padding = '20px';
            p.style.cursor = 'default';
            p.style.wordBreak = 'break-all';
            p.style.maxWidth = '180px';
            contentWrapper.appendChild(p);
        }

        contentWrapper.appendChild(removeBtn);
        slide.appendChild(contentWrapper);
        container.appendChild(slide);
    });

    wrapper.appendChild(container);

    postcardUploadBox.innerHTML = '';
    postcardUploadBox.appendChild(wrapper);

    // Arrows
    if (postcardFiles.length > 1) addSliderArrows(wrapper, container);

    // 3. Price Text (5 pesos per file)
    const info = document.createElement('div');
    info.style.marginTop = '8px';
    info.style.textAlign = 'center';
    info.style.color = '#555';
    info.style.fontSize = '15px';
    info.innerHTML = `File Upload (${postcardFiles.length} files) <span style="color: #333; font-weight: bold;">₱${(postcardFiles.length * 5).toFixed(2)}</span>`;

    postcardUploadBox.appendChild(info);
}

// =======================
// SLIDER ARROWS (Helper)
// =======================
function addSliderArrows(wrapper, container) {
    let currentIndex = 0;
    const totalSlides = Math.max(1, container.children.length);

    // container.style.width = `${totalSlides * 100}%`; // This was breaking layout

    // ✅ DELETED: This block was breaking the slide widths
    // Array.from(container.children).forEach(slide => {
    //     slide.style.width = `${100 / totalSlides}%`;
    // });

    const leftArrow = document.createElement('button');
    leftArrow.innerHTML = '&#10094;';
    const rightArrow = document.createElement('button');
    rightArrow.innerHTML = '&#10095;';

    const commonStyle = `
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        font-size: 24px;
        background: rgba(60, 60, 60, 0.8);
        color: #fff;
        border: none;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 10;
        transition: background 0.3s;
    `;

    leftArrow.style.cssText = commonStyle + 'left: 10px;';
    rightArrow.style.cssText = commonStyle + 'right: 10px;';

    leftArrow.onmouseover = () => leftArrow.style.background = '#000';
    leftArrow.onmouseout = () => leftArrow.style.background = 'rgba(60, 60, 60, 0.8)';
    rightArrow.onmouseover = () => rightArrow.style.background = '#000';
    rightArrow.onmouseout = () => rightArrow.style.background = 'rgba(60, 60, 60, 0.8)';

    leftArrow.addEventListener('click', e => {
        e.preventDefault();
        e.stopPropagation();
        currentIndex = (currentIndex > 0) ? currentIndex - 1 : totalSlides - 1;
        // ✅ FIXED: Changed calculation to slide by 100%
        container.style.transform = `translateX(-${currentIndex * 100}%)`;
    });

    rightArrow.addEventListener('click', e => {
        e.preventDefault();
        e.stopPropagation();
        currentIndex = (currentIndex < totalSlides - 1) ? currentIndex + 1 : 0;
        // ✅ FIXED: Changed calculation to slide by 100%
        container.style.transform = `translateX(-${currentIndex * 100}%)`;
    });

    wrapper.appendChild(leftArrow);
    wrapper.appendChild(rightArrow);
}


// =======================
// SUBMIT ORDER
// =======================
function submitOrder(orderSpeed) {
    if (typeof event !== 'undefined') event.preventDefault();
    else if (window.event) window.event.preventDefault();

    orderSpeed = typeof orderSpeed === 'string' ? orderSpeed : 'standard';
    console.log("🚀 Submitting Postcard Order via JS...");

    const form = document.getElementById("postcardForm");
    if (!form) {
        console.error("❌ Error: Form 'postcardForm' not found!");
        return;
    }

    const sizeEl = document.getElementById("size");
    const paperTypeEl = document.getElementById("paperType");
    const finishEl = document.getElementById("finish");

    // Validation
    if (!sizeEl.value || sizeEl.value === "Select Size" ||
        !paperTypeEl.value || paperTypeEl.value === "Select" ||
        !finishEl.value || finishEl.value === "Select") {

        const validationModal = document.getElementById('cancelOrderModal');
        if (validationModal) {
                const content = validationModal.querySelector('.modal-content');
                content.innerHTML = `
                <span class="modal-close" onclick="closeModal()">&times;</span>
                <h3>Required Fields Missing</h3>
                <p>Please complete all required fields (Size, Paper Type, Finish) before submitting your order.</p>
                <div class="modal-buttons">
                        <button class="modal-btn stay" onclick="closeModal()">OK</button>
                </div>
            `;
            validationModal.style.display = 'flex';
            setTimeout(() => validationModal.classList.add('is-active'), 10);
        } else {
                alert("Please complete all required fields.");
        }
        return;
    }

    // Add orderSpeed to form
    let speedInput = form.querySelector('input[name="orderSpeed"]');
    if (!speedInput) {
        speedInput = document.createElement('input');
        speedInput.type = 'hidden';
        speedInput.name = 'orderSpeed';
        form.appendChild(speedInput);
    }
    speedInput.value = orderSpeed;

    // Set form attributes
    form.method = "POST";
    form.enctype = "multipart/form-data";

    // Attach files to the file input
    const fileInput = document.getElementById('fileInput');
    fileInput.name = "designFile[]";

    console.log("📂 Postcard Files Array:", postcardFiles);

    if (postcardFiles.length > 0) {
        const dataTransfer = new DataTransfer();
        postcardFiles.forEach(item => {
            if (item.file) dataTransfer.items.add(item.file);
        });
        fileInput.files = dataTransfer.files;
    }

    form.action = "confirmation.php";
    form.submit();
}

// =======================
// HELPER FUNCTIONS
// =======================

function toggleCustomSize() {
    const sizeSelect = document.getElementById('size');
    const customInputGroup = document.getElementById('customSizeInputGroup');
    const customInput = document.getElementById('customSize');

    if (!customInputGroup || !customInput) return;

    if (sizeSelect.value === 'Custom Size') {
        customInputGroup.style.display = 'block';
        customInput.setAttribute('required', 'required');
    } else {
        customInputGroup.style.display = 'none';
        customInput.removeAttribute('required');
        customInput.value = '';
    }
}

// Postcard Qty: Min 1, Step 1
function increaseQty() {
    const qtyInput = document.getElementById('quantity');
    if (!qtyInput) return;
    let currentQty = parseInt(qtyInput.value || 0, 10);
    if(isNaN(currentQty) || currentQty < 1) { // Changed from 25
        qtyInput.value = 1; // Changed from 25
    } else {
        qtyInput.value = currentQty + 1; // Changed from + 25
    }
}

function decreaseQty() {
    const qtyInput = document.getElementById('quantity');
    if (!qtyInput) return;
    let currentQty = parseInt(qtyInput.value || 0, 10);
    if (currentQty > 1) { // Changed from 25
        qtyInput.value = currentQty - 1; // Changed from - 25
    } else {
        qtyInput.value = 1; // Changed from 25
    }
}

function changePreview(thumbnail) {
    const mainPreview = document.getElementById('mainPostcardPreview');
    if (mainPreview && thumbnail && thumbnail.src) {
        mainPreview.src = thumbnail.src;
        
        // Update active thumbnail
        const thumbs = document.querySelectorAll('.postcard-preview .thumb');
        thumbs.forEach(t => t.classList.remove('active'));
        if (thumbnail.classList.contains('thumb')) {
            thumbnail.classList.add('active');
        }
    }
}