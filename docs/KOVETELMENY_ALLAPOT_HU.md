# Követelmények megvalósítási állapota – 0.1.8

Az „elkészült” megjelölés a forráskódban szereplő megoldást jelenti. A követelmény csak a saját XAMPP-környezetben lefuttatott sikeres teszt után minősíthető teljesültnek a diplomamunkában.

## Funkcionális követelmények

| ID | Állapot | Megvalósítás | Következő bizonyíték |
|---|---|---|---|
| FR-01 | Elkészült, igazolt | Kötelező, egyedi és pontosan 8 számjegyű indexszámmal végzett hallgatói regisztráció, e-mailes fiókaktiválás, belépés, kilépés, munkamenet és belépési korlátozás | T-01–T-03, T-21–T-27 sikeres; az üres és 7 jegyű mezőt a böngésző, a duplikált indexszámot a szerver utasította el, a szerveroldali formátumfüggvény automatikus tesztje sikeres. A böngészőellenőrzést megkerülő külön HTTP-próba még elvégezhető. |
| FR-02 | Elkészült, igazolt | Csak aktív szolgáltatások jelennek meg a foglalói nézetben | T-04 sikeres, 2026. szeptember 14. |
| FR-03 | Elkészült, igazolt | Dátum- és szolgáltatásszűrés, múltbeli/foglalt/zárolt időpontok kizárása | T-05 és T-13 sikeres; a szabad, de már elkezdődött időablak kizárását a 2026. szeptember 16-i kiegészítő próba igazolta (a listából eltűnt, a véglegesítést a szerver elutasította). |
| FR-04 | Elkészült, igazolt | Megerősítő oldal, tranzakciós mentés, foglalási azonosító | T-06 sikeres, 2026. szeptember 14. |
| FR-05 | Elkészült, igazolt | Sorzárolás és egyedi generáltoszlop-megszorítás | T-07 sikeres, 2026. szeptember 14. |
| FR-06 | Elkészült, igazolt | Felhasználóazonosítóval szűrt saját foglalások | T-08 sikeres, 2026. szeptember 15. |
| FR-07 | Elkészült, igazolt | Tulajdonosi ellenőrzés és 24 órás lemondási szabály | T-09 és T-10 sikeres, 2026. szeptember 14. |
| FR-08 | Elkészült, igazolt | Időablak létrehozása, módosítása, átfedésvizsgálata és zárolása | T-11–T-13 és T-32 sikeres, 2026. szeptember 14–15. |
| FR-09 | Elkészült, igazolt | Adminisztrátori szolgáltatás-, szerepkör- és hozzárendelés-kezelés | T-14, T-34 és T-35 sikeres; a meglévő „Iskolai ügyintézés” módosítása után a frissített adatok és a mentési sikerüzenet megjelentek. Az inaktív szolgáltatás sem az adminisztrátori hozzárendelési választékban, sem a foglalói szolgáltatásszűrőben nem szerepel. A mintadiák foglalói szerepköre és a hiányzó hozzárendelés újabb képen is látható. |
| FR-10 | Elkészült, részben igazolt | A visszaállított tesztadatbázis hét függő foglalási értesítése a helyi naplóadapterrel sikeresen feldolgozódott (T-17). A Gmail SMTP-n küldött aktiváló levél kézbesítése külön sikeres próba volt (T-23); a foglalási értesítések SMTP-kézbesítését ez a két próba nem igazolja. | T-17, T-23 |
| FR-11 | Elkészült, igazolt | UTF-8 kódolású, pontosvesszővel tagolt CSV-export | T-16 sikeres, 2026. szeptember 15.; egy régi tesztrekord indexszáma adattisztításra szorul |
| FR-12 | Elkészült, igazolt | 60 perces, egyszer használható, SHA-256 kivonattal tárolt e-mailes jelszó-visszaállító token; általános kérési válasz és belépési zárolás feloldása | T-28–T-31 sikeres, 2026. szeptember 14. |

## Nem funkcionális követelmények

| ID | Állapot | Megjegyzés |
|---|---|---|
| NFR-01 | Mérendő | A 20 egyidejű felhasználóra vonatkozó válaszidőmérés még nem történt meg. |
| NFR-02 | Részben igazolt | A szerepkör-alapú jogosultságvédelem T-14 és a hibás CSRF-token elutasításának T-15 tesztje sikeres; a helyi XAMPP HTTP-t használ, éles környezetben HTTPS szükséges. |
| NFR-03 | Elkészült, igazolt | Korszerű PHP-jelszókivonat és 5 próbálkozás utáni 15 perces zárolás; a T-03 manuális próba sikeres. |
| NFR-04 | Elkészült, igazolt | A tranzakció, `FOR UPDATE` zárolás és egyedi adatbázis-megszorítás konkurens foglalási próbája sikeres (T-07). |
| NFR-05 | Elkészült, igazolt | A foglalás fő folyamata legfeljebb öt lépésben, billentyűzettel is végrehajtható volt (T-19). |
| NFR-06 | Elkészült, igazolt | A címkék, látható fókusz, billentyűzettel elérhető vezérlők és akadálymentes jelszómegjelenítő gombok manuális próbája sikeres (T-19, T-27). |
| NFR-07 | Részben igazolt | A 360 px széles reszponzív nézet sikeres (T-18); a három különböző böngészőben végzett ellenőrzés még hátravan. |
| NFR-08 | Elkészült, igazolt | A mentési parancsfájl és a külön tesztadatbázisba végzett helyreállítás sikeres; mind a 10 tábla visszaállt (T-20). |
| NFR-09 | Elkészült, igazolt | Az auditnaplóban a foglalási, lemondási, időablak-állapotváltási és módosítási események visszakereshetők (T-33). |
| NFR-10 | Elkészült | Konfigurációs minta, telepítési útmutató, adatbázisséma és verziófájl rendelkezésre áll. |
| NFR-11 | Részben elkészült | A foglaláshoz és intézményi azonosításhoz szükséges adatkör – köztük az indexszám – és az adatkezelési figyelmeztetés kész; az intézményi megőrzési idő még jóváhagyandó. |
| NFR-12 | Részben elkészült | A felület magyar, de a teljes szöveg-erőforrásos lokalizáció a következő iteráció feladata. |
