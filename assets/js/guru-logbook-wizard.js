document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('logbook-wizard');
    if (!form) return;

    const panels = Array.from(form.querySelectorAll('.wizard-panel'));
    const steps = Array.from(form.querySelectorAll('.wizard-step'));
    const previousButton = document.getElementById('wizard-previous');
    const nextButton = document.getElementById('wizard-next');
    const submitButton = document.getElementById('wizard-submit');
    const progressLabel = document.getElementById('wizard-progress-label');
    const title = document.getElementById('wizard-panel-title');
    const description = document.getElementById('wizard-panel-description');
    const review = document.getElementById('wizard-review');
    const classInput = form.querySelector('[name="nama_kelas"]');
    const dateInput = form.querySelector('[name="tanggal"]');
    const studentInput = form.querySelector('[name="nama_siswa"]');
    const mediaInput = form.querySelector('[name="dokumentasi_media[]"]');
    const mediaPreview = document.getElementById('wizard-media-preview');
    const sectionFields = [
        ['pembelajaran', 'Pembelajaran'],
        ['praktik_ibadah', 'Praktik Ibadah'],
        ['ekskul', 'Ekskul'],
        ['catatan_anak', 'Catatan Anak'],
        ['dokumentasi', 'Dokumentasi']
    ];
    const descriptions = [
        'Tentukan tanggal, kelas, dan siswa yang akan dibuatkan catatan.',
        'Ceritakan kegiatan belajar dan perkembangan siswa.',
        'Catat pembiasaan ibadah siswa jika ada.',
        'Catat kegiatan ekstrakurikuler dan minat siswa.',
        'Tambahkan apresiasi atau hal yang perlu diperhatikan.',
        'Tambahkan foto atau video, lalu periksa ringkasan sebelum menyimpan.'
    ];
    let previewUrls = [];
    let activeStep = 0;

    function validateInformation() {
        if (!classInput.value) {
            classInput.setCustomValidity('Pilih kelas terlebih dahulu.');
            classInput.reportValidity();
            classInput.setCustomValidity('');
            return false;
        }
        const validDate = dateInput.value !== '' && !Number.isNaN(Date.parse(dateInput.value));
        if (!validDate) {
            dateInput.setCustomValidity('Pilih tanggal observasi yang valid.');
            dateInput.reportValidity();
            dateInput.setCustomValidity('');
            return false;
        }
        if (!studentInput.value) {
            studentInput.setCustomValidity('Pilih siswa sebelum melanjutkan.');
            studentInput.reportValidity();
            studentInput.setCustomValidity('');
            return false;
        }
        return true;
    }

    function filterStudents() {
        const selectedClass = classInput.value;
        const currentStudent = studentInput.value;
        let currentStudentStillAvailable = false;
        Array.from(studentInput.options).forEach(function (option) {
            if (!option.value) return;
            const belongsToClass = option.dataset.class === selectedClass;
            option.hidden = !belongsToClass;
            option.disabled = !belongsToClass;
            if (belongsToClass && option.value === currentStudent) currentStudentStillAvailable = true;
        });
        if (!currentStudentStillAvailable) studentInput.value = '';
        studentInput.disabled = !selectedClass || !Array.from(studentInput.options).some(function (option) {
            return option.dataset.class === selectedClass;
        });
    }

    function updateReview() {
        if (!review) return;
        const studentName = studentInput.options[studentInput.selectedIndex]?.text || 'Belum dipilih';
        const filledSections = sectionFields
            .filter(([name]) => form.elements[name].value.trim() !== '')
            .map(([, label]) => label);
        const detail = filledSections.length
            ? 'Bagian terisi: ' + filledSections.join(', ') + '.'
            : 'Tambahkan catatan kegiatan atau lampirkan foto/video.';
        const fileCount = mediaInput ? mediaInput.files.length : 0;

        review.innerHTML = '';
        const heading = document.createElement('strong');
        heading.textContent = 'Ringkasan sebelum disimpan';
        const student = document.createElement('span');
        student.textContent = 'Kelas: ' + (classInput.options[classInput.selectedIndex]?.text || 'Belum dipilih') +
            ' · Siswa: ' + studentName + ' · ' + (dateInput.value || 'Tanggal belum dipilih');
        const sections = document.createElement('span');
        sections.textContent = detail + (fileCount ? ' ' + fileCount + ' file media dilampirkan.' : '');
        review.append(heading, student, sections);
    }

    function showStep(index) {
        activeStep = Math.max(0, Math.min(index, panels.length - 1));
        panels.forEach((panel, panelIndex) => {
            const isActive = panelIndex === activeStep;
            panel.hidden = !isActive;
            panel.classList.toggle('is-active', isActive);
        });
        steps.forEach((step, stepIndex) => {
            const isActive = stepIndex === activeStep;
            step.classList.toggle('is-active', isActive);
            step.classList.toggle('is-complete', stepIndex < activeStep);
            if (isActive) {
                step.setAttribute('aria-current', 'step');
            } else {
                step.removeAttribute('aria-current');
            }
        });
        progressLabel.textContent = 'LANGKAH ' + (activeStep + 1) + ' DARI ' + panels.length;
        title.textContent = steps[activeStep].lastElementChild.textContent.trim();
        description.textContent = descriptions[activeStep];
        previousButton.hidden = activeStep === 0;
        nextButton.hidden = activeStep === panels.length - 1;
        submitButton.hidden = activeStep !== panels.length - 1;
        if (activeStep === panels.length - 1) updateReview();
    }

    nextButton.addEventListener('click', function () {
        if (activeStep === 0 && !validateInformation()) return;
        showStep(activeStep + 1);
    });

    previousButton.addEventListener('click', function () {
        showStep(activeStep - 1);
    });

    classInput.addEventListener('change', function () {
        filterStudents();
        updateReview();
    });

    steps.forEach((step, index) => {
        step.addEventListener('click', function () {
            if (index > activeStep && activeStep === 0 && !validateInformation()) return;
            showStep(index);
        });
    });

    form.addEventListener('submit', function (event) {
        if (!validateInformation()) {
            event.preventDefault();
            showStep(0);
            return;
        }
        const hasActivity = sectionFields.some(([name]) => form.elements[name].value.trim() !== '') ||
            (mediaInput && mediaInput.files.length > 0);
        if (!hasActivity) {
            event.preventDefault();
            showStep(1);
            form.elements.pembelajaran.setCustomValidity('Isi catatan kegiatan atau lampirkan foto/video sebelum menyimpan.');
            form.elements.pembelajaran.reportValidity();
            form.elements.pembelajaran.setCustomValidity('');
        }
    });

    if (mediaInput && mediaPreview) {
        mediaInput.addEventListener('change', function () {
            previewUrls.forEach(URL.revokeObjectURL);
            previewUrls = [];
            mediaPreview.replaceChildren();

            const selectedFiles = Array.from(mediaInput.files);
            const totalSize = selectedFiles.reduce((sum, file) => sum + file.size, 0);
            if (selectedFiles.length > 10 || totalSize > 35 * 1024 * 1024 ||
                selectedFiles.some(file => file.size > 20 * 1024 * 1024)) {
                mediaInput.setCustomValidity('Maksimal 10 file, 20 MB per file, dan 35 MB total.');
                mediaInput.reportValidity();
                mediaInput.setCustomValidity('');
                mediaInput.value = '';
                updateReview();
                return;
            }

            selectedFiles.forEach(function (file) {
                const url = URL.createObjectURL(file);
                previewUrls.push(url);
                const item = document.createElement('div');
                item.className = 'wizard-media-item';
                const preview = file.type.startsWith('video/') ? document.createElement('video') : document.createElement('img');
                preview.src = url;
                preview.setAttribute('aria-label', file.name);
                if (preview.tagName === 'VIDEO') {
                    preview.controls = true;
                    preview.preload = 'metadata';
                } else {
                    preview.alt = file.name;
                }
                const name = document.createElement('span');
                name.textContent = file.name;
                item.append(preview, name);
                mediaPreview.append(item);
            });
            updateReview();
        });
    }

    form.addEventListener('input', updateReview);
    form.addEventListener('change', updateReview);
    filterStudents();
    showStep(0);
});
