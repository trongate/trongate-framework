const TG_ADMIN = {
  init() {
    this.hamburger = document.getElementById('hamburger');
    this.slideNav  = document.getElementById('slide-nav');
    this.overlay   = document.getElementById('nav-overlay');
    this.closeBtn  = document.getElementById('close-slide-nav');
    this.topRhsSelectors = document.querySelectorAll('.top-rhs-selector');
    this.adminSettingsDropdown = document.getElementById('admin-settings-dropdown');
    
    // Initialize dropdown navigation
    this.initDropdowns();
    
    // Initialize admin settings dropdown
    this.initAdminDropdown();
    
    // ------------------------------------------------------------------
    // INSTANT OPEN (touchstart + mousedown)
    // ------------------------------------------------------------------
    const triggerOpen = (e) => {
      if (e.button === undefined || e.button === 0) {
        e.preventDefault();
        this.openSlideNav();
      }
    };
    this.hamburger?.addEventListener('touchstart', triggerOpen, { passive: false });
    this.hamburger?.addEventListener('mousedown',  triggerOpen);
    // Fallback click for keyboard only
    this.hamburger?.addEventListener('click', (e) => {
      if (!this.slideNav?.classList.contains('open')) {
        e.preventDefault();
        this.openSlideNav();
      }
    });
    // ------------------------------------------------------------------
    // INSTANT CLOSE — now on touchstart/mousedown too!
    // ------------------------------------------------------------------
    const triggerClose = (e) => {
      if (e.button === undefined || e.button === 0) {
        e.preventDefault();
        this.closeSlideNav();
      }
    };
    // Close button (×)
    this.closeBtn?.addEventListener('touchstart', triggerClose, { passive: false });
    this.closeBtn?.addEventListener('mousedown',  triggerClose);
    // Overlay (tap outside)
    this.overlay?.addEventListener('touchstart', triggerClose, { passive: false });
    this.overlay?.addEventListener('mousedown',  triggerClose);
    // Keep click fallbacks (keyboard accessibility + old devices)
    this.closeBtn?.addEventListener('click', (e) => {
      e.preventDefault();
      this.closeSlideNav();
    });
    this.overlay?.addEventListener('click', (e) => {
      e.preventDefault();
      this.closeSlideNav();
    });
    // Escape key (still instant — keyboard doesn't have touch delay)
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && this.slideNav?.classList.contains('open')) {
        this.closeSlideNav();
      }
    });
  },
  
  initDropdowns() {
    const dropdowns = document.querySelectorAll('.side-nav-menu .nav-dropdown');
    
    dropdowns.forEach((dropdown) => {
      dropdown.addEventListener('click', (e) => {
        const arrow = dropdown.querySelector('.arrow-icon');
        arrow?.classList.toggle('rotate');
        
        dropdown.classList.toggle('open');
        
        const submenu = dropdown.querySelector('.nav-submenu');
        
        if (submenu.style.maxHeight && submenu.style.maxHeight !== '0px') {
          submenu.style.maxHeight = '0';
        } else {
          submenu.style.maxHeight = submenu.scrollHeight + 'px';
        }
      });
    });
  },
  
  initAdminDropdown() {
    // Toggle dropdown when clicking any top-rhs-selector (mobile or desktop)
    this.topRhsSelectors?.forEach((selector) => {
      selector.addEventListener('click', (e) => {
        e.stopPropagation();
        this.adminSettingsDropdown?.classList.toggle('active');
      });
    });
    
    // Close dropdown when clicking anywhere else on the page
    document.body.addEventListener('click', (e) => {
      if (this.adminSettingsDropdown && 
          !this.adminSettingsDropdown.contains(e.target) && 
          !e.target.closest('.top-rhs-selector')) {
        this.adminSettingsDropdown.classList.remove('active');
      }
    });
    
    // Close dropdown when pressing Escape key
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && this.adminSettingsDropdown?.classList.contains('active')) {
        this.adminSettingsDropdown.classList.remove('active');
      }
    });
  },
  
  openSlideNav() {
    this.slideNav?.classList.add('open');
    this.overlay?.classList.add('open');
    document.body.classList.add('nav-open');
  },
  
  closeSlideNav() {
    this.slideNav?.classList.remove('open');
    this.overlay?.classList.remove('open');
    document.body.classList.remove('nav-open');
  }
};

document.addEventListener('DOMContentLoaded', () => TG_ADMIN.init());