// ======================= 
// CANCELLATION GUARD LOGIC
// =======================
let targetUrl = '';
let stickerFiles = []; 

// --- AUTO-FIX: Prevent GET Request Issues on Page Load ---
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('stickerForm');
    if (form) {
        form.setAttribute('method', 'POST');
        form.setAttribute('enctype', 'multipart/form-data');

        const btn = form.querySelector('button[type="submit"]');
        if (btn) {
            console.log("🔧 Fixed: Converted Sticker Submit Button to type='button'");
            btn.type = 'button';
        }
    }
    toggleCustomSize();
});

function isFormDirty() {
    const sizeEl = document.getElementById("size");
    const materialEl = document.getElementById("material");
    const cutTypeEl = document.getElementById("cutType");
    const quantityEl = document.getElementById("quantity");
    const customSizeEl = document.getElementById("customSize");

    if (sizeEl && sizeEl.value && sizeEl.value !== 'Select Size' && sizeEl.value !== '') return true;
    if (materialEl && materialEl.value && materialEl.value !== 'Select' && materialEl.value !== '') return true;
    if (cutTypeEl && cutTypeEl.value && cutTypeEl.value !== 'Select' && cutTypeEl.value !== '') return true;
    if (quantityEl && parseInt(quantityEl.value, 10) > 1) return true;
    if (sizeEl && sizeEl.value === 'Custom Size' && customSizeEl && customSizeEl.value.trim() !== '') return true;
    if (stickerFiles && stickerFiles.length > 0) return true;

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
        if (content.querySelector('h3').textContent === "Cancel Order Confirmation") return;

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
// STICKER ORDER
// =======================

const stickerFileInput = document.getElementById('fileInput');
const stickerUploadBox = document.getElementById('uploadBox');

const stickerError = document.createElement('p');
stickerError.style.color = "#d9534f";
stickerError.style.fontSize = "14px";
stickerError.style.marginTop = "8px";
stickerError.style.display = "none";
if (stickerUploadBox) stickerUploadBox.insertAdjacentElement("afterend", stickerError);

if (stickerFileInput) {
    stickerFileInput.addEventListener('change', e => {
        const files = Array.from(e.target.files);
        const validFiles = [];
        const maxSize = 5 * 1024 * 1024; // 5MB

        stickerError.style.display = "none";
        stickerError.textContent = "";

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
                    if (!stickerError.textContent) {
                        stickerError.textContent = `❌ ${r.error}`;
                        stickerError.style.display = "block";
                    }
                    continue;
                }
                if (r.file) {
                    validFiles.push(r);
                }
            }

            const currentMain = document.querySelector(".sticker-preview .main-preview") || document.getElementById('mainStickerPreview');
            const currentMainSrc = currentMain ? currentMain.src : null;

            stickerFiles = stickerFiles.concat(validFiles);
            renderStickerPreviews();

            if (currentMain && currentMainSrc) {
                currentMain.src = currentMainSrc;
            }
        });
    });
}

// =======================
// RENDER STICKER PREVIEWS (UPDATED WITH REMOVE BUTTON)
// =======================
function renderStickerPreviews() {
    if (!stickerUploadBox) return;
    
    // Force visibility
    stickerUploadBox.style.display = 'block';
    stickerUploadBox.style.height = 'auto';

    if (!stickerFiles || stickerFiles.length === 0) {
        stickerUploadBox.textContent = "Accepted file types: .png, .jpg, .pdf, .ai, .svg (Max: 5MB each)";
        return;
    }

    // 1. Slider Wrapper (Dashed Box)
    const wrapper = document.createElement('div');
    wrapper.className = 'slider-wrapper';
    wrapper.style.position = 'relative';
    wrapper.style.overflow = 'hidden';
    wrapper.style.width = '100%';
    // wrapper.style.maxWidth = '100%';
    wrapper.style.height = '220px';
    wrapper.style.margin = '0 auto';
    wrapper.style.border = '2px dashed #999'; // Dashed
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

    stickerFiles.forEach((entry, idx) => {
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
            stickerFiles.splice(idx, 1); // Remove from array
            renderStickerPreviews(); // Re-render
        };

        if (entry.dataUrl) {
            const img = document.createElement('img');
            img.src = entry.dataUrl;
            img.alt = `Preview ${idx + 1}`;
            // Restrict size so buttons fit
            img.style.maxWidth = '200px'; 
            img.style.maxHeight = '180px';
            img.style.objectFit = 'contain';
            img.style.boxShadow = '0 2px 5px rgba(0,0,0,0.1)';
            img.style.cursor = 'pointer';
            img.style.display = 'block';
            
            img.onclick = function() {
                changePreview(img);
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
    
    stickerUploadBox.innerHTML = '';
    stickerUploadBox.appendChild(wrapper);
    
    // Arrows
    if (stickerFiles.length > 1) addSliderArrows(wrapper, container);

    // 3. Price Text (Fixed Style)
    const info = document.createElement('div');
    info.style.marginTop = '8px';
    info.style.textAlign = 'center';
    info.style.color = '#555';
    info.style.fontSize = '15px';
    info.innerHTML = `File Upload (${stickerFiles.length} files) <span style="color: #333; font-weight: bold;">₱${(stickerFiles.length * 5).toFixed(2)}</span>`;
    
    stickerUploadBox.appendChild(info);
}

// =======================
// SLIDER ARROWS (UPDATED STYLE)
// =======================
function addSliderArrows(wrapper, container) {
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

function submitOrder(orderSpeed) {
    if (typeof event !== 'undefined') event.preventDefault();
    else if (window.event) window.event.preventDefault();

    orderSpeed = typeof orderSpeed === 'string' ? orderSpeed : 'standard';
    console.log("🚀 Submitting Sticker Order via JS...");

    const form = document.getElementById("stickerForm");
    if (!form) {
        console.error("❌ Error: Form 'stickerForm' not found!");
        return;
    }

    const sizeEl = document.getElementById("size");
    const materialEl = document.getElementById("material");
    const cutTypeEl = document.getElementById("cutType");

    const size = sizeEl ? sizeEl.value : '';
    const material = materialEl ? materialEl.value : '';
    const cutType = cutTypeEl ? cutTypeEl.value : '';

    if (size === "Select Size" || material === "Select" || cutType === "Select" || size === "") {
        const validationModal = document.getElementById('cancelOrderModal'); 
        if (validationModal) {
                const content = validationModal.querySelector('.modal-content');
                content.innerHTML = `
                <span class="modal-close" onclick="closeModal()">&times;</span>
                <h3>Required Fields Missing</h3>
                <p>Please complete all required fields (Size, Material, Cut Type) before submitting your order.</p>
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
    
    let speedInput = form.querySelector('input[name="orderSpeed"]');
    if (!speedInput) {
        speedInput = document.createElement('input');
        speedInput.type = 'hidden';
        speedInput.name = 'orderSpeed';
        form.appendChild(speedInput);
    }
    speedInput.value = orderSpeed;

    form.method = "POST";
    form.enctype = "multipart/form-data";

    const fileInput = document.getElementById('fileInput');
    fileInput.name = "designFile[]"; 

    console.log("📂 Sticker Files Array:", stickerFiles);

    if (stickerFiles.length > 0) {
        const dataTransfer = new DataTransfer();
        stickerFiles.forEach(item => {
            if (item.file) dataTransfer.items.add(item.file);
        });
        fileInput.files = dataTransfer.files;
    }

    form.action = "confirmation.php"; 
    form.submit();
}

function toggleCustomSize() {
    const sizeSelect = document.getElementById('size');
    const customInputGroup = document.getElementById('customSizeInputGroup');
    const customInput = document.getElementById('customSize');

    if (!customInputGroup || !customInput) return; 

    if (sizeSelect.value === 'Custom Size') {
        customInputGroup.style.display = 'block';
        if (customInput) customInput.setAttribute('required', 'required');
    } else {
        customInputGroup.style.display = 'none';
        if (customInput) {
            customInput.removeAttribute('required');
            customInput.value = ''; 
        }
    }
}

function increaseQty() {
    const qtyInput = document.getElementById('quantity');
    if (!qtyInput) return;
    qtyInput.value = parseInt(qtyInput.value || 0, 10) + 1;
}

function decreaseQty() {
    const qtyInput = document.getElementById('quantity');
    if (!qtyInput) return;
    if (parseInt(qtyInput.value || 0, 10) > 1)
        qtyInput.value = parseInt(qtyInput.value, 10) - 1;
}

function changePreview(thumbnail) {
    const mainPreview = document.getElementById('mainStickerPreview') || document.querySelector('.sticker-preview .main-preview');
    if (mainPreview && thumbnail && thumbnail.src) {
        mainPreview.src = thumbnail.src;
    }
    const thumbs = document.querySelectorAll('.thumb');
    thumbs.forEach(t => t.classList.remove('active'));
    if (thumbnail && thumbnail.classList) thumbnail.classList.add('active');
}