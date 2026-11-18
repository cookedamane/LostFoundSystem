import { Component, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { ViewWillEnter } from '@ionic/angular';
import {
  IonButton, IonContent, IonHeader, IonInput, IonSelect, IonSelectOption, IonSpinner, IonTextarea, IonTitle, IonToolbar,
} from '@ionic/angular/standalone';
import { ApiService, Category } from '../core/api.service';
import { Notice, errorMessage, fieldErrors } from '../core/ui';

@Component({
  selector: 'app-report',
  standalone: true,
  imports: [FormsModule, IonHeader, IonToolbar, IonTitle, IonContent, IonInput, IonTextarea, IonSelect, IonSelectOption, IonButton, IonSpinner],
  template: `
  <ion-header><ion-toolbar><ion-title>Report a lost item</ion-title></ion-toolbar></ion-header>
  <ion-content class="ion-padding">
    <div class="wrap">
      <p class="eyebrow">{{ department || 'DEPARTMENT LOST & FOUND' }}</p>
      <p class="muted">Report items lost inside this department only. The office reviews your report and lists it so staff can help match it.</p>
      <form (ngSubmit)="submit()" novalidate>
        <div class="field"><ion-input label="What did you lose?" labelPlacement="stacked" fill="outline" name="name" [(ngModel)]="f.name"></ion-input>@if (errs['name']) { <p class="err">{{ errs['name'] }}</p> }</div>
        <div class="field">
          <ion-select label="Category" labelPlacement="stacked" fill="outline" interface="action-sheet" name="category_id" [(ngModel)]="f.category_id">
            @for (c of cats; track c.id) { <ion-select-option [value]="c.id">{{ c.name }}</ion-select-option> }
          </ion-select>
          @if (errs['category_id']) { <p class="err">{{ errs['category_id'] }}</p> }
        </div>
        <div class="field"><ion-select label="Where did you lose it?" labelPlacement="stacked" fill="outline" interface="action-sheet" name="location" [(ngModel)]="f.location"><ion-select-option value="">Choose department building / area</ion-select-option>@for (location of locations; track location) { <ion-select-option [value]="location">{{ location }}</ion-select-option> }</ion-select>@if (errs['location']) { <p class="err">{{ errs['location'] }}</p> }</div>
        <div class="field"><ion-input label="Date lost" labelPlacement="stacked" fill="outline" type="date" name="date_event" [max]="today" [(ngModel)]="f.date_event"></ion-input>@if (errs['date_event']) { <p class="err">{{ errs['date_event'] }}</p> }</div>
        <div class="field"><ion-textarea label="Describe it (color, brand, marks)" labelPlacement="stacked" fill="outline" rows="4" name="description" [(ngModel)]="f.description"></ion-textarea>@if (errs['description']) { <p class="err">{{ errs['description'] }}</p> }</div>
        <div class="field">
          <input #file type="file" accept="image/*" hidden (change)="pick($event)" />
          <ion-button fill="outline" (click)="file.click()">{{ photo ? 'Change photo' : 'Add a photo (optional)' }}</ion-button>
          @if (photo) { <img class="photo" [src]="photo" alt="Selected photo" /> }
          @if (errs['photo']) { <p class="err">{{ errs['photo'] }}</p> }
        </div>
        <ion-button expand="block" type="submit" [disabled]="busy">
          @if (busy) { <ion-spinner name="dots"></ion-spinner> } @else { Send report }
        </ion-button>
      </form>
    </div>
  </ion-content>`,
})
export class ReportPage implements ViewWillEnter {
  private api = inject(ApiService);
  private notice = inject(Notice);
  private router = inject(Router);
  cats: Category[] = [];
  locations: string[] = [];
  department = '';
  today = new Date().toISOString().slice(0, 10);
  f: Record<string, any> = { name: '', category_id: null, location: '', date_event: this.today, description: '' };
  errs: Record<string, string> = {};
  photo = '';
  busy = false;

  ionViewWillEnter() {
    if (!this.cats.length) { this.api.categories().subscribe({ next: r => (this.cats = r.categories), error: e => this.notice.show(errorMessage(e), 'danger') }); }
    this.api.departmentInfo().subscribe({ next: r => { this.locations = r.locations; this.department = r.department; }, error: e => this.notice.show(errorMessage(e), 'danger') });
  }

  // Shrinks the picture before upload so it stays small on mobile data.
  pick(ev: Event) {
    const file = (ev.target as HTMLInputElement).files?.[0];
    if (!file) { return; }
    if (!file.type.startsWith('image/')) { this.notice.show('Choose an image file.', 'danger'); return; }
    const img = new Image();
    const url = URL.createObjectURL(file);
    img.onload = () => {
      const s = Math.min(1, 1024 / Math.max(img.width, img.height));
      const c = document.createElement('canvas');
      c.width = Math.round(img.width * s);
      c.height = Math.round(img.height * s);
      c.getContext('2d')!.drawImage(img, 0, 0, c.width, c.height);
      this.photo = c.toDataURL('image/jpeg', 0.8);
      URL.revokeObjectURL(url);
    };
    img.onerror = () => this.notice.show('That image could not be read.', 'danger');
    img.src = url;
  }

  submit() {
    this.errs = {};
    const f = this.f;
    if (String(f['name']).trim().length < 2) { this.errs['name'] = 'Enter the item name.'; }
    if (!f['category_id']) { this.errs['category_id'] = 'Choose a category.'; }
    if (!this.locations.includes(String(f['location']))) { this.errs['location'] = 'Choose a location inside the department.'; }
    if (!f['date_event'] || f['date_event'] > this.today) { this.errs['date_event'] = 'Enter a date that is not in the future.'; }
    if (String(f['description']).trim().length < 5) { this.errs['description'] = 'Add a short description (5+ characters).'; }
    if (Object.keys(this.errs).length) { return; }

    this.busy = true;
    this.api.reportLost({ ...f, photo: this.photo || null }).subscribe({
      next: r => {
        this.busy = false;
        this.f = { name: '', category_id: null, location: '', date_event: this.today, description: '' };
        this.photo = '';
        this.notice.show(r.message, 'success');
        this.router.navigateByUrl('/tabs/activity');
      },
      error: e => { this.busy = false; this.errs = fieldErrors(e); if (!Object.keys(this.errs).length) { this.notice.show(errorMessage(e), 'danger'); } },
    });
  }
}
