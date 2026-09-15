# Hanko Apartmaji

Spletna stran za tri apartmaje (Lipa, Javor, Hrast) z koledarjem razpoložljivosti, rezervacijami, prevozom na letališče v slogu GoOpti in admin panelom.

## Kaj je notri

- Javna stran: apartmaji, koledar, rezervacija, prevoz
- Ob rezervaciji in ob prevozu gre e-pošta gostu **in** na `info@hanko-apartmaji.si`
- Admin na `/admin`: vidi, kdo se je kje naročil, lahko dodaja in briše
- SQLite baza v `data/hanko.sqlite` (kasneje lahko zamenjate z MySQL)

## Lokalni zagon

```bash
chmod +x start.sh
./start.sh
```

Nato odprite http://localhost:8080

Produkcijski Docker (isti kot na Railway/Render):

```bash
docker compose up --build
```

### Admin (lokalno)

- Naslov: http://localhost:8080/admin
- Uporabnik: `admin`
- Geslo: `HankoAdmin2026!`

## Objava na internetu (test)

Netlify **ne pride v poštev**. Uporabite Railway ali Render (Docker).

Najprej kodo potisnite na GitHub, nato povežite storitev z repozitorijem.

### Railway

1. Odprite [https://railway.app](https://railway.app) in prijavite se z GitHubom.
2. **New project → Deploy from GitHub repo**.
3. Railway zazna `Dockerfile` in `railway.toml`.
4. V Variables dodajte vsaj:

```
HANKO_ADMIN_USER=admin
HANKO_ADMIN_PASS=močno-geslo
HANKO_SITE_EMAIL=info@hanko-apartmaji.si
HANKO_ADMIN_EMAIL=info@hanko-apartmaji.si
```

5. **Settings → Networking → Generate domain**.

Če želite, da rezervacije preživijo restarte, dodajte Volume in ga namestite na `/var/www/html/data`.

### Render

1. Odprite [https://render.com](https://render.com).
2. **New → Web Service →** povežite GitHub repo.
3. Runtime: **Docker**.
4. Health check path: `/health.php`
5. Enake environment variables kot zgoraj.
6. Render dodeli URL, npr. `https://hanko-apartmaji.onrender.com`.

Brez diska se SQLite ob vsakem redeployu ponastavi (za kratek test je to v redu).

### E-pošta v oblaku

Kopije so vedno v `data/mail/`. Za pravo pošiljanje:

```
HANKO_SMTP_ENABLED=1
HANKO_SMTP_HOST=smtp.vas-ponudnik.si
HANKO_SMTP_PORT=587
HANKO_SMTP_USER=info@hanko-apartmaji.si
HANKO_SMTP_PASS=geslo
HANKO_SMTP_SECURE=tls
```

Če ste admin geslo pozabili ob obstoječi bazi, za en restart nastavite `HANKO_RESET_ADMIN=1`, nato ga spet izklopite.

Celoten seznam spremenljivk je v `.env.example`.
