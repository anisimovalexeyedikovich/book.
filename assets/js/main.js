document.addEventListener('DOMContentLoaded', function() {
    // Инициализация тултипов
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Анимация появления элементов
    const fadeElements = document.querySelectorAll('.fade-in');
    fadeElements.forEach(element => {
        element.style.opacity = '1';
    });

    // Фильтрация книг
    const filterForm = document.getElementById('filterForm');
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(filterForm);
            const searchParams = new URLSearchParams(formData);
            window.location.href = '/catalog.php?' + searchParams.toString();
        });
    }

    // Обработка кнопок аренды/покупки
    document.querySelectorAll('.rent-button, .buy-button').forEach(button => {
        button.addEventListener('click', function(e) {
            const bookId = this.dataset.bookId;
            const action = this.dataset.action;
            const duration = this.dataset.duration;

            fetch('/api/process_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    bookId: bookId,
                    action: action,
                    duration: duration
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Успешно!', data.message, 'success');
                } else {
                    showNotification('Ошибка!', data.message, 'danger');
                }
            })
            .catch(error => {
                showNotification('Ошибка!', 'Произошла ошибка при обработке запроса', 'danger');
            });
        });
    });
});

// Функция для отображения уведомлений
function showNotification(title, message, type = 'info') {
    const notificationHtml = `
        <div class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <strong>${title}</strong><br>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;

    const toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        const container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(container);
    }

    const toastElement = document.createElement('div');
    toastElement.innerHTML = notificationHtml;
    document.getElementById('toastContainer').appendChild(toastElement.firstChild);

    const toast = new bootstrap.Toast(document.getElementById('toastContainer').lastChild);
    toast.show();
}

// Функция для предварительного просмотра изображения
function previewImage(input, previewId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById(previewId).src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}
