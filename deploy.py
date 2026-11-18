#!/usr/bin/env python3
"""
Deploy script for Campfire Bandits website to:
https://juliejung.de/campfire-bandits/
"""
import os
import sys
import json
from ftplib import FTP_TLS, error_perm

CONFIG_PATH = os.path.join(os.path.dirname(__file__), '.ftp_config.json')
LOCAL_DIR = os.path.dirname(__file__)
SUBDIR = "campfire-bandits"

def load_config():
    with open(CONFIG_PATH, 'r') as f:
        return json.load(f)

def ensure_dir(ftps, dirname):
    try:
        ftps.cwd(dirname)
    except error_perm:
        print(f"Erstelle Verzeichnis '{dirname}'...")
        ftps.mkd(dirname)
        ftps.cwd(dirname)

def upload_directory(ftps, local_dir):
    for item in sorted(os.listdir(local_dir)):
        if item.startswith('.') or item == 'deploy.py' or item.endswith('.pyc') or item == '__pycache__':
            continue
        local_path = os.path.join(local_dir, item)
        if os.path.isfile(local_path):
            print(f"  Lade Datei hoch: {item} ...", end=" ", flush=True)
            with open(local_path, 'rb') as f:
                ftps.storbinary(f"STOR {item}", f)
            print("✓")
        elif os.path.isdir(local_path):
            print(f"Verzeichnis: {item}/")
            ensure_dir(ftps, item)
            upload_directory(ftps, local_path)
            ftps.cwd("..")

def main():
    config = load_config()
    print(f"Verbinde zu {config['host']} via FTPS...")
    ftps = FTP_TLS(timeout=20)
    ftps.connect(config['host'], 21)
    ftps.login(config['username'], config['password'])
    ftps.prot_p()
    print("✓ Verbunden.")

    ftps.cwd('httpdocs')
    ensure_dir(ftps, SUBDIR)
    print(f"Zielverzeichnis auf Server: {ftps.pwd()}")

    upload_directory(ftps, LOCAL_DIR)

    ftps.cwd("..")
    ftps.quit()
    print("\n✓ Erfolgreich online gestellt:")
    print(f"https://{config['host']}/{SUBDIR}/")

if __name__ == '__main__':
    main()
