import { Component, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { IonButton, IonContent, IonInput, IonSpinner } from '@ionic/angular/standalone';
import { ApiService } from '../core/api.service';
import { errorMessage, fieldErrors } from '../core/ui';

@Component({
  selector: 'app-register',
  standalone: true,
  imports: [FormsModule, RouterLink, IonContent, IonInput, IonButton, IonSpinner],
  template: `
  <ion-content class="ion-padding">
    <div class="wrap">
      <div class="login-brand compact-brand">
        <span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 32 32" fill="none"><circle cx="13.5" cy="13.5" r="9" stroke="currentColor" stroke-width="2.4"/><path d="m20.2 20.2 7.1 7.1" stroke="currentColor" stroke-width="2.8" stroke-linecap="round"/><path d="M13.5 9.5v8M9.5 13.5h8" stroke="#235347" stroke-width="1.8" stroke-linecap="round"/></svg></span>
        <span><p class="eyebrow">DEPARTMENT LOST &amp; FOUND</p><h1>Create your account</h1></span>
      </div>
      <p class="muted">You need an account to report items and send claims.</p>
      <p class="muted registration-help">Use a unique email address. Passwords must be 8–72 characters and include at least one letter and one number. Student/employee ID and phone are optional.</p>
      <form (ngSubmit)="submit()" novalidate>
        @for (f of fields; track f.key) {
          <div class="field">
            <ion-input [label]="f.label" labelPlacement="stacked" fill="outline" [type]="f.type" [name]="f.key" [(ngModel)]="form[f.key]"></ion-input>
            @if (errs[f.key]) { <p class="err">{{ errs[f.key] }}</p> }
          </div>
        }
        @if (error) { <p class="err">{{ error }}</p> }
        <ion-button expand="block" type="submit" [disabled]="busy">
          @if (busy) { <ion-spinner name="dots"></ion-spinner> } @else { Create account }
        </ion-button>
      </form>
      <p class="ion-text-center">Already registered? <a routerLink="/login">Sign in</a></p>
    </div>
  </ion-content>`,
})
export class RegisterPage {
  private api = inject(ApiService);
  private router = inject(Router);
  fields = [
    { key: 'name', label: 'Full name', type: 'text' },
    { key: 'email', label: 'Email', type: 'email' },
    { key: 'student_id', label: 'Student / employee ID (optional)', type: 'text' },
    { key: 'phone', label: 'Phone (optional)', type: 'tel' },
    { key: 'password', label: 'Password (8+ characters, letters and numbers)', type: 'password' },
    { key: 'confirm', label: 'Confirm password', type: 'password' },
  ];
  form: Record<string, string> = { name: '', email: '', student_id: '', phone: '', password: '', confirm: '' };
  errs: Record<string, string> = {};
  error = '';
  busy = false;

  submit() {
    this.errs = {}; this.error = '';
    const f = this.form;
    const name = f['name'].trim();
    const email = f['email'].trim().toLowerCase();
    const passwordBytes = new TextEncoder().encode(f['password']).length;
    if (name.length < 2 || name.length > 120) { this.errs['name'] = 'Enter a name between 2 and 120 characters.'; }
    if (email.length > 150 || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { this.errs['email'] = 'Enter a valid email address (up to 150 characters).'; }
    if (f['student_id'].trim().length > 40) { this.errs['student_id'] = 'Student ID must be 40 characters or fewer.'; }
    if (f['phone'].trim() && !/^[0-9+\-\s()]{7,30}$/.test(f['phone'].trim())) { this.errs['phone'] = 'Use 7–30 digits or phone symbols (+, -, spaces, parentheses).'; }
    if (passwordBytes < 8 || passwordBytes > 72 || !/[A-Za-z]/.test(f['password']) || !/\d/.test(f['password'])) { this.errs['password'] = 'Use 8–72 bytes with at least one letter and one number.'; }
    if (f['password'] !== f['confirm']) { this.errs['confirm'] = 'Passwords do not match.'; }
    if (Object.keys(this.errs).length) { return; }

    this.busy = true;
    const body = {
      name,
      email,
      student_id: f['student_id'].trim(),
      phone: f['phone'].trim(),
      password: f['password'],
    };
    this.api.register(body).subscribe({
      next: () => { this.busy = false; this.router.navigateByUrl('/tabs'); },
      error: e => { this.busy = false; this.errs = fieldErrors(e); this.error = Object.keys(this.errs).length ? '' : errorMessage(e); },
    });
  }
}
