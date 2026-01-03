import { Component, OnInit, OnDestroy, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { ApiService } from '../services/api.service';

interface Project {
  id?: number;
  title: string;
  description: string;
  image: string;
  technologies: string[];
  category: string;
  featured: boolean;
  githubUrl?: string;
  liveUrl?: string;
}

@Component({
  standalone: true,
  selector: 'app-admin-dashboard',
  imports: [CommonModule, FormsModule],
  templateUrl: './admin-dashboard.html',
  styleUrls: ['./admin-dashboard.css']
})
export class AdminDashboard implements OnInit, OnDestroy {
  private apiService = inject(ApiService);
  private router = inject(Router);
  
  projects: Project[] = [];
  loading = false;
  showForm = false;
  editMode = false;
  
  currentProject: Project = this.getEmptyProject();
  
  techInput = '';
  
  ngOnInit() {
    this.checkAuth();
    // show cached projects immediately if available to improve perceived load time
    try {
      const raw = localStorage.getItem('admin_projects_cache');
      if (raw) {
        this.projects = JSON.parse(raw) || [];
      }
    } catch (e) {
      // ignore parse errors
    }

    // Always fetch fresh data in the background and update the cache
    this.loadProjects();
    
    // Ensure we log out server-side when the admin refreshes or closes the page
    window.addEventListener('beforeunload', this.handleBeforeUnload);
  }

  ngOnDestroy() {
    try { window.removeEventListener('beforeunload', this.handleBeforeUnload); } catch(e) {}
  }

  // Use navigator.sendBeacon if available, otherwise use fetch with keepalive
  handleBeforeUnload = (event: BeforeUnloadEvent) => {
    try {
      const base = this.apiService.getBaseUrl ? this.apiService.getBaseUrl() : 'http://localhost/myportfolio';
      const url = base.replace(/\/$/, '') + '/auth.php?action=logout';
      const payload = new Blob([], { type: 'application/x-www-form-urlencoded' });
      if (navigator && typeof navigator['sendBeacon'] === 'function') {
        navigator['sendBeacon'](url, payload);
      } else {
        // Best-effort: synchronous fetch is deprecated, use keepalive if available
        try {
          fetch(url, { method: 'GET', keepalive: true });
        } catch (e) {
          // nothing we can do on unload
        }
      }
    } catch (e) {
      // swallow errors during unload
    }
  }
  
  checkAuth() {
    this.apiService.checkAuth().subscribe({
      error: () => {
        // Not authenticated -> redirect to login
        this.router.navigate(['/admin/login']);
      }
    });
  }
  
  loadProjects() {
    this.loading = true;
    this.apiService.getProjects().subscribe({
      next: (response) => {
        this.projects = response.data || [];
        // cache for next visit (small, so safe in localStorage)
        try {
          localStorage.setItem('admin_projects_cache', JSON.stringify(this.projects));
        } catch (e) {}
        this.loading = false;
      },
      error: () => {
        this.loading = false;
      }
    });
  }

  // trackBy to speed up ngFor rendering on updates
  trackByProject(index: number, project: any) {
    return project.id || index;
  }
  
  getEmptyProject(): Project {
    return {
      title: '',
      description: '',
      image: '',
      technologies: [],
      category: 'Web App',
      featured: false,
      githubUrl: '',
      liveUrl: ''
    };
  }
  
  openAddForm() {
    this.currentProject = this.getEmptyProject();
    this.editMode = false;
    this.showForm = true;
  }
  
  editProject(project: Project) {
    this.currentProject = { ...project };
    this.editMode = true;
    this.showForm = true;
  }
  
  cancelForm() {
    this.showForm = false;
    this.currentProject = this.getEmptyProject();
  }
  
  addTechnology() {
    if (this.techInput.trim()) {
      this.currentProject.technologies.push(this.techInput.trim());
      this.techInput = '';
    }
  }
  
  removeTechnology(index: number) {
    this.currentProject.technologies.splice(index, 1);
  }
  
  saveProject() {
    if (!this.currentProject.title || !this.currentProject.description) {
      alert('Title and description are required');
      return;
    }
    
    this.loading = true;
    
    const observable = this.editMode
      ? this.apiService.updateProject(this.currentProject)
      : this.apiService.createProject(this.currentProject);
    
    observable.subscribe({
      next: () => {
        this.loadProjects();
        this.cancelForm();
        this.loading = false;
        // Clear any cached projects data to force refresh on projects page
        this.clearProjectsCache();
      },
      error: (error) => {
        alert('Error: ' + error.message);
        this.loading = false;
      }
    });
  }
  
  deleteProject(id: number) {
    if (!confirm('Are you sure you want to delete this project?')) {
      return;
    }
    
    this.loading = true;
    this.apiService.deleteProject(id).subscribe({
      next: () => {
        this.loadProjects();
        // Clear any cached projects data to force refresh on projects page
        this.clearProjectsCache();
      },
      error: (error) => {
        alert('Error: ' + error.message);
        this.loading = false;
      }
    });
  }
  
  clearProjectsCache() {
    // Dispatch a custom event to notify other components that projects have changed
    window.dispatchEvent(new CustomEvent('projectsUpdated'));
  }
  
  logout() {
    this.apiService.adminLogout().subscribe({
      next: () => {
        this.router.navigate(['/admin/login']);
      },
      error: () => {
        // navigate back to login even if logout failed
        this.router.navigate(['/admin/login']);
      }
    });
  }
}
