# Tesztelési jegyzőkönyv előkészítése

A lenti táblázatot csak a saját XAMPP-környezetben végrehajtott próbák után szabad tényleges eredménnyel és PASS/FAIL értékkel kitölteni. A diplomamunkába kizárólag valóban lefuttatott eredmény kerüljön.

| ID | Vizsgált funkció | Elvárt eredmény | Bizonyíték |
|---|---|---|---|
| T-01 | Helyes bejelentkezés | A felhasználó eléri az áttekintő oldalt | Képernyőkép, auditnapló |
| T-02 | Hibás bejelentkezés | A rendszer nem enged be, általános hibaüzenetet ad | Képernyőkép, auditnapló |
| T-03 | Öt sikertelen belépés | A fiók 15 percre zárolódik | Adatbázis-lekérdezés, napló |
| T-04 | Aktív szolgáltatások | Csak aktív szolgáltatás jelenik meg | Képernyőképek admin- és foglalói nézetből |
| T-05 | Szabad időpontok szűrése | Foglalt, múltbeli és zárolt időpont nem választható | Képernyőkép |
| T-06 | Sikeres foglalás | Megerősített foglalás és azonosító jön létre | Képernyőkép, adatbázisrekord |
| T-07 | Konkurens foglalás | Két közel egyidejű próbából pontosan egy sikeres | `tests/run.php --integration` kimenete |
| T-08 | Saját foglalások védelme | Más felhasználó foglalása nem látható és nem módosítható | Negatív jogosultsági próba |
| T-09 | Lemondás 24 órán túl | A foglalás lemondott lesz, az időpont újra foglalható | Képernyőkép, napló |
| T-10 | Lemondás 24 órán belül | A foglaló nem mondhatja le | Hibaüzenet képernyőképe |
| T-11 | Időablak létrehozása | A jövőbeli időablak megjelenik a szabad listában | Két nézet képernyőképe |
| T-12 | Átfedő időablak | A rendszer megtagadja ugyanazon időpontgazda átfedő időablakát | Hibaüzenet |
| T-13 | Időablak zárolása | A zárolt időablak eltűnik a foglalható listából | Képernyőkép |
| T-14 | Adminisztrátori jogosultság | Foglaló közvetlen URL-lel sem éri el az adminoldalt | 403-as oldal képernyőképe |
| T-15 | CSRF-védelem | Hiányzó vagy hibás token esetén nincs állapotváltozás | Negatív kérés eredménye |
| T-16 | CSV-export | Az export UTF-8 kódolással megnyitható és helyes adatokat tartalmaz | CSV-fájl részlete |
| T-17 | Értesítési adapter | A függő értesítés feldolgozás után `sent` állapotú | Parancskimenet, `mail.log` |
| T-18 | Reszponzív nézet | 360 képpontos szélességnél a fő folyamat használható | Böngésző fejlesztői nézetének képernyőképe |
| T-19 | Billentyűzetes kezelés | A foglalás egér nélkül végrehajtható, fókusz látható | Megfigyelési jegyzőkönyv |
| T-20 | Adatbázis-mentés és visszaállítás | Külön tesztadatbázisba sikeresen visszaállítható | Rekordszámok és képernyőkép |
| T-21 | Indexszámos regisztráció | Indexszám nélkül, nem 8 számjegyű vagy már használt indexszámmal nem hozható létre fiók; sikeres foglalásnál a név mellett megjelenik | Regisztrációs hibaüzenetek és adminisztrátori foglaláslista képernyőképe |
| T-22 | Aktiválás előtti bejelentkezés | A helyes jelszó ellenére sem enged be; aktiválásra figyelmeztet | Képernyőkép, auditnapló |
| T-23 | Aktiváló levél kézbesítése | A valódi címre megérkezik a 24 órás aktiváló hivatkozás | E-mail képernyőképe személyes adatok kitakarásával |
| T-24 | E-mail-cím aktiválása | Az egyszer használható hivatkozás után a bejelentkezés sikerül | Sikerüzenet, adminisztrátori felhasználólista |
| T-25 | Aktiváló hivatkozás újbóli használata | A rendszer a már használt hivatkozást elutasítja | Hibaüzenet |
| T-26 | Aktiváló levél újrakérése | Új levél érkezik, a korábbi hivatkozás érvénytelenné válik | Két levél és a régi hivatkozás hibaüzenete |
| T-27 | Jelszómegjelenítő ikon | A jelszó megjeleníthető és ismét elrejthető a regisztrációnál és belépésnél | Képernyőkép, manuális próba |
| T-28 | Jelszó-visszaállító levél kérése | Aktivált fiókhoz megérkezik a 60 perces hivatkozás; ismeretlen címnél is azonos általános válasz látható | Két kérési próba és e-mail képernyőképe |
| T-29 | Új jelszó beállítása | A szabályos, kétszer azonosan megadott új jelszó menthető, majd belépésre használható | Sikerüzenet és bejelentkezési próba |
| T-30 | Régi jelszó és token elutasítása | A régi jelszóval nem lehet belépni, a felhasznált hivatkozás nem nyitható meg újra | Két hibaüzenet képernyőképe |
| T-31 | Hibás új jelszó elutasítása | A túl rövid, hiányos vagy eltérően ismételt jelszó nem menthető | Űrlaphibák képernyőképe |
| T-32 | Időablak módosítása | A szabad időablak szolgáltatása és időtartama módosítható, az új adatok megjelennek a listában | Sikerüzenet és időablaklista képernyőképe |
| T-33 | Auditnapló | A foglalás, lemondás, zárolás, megnyitás és módosítás eseményei visszakereshetők | Naplóbejegyzések képernyőképei |
| T-34 | Adminisztrátori szolgáltatáskezelés | Új szolgáltatás létrejön és inaktiválható; egy meglévő szolgáltatás adatai módosíthatók | Sikerüzenet, aktív és inaktív állapot, valamint módosított szolgáltatás képernyőképe |
| T-35 | Adminisztrátori jogosultság és hozzárendelés | Foglalóból időpontgazda alakítható, majd szolgáltatás rendelhető hozzá; a hozzárendelés eltávolítható és az eredeti szerepkör visszaállítható | Sikerüzenetek, felhasználólista előtte és utána |

## Rögzített eredmények – 2026. szeptember 14–15.

A következő próbákat Windows–XAMPP környezetben hajtották végre. A képernyőképek elkészültek; a diplomamunkába helyezés előtt a személyes adatokat és a tokeneket ki kell takarni.

| ID | Tényleges eredmény | Minősítés | Bizonyíték |
|---|---|---|---|
| T-01 | Az aktivált próba-fiók bejelentkezett és elérte az áttekintő oldalt. | PASS | Képernyőkép |
| T-02 | A rendszer a hibás jelszóval végzett belépést megtagadta, és általános hibaüzenetet jelenített meg. | PASS | Bejelentkezési hibaüzenet képernyőképe |
| T-03 | Öt hibás jelszópróba után a fiók 15 perces ideiglenes zárolásba került; a következő belépési próbát a rendszer zárolási üzenettel utasította el. | PASS | Zárolási hibaüzenet képernyőképe |
| T-04 | Az aktív szolgáltatáshoz tartozó időpont megjelent a foglalói nézetben. | PASS | Szabad időpontok képernyőképe |
| T-05 | A megerősített foglalás idején az időpont nem volt másik felhasználó számára választható; lemondás után ismét megjelent. A zárolt időablak kizárását a T-13 külön igazolta. A 2026. szeptember 14-re szűrt foglalói listában nem volt időpont, de az adminisztrátori képen az aznapi időablakok foglaltak voltak, ezért a múltbeli, de szabad időablak kizárását ez a próba nem bizonyítja külön. | RÉSZBEN IGAZOLT | Foglalás előtti és lemondás utáni, valamint múltbeli dátumra szűrt foglalói és adminisztrátori képernyőkép |
| T-06 | A megerősítő oldal helyes adatokat mutatott, majd létrejött a #7 azonosítójú megerősített foglalás. | PASS | Megerősítő oldal és sikerüzenet képernyőképe |
| T-07 | A 0.1.7-es teljes tesztfuttatás 10 sikeres és 0 hibás tesztet jelzett; a konkurens foglalásnál csak egy aktív foglalás jött létre. | PASS | Parancssori kimenet és képernyőkép |
| T-08 | A második foglalói fiók „Saját foglalásaim” oldalán nem jelent meg az első felhasználó korábban létrehozott foglalása. | PASS | A második felhasználó üres foglaláslistájának képernyőképe |
| T-09 | A több mint 24 órával későbbi foglalás lemondható volt, „Lemondott” állapotot kapott, az időpont pedig ismét foglalhatóvá vált. | PASS | Megerősítő párbeszéd, lemondási sikerüzenet és szabad időpont képernyőképe |
| T-10 | A 24 órán belül kezdődő, #8 azonosítójú foglalásnál nem jelent meg lemondási gomb; a rendszer „A lemondási határidő lejárt.” tájékoztatást adott. | PASS | Saját foglalások oldal képernyőképe |
| T-11 | Az időpontgazda létrehozott egy jövőbeli időablakot, amely megjelent az időablaklistában és foglalhatóvá vált. | PASS | Időablaklista és foglalói nézet képernyőképe |
| T-12 | A rendszer elutasította az ugyanazon időpontgazda meglévő időablakával átfedő új időablakot. | PASS | Átfedési hibaüzenet képernyőképe |
| T-13 | Az időpontgazda zárolta a 2026. szeptember 23. 10:30–11:00 közötti időablakot; az „Zárolt” állapotot kapott és eltűnt a foglalható időpontok közül. | PASS | Időpontgazdai és foglalói nézet képernyőképe |
| T-14 | A foglalói szerepkörrel közvetlenül megnyitott adminisztrátori URL-re a rendszer „Hozzáférés megtagadva” választ adott. | PASS | Jogosultsági hibaoldal képernyőképe |
| T-15 | A módosított `_token` mezővel indított új szolgáltatás létrehozását a rendszer „A biztonsági token lejárt vagy érvénytelen” üzenettel elutasította; az adminisztrátori szolgáltatáslistában a `CSRF-negatív-02` nem jelent meg. A korábbi `CSRF-próba` a szabályos tokennel végzett korábbi mentésből származó külön tesztadat. | PASS | Tokenhiba-üzenet, szolgáltatáslista és felhasználói visszaellenőrzés |
| T-16 | A CSV-export Excelben megnyílt, és elkülönített oszlopokban tartalmazta a foglalási adatokat. Egy korábbi tesztfiók 7 számjegyű indexszáma adattisztításra szorul. | PASS | Excelben megnyitott CSV képernyőképe |
| T-17 | A külön visszaállított `idopontfoglalo_restore_test` adatbázison, `MAIL_DRIVER=log` mellett futtatott `bin\process_notifications.php` hét feldolgozott és hét sikeres értesítést jelzett. A tesztadatbázis `notifications` táblájában mind a hét sor `sent` állapotban, `attempts=1`, `last_error=NULL` mezőkkel és kitöltött `sent_at` időponttal szerepelt. A naplóadapteres próba nem igazolja a foglalási levelek valódi SMTP-kézbesítését. | PASS | Parancskimenet, a tesztadatbázis `notifications` táblájának feldolgozás előtti és utáni képernyőképe |
| T-18 | A szabad időpontok oldala 360 képpontos szélességnél kártyás mobilnézetre váltott; a szűrők, adatok és műveleti gombok vízszintes görgetés nélkül használhatók maradtak. | PASS | 360 px széles böngészőnézet képernyőképe |
| T-19 | A fókuszjelölés billentyűzetes léptetéskor jól látható volt, és a foglalási folyamat vezérlői `Tab` és `Enter` billentyűkkel használhatók voltak. | PASS | Látható fókuszjelölés képernyőképe és manuális próba |
| T-20 | A parancsfájl létrehozta az SQL-mentést, amelyet a külön `idopontfoglalo_restore_test` adatbázisba sikerült importálni. A phpMyAdmin 119 lekérdezést végrehajtott, és mind a 10 szükséges tábla helyreállt. | PASS | Parancssori mentés, sikeres importálás és táblalista képernyőképe |
| T-21 | Az üres indexszámot a böngésző kötelezőmező-ellenőrzése, a 7 számjegyű indexszámot a böngésző formátumellenőrzése utasította el. A már használt, 8 számjegyű indexszámot a szerver meglévő fiókra hivatkozó üzenettel utasította el; a korábbi adminisztrátori foglalási képen a név mellett megjelent az indexszám. A szerveroldali formátumellenőrző függvény automatikus tesztje a 7 és 9 jegyű, illetve betűt tartalmazó indexszámot elutasította. Külön, böngészőellenőrzést megkerülő HTTP-próba nem történt. | PASS | Három regisztrációs ellenőrzés, korábbi foglalási képernyőkép és `tests/run.php` automatikus teszt |
| T-22 | A rendszer a helyes jelszó ellenére elutasította a még nem aktivált fiókot. | PASS | Bejelentkezési hibaüzenet képernyőképe |
| T-23 | A Gmail SMTP-n küldött, 24 órás aktiváló hivatkozás megérkezett. | PASS | E-mail képernyőképe |
| T-24 | A hivatkozás aktiválta az e-mail-címet; az adminfelületen megjelent az aktivált állapot. | PASS | Sikerüzenet és adminfelület képernyőképe |
| T-25 | A rendszer elutasította a már felhasznált aktiváló hivatkozást. | PASS | Hibaüzenet képernyőképe |
| T-26 | Az újrakért levél megérkezett, a korábbi hivatkozás érvénytelenné vált. | PASS | E-mailek és hibaüzenet képernyőképe |
| T-27 | A jelszó a szem ikonnal megjeleníthető, majd ismét elrejthető. | PASS | Manuális próba és képernyőkép |
| T-28 | A rendszer megjelenítette az általános kérési visszajelzést, majd a Gmail-címre megérkezett a 60 percig érvényes, egyszer használható hivatkozás. | PASS | Kérési visszajelzés és e-mail képernyőképe |
| T-29 | A hivatkozás megnyitotta az új jelszó űrlapját, a szabályos jelszó mentése sikerült, majd az új jelszóval a felhasználó bejelentkezett. | PASS | Űrlap, sikerüzenet és felhasználói próba |
| T-30 | A rendszer a régi jelszót és a már felhasznált visszaállító hivatkozást is elutasította. | PASS | Felhasználói negatív próbák |
| T-31 | Az automatikus jelszószabály-teszt elfogadta a megfelelő, és elutasította a túl rövid, szám, kisbetű vagy nagybetű nélküli jelszavakat. | PASS | `tests/run.php --integration`: 10 PASS, 0 FAIL |
| T-32 | A szabad időablak szolgáltatásának és időtartamának módosítása sikerült; a frissített adatok megjelentek az időablaklistában. | PASS | Sikerüzenet és módosított időablak képernyőképe |
| T-33 | Az auditnapló rögzítette a `BOOKING_CREATED`, `BOOKING_CANCELLED`, `SLOT_STATUS_CHANGED` és `SLOT_UPDATED` eseményeket, valamint az érintett felhasználót és objektumot. | PASS | Öt naplóbejegyzés képernyőképe |
| T-34 | A „Teszt szolgáltatás” létrejött, 25 perces időtartammal, majd az adminisztrátor inaktiválta; az „Inaktív” jelzés megjelent. Az inaktív szolgáltatás az adminisztrátori hozzárendelési választékban és a foglalói „Szabad időpontok” oldal lenyitott szolgáltatásszűrőjében sem szerepelt. A meglévő „Iskolai ügyintézés” módosítása után a rendszer sikeres mentést jelzett; az adminisztrátori listában 25 perces időtartam és „Titkárság - 205” helyszín látható. | PASS | Mentési sikerüzenetek, aktív és inaktív állapot, adminisztrátori választék, foglalói szűrő és módosított meglévő szolgáltatás képernyőképe |
| T-35 | A mintadiák szerepköre időpontgazdára változott, megjelent nála a hozzárendelt „Teszt szolgáltatás”, majd a hozzárendelés eltávolítása után ismét foglalói szerepkörben, „Nincs hozzárendelés” állapotban szerepelt. A visszaállított szerepkört egy későbbi, külön képernyőkép is megerősítette. | PASS | Jogosultságfrissítési, hozzárendelési és két visszaállítás utáni felhasználólista képernyőképe |

Az inaktív szolgáltatást ellenőrző köztes képen a mintadiák „Időpontgazda” szerepkörben szerepelt, hozzárendelés nélkül. Az ezt követően megküldött felhasználólista-kép viszont Minta Diákot (26000000) ismét „Foglaló felhasználó” szerepkörben, „Nincs hozzárendelés” állapotban mutatja, hozzárendelési űrlap nélkül.

## Automatikus teszt futtatása

```bat
cd C:\xampp\htdocs\idopontfoglalo_rendszer
C:\xampp\php\php.exe tests\run.php --integration
```

Az eredményt képernyőképként és egyszerű szövegfájlként is célszerű megőrizni:

```bat
C:\xampp\php\php.exe tests\run.php --integration > storage\teszteredmeny.txt
```

## További próbákhoz használható eredménytábla

| Teszteset azonosítója | Vizsgált funkció | Elvárt eredmény | Tényleges eredmény | PASS/FAIL | Megjegyzés |
|---|---|---|---|---|---|
| T-… | … | … | A futtatás után kitöltendő | … | Dátum, környezet, bizonyíték hivatkozása |
