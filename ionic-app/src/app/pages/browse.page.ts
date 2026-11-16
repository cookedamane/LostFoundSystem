import { Component, inject } from '@angular/core';
import { RouterLink } from '@angular/router';
import { ViewWillEnter } from '@ionic/angular';
import {
  IonContent, IonHeader, IonItem, IonLabel, IonList, IonRefresher, IonRefresherContent, IonSearchbar,
  IonSegment, IonSegmentButton, IonSpinner, IonThumbnail, IonTitle, IonToolbar,
} from '@ionic/angular/standalone';
import { ApiService, Item } from '../core/api.service';
import { Notice, errorMessage, label, niceDate } from '../core/ui';

@Component({
  selector: 'app-browse',
  standalone: true,
  imports: [RouterLink, IonHeader, IonToolbar, IonTitle, IonSearchbar, IonSegment, IonSegmentButton, IonLabel, IonContent, IonRefresher, IonRefresherContent, IonList, IonItem, IonThumbnail, IonSpinner],
  template: `
  <ion-header>
    <ion-toolbar><ion-title><span class="brand-inline"><span class="brand-mini" aria-hidden="true"><svg viewBox="0 0 32 32" fill="none"><path d="M16 3.5 27 8v7.1c0 6.7-4.4 11.2-11 13.4C10.4 26.3 6 21.8 6 15.1V8l10-4.5Z" stroke="currentColor" stroke-width="1.8"/><path d="M12.2 16.1h7.6M16 12.3v7.6" stroke="#235347" stroke-width="2.2" stroke-linecap="round"/></svg></span>Lost &amp; Found</span></ion-title></ion-toolbar>
    <ion-toolbar><ion-searchbar placeholder="Search by name or place" [debounce]="400" (ionInput)="search($any($event).detail.value)"></ion-searchbar></ion-toolbar>
    <ion-toolbar>
      <ion-segment [value]="type" (ionChange)="setType($any($event).detail.value)">
        <ion-segment-button value=""><ion-label>All</ion-label></ion-segment-button>
        <ion-segment-button value="found"><ion-label>Found</ion-label></ion-segment-button>
        <ion-segment-button value="lost"><ion-label>Lost</ion-label></ion-segment-button>
      </ion-segment>
    </ion-toolbar>
  </ion-header>
  <ion-content>
    <ion-refresher slot="fixed" (ionRefresh)="refresh($event)"><ion-refresher-content></ion-refresher-content></ion-refresher>
    @if (loading) {
      <div class="center"><ion-spinner></ion-spinner></div>
    } @else if (!items.length) {
      <div class="empty"><h2>Nothing here yet</h2><p>No items match. Try another search, or report your lost item so the office can look for it.</p></div>
    } @else {
      <ion-list>
        @for (i of items; track i.id) {
          <ion-item button detail [routerLink]="['/item', i.id]">
            <ion-thumbnail slot="start">@if (i.photo_url) { <img [src]="i.photo_url" [alt]="i.name" /> }</ion-thumbnail>
            <ion-label>
              <h2>{{ i.name }}</h2>
              <p>{{ i.category }} &middot; {{ i.location }}</p>
              <p>{{ date(i.date_event) }}</p>
            </ion-label>
            <span slot="end" class="tag" [class]="'tag t-' + i.type">{{ label(i.type) }}</span>
          </ion-item>
        }
      </ion-list>
    }
  </ion-content>`,
})
export class BrowsePage implements ViewWillEnter {
  private api = inject(ApiService);
  private notice = inject(Notice);
  items: Item[] = [];
  type = '';
  q = '';
  loading = true;
  label = label;
  date = niceDate;

  ionViewWillEnter() { this.load(); }
  search(v: string) { this.q = (v || '').trim(); this.load(); }
  setType(v: string) { this.type = v || ''; this.load(); }

  load(done?: () => void) {
    const params: Record<string, string> = {};
    if (this.type) { params['type'] = this.type; }
    if (this.q) { params['q'] = this.q; }
    this.api.items(params).subscribe({
      next: r => { this.items = r.items; this.loading = false; done?.(); },
      error: e => { this.loading = false; done?.(); this.notice.show(errorMessage(e), 'danger'); },
    });
  }
  refresh(ev: any) { this.load(() => ev.target.complete()); }
}
