import { Component, inject } from '@angular/core';
import { RouterLink } from '@angular/router';
import { ViewWillEnter, ViewWillLeave } from '@ionic/angular';
import { Subscription, timer } from 'rxjs';
import {
  AlertController, IonButton, IonContent, IonHeader, IonLabel, IonRefresher, IonRefresherContent, IonSegment, IonSegmentButton,
  IonSpinner, IonTitle, IonToolbar,
} from '@ionic/angular/standalone';
import { ApiService, Claim, Item } from '../core/api.service';
import { Notice, errorMessage, label, niceDate } from '../core/ui';

@Component({
  selector: 'app-activity',
  standalone: true,
  imports: [RouterLink, IonHeader, IonToolbar, IonTitle, IonSegment, IonSegmentButton, IonLabel, IonContent, IonRefresher, IonRefresherContent, IonSpinner, IonButton],
  template: `
  <ion-header>
    <ion-toolbar><ion-title>My activity</ion-title></ion-toolbar>
    <ion-toolbar>
      <ion-segment [value]="tab" (ionChange)="tab = $any($event).detail.value">
        <ion-segment-button value="claims"><ion-label>My claims</ion-label></ion-segment-button>
        <ion-segment-button value="reports"><ion-label>My lost reports</ion-label></ion-segment-button>
      </ion-segment>
    </ion-toolbar>
  </ion-header>
  <ion-content class="ion-padding">
    <ion-refresher slot="fixed" (ionRefresh)="refresh($event)"><ion-refresher-content></ion-refresher-content></ion-refresher>
    @if (loading) { <div class="center"><ion-spinner></ion-spinner></div> }
    @else if (tab === 'claims') {
      @if (!claims.length) { <div class="empty"><h2>No claims yet</h2><p>Open a found item in Browse and tap Claim this item.</p></div> }
      @for (c of claims; track c.id) {
        <div class="card activity-card">
          <div class="activity-title"><a [routerLink]="['/item', c.item_id]"><b>{{ c.item_name }}</b></a> <span class="tag" [class]="'tag s-' + c.status">{{ label(c.status) }}</span></div>
          <p class="muted">Sent {{ date(c.created_at) }}</p>
          @if (c.status === 'approved') { <p>Approved. Bring a valid ID to the Lost and Found office.</p> }
          @if (c.status === 'approved' && c.item_status === 'returned') { <p>The item was returned to you.</p> }
          @if (c.admin_remarks) { <div class="remark">{{ c.admin_remarks }}</div> }
          @if (c.status === 'pending') { <ion-button size="small" fill="outline" color="danger" (click)="cancel(c)">Cancel claim</ion-button> }
        </div>
      }
    } @else {
      @if (!reports.length) { <div class="empty"><h2>No reports yet</h2><p>Use the Report lost tab to tell the office what you lost.</p></div> }
      @for (r of reports; track r.id) {
        <div class="card activity-card">
          <div class="activity-title"><a [routerLink]="['/item', r.id]"><b>{{ r.name }}</b></a> <span class="tag" [class]="'tag s-' + r.status">{{ statusText(r.status) }}</span></div>
          <p class="muted">{{ r.category }} &middot; {{ date(r.created_at) }}</p>
          @if (r.admin_note) { <div class="remark">{{ r.admin_note }}</div> }
        </div>
      }
    }
  </ion-content>`,
})
export class ActivityPage implements ViewWillEnter, ViewWillLeave {
  private api = inject(ApiService);
  private notice = inject(Notice);
  private alerts = inject(AlertController);
  tab = 'claims';
  claims: Claim[] = [];
  reports: Item[] = [];
  loading = true;
  private refreshing = false;
  private refreshTimer?: Subscription;
  label = label;
  date = niceDate;

  statusText(s: string) { return s === 'pending' ? 'Waiting for review' : s === 'listed' ? 'Listed' : label(s); }
  ionViewWillEnter() {
    this.load();
    this.refreshTimer?.unsubscribe();
    // Keep the student's status in step with decisions made in the staff portal.
    this.refreshTimer = timer(10000, 10000).subscribe(() => this.load());
  }

  ionViewWillLeave() { this.refreshTimer?.unsubscribe(); }

  load(done?: () => void) {
    if (this.refreshing) { done?.(); return; }
    this.refreshing = true;
    let left = 2;
    const finish = () => {
      if (--left === 0) {
        this.loading = false;
        this.refreshing = false;
        done?.();
      }
    };
    this.api.myClaims().subscribe({ next: r => { this.claims = r.claims; finish(); }, error: e => { this.notice.show(errorMessage(e), 'danger'); finish(); } });
    this.api.myItems().subscribe({ next: r => { this.reports = r.items.filter(i => i.type === 'lost'); finish(); }, error: () => finish() });
  }
  refresh(ev: any) { this.load(() => ev.target.complete()); }

  async cancel(c: Claim) {
    const a = await this.alerts.create({
      header: 'Cancel this claim?', message: `Your claim on ${c.item_name} will be removed.`,
      buttons: [{ text: 'Keep it', role: 'cancel' }, { text: 'Cancel claim', role: 'destructive', handler: () => this.doCancel(c) }],
    });
    await a.present();
  }
  private doCancel(c: Claim) {
    this.api.cancelClaim(c.id).subscribe({
      next: () => { this.claims = this.claims.filter(x => x.id !== c.id); this.notice.show('Claim cancelled.', 'success'); },
      error: e => this.notice.show(errorMessage(e), 'danger'),
    });
  }
}
