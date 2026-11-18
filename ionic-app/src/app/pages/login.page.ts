import { Component, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { Capacitor } from '@capacitor/core';
import { IonButton, IonContent, IonInput, IonSpinner } from '@ionic/angular/standalone';
import { ApiService } from '../core/api.service';
import { errorMessage } from '../core/ui';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [FormsModule, RouterLink, IonContent, IonInput, IonButton, IonSpinner],
  template: `
  <ion-content class="ion-padding">
    <div class="wrap login-wrap">
      <div class="login-brand">
        <span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 32 32" fill="none"><path d="M16 3.5 27 8v7.1c0 6.7-4.4 11.2-11 13.4C10.4 26.3 6 21.8 6 15.1V8l10-4.5Z" stroke="currentColor" stroke-width="1.8"/><path d="M12.2 16.1h7.6M16 12.3v7.6" stroke="#235347" stroke-width="2.2" stroke-linecap="round"/></svg></span>
        <span><p class="eyebrow">TEAM BANGAN</p><h1>Lost &amp; Found</h1></span>
      </div>
      <p class="muted login-intro">Report what you lost. Claim what you left behind.</p>
      @if (nativeApp) {
        <section class="server-card" aria-label="Laptop server connection">
          <h2>Connect to your laptop</h2>
          <p class="muted">Use the laptop's current Wi-Fi or hotspot IPv4 address. Example: http://192.168.1.25:8000/api</p>
          <div class="field"><ion-input label="API server address" labelPlacement="stacked" fill="outline" type="url" name="apiUrl" [(ngModel)]="apiUrl"></ion-input></div>
          <ion-button expand="block" fill="outline" type="button" [disabled]="checkingServer" (click)="checkServer()">
            @if (checkingServer) { <ion-spinner name="dots"></ion-spinner> } @else { Test connection }
          </ion-button>
          @if (serverMessage) { <p class="server-message" [class.err]="!serverConnected">{{ serverMessage }}</p> }
        </section>
      } @else {
        <p class="server-hint">API server: {{ apiUrl }}</p>
      }
      <form (ngSubmit)="submit()" novalidate>
        <div class="field"><ion-input label="Email" labelPlacement="stacked" fill="outline" type="email" name="email" autocomplete="email" [(ngModel)]="email"></ion-input></div>
        <div class="field"><ion-input label="Password" labelPlacement="stacked" fill="outline" type="password" name="password" autocomplete="current-password" [(ngModel)]="password"></ion-input></div>
        @if (error) { <p class="err">{{ error }}</p> }
        <ion-button expand="block" type="submit" [disabled]="busy">
          @if (busy) { <ion-spinner name="dots"></ion-spinner> } @else { Sign in }
        </ion-button>
      </form>
      <p class="ion-text-center">New here? <a routerLink="/register">Create an account</a></p>
    </div>
  </ion-content>`,
})
export class LoginPage {
  private api = inject(ApiService);
  private router = inject(Router);
  email = '';
  password = '';
  error = '';
  busy = false;
  nativeApp = Capacitor.isNativePlatform();
  apiUrl = this.api.apiUrl;
  checkingServer = false;
  serverConnected = false;
  serverMessage = '';

  checkServer() {
    this.serverMessage = '';
    try {
      this.api.setApiUrl(this.apiUrl);
    } catch (e) {
      this.serverConnected = false;
      this.serverMessage = e instanceof Error ? e.message : 'Enter a valid API address.';
      return;
    }
    this.apiUrl = this.api.apiUrl;
    this.checkingServer = true;
    this.api.testConnection().subscribe({
      next: r => { this.checkingServer = false; this.serverConnected = r.ok; this.serverMessage = r.ok ? `Connected to the API and database${r.database ? ` (${r.database})` : ''}.` : 'The server responded, but it did not confirm the API and database are ready.'; },
      error: () => { this.checkingServer = false; this.serverConnected = false; this.serverMessage = 'Cannot reach the API. Check the laptop IP, port 8000, Wi-Fi, and that the API window is running.'; },
    });
  }

  submit() {
    this.error = '';
    if (!this.email.trim() || !this.password) { this.error = 'Enter your email and password.'; return; }
    this.busy = true;
    this.api.login(this.email.trim(), this.password).subscribe({
      next: () => { this.busy = false; this.router.navigateByUrl('/tabs'); },
      error: e => { this.busy = false; this.error = errorMessage(e); },
    });
  }
}
