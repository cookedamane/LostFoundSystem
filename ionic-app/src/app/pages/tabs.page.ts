import { Component } from '@angular/core';
import { IonIcon, IonLabel, IonTabBar, IonTabButton, IonTabs } from '@ionic/angular/standalone';
import { addIcons } from 'ionicons';
import { addCircleOutline, listOutline, personOutline, searchOutline } from 'ionicons/icons';

@Component({
  selector: 'app-tabs',
  standalone: true,
  imports: [IonTabs, IonTabBar, IonTabButton, IonIcon, IonLabel],
  template: `
  <ion-tabs>
    <ion-tab-bar slot="bottom">
      <ion-tab-button tab="browse"><ion-icon name="search-outline"></ion-icon><ion-label>Browse</ion-label></ion-tab-button>
      <ion-tab-button tab="report"><ion-icon name="add-circle-outline"></ion-icon><ion-label>Report lost</ion-label></ion-tab-button>
      <ion-tab-button tab="activity"><ion-icon name="list-outline"></ion-icon><ion-label>Activity</ion-label></ion-tab-button>
      <ion-tab-button tab="profile"><ion-icon name="person-outline"></ion-icon><ion-label>Profile</ion-label></ion-tab-button>
    </ion-tab-bar>
  </ion-tabs>`,
})
export class TabsPage {
  constructor() { addIcons({ searchOutline, addCircleOutline, listOutline, personOutline }); }
}
