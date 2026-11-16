import { Component, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { IonButton, IonContent, IonHeader, IonInput, IonTitle, IonToolbar } from '@ionic/angular/standalone';
import { ApiService } from '../core/api.service';
import { Notice, errorMessage, fieldErrors } from '../core/ui';

@Component({
  selector: 'app-profile',
  standalone: true,
  imports: [FormsModule, IonHeader, IonToolbar, IonTitle, IonContent, IonInput, IonButton],
  template: `
  <ion-header><ion-toolbar><ion-title>Profile</ion-title></ion-toolbar></ion-header>
  <ion-content class="ion-padding">
    <div class="wrap">
      <div class="card"><p><b>{{ email }}</b></p><p class="muted">Student ID: {{ studentId || 'Not set' }}</p></div>

      <h2>Your details</h2>
      <form (ngSubmit)="save()" novalidate>
        <div class="field"><ion-input label="Full name" labelPlacement="stacked" fill="outline" name="name" [(ngModel)]="name"></ion-input>@if (errs['name']) { <p class="err">{{ errs['name'] }}</p> }</div>
        <div class="field"><ion-input label="Phone" labelPlacement="stacked" fill="outline" type="tel" name="phone" [(ngModel)]="phone"></ion-input>@if (errs['phone']) { <p class="err">{{ errs['phone'] }}</p> }</div>
        <ion-button expand="block" type="submit">Save details</ion-button>
      </form>

      <h2 style="margin-top:1.5rem">Change password</h2>
      <form (ngSubmit)="changePassword()" novalidate>
        <div class="field"><ion-input label="Current password" labelPlacement="stacked" fill="outline" type="password" name="cur" [(ngModel)]="cur"></ion-input>@if (pwErrs['current_password']) { <p class="err">{{ pwErrs['current_password'] }}</p> }</div>
        <div class="field"><ion-input label="New password" labelPlacement="stacked" fill="outline" type="password" name="next" [(ngModel)]="next"></ion-input>@if (pwErrs['new_password']) { <p class="err">{{ pwErrs['new_password'] }}</p> }</div>
        <ion-button expand="block" fill="outline" type="submit">Change password</ion-button>
      </form>

      <ion-button expand="block" color="danger" fill="clear" style="margin-top:1.5rem" (click)="logout()">Sign out</ion-button>
    </div>
  </ion-content>`,
})
export class ProfilePage {
  private api = inject(ApiService);
  private notice = inject(Notice);
  private router = inject(Router);
  email = this.api.user?.email ?? '';
  studentId = this.api.user?.student_id ?? '';
  name = this.api.user?.name ?? '';
  phone = this.api.user?.phone ?? '';
  cur = '';
  next = '';
  errs: Record<string, string> = {};
  pwErrs: Record<string, string> = {};

  save() {
    this.errs = {};
    if (this.name.trim().length < 2) { this.errs['name'] = 'Enter your full name.'; return; }
    this.api.updateProfile({ name: this.name, phone: this.phone || '' }).subscribe({
      next: () => this.notice.show('Details saved.', 'success'),
      error: e => { this.errs = fieldErrors(e); if (!Object.keys(this.errs).length) { this.notice.show(errorMessage(e), 'danger'); } },
    });
  }

  changePassword() {
    this.pwErrs = {};
    if (!this.cur) { this.pwErrs['current_password'] = 'Enter your current password.'; return; }
    if (this.next.length < 8 || !/[A-Za-z]/.test(this.next) || !/\d/.test(this.next)) { this.pwErrs['new_password'] = 'Use 8 or more characters with letters and numbers.'; return; }
    this.api.changePassword({ current_password: this.cur, new_password: this.next }).subscribe({
      next: () => { this.cur = ''; this.next = ''; this.notice.show('Password changed.', 'success'); },
      error: e => { this.pwErrs = fieldErrors(e); if (!Object.keys(this.pwErrs).length) { this.notice.show(errorMessage(e), 'danger'); } },
    });
  }

  logout() { this.api.logout().subscribe(() => this.router.navigateByUrl('/login')); }
}
