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
        <span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 32 32" fill="none"><path d="M16 3.5 27 8v7.1c0 6.7-4.4 11.2-11 13.4C10.4 26.3 6 21.8 6 15.1V8l10-4.5Z" stroke="currentColor" stroke-width="1.8"/><path d="M12.2 16.1h7.6M16 12.3v7.6" stroke="#235347" stroke-width="2.2" stroke-linecap="round"/></svg></span>
        <span><p class="eyebrow">TEAM BANGAN</p><h1>Create your account</h1></span>
      </div>
      <p class="muted">You need an account to report items and send claims.</p>
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
    { key: 'student_id', label: 'Student ID (optional)', type: 'text' },
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
    if (f['name'].trim().length < 2) { this.errs['name'] = 'Enter your full name.'; }
    if (!/^\S+@\S+\.\S+$/.test(f['email'].trim())) { this.errs['email'] = 'Enter a valid email address.'; }
    if (f['password'].length < 8 || !/[A-Za-z]/.test(f['password']) || !/\d/.test(f['password'])) { this.errs['password'] = 'Use 8 or more characters with letters and numbers.'; }
    if (f['password'] !== f['confirm']) { this.errs['confirm'] = 'Passwords do not match.'; }
    if (Object.keys(this.errs).length) { return; }

    this.busy = true;
    const { confirm, ...body } = f;
    this.api.register(body).subscribe({
      next: () => { this.busy = false; this.router.navigateByUrl('/tabs'); },
      error: e => { this.busy = false; this.errs = fieldErrors(e); this.error = Object.keys(this.errs).length ? '' : errorMessage(e); },
    });
  }
}
