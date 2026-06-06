document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('ppdbForm');
    if (!form) return;

    const steps = Array.from(document.querySelectorAll('.form-step'));
    const nodes = Array.from(document.querySelectorAll('.step-node'));
    const btnNext = document.getElementById('btnNext');
    const btnPrev = document.getElementById('btnPrev');
    const btnSubmit = document.getElementById('btnSubmit');
    const stepBar = document.getElementById('stepBar');
    
    let currentStep = 1;
    const totalSteps = steps.length;

    // Menangani update step indicator progress bar
    const updateProgress = () => {
        // Update bar width
        const percent = ((currentStep - 1) / (totalSteps - 1)) * 100;
        stepBar.style.width = `${percent}%`;

        // Update step nodes
        nodes.forEach((node, idx) => {
            const stepNum = idx + 1;
            if (stepNum < currentStep) {
                node.classList.add('completed');
                node.classList.remove('active');
            } else if (stepNum === currentStep) {
                node.classList.add('active');
                node.classList.remove('completed');
            } else {
                node.classList.remove('active', 'completed');
            }
        });

        // Update step visibility
        steps.forEach((step, idx) => {
            if (idx + 1 === currentStep) {
                step.classList.add('active');
            } else {
                step.classList.remove('active');
            }
        });

        // Update buttons visibility
        if (currentStep === 1) {
            btnPrev.style.visibility = 'hidden';
        } else {
            btnPrev.style.visibility = 'visible';
        }

        if (currentStep === totalSteps) {
            btnNext.style.display = 'none';
            btnSubmit.style.display = 'inline-flex';
        } else {
            btnNext.style.display = 'inline-flex';
            btnSubmit.style.display = 'none';
        }
    };

    // Validasi input pada langkah tertentu
    const validateStep = (stepNum) => {
        const currentStepEl = steps[stepNum - 1];
        const inputs = Array.from(currentStepEl.querySelectorAll('input[required], select[required], textarea[required]'));
        
        let isValid = true;
        inputs.forEach(input => {
            if (!input.value.trim()) {
                isValid = false;
                input.reportValidity();
            } else if (input.type === 'file' && input.files.length === 0) {
                isValid = false;
                alert(`Harap unggah berkas: ${input.previousElementSibling.previousElementSibling.innerText}`);
            }
        });
        return isValid;
    };

    // Event listener tombol Next
    btnNext.addEventListener('click', () => {
        if (validateStep(currentStep)) {
            if (currentStep < totalSteps) {
                currentStep++;
                updateProgress();
            }
        }
    });

    // Event listener tombol Prev
    btnPrev.addEventListener('click', () => {
        if (currentStep > 1) {
            currentStep--;
            updateProgress();
        }
    });

    // Event listener file upload
    const fileInputs = document.querySelectorAll('.file-upload-wrapper input[type="file"]');
    fileInputs.forEach(input => {
        const wrapper = input.parentElement;
        const textEl = wrapper.querySelector('.file-upload-text');
        
        input.addEventListener('change', (e) => {
            if (input.files.length > 0) {
                const file = input.files[0];
                // Validasi ukuran berkas (Maks 2MB)
                if (file.size > 2 * 1024 * 1024) {
                    alert('Ukuran file tidak boleh melebihi 2MB.');
                    input.value = '';
                    wrapper.classList.remove('has-file');
                    textEl.innerText = 'Pilih file atau seret ke sini';
                    return;
                }
                
                wrapper.classList.add('has-file');
                textEl.innerText = file.name;
            } else {
                wrapper.classList.remove('has-file');
                textEl.innerText = 'Pilih file atau seret ke sini';
            }
        });

        // Drag & drop effects
        wrapper.addEventListener('dragover', () => {
            wrapper.style.borderColor = 'var(--gold)';
            wrapper.style.background = 'rgba(255,255,255,0.05)';
        });

        wrapper.addEventListener('dragleave', () => {
            wrapper.style.borderColor = 'var(--border-glass)';
            wrapper.style.background = 'rgba(255,255,255,0.01)';
        });
    });

    // Loading state saat submit form
    form.addEventListener('submit', (e) => {
        if (!validateStep(currentStep)) {
            e.preventDefault();
            return;
        }
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = 'Mengirim Data... <i class="fa-solid fa-spinner fa-spin"></i>';
        btnPrev.disabled = true;
    });

    // Inisialisasi progress awal
    updateProgress();
});
