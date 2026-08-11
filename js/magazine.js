// =======================
// CANCELLATION GUARD LOGIC
// =======================
let targetUrl = '';
let magazineFiles = []; // Use a separate array for magazine files

// --- AUTO-FIX: Prevent GET Request Issues on Page Load ---
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('magazineForm');
    if (form) {
        form.setAttribute('method', 'POST');
        form.setAttribute('enctype', 'multipart/form-data');
    }
    // Set min values
    const qtyInput = document.getElementById('quantity');
    if(qtyInput && !qtyInput.value) {
        qtyInput.value = "1";
    }
    const pageInput = document.getElementById('pageCount');
    if(pageInput && !pageInput.value) {
        pageInput.value = "8";
    }
});

function isFormDirty() {
    const sizeEl = document.getElementById("size");
    const coverPaperEl = document.getElementById("coverPaper");
    const insidePaperEl = document.getElementById("insidePaper");
    const bindingEl = document.getElementById("binding");
    const pageCountEl = document.getElementById("pageCount");
    const quantityEl = document.getElementById("quantity");

    if (sizeEl && sizeEl.value && sizeEl.value !== 'Select Size' && sizeEl.value !== '') return true;
    if (coverPaperEl && coverPaperEl.value && coverPaperEl.value !== 'Select' && coverPaperEl.value !== '') return true;
    if (insidePaperEl && insidePaperEl.value && insidePaperEl.value !== 'Select' && insidePaperEl.value !== '') return true;
    if (bindingEl && bindingEl.value && bindingEl.value !== 'Select Binding' && bindingEl.value !== '') return true;
    if (pageCountEl && parseInt(pageCountEl.value, 10) > 8) return true;
    if (quantityEl && parseInt(quantityEl.value, 10) > 1) return true;
    if (magazineFiles && magazineFiles.length > 0) return true;

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
// MAGAZINE ORDER
// =======================

const magazineFileInput = document.getElementById('fileInput');
const magazineUploadBox = document.getElementById('uploadBox');

const magazineError = document.createElement('p');
magazineError.style.color = "#d9534f";
magazineError.style.fontSize = "14px";
magazineError.style.marginTop = "8px";
magazineError.style.display = "none";
if (magazineUploadBox) magazineUploadBox.insertAdjacentElement("afterend", magazineError);

if (magazineFileInput) {
    magazineFileInput.addEventListener('change', e => {
        const files = Array.from(e.target.files);
        const validFiles = [];
        const maxSize = 50 * 1024 * 1024; // 50MB for magazines

        magazineError.style.display = "none";
        magazineError.textContent = "";

        const readPromises = files.map(file => {
            return new Promise(resolve => {
                if (file.size > maxSize) {
                    resolve({ error: `${file.name} exceeds 50MB file limit.` });
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
                    if (!magazineError.textContent) {
                        magazineError.textContent = `❌ ${r.error}`;
                        magazineError.style.display = "block";
                    }
                    continue;
                }
                if (r.file) {
                    validFiles.push(r);
                }
            }
            
            const currentMain = document.getElementById('mainMagazinePreview');
            const currentMainSrc = currentMain ? currentMain.src : null;

            magazineFiles = magazineFiles.concat(validFiles);
            renderMagazinePreviews();

            if (currentMain && currentMainSrc) {
                currentMain.src = currentMainSrc;
            }
        });
    });
}

// =======================
// RENDER MAGAZINE PREVIEWS (with Remove Button)
// =======================
function renderMagazinePreviews() {
    if (!magazineUploadBox) return;

    magazineUploadBox.style.display = 'block';
    magazineUploadBox.style.height = 'auto';

    if (!magazineFiles || magazineFiles.length === 0) {
        magazineUploadBox.textContent = "Accepted file types: .pdf, .ai, .psd, .png, .jpg (Max: 50MB each)";
        return;
    }

    const wrapper = document.createElement('div');
    wrapper.className = 'slider-wrapper';
    wrapper.style.position = 'relative';
    wrapper.style.overflow = 'hidden';
    wrapper.style.width = '100%';
    // wrapper.style.maxWidth = '100%'; // Let CSS handle this
    wrapper.style.height = '220px';
    wrapper.style.margin = '0 auto';
    wrapper.style.border = '2px dashed #999';
    wrapper.style.borderRadius = '8px';
    wrapper.style.display = 'flex';
    wrapper.style.alignItems = 'center';
    wrapper.style.justifyContent = 'center';
    wrapper.style.background = '#f9f9f9';

    const container = document.createElement('div');
    container.className = 'slider-container';
    container.style.display = 'flex';
    container.style.transition = 'transform 0.45s ease';
    container.style.height = '100%';

    magazineFiles.forEach((entry, idx) => {
        const slide = document.createElement('div');
        slide.style.flex = '0 0 100%';
        slide.style.display = 'flex';
        slide.style.alignItems = 'center';
        slide.style.justifyContent = 'center';
        slide.style.height = '100%';

        const contentWrapper = document.createElement('div');
        contentWrapper.style.position = 'relative';
        contentWrapper.style.display = 'inline-block';

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

        removeBtn.onclick = (e) => {
            e.stopPropagation();
            e.preventDefault();
            magazineFiles.splice(idx, 1); 
            renderMagazinePreviews(); 
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
                changePreview(img); 
            };
            
            contentWrapper.appendChild(img);
        } else {
            const p = document.createElement('div');
            let iconClass = "fa-solid fa-file";
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

    magazineUploadBox.innerHTML = '';
    magazineUploadBox.appendChild(wrapper);

    if (magazineFiles.length > 1) addSliderArrows(wrapper, container);

    const info = document.createElement('div');
    info.style.marginTop = '8px';
    info.style.textAlign = 'center';
    info.style.color = '#555';
    info.style.fontSize = '15px';
    info.innerHTML = `File Upload (${magazineFiles.length} files) <span style="color: #333; font-weight: bold;">₱${(magazineFiles.length * 5).toFixed(2)}</span>`;

    magazineUploadBox.appendChild(info);
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
    console.log("🚀 Submitting Magazine Order via JS...");

    const form = document.getElementById("magazineForm");
    if (!form) {
        console.error("❌ Error: Form 'magazineForm' not found!");
        return;
    }

    const sizeEl = document.getElementById("size");
    const coverPaperEl = document.getElementById("coverPaper");
    const insidePaperEl = document.getElementById("insidePaper");
    const bindingEl = document.getElementById("binding");

    // Validation
    if (!sizeEl.value || sizeEl.value === "Select Size" ||
        !coverPaperEl.value || coverPaperEl.value === "Select" ||
        !insidePaperEl.value || insidePaperEl.value === "Select" ||
        !bindingEl.value || bindingEl.value === "Select Binding") {

        const validationModal = document.getElementById('cancelOrderModal');
        if (validationModal) {
                const content = validationModal.querySelector('.modal-content');
                content.innerHTML = `
                <span class="modal-close" onclick="closeModal()">&times;</span>
                <h3>Required Fields Missing</h3>
                <p>Please complete all required fields (Size, Cover, Inside Paper, Binding) before submitting.</p>
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

    form.method = "POST";
    form.enctype = "multipart/form-data";

    const fileInput = document.getElementById('fileInput');
    fileInput.name = "designFile[]";

    console.log("📂 Magazine Files Array:", magazineFiles);

    if (magazineFiles.length > 0) {
        const dataTransfer = new DataTransfer();
        magazineFiles.forEach(item => {
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

// Qty: Min 1, Step 1
function increaseQty() {
    const qtyInput = document.getElementById('quantity');
    if (!qtyInput) return;
    let currentQty = parseInt(qtyInput.value || 0, 10);
    if(isNaN(currentQty) || currentQty < 1) {
        qtyInput.value = 1;
    } else {
        qtyInput.value = currentQty + 1;
    }
}

function decreaseQty() {
    const qtyInput = document.getElementById('quantity');
    if (!qtyInput) return;
    let currentQty = parseInt(qtyInput.value || 0, 10);
    if (currentQty > 1) {
        qtyInput.value = currentQty - 1;
    } else {
        qtyInput.value = 1;
    }
}

// Page Count: Min 8, Step 4
function increasePageCount() {
    const qtyInput = document.getElementById('pageCount');
    if (!qtyInput) return;
    let currentQty = parseInt(qtyInput.value || 0, 10);
    if(isNaN(currentQty) || currentQty < 8) {
        qtyInput.value = 8;
    } else {
        qtyInput.value = currentQty + 4;
    }
}

function decreasePageCount() {
    const qtyInput = document.getElementById('pageCount');
    if (!qtyInput) return;
    let currentQty = parseInt(qtyInput.value || 0, 10);
    if (currentQty > 8) {
        qtyInput.value = currentQty - 4;
    } else {
        qtyInput.value = 8;
    }
}


function changePreview(thumbnail) {
    const mainPreview = document.getElementById('mainMagazinePreview');
    if (mainPreview && thumbnail && thumbnail.src) {
        mainPreview.src = thumbnail.src;
        
        const thumbs = document.querySelectorAll('.magazine-preview .thumb');
        thumbs.forEach(t => t.classList.remove('active'));
        if (thumbnail.classList.contains('thumb')) {
            thumbnail.classList.add('active');
        }
    }
}