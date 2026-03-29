/**
 * Theme Manager for SCHOOLAPP
 * Handles professional dark/light mode switching and persistence
 */

const ThemeManager = {
    storageKey: 'theme',
    initialized: false,
    
    init() {
        if (this.initialized) return;
        this.initialized = true;

        console.log('ThemeManager: Initializing...');
        const savedTheme = this.getSavedTheme();
        console.log('ThemeManager: Applying theme:', savedTheme);
        this.setTheme(savedTheme);
        this.initToggles();
        this.watchSystemPreference();
    },

    getSavedTheme() {
        return localStorage.getItem(this.storageKey) || 'light';
    },

    setTheme(theme) {
        console.log('ThemeManager: Setting theme to:', theme);
        const html = document.documentElement;
        let themeToApply = theme;
        
        if (theme === 'auto') {
            themeToApply = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }
        
        html.setAttribute('data-bs-theme', themeToApply);
        localStorage.setItem(this.storageKey, theme);
        this.updateIcons(themeToApply);
        
        // Dispatch custom event for other components
        window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: themeToApply } }));
    },

    updateIcons(appliedTheme) {
        console.log('ThemeManager: Updating icons for:', appliedTheme);
        const themeToggles = document.querySelectorAll('#themeToggle');

        if (appliedTheme === 'dark') {
            themeToggles.forEach(btn => {
                btn.classList.add('border-info');
                btn.style.borderColor = 'rgba(0, 255, 255, 0.3)';
            });
        } else {
            themeToggles.forEach(btn => {
                btn.classList.remove('border-info');
                btn.style.borderColor = 'rgba(0, 0, 0, 0.1)';
            });
        }
    },

    initToggles() {
        console.log('ThemeManager: Initializing toggles...');
        
        // Use event delegation on the document for the "Auto" preference toggle if it exists
        // The main themeToggle button is handled via inline onclick in header.php for reliability
        document.addEventListener('click', (e) => {
            const autoToggle = e.target.closest('#themeAuto');
            if (autoToggle) {
                console.log('ThemeManager: Auto toggle clicked');
                e.preventDefault();
                this.setTheme('auto');
            }
        });
    },

    watchSystemPreference() {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (this.getSavedTheme() === 'auto') {
                this.setTheme('auto');
            }
        });
    }
};

// Make it global
window.ThemeManager = ThemeManager;

// Initialize
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => ThemeManager.init());
} else {
    ThemeManager.init();
}
