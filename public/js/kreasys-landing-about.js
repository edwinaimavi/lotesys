document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('image');
    const preview = document.getElementById('aboutImagePreview');
    const form = document.getElementById('landingAboutForm');
    const submit = document.getElementById('saveLandingAbout');

    if (input && preview) {
        input.addEventListener('change', function () {
            const file = this.files && this.files[0];
            const label = this.nextElementSibling;

            if (label) {
                label.textContent = file ? file.name : 'Seleccionar imagen';
            }

            if (!file || !file.type.startsWith('image/')) return;

            const url = URL.createObjectURL(file);
            preview.innerHTML = '';

            const img = document.createElement('img');
            img.src = url;
            img.alt = 'Vista previa de imagen institucional';
            img.onload = () => URL.revokeObjectURL(url);

            preview.appendChild(img);
        });
    }

    if (form && submit) {
        form.addEventListener('submit', function () {
            submit.disabled = true;
            submit.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Guardando...';
        });
    }
});
