/**
 * اسکریپت تعاملی پیشخوان‌یار — نسخه پیشرفته و بهینه
 */
document.addEventListener('DOMContentLoaded', () => {
    // باز و بسته کردن منوی موبایل
    const toggleBtn = document.querySelector('.mobile-toggle');
    const mainNav = document.querySelector('.main-nav');
    if (toggleBtn && mainNav) {
        toggleBtn.addEventListener('click', () => {
            mainNav.classList.toggle('open');
            const expanded = mainNav.classList.contains('open');
            toggleBtn.setAttribute('aria-expanded', expanded);
        });
    }

    // جابجایی تب‌ها
    const tabButtons = document.querySelectorAll('.tab-btn[data-target]');
    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const group = btn.closest('.tabs-container') || document;
            const targetId = btn.getAttribute('data-target');
            
            // غیرفعال کردن تب‌های دیگر در همان گروه
            group.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            group.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
            
            // فعال کردن تب انتخابی
            btn.classList.add('active');
            const targetPane = document.getElementById(targetId);
            if (targetPane) {
                targetPane.classList.add('active');
            }
        });
    });

    // کپی کردن به کلیپ‌بورد با بازخورد بصری
    document.querySelectorAll('[data-copy]').forEach(el => {
        el.addEventListener('click', async (e) => {
            e.preventDefault();
            const textToCopy = el.getAttribute('data-copy');
            if (!textToCopy) return;

            try {
                await navigator.clipboard.writeText(textToCopy);
                const originalHtml = el.innerHTML;
                el.innerHTML = '<span style="color:#059669;font-weight:700">کپی شد ✓</span>';
                setTimeout(() => {
                    el.innerHTML = originalHtml;
                }, 2000);
            } catch (err) {
                // روش جایگزین اگر کلیپ‌بورد مدرن در دسترس نبود
                const input = document.createElement('input');
                input.value = textToCopy;
                document.body.appendChild(input);
                input.select();
                document.execCommand('copy');
                document.body.removeChild(input);
                alert('شماره یا کد در کلیپ‌بورد کپی شد: ' + textToCopy);
            }
        });
    });

    // بستن پیام‌های هشدار
    document.querySelectorAll('.alert-close').forEach(btn => {
        btn.addEventListener('click', () => {
            const alert = btn.closest('.alert');
            if (alert) alert.remove();
        });
    });

    // چاپ رسید نوبت
    document.querySelectorAll('.btn-print').forEach(btn => {
        btn.addEventListener('click', () => {
            window.print();
        });
    });
});
