import { Component, OnInit, HostListener, PLATFORM_ID, inject } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { CommonModule, isPlatformBrowser } from '@angular/common';
import { Header } from './header/header';
import { Footer } from './footer/footer';

@Component({
  standalone: true,
  selector: 'app-root',
  imports: [RouterOutlet, Header, Footer, CommonModule],
  templateUrl: './app.html',
  styleUrls: ['./app.css']
})
export class App implements OnInit {
  private platformId = inject(PLATFORM_ID);
  protected title = 'Myportfolio';
  showBackToTop = false;
  isDarkMode = false;
  
  ngOnInit(): void {
    // Only run browser-specific code in the browser
    if (isPlatformBrowser(this.platformId)) {
      // Add Bootstrap icons CSS
      this.addBootstrapIcons();
      
      // Add preloader
      this.setupPreloader();
      
      // Check for dark mode
      this.checkDarkMode();
      
      // Listen for dark mode changes
      window.addEventListener('darkModeChange', (e: any) => {
        this.isDarkMode = e.detail.darkMode;
      });
    }
  }
  
  public checkDarkMode(): void {
    if (!isPlatformBrowser(this.platformId)) return;
    
    // Check if dark mode is enabled in localStorage
    const savedDarkMode = localStorage.getItem('darkMode');
    if (savedDarkMode === 'true') {
      this.isDarkMode = true;
    } else if (savedDarkMode === null) {
      // Check system preference if not set in localStorage
      const prefersDarkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;
      this.isDarkMode = prefersDarkMode;
    }
  }
  
  @HostListener('window:scroll', [])
  onWindowScroll(): void {
    if (!isPlatformBrowser(this.platformId)) return;
    // Show back to top button when scrolled down 300px
    this.showBackToTop = window.scrollY > 300;
  }
  
  scrollToTop(): void {
    if (!isPlatformBrowser(this.platformId)) return;
    window.scrollTo({
      top: 0,
      behavior: 'smooth'
    });
  }
  
  public addBootstrapIcons(): void {
    if (!isPlatformBrowser(this.platformId)) return;
    // Add Bootstrap icons if not already added
    if (!document.getElementById('bootstrap-icons-css')) {
      const link = document.createElement('link');
      link.id = 'bootstrap-icons-css';
      link.rel = 'stylesheet';
      link.href = 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css';
      document.head.appendChild(link);
    }
  }
  
  public setupPreloader(): void {
    if (!isPlatformBrowser(this.platformId)) return;
    // Create preloader element
    const preloader = document.createElement('div');
    preloader.className = 'preloader';
    preloader.innerHTML = `
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    `;
    
    // Add preloader styles
    const style = document.createElement('style');
    style.innerHTML = `
      .preloader {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        transition: opacity 0.5s ease, visibility 0.5s ease;
      }
      
      body.dark-mode .preloader {
        background-color: #121212;
      }
      
      .preloader.fade-out {
        opacity: 0;
        visibility: hidden;
      }
    `;
    document.head.appendChild(style);
    
    // Add preloader to body
    document.body.appendChild(preloader);
    
    // Remove preloader after page load (reduced delay for faster UX)
    window.addEventListener('load', () => {
      setTimeout(() => {
        preloader.classList.add('fade-out');
        setTimeout(() => {
          preloader.remove();
        }, 300);
      }, 100);
    });
  }
}
