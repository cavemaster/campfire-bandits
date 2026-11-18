# Campfire Bandits

Offizielle Website der Band **Campfire Bandits** – Handmade Acoustic Rock & Pub Classics.

🌐 **Live-Website:** [https://juliejung.de/campfire-bandits/](https://juliejung.de/campfire-bandits/)  
🔒 **Mitgliederbereich:** [https://juliejung.de/campfire-bandits/intern.php](https://juliejung.de/campfire-bandits/intern.php)

---

## 🎸 Features

* **Atmosphärische Startseite:**
  * Urige Kneipen- & Tavernenkulisse mit Akustikgitarre im Vordergrund.
  * Interaktiver Soundscape-Player (Web Audio API: Akustik-Akkorde & Lagerfeuerknistern direkt im Browser).
  * Fliegende Glut- und Funkenpartikel (Canvas-Animation).
  * Direkter Buchungs- & Kontakt-Button.

* **Geschützter Mitgliederbereich (`intern.php`):**
  * Passwortgeschützter interner Bereich für Bandmitglieder.
  * **E-Mail-Verteiler (`band@juliejung.de`):** Zentrale Adresse, um alle Bandmitglieder mit einer einzigen Mail zu erreichen.
  * **Dateidownloads:** Setlisten, Akkorde, Noten, PDFs und Audio-Demos direkt im Browser herunterladen.

* **Mac-Dateisynchronisation (`sync_dateien.py`):**
  * Lokaler Dateiordner (`intern_files/`) auf dem Mac.
  * Mit 1-Klick (`Dateien_Jetzt_Synchronisieren.command`) werden geänderte und neue Dateien automatisch per FTPS in den Mitgliederbereich synchronisiert.

---

## 📁 Projektstruktur

```
├── assets/
│   └── hero-bg.jpg             # Kneipen-Hintergrundbild mit Akustikgitarre
├── intern_files/               # Lokaler Ordner für Mitglieder-Downloads
│   ├── Setliste_Campfire_Bandits.txt
│   └── Band_Notizen_und_Akkorde.txt
├── index.html                  # Öffentliche Startseite
├── intern.php                  # Passwortgeschützter Mitgliederbereich
├── style.css                   # Styling & Responsive Design
├── app.js                      # Canvas-Partikel & Soundscape
├── sync_dateien.py             # Mac-Synchronisationsskript für Downloads
├── Dateien_Jetzt_Synchronisieren.command # 1-Klick Sync-Starter für macOS
└── deploy.py                   # FTPS-Deployment auf juliejung.de
```
