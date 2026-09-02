document.addEventListener('DOMContentLoaded', function() {
    // Initialize Lucide Icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    // Mobile Menu Logic
    const menuToggle    = document.getElementById('menuToggle');
    const mobileDrawer  = document.getElementById('mobileDrawer');
    const closeMenu     = document.getElementById('closeMenu');
    const drawerBackdrop = document.getElementById('drawerBackdrop');

    function openDrawer() {
        mobileDrawer.classList.add('active');
        if (drawerBackdrop) drawerBackdrop.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeDrawer() {
        mobileDrawer.classList.remove('active');
        if (drawerBackdrop) drawerBackdrop.classList.remove('active');
        document.body.style.overflow = '';
    }

    if (menuToggle && mobileDrawer && closeMenu) {
        menuToggle.addEventListener('click', openDrawer);
        closeMenu.addEventListener('click', closeDrawer);
        if (drawerBackdrop) drawerBackdrop.addEventListener('click', closeDrawer);
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeDrawer(); });
    }

    // Smooth Scroll
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });

    // Roles Tab Switcher
    const roleTabs = document.querySelectorAll('.role-tab');
    const rolePanels = document.querySelectorAll('.role-panel');

    roleTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            roleTabs.forEach(t => t.classList.remove('active'));
            rolePanels.forEach(p => p.classList.remove('active'));

            tab.classList.add('active');

            const targetId = tab.getAttribute('data-target');
            const targetPanel = document.getElementById(`panel-${targetId}`);
            if (targetPanel) {
                targetPanel.classList.add('active');
            }
        });
    });
});
