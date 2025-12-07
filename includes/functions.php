<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function flash($message, $type = 'info')
{
    if (!isset($_SESSION['toasts'])) {
        $_SESSION['toasts'] = [];
    }
    $_SESSION['toasts'][] = ['message' => $message, 'type' => $type];
}

function displayFlash()
{
    if (!isset($_SESSION['toasts'])) {
        return;
    }

    $toasts = $_SESSION['toasts'];
    unset($_SESSION['toasts']);
?>

    <style>
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            pointer-events: none;
        }

        .toast {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            padding: 16px 20px;
            margin-bottom: 12px;
            min-width: 300px;
            max-width: 400px;
            display: flex;
            align-items: center;
            gap: 12px;
            pointer-events: auto;
            animation: slideIn 0.3s ease-out;
            opacity: 0;
            transform: translateX(400px);
            position: relative;
        }

        .toast.show {
            opacity: 1;
            transform: translateX(0);
        }

        .toast.hide {
            animation: slideOut 0.3s ease-out forwards;
        }

        @keyframes slideIn {
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideOut {
            to {
                opacity: 0;
                transform: translateX(400px);
            }
        }

        .toast-icon {
            flex-shrink: 0;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: bold;
        }

        .toast-success .toast-icon {
            background: #d1f0dd;
            color: #198754;
        }

        .toast-danger .toast-icon {
            background: #f8d7da;
            color: #dc3545;
        }

        .toast-warning .toast-icon {
            background: #fff3cd;
            color: #ffc107;
        }

        .toast-info .toast-icon {
            background: #cfe2ff;
            color: #0d6efd;
        }

        .toast-content {
            flex: 1;
        }

        .toast-title {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 2px;
            color: #333;
        }

        .toast-message {
            font-size: 13px;
            color: #666;
            line-height: 1.4;
        }

        .toast-close {
            flex-shrink: 0;
            width: 20px;
            height: 20px;
            border: none;
            background: transparent;
            color: #999;
            cursor: pointer;
            font-size: 18px;
            line-height: 1;
            padding: 0;
            transition: color 0.2s;
        }

        .toast-close:hover {
            color: #333;
        }

        .toast-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            background: currentColor;
            opacity: 0.3;
            animation: progress 3s linear;
        }

        .toast-success .toast-progress {
            color: #198754;
        }

        .toast-danger .toast-progress {
            color: #dc3545;
        }

        .toast-warning .toast-progress {
            color: #ffc107;
        }

        .toast-info .toast-progress {
            color: #0d6efd;
        }

        @keyframes progress {
            from {
                width: 100%;
            }

            to {
                width: 0%;
            }
        }

        @media (max-width: 576px) {
            .toast-container {
                left: 10px;
                right: 10px;
                top: 10px;
            }

            .toast {
                min-width: auto;
                max-width: none;
            }
        }
    </style>

    <div class="toast-container" id="toastContainer"></div>

    <script>
        (function() {
            const toasts = <?= json_encode($toasts) ?>;
            const container = document.getElementById('toastContainer');

            const icons = {
                success: '✓',
                danger: '✕',
                warning: '⚠',
                info: 'ℹ'
            };

            const titles = {
                success: 'Success',
                danger: 'Error',
                warning: 'Warning',
                info: 'Information'
            };

            toasts.forEach((toast, index) => {
                setTimeout(() => showToast(toast), index * 150);
            });

            function showToast(toast) {
                const toastEl = document.createElement('div');
                toastEl.className = `toast toast-${toast.type}`;
                toastEl.innerHTML = `
                <div class="toast-icon">${icons[toast.type]}</div>
                <div class="toast-content">
                    <div class="toast-title">${titles[toast.type]}</div>
                    <div class="toast-message">${escapeHtml(toast.message)}</div>
                </div>
                <button class="toast-close" onclick="closeToast(this)">×</button>
                <div class="toast-progress"></div>
            `;

                container.appendChild(toastEl);

                setTimeout(() => toastEl.classList.add('show'), 10);

                setTimeout(() => {
                    toastEl.classList.add('hide');
                    setTimeout(() => toastEl.remove(), 300);
                }, 3000);
            }

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            window.closeToast = function(btn) {
                const toast = btn.closest('.toast');
                toast.classList.add('hide');
                setTimeout(() => toast.remove(), 300);
            };
        })();
    </script>
<?php
}
