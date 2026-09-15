# Iskolai online időpontfoglaló rendszer

Oktatási környezetre készített, PHP- és MySQL/MariaDB-alapú webalkalmazás. A projekt a „Online időpontfoglaló rendszer fejlesztése projektmenedzsment megközelítésben” című diplomamunka gyakorlati része.

## Megvalósított fő funkciók

- kötelező, egyedi és pontosan 8 számjegyű indexszámmal végzett hallgatói regisztráció;
- 24 órás, egyszer használható hivatkozással végzett kötelező e-mailes fiókaktiválás és aktiválólevél-újraküldés;
- bejelentkezés, kijelentkezés, jelszómegjelenítő ikonok és sikertelen belépések korlátozása;
- 60 perces, egyszer használható e-mailes hivatkozással végzett biztonságos jelszó-visszaállítás;
- foglaló, időpontgazda és adminisztrátor szerepkör;
- aktív szolgáltatások és szabad időpontok listázása, dátum szerinti szűrése;
- foglalás létrehozása tranzakcióval és adatbázis-szintű kettősfoglalás-védelemmel;
- saját foglalások megtekintése és 24 órás szabály szerinti lemondása;
- időablak létrehozása, módosítása és zárolása;
- szolgáltatás-, felhasználó-, jogosultság- és foglaláskezelés;
- állapotváltozások és fontos biztonsági események naplózása;
- értesítési sor napló-, PHP `mail()`- vagy Gmail-kompatibilis SMTP-adapterrel;
- adminisztrátori CSV-export;
- reszponzív és billentyűzettel használható felület;
- parancssori egységtesztek és adatbázisos konkurenciateszt.

## Követelmények

- Windows és XAMPP;
- PHP 8.1 vagy újabb, PDO MySQL bővítménnyel;
- MySQL 8.0+ vagy MariaDB 10.4+;
- Apache 2.4+;
- modern Chrome, Edge vagy Firefox böngésző.

## Gyors telepítés

1. Másolja a teljes `idopontfoglalo_rendszer` mappát a `C:\xampp\htdocs\` könyvtárba.
2. A XAMPP vezérlőpultján indítsa el az Apache és a MySQL modult.
3. Nyissa meg a `http://localhost/phpmyadmin` oldalt.
4. Importálja előbb a `database/schema.sql`, majd a `database/seed.sql` fájlt.
5. Másolja le a `.env.example` fájlt `.env` néven. A XAMPP alapértelmezett helyi beállításánál a megadott adatbázisértékek általában megfelelőek.
6. A Parancssorban lépjen a projekt mappájába, majd hozza létre a bemutató adatokat:

   ```bat
   C:\xampp\php\php.exe bin\seed_demo.php
   ```

7. Nyissa meg: `http://localhost/idopontfoglalo_rendszer/public/`

A részletes telepítési és ellenőrzési leírás a `docs/TELEPITES_HU.md` fájlban található.

### Frissítés a 0.1.7-es verzióról

A programfájlok felülírása elegendő; adatbázis-frissítést nem kell futtatni. A 0.1.8-as változat a Windows területi beállításaitól független, szabályos időbélyeget használ az adatbázis-mentések fájlnevében.

### Frissítés a 0.1.6-os verzióról

Ha a meglévő adatbázis már a 0.1.6-os verzióval működik, a programfájlok felülírása után egyszer futtassa:

```bat
C:\xampp\php\php.exe bin\migrate_password_reset.php
```

A parancs nem törli a meglévő adatokat, és ismételten is biztonságosan futtatható. A jelenlegi `.env` Gmail SMTP-beállításai változtatás nélkül használhatók. A link alapértelmezett 60 perces érvényessége a `PASSWORD_RESET_MINUTES` értékkel módosítható.

### Frissítés a 0.1.5-ös verzióról

Ha a meglévő adatbázis már a 0.1.5-ös verzióval működik, a programfájlok felülírása után egyszer futtassa:

```bat
C:\xampp\php\php.exe bin\migrate_email_verification.php
```

Ez létrehozza az e-mail-aktiváláshoz szükséges mezőt és tokentáblát. A korábbi fiókok ellenőrzött állapotot kapnak, ezért továbbra is be tudnak jelentkezni. A parancs ismételt futtatása is biztonságos.

Ha 0.1.5-ös vagy régebbi verzióról frissít, a szükséges korábbi frissítők után a `bin\migrate_password_reset.php` parancsot is futtassa.

## Bemutatófiókok

| Szerepkör | Indexszám | E-mail | Jelszó |
|---|---|---|---|
| Adminisztrátor | – | `admin@iskola.local` | `Admin123!` |
| Időpontgazda | – | `tanar@iskola.local` | `Tanar123!` |
| Foglaló | `26000000` | `diak@iskola.local` | `Diak123!` |

Ezek kizárólag helyi bemutatáshoz használhatók. Éles környezetben a mintafiókokat törölni vagy a jelszavukat azonnal módosítani kell.

## Tesztek

Egységtesztek:

```bat
C:\xampp\php\php.exe tests\run.php
```

Egység- és adatbázisos konkurenciateszt:

```bat
C:\xampp\php\php.exe tests\run.php --integration
```

A konkurenciateszt két külön PHP-folyamatból közel azonos pillanatban próbálja lefoglalni ugyanazt az időablakot. A teszt akkor sikeres, ha pontosan egy kérés hoz létre megerősített foglalást.

## Értesítések feldolgozása

Helyi tesztelésnél a `.env` fájlban a `MAIL_DRIVER=log` érték használható. A függő értesítések feldolgozása:

```bat
C:\xampp\php\php.exe bin\process_notifications.php
```

Az eredmény a `storage/mail.log` fájlba kerül. Ez tesztadapter, nem tényleges e-mail-kézbesítés.

Valódi Gmail-küldéshez állítsa be a `.env` fájlban a `MAIL_DRIVER=smtp` értéket, a küldő Gmail-címet és a Google-fiókban létrehozott 16 karakteres alkalmazásjelszót. A részletes lépések a `docs/GMAIL_SMTP_HU.md` fájlban találhatók. A beállítás külön tesztelhető:

```bat
C:\xampp\php\php.exe bin\test_email.php sajat@gmail.com
```

## Biztonsági megoldások

- `password_hash()` és `password_verify()` alapú jelszókezelés;
- véletlenszerű aktiváló és jelszó-visszaállító tokenek, kizárólag SHA-256 kivonatként tárolva, egyszeri felhasználással és lejárattal;
- paraméterezett PDO-lekérdezések;
- CSRF-token minden állapotváltoztató űrlapon;
- kimeneti HTML-kódolás;
- szerveroldali szerepkör- és objektumszintű jogosultság-ellenőrzés;
- munkamenet-azonosító megújítása belépéskor;
- `HttpOnly` és `SameSite=Lax` munkamenetsüti, HTTPS esetén `Secure` beállítással;
- belépési próbálkozások korlátozása;
- foglalási tranzakció, sorzárolás és egyedi adatbázis-megszorítás;
- auditnapló a lényeges eseményekhez.

## Követelmények állapota

Az FR-01–FR-12 követelmények forráskódszinten elkészültek. A Gmail SMTP, a kötelező e-mail-aktiválás és a biztonságos jelszó-visszaállítás teljes felhasználói folyamatát a saját XAMPP-környezetben 2026. szeptember 14-én sikeresen ellenőrizték.
