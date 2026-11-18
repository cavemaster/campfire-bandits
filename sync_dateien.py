#!/usr/bin/env python3
"""
Sync-Skript für Campfire Bandits Mitgliederdateien:
Lädt alle Dateien aus dem lokalen 'intern_files'-Ordner per FTPS auf den Server
nach /httpdocs/campfire-bandits/intern_files/
"""
import os
import sys
import json
from ftplib import FTP_TLS, error_perm

PROJECT_DIR = os.path.dirname(os.path.abspath(__file__))
CONFIG_PATH = os.path.join(PROJECT_DIR, '.ftp_config.json')
LOCAL_FILES_DIR = os.path.join(PROJECT_DIR, 'intern_files')

def load_config():
    if not os.path.exists(CONFIG_PATH):
        print(f"Fehler: Konfigurationsdatei nicht gefunden: {CONFIG_PATH}")
        sys.exit(1)
    with open(CONFIG_PATH, 'r') as f:
        return json.load(f)

def ensure_dir(ftps, dirname):
    try:
        ftps.cwd(dirname)
    except error_perm:
        print(f"Erstelle Verzeichnis auf Server: {dirname}/")
        ftps.mkd(dirname)
        ftps.cwd(dirname)

def sync_files():
    if not os.path.exists(LOCAL_FILES_DIR):
        os.makedirs(LOCAL_FILES_DIR, exist_ok=True)

    config = load_config()
    print("==================================================")
    print("🎸 CAMPFIRE BANDITS // DATEI-SYNCHRONISATION")
    print("==================================================")
    print(f"Lokaler Ordner: {LOCAL_FILES_DIR}")
    print(f"Verbinde zu Server {config['host']} via FTPS...")

    ftps = FTP_TLS(timeout=20)
    ftps.connect(config['host'], 21)
    ftps.login(config['username'], config['password'])
    ftps.prot_p()
    print("✓ Verbunden.")

    ftps.cwd('httpdocs')
    ensure_dir(ftps, 'campfire-bandits')
    ensure_dir(ftps, 'intern_files')
    print(f"Zielordner: {ftps.pwd()}\n")

    local_items = sorted(os.listdir(LOCAL_FILES_DIR))
    uploaded_count = 0

    for item in local_items:
        if item.startswith('.') or item == '__pycache__':
            continue
        local_path = os.path.join(LOCAL_FILES_DIR, item)
        if os.path.isfile(local_path):
            size_kb = os.path.getsize(local_path) / 1024
            print(f"  Synchronisiere: {item} ({size_kb:.1f} KB) ...", end=" ", flush=True)
            with open(local_path, 'rb') as f:
                ftps.storbinary(f"STOR {item}", f)
            print("✓ Fertig")
            uploaded_count += 1

    ftps.cwd("../..")
    ftps.quit()

    print("\n--------------------------------------------------")
    print(f"✓ Synchronisation erfolgreich! {uploaded_count} Datei(en) aktualisiert.")
    print("Live abrufbar im geschützten Mitgliederbereich:")
    print("https://juliejung.de/campfire-bandits/intern.php")
    print("==================================================")

if __name__ == '__main__':
    sync_files()
