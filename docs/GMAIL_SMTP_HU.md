# Gmail SMTP, fiókaktiválás és jelszó-visszaállítás

## 1. Mire szolgál?

Az új hallgatói fiók a regisztráció után még nem használható. A rendszer egy 24 óráig érvényes, egyszer használható aktiváló hivatkozást küld a megadott e-mail-címre. A bejelentkezés csak a hivatkozás megnyitása után engedélyezett.

Ugyanez a levelezési kapcsolat küldi az elfelejtett jelszóhoz tartozó, alapértelmezetten 60 percig érvényes és egyszer használható visszaállító hivatkozást is.

Helyi XAMPP-használatnál az aktiváló hivatkozás `localhost` címet tartalmaz, ezért ugyanazon a számítógépen kell megnyitni, amelyen az Apache fut.

## 2. Google alkalmazásjelszó létrehozása

1. A küldő Gmail-fiókban kapcsolja be a kétlépcsős azonosítást.
2. A Google-fiók biztonsági beállításainál hozzon létre alkalmazásjelszót az időpontfoglalóhoz.
3. A kapott 16 karakteres jelszót szóközök nélkül használja.
4. A normál Gmail-jelszót ne írja a programba, és senkinek ne küldje el.

## 3. A `.env` fájl beállítása

A projekt gyökerében található `.env` fájlban a levelezési rész legyen például:

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

A `MAIL_FROM_ADDRESS` és a `SMTP_USERNAME` ugyanaz a Gmail-cím legyen. A `.env` fájlt nem szabad képernyőképen, diplomamunkában vagy verziókezelőben közzétenni.

## 4. Küldési próba

Parancssorban:

```bat
cd C:\xampp\htdocs\idopontfoglalo_rendszer
C:\xampp\php\php.exe bin\test_email.php cimzett@gmail.com
```

Siker esetén a parancs ezt írja ki:

```text
A tesztlevél elküldése sikerült: cimzett@gmail.com
```

Ha a levél nem látható a Beérkezett üzenetek között, ellenőrizze a Spam mappát is.

## 5. Teljes aktiválási próba

1. Regisztráljon egy korábban nem használt, valódi e-mail-címmel és egyedi, 8 számjegyű indexszámmal.
2. Ellenőrizze, hogy aktiválás előtt a rendszer nem enged be.
3. Nyissa meg a levélben kapott hivatkozást ugyanazon a számítógépen.
4. Jelentkezzen be az új fiókkal.
5. Az adminisztrátori felhasználólistán ellenőrizze az „E-mail aktiválva” jelzést.
6. Ellenőrizze, hogy ugyanaz a hivatkozás másodszor már nem használható.

Ha a levél elveszett vagy lejárt, a bejelentkezési oldalon az „Új levél kérése” hivatkozással kérhető másik. Az új kérés érvényteleníti a korábbi, még fel nem használt aktiváló hivatkozásokat.

## 6. Jelszó-visszaállítási próba

1. A bejelentkezési oldalon válassza az „Új jelszó kérése” hivatkozást.
2. Adja meg egy aktivált fiók e-mail-címét.
3. Nyissa meg a levélben kapott hivatkozást ugyanazon a számítógépen.
4. Adjon meg két alkalommal egy legalább 10 karakteres, kisbetűt, nagybetűt és számot tartalmazó új jelszót.
5. Ellenőrizze, hogy az új jelszóval be lehet jelentkezni, a régivel viszont nem.
6. Nyissa meg ismét ugyanazt a hivatkozást; a rendszernek el kell utasítania.

A kérőoldal létező és nem létező e-mail-címnél azonos általános választ jelenít meg. Ez szándékos biztonsági megoldás.
