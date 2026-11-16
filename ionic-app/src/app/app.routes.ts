import { Routes } from '@angular/router';
import { authGuard, guestGuard } from './core/api.service';

export const routes: Routes = [
  { path: 'login', canActivate: [guestGuard], loadComponent: () => import('./pages/login.page').then(m => m.LoginPage) },
  { path: 'register', canActivate: [guestGuard], loadComponent: () => import('./pages/register.page').then(m => m.RegisterPage) },
  {
    path: 'tabs',
    canActivate: [authGuard],
    loadComponent: () => import('./pages/tabs.page').then(m => m.TabsPage),
    children: [
      { path: 'browse', loadComponent: () => import('./pages/browse.page').then(m => m.BrowsePage) },
      { path: 'report', loadComponent: () => import('./pages/report.page').then(m => m.ReportPage) },
      { path: 'activity', loadComponent: () => import('./pages/activity.page').then(m => m.ActivityPage) },
      { path: 'profile', loadComponent: () => import('./pages/profile.page').then(m => m.ProfilePage) },
      { path: '', redirectTo: 'browse', pathMatch: 'full' },
    ],
  },
  { path: 'item/:id', canActivate: [authGuard], loadComponent: () => import('./pages/item-detail.page').then(m => m.ItemDetailPage) },
  { path: '', redirectTo: 'tabs', pathMatch: 'full' },
  { path: '**', redirectTo: 'tabs' },
];
