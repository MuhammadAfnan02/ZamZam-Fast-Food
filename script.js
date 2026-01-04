// ZamZam Fast Food - Main JavaScript

// DOM Elements
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the application
    initApp();
});

// Initialize the application
function initApp() {
    // Load all data from JSON
    loadData();
    
    // Setup navigation
    setupNavigation();
    
    // Setup animations on scroll
    setupScrollAnimations();
    
    // Setup back to top button
    setupBackToTop();
    
    // Setup mobile menu
    setupMobileMenu();
}

// Load data from data.json
function loadData() {
    fetch('data.json')
        .then(response => response.json())
        .then(data => {
            // Render all sections with data
            renderAbout(data.about);
            renderMenu(data.menu);
            renderOffers(data.offers);
            renderContact(data.contact);
        })
        .catch(error => {
            console.error('Error loading data:', error);
            // Fallback data in case JSON fails to load
            loadFallbackData();
        });
}

// Fallback data if JSON fails to load
function loadFallbackData() {
    const fallbackData = {
        about: {
            text: "Welcome to ZamZam Fast Food, where taste meets perfection! Since 2019, we've been serving the community with high-quality fast food made from the freshest ingredients."
        },
        contact: {
            phone: "+1 (555) 123-4567",
            location: "123 Food Street, Cityville, ST 12345",
            hours: "Mon-Sun: 10:00 AM - 11:00 PM"
        },
        menu: [
            {
                id: 1,
                name: "ZamZam Special Burger",
                price: 8.99,
                description: "Juicy beef patty with lettuce, tomato, onion, pickles, and our special sauce."
            },
            {
                id: 2,
                name: "Crispy Chicken Sandwich",
                price: 7.99,
                description: "Crispy fried chicken fillet with mayo, lettuce, and tomato on a brioche bun."
            }
        ],
        offers: {
            student: [
                {
                    id: 1,
                    title: "Student Meal Deal",
                    description: "Burger + Fries + Soft Drink at a special price for students",
                    price: 9.99
                }
            ],
            family: [
                {
                    id: 1,
                    title: "Family Feast Pack",
                    description: "4 burgers, 4 fries, 4 drinks, and a free dessert",
                    price: 29.99
                }
            ],
            special: [
                {
                    id: 1,
                    title: "Weekend Breakfast Special",
                    description: "Breakfast sandwich with coffee or juice",
                    price: 5.99
                }
            ]
        }
    };
    
    renderAbout(fallbackData.about);
    renderMenu(fallbackData.menu);
    renderOffers(fallbackData.offers);
    renderContact(fallbackData.contact);
}

// Render About section
function renderAbout(aboutData) {
    const aboutElement = document.getElementById('about-text-content');
    if (aboutElement && aboutData && aboutData.text) {
        // Replace newlines with paragraphs
        const paragraphs = aboutData.text.split('\n\n');
        let html = '';
        
        paragraphs.forEach(paragraph => {
            if (paragraph.trim()) {
                html += `<p>${paragraph}</p>`;
            }
        });
        
        aboutElement.innerHTML = html;
        
        // Add animation
        aboutElement.classList.add('fade-in');
    }
}

// Render Menu section
function renderMenu(menuData) {
    const menuContainer = document.getElementById('menu-items');
    if (!menuContainer || !menuData || !Array.isArray(menuData)) return;
    
    let html = '';
    
    menuData.forEach(item => {
        html += `
            <div class="menu-item fade-in">
                <div class="menu-item-header">
                    <h3 class="menu-item-name">${item.name}</h3>
                    <span class="menu-item-price">$${item.price.toFixed(2)}</span>
                </div>
                <p class="menu-item-description">${item.description}</p>
            </div>
        `;
    });
    
    menuContainer.innerHTML = html;
    
    // Add staggered animation
    const menuItems = menuContainer.querySelectorAll('.menu-item');
    menuItems.forEach((item, index) => {
        item.style.animationDelay = `${index * 0.1}s`;
    });
}

// Render Offers section
function renderOffers(offersData) {
    const offersContainer = document.getElementById('offers-content');
    if (!offersContainer || !offersData) return;
    
    // Default to showing student offers
    showOffersByCategory('student', offersData);
    
    // Setup tab switching
    setupOfferTabs(offersData);
}

// Show offers by category
function showOffersByCategory(category, offersData) {
    const offersContainer = document.getElementById('offers-content');
    const categoryOffers = offersData[category];
    
    if (!categoryOffers || !Array.isArray(categoryOffers)) {
        offersContainer.innerHTML = '<div class="loading">No offers available in this category.</div>';
        return;
    }
    
    let html = '<div class="offers-grid">';
    
    categoryOffers.forEach(offer => {
        html += `
            <div class="offer-card fade-in">
                <h3 class="offer-title">${offer.title}</h3>
                <p class="offer-description">${offer.description}</p>
                <div class="offer-price">$${offer.price}</div>
            </div>
        `;
    });
    
    html += '</div>';
    offersContainer.innerHTML = html;
    
    // Add staggered animation
    const offerCards = offersContainer.querySelectorAll('.offer-card');
    offerCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
    });
}

// Setup offer tabs
function setupOfferTabs(offersData) {
    const tabs = document.querySelectorAll('.offer-tab');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active class from all tabs
            tabs.forEach(t => t.classList.remove('active'));
            
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Get category
            const category = this.getAttribute('data-category');
            
            // Show offers for this category
            showOffersByCategory(category, offersData);
        });
    });
}

// Render Contact section
function renderContact(contactData) {
    if (!contactData) return;
    
    const phoneElement = document.getElementById('contact-phone');
    const locationElement = document.getElementById('contact-location');
    const hoursElement = document.getElementById('contact-hours');
    
    if (phoneElement && contactData.phone) {
        phoneElement.textContent = contactData.phone;
        phoneElement.classList.add('fade-in');
    }
    
    if (locationElement && contactData.location) {
        locationElement.textContent = contactData.location;
        locationElement.classList.add('fade-in');
    }
    
    if (hoursElement && contactData.hours) {
        hoursElement.textContent = contactData.hours;
        hoursElement.classList.add('fade-in');
    }
}

// Setup navigation
function setupNavigation() {
    const navLinks = document.querySelectorAll('.nav-link');
    const sections = document.querySelectorAll('section');
    
    // Highlight active nav link on scroll
    window.addEventListener('scroll', function() {
        let current = '';
        
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.clientHeight;
            
            if (scrollY >= (sectionTop - 200)) {
                current = section.getAttribute('id');
            }
        });
        
        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === `#${current}`) {
                link.classList.add('active');
            }
        });
    });
    
    // Smooth scrolling for nav links
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            if (targetId === '#home') {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            } else {
                const targetSection = document.querySelector(targetId);
                if (targetSection) {
                    const offsetTop = targetSection.offsetTop - 80;
                    window.scrollTo({
                        top: offsetTop,
                        behavior: 'smooth'
                    });
                }
            }
            
            // Close mobile menu if open
            const navLinksContainer = document.querySelector('.nav-links');
            if (navLinksContainer.classList.contains('active')) {
                navLinksContainer.classList.remove('active');
            }
        });
    });
}

// Setup scroll animations
function setupScrollAnimations() {
    const fadeElements = document.querySelectorAll('.fade-in, .slide-up');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                // Get animation delay from data attribute
                const delay = entry.target.getAttribute('data-delay') || '0s';
                entry.target.style.animationDelay = delay;
                entry.target.classList.add('animate');
                
                // Stop observing after animation
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });
    
    fadeElements.forEach(element => {
        observer.observe(element);
    });
}

// Setup back to top button
function setupBackToTop() {
    const backToTopBtn = document.querySelector('.back-to-top');
    
    window.addEventListener('scroll', function() {
        if (window.scrollY > 300) {
            backToTopBtn.classList.add('visible');
        } else {
            backToTopBtn.classList.remove('visible');
        }
    });
    
    backToTopBtn.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
}

// Setup mobile menu
function setupMobileMenu() {
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const navLinks = document.querySelector('.nav-links');
    
    if (mobileMenuBtn && navLinks) {
        mobileMenuBtn.addEventListener('click', function() {
            navLinks.classList.toggle('active');
            
            // Change icon
            const icon = this.querySelector('i');
            if (navLinks.classList.contains('active')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!navLinks.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
                navLinks.classList.remove('active');
                const icon = mobileMenuBtn.querySelector('i');
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });
    }
}