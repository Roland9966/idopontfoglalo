# Telepítési útmutató – Windows és XAMPP

## 1. Környezet verzióinak rögzítése

A diplomamunka 3.10.1. fejezetéhez a saját gépen használt pontos verziókat kell feljegyezni. Parancssorban futtassa:

```bat
C:\xampp\php\php.exe -v
C:\xampp\mysql\bin\mysql.exe --version
C:\xampp\apache\bin\httpd.exe -v
```

Jegyezze fel a Windows és a Visual Studio Code verzióját is. A verziók képernyőképét érdemes megőrizni, de a dolgozatba általában elegendő egy rövid technológiai táblázat.

## 2. Projekt elhelyezése

A projekt végleges helye:

```text
C:\xampp\htdocs\idopontfoglalo_rendszer
```

Az alkalmazás nyilvános belépési pontja a `public/index.php`. A böngészőben használt cím:

```text
http://localhost/idopontfoglalo_rendszer/public/
```

## 3. Adatbázis létrehozása

1. Indítsa el a XAMPP Apache és MySQL modulját.
2. Nyissa meg a `http://localhost/phpmyadmin` oldalt.
3. Válassza az **Importálás** lehetőséget.
4. Importálja a `database/schema.sql` fájlt.
5. Importálja a `database/seed.sql` fájlt.

A séma InnoDB táblákat használ. Az `active_slot_id` generált oszlop egyedi megszorítása gondoskodik arról, hogy egy időablakhoz legfeljebb egy megerősített foglalás tartozhasson. A lemondott foglalás megmarad előzményként, miközben ugyanaz az időablak újra foglalható lesz.

### Meglévő 0.1.6-os adatbázis frissítése a 0.1.7 verzióra

Ha a rendszer 0.1.6-os változata már működik, a programfájlok felülírása után egyszer futtassa:

```bat
C:\xampp\php\php.exe bin\migrate_password_reset.php
```

A parancs létrehozza vagy kiegészíti a jelszó-visszaállító tokenek tábláját. Nem töröl felhasználót, foglalást vagy korábbi beállítást, és ismételten is biztonságosan futtatható. Ugyanez phpMyAdminból a `database/migrations/0.1.7_password_reset.sql` fájl importálásával végezhető el.

### Meglévő 0.1.5-ös adatbázis frissítése a 0.1.6 verzióra

Ha a rendszer 0.1.5-ös változata már működik, a teljes séma újbóli importálása helyett a programfájlok felülírása után egyszer futtassa:

```bat
C:\xampp\php\php.exe bin\migrate_email_verification.php
```

A parancs hozzáadja az e-mail-ellenőrzés mezőjét és az aktiváló tokenek tábláját. A már meglévő fiókok ellenőrzött állapotot kapnak, ezért továbbra is be tudnak jelentkezni. Az újonnan regisztrált fiókok aktiválásra várnak. A frissítés nem törli a felhasználókat vagy foglalásokat, és ismételten is biztonságosan futtatható. Ugyanez a módosítás phpMyAdminból a `database/migrations/0.1.6_email_verification.sql` fájl importálásával is elvégezhető.

Ha 0.1.3-as vagy korábbi változatról frissít, előbb a `bin\migrate_student_index.php`, utána a `bin\migrate_email_verification.php`, végül a `bin\migrate_password_reset.php` parancsot futtassa.

## 4. Helyi konfiguráció

Másolja a `.env.example` fájlt `.env` néven. Alapértelmezett XAMPP esetén:

```ini
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=idopontfoglalo
DB_USER=root
DB_PASS=
```

Ha a MySQL `root` felhasználójához jelszó tartozik, azt csak a helyi `.env` fájlban adja meg. A `.env` fájlt a `.gitignore` kizárja a verziókezelésből.

### Gmail SMTP beállítása

Valódi aktiváló levélhez a `.env` fájlban `MAIL_DRIVER=smtp` szükséges. A küldő Gmail-fiókban kapcsolja be a kétlépcsős azonosítást, majd hozzon létre külön alkalmazásjelszót. A normál Gmail-jelszót ne használja.

```ini
MAIL_DRIVER=smtp
MAIL_FROM_ADDRESS=sajat.kuldo@gmail.com
MAIL_FROM_NAME=Időpontfoglaló
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_ENCRYPTION=tls
SMTP_USERNAME=sajat.kuldo@gmail.com
SMTP_PASSWORD=ide_jon_a_16_karakteres_alkalmazasjelszo
SMTP_TIMEOUT=20
EMAIL_VERIFICATION_HOURS=24
PASSWORD_RESET_MINUTES=60
```

A részletes leírás a `docs/GMAIL_SMTP_HU.md` fájlban található. A beállítás regisztráció nélkül is ellenőrizhető:

```bat
C:\xampp\php\php.exe bin\test_email.php cimzett@gmail.com
```

## 5. Kezdőadatok

A mintafiókok és a következő napokra szóló időablakok létrehozása:

```bat
cd C:\xampp\htdocs\idopontfoglalo_rendszer
C:\xampp\php\php.exe bin\seed_demo.php
```

Saját adminisztrátori fiók létrehozása:

```bat
C:\xampp\php\php.exe bin\create_admin.php sajat@email.hu "Pálfi Noémi" "ErosJelszo123!"
```

A jelszó ne kerüljön képernyőképbe, dolgozatba vagy verziókezelőbe.

## 6. Jogosultságok kipróbálása

- Foglalóként ellenőrizze az egyedi indexszámos regisztrációt, az aktiváló levelet, az aktiválás előtti belépés tiltását, majd a szabad időpontokat, a foglalást és a lemondást.
- Kijelentkezett felhasználóként kérjen jelszó-visszaállító levelet, állítson be új jelszót, majd ellenőrizze a régi jelszó és a felhasznált hivatkozás elutasítását.
- Időpontgazdaként hozzon létre, módosítson és zároljon egy időablakot, majd kezelje a hozzá tartozó foglalást.
- Adminisztrátorként módosítson szolgáltatást és szerepkört, rendeljen szolgáltatást az időpontgazdához, tekintse meg a naplót, majd készítsen CSV-exportot.

## 7. Mentés és helyreállítás

A `bin/backup_database.bat` alapértelmezett XAMPP-útvonal esetén SQL-mentést készít a `storage/backups` könyvtárba. A fájlnév Windows területi beállításoktól független `idopontfoglalo_ÉÉÉÉHHNN_ÓÓPPMM.sql` időbélyeget kap. Ha a MySQL jelszavas, a parancsfájlt ne egészítse ki nyílt jelszóval; használjon külön, védett klienskonfigurációt.

Helyreállítási próba javasolt menete:

1. Készítsen mentést.
2. Hozzon létre külön `idopontfoglalo_restore_test` adatbázist.
3. A mentésben ideiglenesen módosítsa a `USE` sort, vagy phpMyAdminban válassza ki a tesztadatbázist.
4. Importálja a mentést.
5. Ellenőrizze a táblák számát, valamint a felhasználók, időablakok és foglalások rekordszámát.
6. Rögzítse a dátumot, az eredményt és egy képernyőképet a tesztjegyzőkönyvben.

## 8. Élesítés előtti minimum

- HTTPS és érvényes tanúsítvány;
- erős adatbázis-jelszó és korlátozott adatbázis-felhasználó;
- `APP_DEBUG=false`;
- mintafiókok eltávolítása vagy jelszavuk módosítása;
- írási jogosultság csak a szükséges `storage` könyvtárra;
- napi automatikus mentés és ellenőrzött visszaállítás;
- intézményi adatkezelési és megőrzési szabály jóváhagyása;
- valós levelezési adapter biztonságos beállítása és ellenőrzött kézbesítési próba.
