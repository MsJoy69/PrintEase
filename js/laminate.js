// =======================
// LAMINATE ORDER
// =======================
let laminateFiles = [];
let targetUrl = ''; // For cancellation guard

const laminateFileInput = document.getElementById('laminateFileInput');
const laminateUploadBox = document.getElementById('laminateUploadBox');

// =======================
// CANCELLATION GUARD LOGIC (NEW)
// =======================
function isFormDirty() {
    const sizeEl = document.getElementById("laminateSize");
    const typeEl = document.getElementById("laminateType");
    const thicknessEl = document.getElementById("thickness");
    const quantityEl = document.getElementById("laminateQuantity");

    if (sizeEl && sizeEl.value && sizeEl.value !== 'Select Size' && sizeEl.value !== '') return true;
    if (typeEl && typeEl.value && typeEl.value !== 'Select Type' && typeEl.value !== '') return true;
    if (thicknessEl && thicknessEl.value && thicknessEl.value !== 'Select Thickness' && thicknessEl.value !== '') return true;
    if (quantityEl && parseInt(quantityEl.value, 10) > 1) return true;
    if (laminateFiles && laminateFiles.length > 0) return true; // Check uploaded files

    return false;
}

function checkCancellation(url) {
    if (typeof event !== 'undefined') event.preventDefault();
    else if(window.event) window.event.preventDefault();

    const modal = document.getElementById('cancelOrderModal');

    if (isFormDirty()) {
        targetUrl = url;
        restoreCancelModalContent(); // Add this
        if (modal) {
            modal.style.display = 'flex'; // Use 'flex'
            setTimeout(() => modal.classList.add('is-active'), 10); // Add class
        }
    } else {
        window.location.href = url;
    }
}

function closeModal() {
    const modal = document.getElementById('cancelOrderModal');
    if (modal) {
        modal.classList.remove('is-active'); // Remove class
        setTimeout(() => modal.style.display = 'none', 300); // Hide after transition
    }
}

function continueNavigation() {
    closeModal();
    if (targetUrl) {
        window.location.href = targetUrl;
    }
}

// NEW: Add restoreCancelModalContent function
function restoreCancelModalContent() {
    const modal = document.getElementById('cancelOrderModal');
    if (!modal) return;
    const content = modal.querySelector('.modal-content');
    if (content) {
        // Check if it's already the correct modal (e.g., not a validation modal)
        const title = content.querySelector('h3');
        if (title && title.textContent === "Cancel Order Confirmation") return;

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

// NEW: Add window.onclick for closing modal
window.onclick = function(event) {
    const modal = document.getElementById('cancelOrderModal');
    if (event.target === modal) {
        closeModal();
    }
}


// --- AUTO-FIX: Prevent GET Request Issues on Page Load ---
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('laminateForm');
    if (form) {
        form.setAttribute('method', 'POST');
        form.setAttribute('enctype', 'multipart/form-data');

        const btn = form.querySelector('button[type="submit"]');
        if (btn) {
            console.log("🔧 Fixed: Converted Laminate Submit Button to type='button'");
            btn.type = 'button';
        }
    }

    // --- NEW: Attach Listeners for Back Button and Modal ---
    const backBtn = document.getElementById('customBackBtn');
    if (backBtn) {
        backBtn.addEventListener('click', (e) => {
            e.preventDefault();
            checkCancellation('product.php'); // Set the target URL
        });
    }

    // REMOVED Modal button listeners that were here
    // The new HTML uses onclick="" attributes, so these are no longer needed.
});

const laminateError = document.createElement('p');
laminateError.style.color = "#d9534f";
laminateError.style.fontSize = "14px";
laminateError.style.marginTop = "8px";
laminateError.style.display = "none";
if (laminateUploadBox) laminateUploadBox.insertAdjacentElement("afterend", laminateError);

if (laminateFileInput) {
    laminateFileInput.addEventListener('change', e => {
        const files = Array.from(e.target.files);
        const validFiles = [];
        const maxSize = 5 * 1024 * 1024; // 5MB

        laminateError.style.display = "none";
        laminateError.textContent = "";

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
                    if (!laminateError.textContent) {
                        laminateError.textContent = `❌ ${r.error}`;
                        laminateError.style.display = "block";
                    }
                    continue;
                }
                validFiles.push(r);
            }
            laminateFiles = laminateFiles.concat(validFiles);
            renderLaminatePreviews();
        });
    });
}

// =======================
// PREVIEW RENDERING (UPDATED WITH REMOVE BUTTON)
// =======================
function renderLaminatePreviews() {
    if (!laminateUploadBox) return;

    // Force visibility
    laminateUploadBox.style.display = 'block';
    laminateUploadBox.style.height = 'auto';

    if (!laminateFiles || laminateFiles.length === 0) {
        laminateUploadBox.textContent = "Accepted file types: .png, .jpg, .pdf, .ai, .svg (Max: 5MB each)";
        return;
    }

    // 1. Create the Slider Wrapper (Dashed Box)
    const wrapper = document.createElement('div');
    wrapper.className = 'slider-wrapper';
    wrapper.style.position = 'relative';
    wrapper.style.overflow = 'hidden';
    wrapper.style.width = '100%';
    // wrapper.style.maxWidth = '100%'; 
    wrapper.style.height = '220px';
    wrapper.style.margin = '0 auto';
    wrapper.style.border = '2px dashed #999'; // Dashed border
    wrapper.style.borderRadius = '8px';
    wrapper.style.display = 'flex';
    wrapper.style.alignItems = 'center';
    wrapper.style.justifyContent = 'center';
    wrapper.style.background = '#f9f9f9';

    // 2. Create Container
    const container = document.createElement('div');
    container.className = 'slider-container';
    container.style.display = 'flex';
    container.style.transition = 'transform 0.45s ease';
    container.style.height = '100%';

    laminateFiles.forEach((entry, idx) => {
        const slide = document.createElement('div');
        slide.style.flex = '0 0 100%';
        slide.style.display = 'flex';
        slide.style.alignItems = 'center';
        slide.style.justifyContent = 'center';
        slide.style.height = '100%';

        // Create a wrapper for the content to position the X button relative to the image/icon
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
            e.stopPropagation(); // Prevent preview click
            e.preventDefault();
            laminateFiles.splice(idx, 1); // Remove from array
            renderLaminatePreviews(); // Re-render
        };

        if (entry.dataUrl) {
            const img = document.createElement('img');
            img.src = entry.dataUrl;
            img.alt = `Preview ${idx + 1}`;
            // Keep image contained but reasonable size
            img.style.maxWidth = '200px'; 
            img.style.maxHeight = '180px';
            img.style.objectFit = 'contain';
            img.style.boxShadow = '0 2px 5px rgba(0,0,0,0.1)';
            img.style.cursor = 'pointer';
            img.style.display = 'block';

            // Update Main Preview on Click
            img.onclick = function() {
                changeLaminatePreview(img);
            };

            contentWrapper.appendChild(img);
        } else {
            const p = document.createElement('div');
            p.innerHTML = `<i class="fa-solid fa-file-pdf" style="font-size: 40px; color: #d9534f;"></i><br><br>${entry.file.name}`;
            p.style.textAlign = 'center';
            p.style.color = '#555';
            p.style.padding = '20px';
            p.style.cursor = 'default';
            contentWrapper.appendChild(p);
        }

        contentWrapper.appendChild(removeBtn);
        slide.appendChild(contentWrapper);
        container.appendChild(slide);
    });

    wrapper.appendChild(container);
    
    // Clear and Append
    laminateUploadBox.innerHTML = '';
    laminateUploadBox.appendChild(wrapper);

    // Add Arrows
    if (laminateFiles.length > 1) addLaminateSliderArrows(wrapper, container);

    // 3. Add Price Text Below (Matching Style)
    const priceText = document.createElement('div');
    priceText.style.marginTop = '8px';
    priceText.style.textAlign = 'center';
    priceText.style.color = '#555';
    priceText.style.fontSize = '15px';
    priceText.innerHTML = `File Upload (${laminateFiles.length} files) <span style="color: #333; font-weight: bold;">₱${(laminateFiles.length * 5).toFixed(2)}</span>`;
    
    laminateUploadBox.appendChild(priceText);
}

// =======================
// SLIDER ARROWS
// =======================
function addLaminateSliderArrows(wrapper, container) {
    let currentIndex = 0;
    const totalSlides = Math.max(1, container.children.length);

    // container.style.width = `${totalSlides * 100}%`;
    Array.from(container.children).forEach(slide => {
        slide.style.flex = "0 0 100%";
    });

    const leftArrow = document.createElement('button');
    leftArrow.innerHTML = '&#10094;';
    const rightArrow = document.createElement('button');
    rightArrow.innerHTML = '&#10095;';

    // Style Arrows (Dark Circles)
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
        container.style.transform = `translateX(-${currentIndex * 100}%)`;
    });

    rightArrow.addEventListener('click', e => {
        e.preventDefault();
        e.stopPropagation();
        currentIndex = (currentIndex < totalSlides - 1) ? currentIndex + 1 : 0;
        container.style.transform = `translateX(-${currentIndex * 100}%)`;
    });

    wrapper.appendChild(leftArrow);
    wrapper.appendChild(rightArrow);
}

function increaseLaminateQty() {
    const qtyInput = document.getElementById('laminateQuantity');
    if (!qtyInput) return;
    qtyInput.value = parseInt(qtyInput.value || 0, 10) + 1;
}

function decreaseLaminateQty() {
    const qtyInput = document.getElementById('laminateQuantity');
    if (!qtyInput) return;
    if (parseInt(qtyInput.value || 0, 10) > 1)
        qtyInput.value = parseInt(qtyInput.value, 10) - 1;
}

function changeLaminatePreview(thumbnail) {
    const mainPreview = document.getElementById('mainLaminatePreview');
    if (mainPreview && thumbnail && thumbnail.src) mainPreview.src = thumbnail.src;
    
    const thumbs = document.querySelectorAll('.thumb'); // Generic selector in case shared class
    thumbs.forEach(t => t.classList.remove('active'));
    
    if (thumbnail && thumbnail.classList && thumbnail.classList.contains('thumb')) {
        thumbnail.classList.add('active');
    }
}

// ------------------------------
// FIXED: Submit Laminate Order
// ------------------------------
function submitLaminateOrder(orderSpeed) {
    if (typeof event !== 'undefined') event.preventDefault();
    else if (window.event) window.event.preventDefault();

    console.log("🚀 Submitting Laminate Order...");
    const form = document.getElementById("laminateForm");
    if (!form) {
        console.error("❌ Error: Form 'laminateForm' not found!");
        return;
    }

    const size = document.getElementById("laminateSize")?.value;
    if (!size || size === "Select Size") {
         // MODIFIED: Use new modal for validation
         const validationModal = document.getElementById('cancelOrderModal');
         if (validationModal) {
             const content = validationModal.querySelector('.modal-content');
             content.innerHTML = `
                 <span class="modal-close" onclick="closeModal()">&times;</span>
                 <h3>Required Field Missing</h3>
                 <p>Please select a Laminate Size before submitting your order.</p>
                 <div class="modal-buttons">
                     <button class="modal-btn stay" onclick="closeModal()">OK</button>
                 </div>
             `;
             validationModal.style.display = 'flex';
             setTimeout(() => validationModal.classList.add('is-active'), 10);
         } else {
             alert("Please select a size.");
         }
         return;
    }

    let speedInput = form.querySelector('input[name="orderSpeed"]');
    if (!speedInput) {
        speedInput = document.createElement('input');
        speedInput.type = 'hidden';
        speedInput.name = 'orderSpeed';
        form.appendChild(speedInput);
    }
    speedInput.value = orderSpeed;

    // Force POST
    form.method = "POST";
    form.enctype = "multipart/form-data";

    const fileInput = document.getElementById('laminateFileInput');
    fileInput.name = "designFile[]";

    console.log("📂 Laminate Files Array:", laminateFiles);

    if (laminateFiles.length > 0) {
        const dataTransfer = new DataTransfer();
        laminateFiles.forEach(item => {
            if (item.file) {
                dataTransfer.items.add(item.file);
            }
        });
        fileInput.files = dataTransfer.files;
    }

    form.action = "confirmation.php";
    form.submit();
}