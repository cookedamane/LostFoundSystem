import { Component, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { IonBackButton, IonButton, IonButtons, IonContent, IonHeader, IonSpinner, IonTextarea, IonTitle, IonToolbar } from '@ionic/angular/standalone';
import { ApiService, Item, MyClaim } from '../core/api.service';
import { Notice, errorMessage, fieldErrors, label, niceDate } from '../core/ui';

@Component({
  selector: 'app-item-detail',
  standalone: true,
  imports: [FormsModule, IonHeader, IonToolbar, IonButtons, IonBackButton, IonTitle, IonContent, IonTextarea, IonButton, IonSpinner],
  template: `
  <ion-header>
    <ion-toolbar><ion-buttons slot="start"><ion-back-button defaultHref="/tabs/browse"></ion-back-button></ion-buttons><ion-title>Item details</ion-title></ion-toolbar>
  </ion-header>
  <ion-content class="ion-padding">
    <div class="wrap">
    @if (loading) { <div class="center"><ion-spinner></ion-spinner></div> }
    @else if (item) {
      @if (item.photo_url) { <img class="photo item-photo" [src]="item.photo_url" [alt]="item.name" /> }
      <div class="item-title-block"><p class="eyebrow">ITEM DETAILS</p><h1>{{ item.name }}</h1>
      <p class="tag-row"><span class="tag" [class]="'tag t-' + item.type">{{ label(item.type) }}</span> <span class="tag" [class]="'tag s-' + item.status">{{ label(item.status) }}</span></p></div>
      <div class="card detail-card">
        <p><b>Category:</b> {{ item.category }}</p>
        <p><b>{{ item.type === 'found' ? 'Found at:' : 'Lost at:' }}</b> {{ item.location }}</p>
        <p><b>Date:</b> {{ date(item.date_event) }}</p>
        <p><b>Description:</b> {{ item.description }}</p>
      </div>
      @if (item.mine && item.admin_note) { <div class="remark">Office note: {{ item.admin_note }}</div> }
      @if (item.mine && item.status === 'pending') { <p class="muted">Your report is waiting for the office to review it.</p> }

      @if (item.type === 'found') {
        @if (myClaim && myClaim.status !== 'rejected') {
          <div class="card">
            <h2>Your claim</h2>
            <p><span class="tag" [class]="'tag s-' + myClaim.status">{{ label(myClaim.status) }}</span></p>
            @if (myClaim.status === 'approved') { <p>Your claim was approved. Bring a valid ID to the Lost and Found office to pick up the item.</p> }
            @if (myClaim.admin_remarks) { <div class="remark">{{ myClaim.admin_remarks }}</div> }
          </div>
        } @else if (item.status === 'listed') {
          <h2>Is this yours?</h2>
          @if (myClaim?.status === 'rejected') { <div class="remark">Your last claim was rejected. {{ myClaim?.admin_remarks }} You can send a new claim with better proof.</div> }
          <p class="muted">Describe something only the owner would know. The office checks it before releasing the item.</p>
          <form (ngSubmit)="claim()" novalidate>
            <div class="field"><ion-textarea label="Proof of ownership" labelPlacement="stacked" fill="outline" rows="4" name="proof" [(ngModel)]="proof"></ion-textarea>@if (errs['proof_of_ownership']) { <p class="err">{{ errs['proof_of_ownership'] }}</p> }</div>
            <div class="field"><ion-textarea label="Other identifying details (optional)" labelPlacement="stacked" fill="outline" rows="2" name="details" [(ngModel)]="details"></ion-textarea>@if (errs['identifying_details']) { <p class="err">{{ errs['identifying_details'] }}</p> }</div>
            <ion-button expand="block" type="submit" [disabled]="busy">@if (busy) { <ion-spinner name="dots"></ion-spinner> } @else { Claim this item }</ion-button>
          </form>
        } @else { <p class="muted">This item is no longer available for claiming.</p> }
      }
    } @else { <div class="empty"><h2>Item not found</h2><p>It may have been returned or removed.</p></div> }
    </div>
  </ion-content>`,
})
export class ItemDetailPage {
  private api = inject(ApiService);
  private route = inject(ActivatedRoute);
  private router = inject(Router);
  private notice = inject(Notice);
  item: Item | null = null;
  myClaim: MyClaim | null = null;
  loading = true;
  busy = false;
  proof = '';
  details = '';
  errs: Record<string, string> = {};
  label = label;
  date = niceDate;

  constructor() { this.load(); }

  load() {
    this.api.item(Number(this.route.snapshot.paramMap.get('id'))).subscribe({
      next: r => { this.item = r.item; this.myClaim = r.my_claim; this.loading = false; },
      error: e => { this.loading = false; if (e.status !== 404) { this.notice.show(errorMessage(e), 'danger'); } },
    });
  }

  claim() {
    this.errs = {};
    if (this.proof.trim().length < 10) { this.errs['proof_of_ownership'] = 'Describe your proof in at least 10 characters.'; return; }
    this.busy = true;
    this.api.claim({ item_id: this.item!.id, proof_of_ownership: this.proof, identifying_details: this.details }).subscribe({
      next: r => { this.busy = false; this.notice.show(r.message, 'success'); this.router.navigateByUrl('/tabs/activity'); },
      error: e => { this.busy = false; this.errs = fieldErrors(e); if (!Object.keys(this.errs).length) { this.notice.show(errorMessage(e), 'danger'); } },
    });
  }
}
