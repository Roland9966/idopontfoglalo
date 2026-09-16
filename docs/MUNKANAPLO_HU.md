# Megvalósítási munkanapló – utólagos összesítés

A projekt során folyamatos, napi óranapló nem készült. Az alábbi adatok a hallgató **utólagos becslései**, amelyek a 32 tevékenységnapon (egy közös nap levonásával 31 külön munkanapon) és munkanaponként körülbelül 4 órán alapulnak.

## Ütemezés és mérföldkövek

A 14 hetes terv első napja: 2026. június 11. (címegyeztetés), tervezett befejezés: 2026. szeptember 16.

| Mérföldkő | Terv (hét / dátum) | Tényleges dátum | Eltérés | Állapot |
|---|---|---|---|---|
| M1 – Projektindítás, hatókör | 2. hét / 06. 24. | 2026. 06. 11. | 13 nappal korábban | Teljesült |
| M2 – Követelmény- és rendszerterv | 4. hét / 07. 08. | 2026. 09. 07. | +61 nap | Teljesült |
| M3 – Hitelesítés, adatbázis alap | 6. hét / 07. 22. | 2026. 09. 08. (0.1.0) | +48 nap | Teljesült |
| M4 – Foglalási magfolyamat | 9. hét / 08. 12. | 2026. 09. 08. (0.1.0) | +27 nap | Teljesült |
| M5 – Tesztelhető MVP | 11. hét / 08. 26. | 2026. 09. 08. (0.1.0) | +13 nap | Teljesült |
| M6 – Elfogadási és telepítési próba | 13. hét / 09. 09. | 2026. 09. 14–15. | +5–6 nap | Részben teljesült |
| M7 – Leadásra kész állapot | 14. hét / 09. 16. | folyamatban | – | Mentori jóváhagyásra vár |

## Összesítés munkacsomagonként

| WBS | Munkacsomag | Tervezett óra | Utólagos becslés | Eltérés | Eltérés oka |
|---|---|---:|---:|---:|---|
| 1.0 | Projektirányítás | 18 | 4 | −14 | Egyszemélyes projekt; heti státuszjegyzet és formális változáskérés nem készült |
| 2.0 | Igények, követelmények | 28 | 8 | −20 | Érintetti interjúk nem voltak, a követelmények a tervezéssel együtt készültek |
| 3.0 | Rendszer- és adatbázisterv | 32 | 12 | −20 | Egyszerű relációs modell, keretrendszer nélküli megoldás |
| 4.0 | Fejlesztői környezet | 24 | 4 | −20 | A XAMPP kész Apache–PHP–MariaDB környezetet adott |
| 5.0 | Foglalási funkciók | 52 | 28 | −24 | A PDO-tranzakciós megoldás a vártnál gyorsabban elkészült |
| 6.0 | Adminisztráció, értesítés | 30 | 16 | −14 | Foglalási értesítések naplóadapterrel; élő SMTP csak a fiókleveleknél |
| 7.0 | Tesztelés, hibajavítás | 36 | 22 | −14 | Terhelési, többböngészős és többfős felhasználói próba nem készült |
| 8.0 | Telepítés, átadás | 18 | 2 | −16 | Csak helyi előkészítés és mentési próba; éles átadás nem történt |
| 9.0 | Szakdolgozat lezárása | 32 | 28 | −4 | A mentori javítások és a megvalósítás dokumentálása a tervhez közeli munkát igényelt |
|  | **Összesen** | **270** | **124** | **−146 (−54,1%)** | 31 munkanap × kb. 4 óra |

## Kockázati események utólagos rögzítése

| Kockázat ID | Bekövetkezett? | Alkalmazott válasz | Eredményes volt? |
|---|---|---|---|
| K-01 | Igen (indexszám, e-mail-aktiválás, jelszó-visszaállítás; dokumentáció elsőbbsége) | Külön kiadások 0.1.4–0.1.7, migrációk, új tesztek | Igen |
| K-02 | Igen (31 munkanapra koncentrált munka, M2 késése) | Kiegészítő vizsgálatok elhagyása | Részben |
| K-03 | Nem | Tranzakció, FOR UPDATE, egyedi megszorítás | Igen (T-07) |
| K-04 | Nem | Jogosultság-ellenőrzés, CSRF, mentés | Igen (T-14, T-15, T-20) |
| K-05 | Részben (Gmail alkalmazásjelszó, STARTTLS) | Naplóadapter, cserélhető adapter, leírás | Igen (T-17, T-23, T-28) |
| K-06 | Nem | Egyszerű felület, megerősítő oldal | Igen (egy külső tesztelő) |
| K-07 | Részben (éles tárhely nem volt) | Helyi XAMPP-demó | A bemutatáshoz igen |
| K-08 | Igen (a 09. 07-i változat nem dokumentálta a megvalósítást) | Tesztjegyzőkönyv, követelményállapot, dolgozatfrissítés | Igen |
| K-09 | Nem | Mentési parancs, visszaállítási próba, GitHub | Igen (T-20) |
| K-10 | Részben (1 külső tesztelő 3–5 helyett) | Forgatókönyves saját teszt + egy külső próba | Részben |
| K-11 (új) | Igen – SMTP hitelesítési követelmények | SMTP-adapter, parancssori teszt, leírás | Igen |
| K-12 (új) | Igen – Windows-függő mentési fájlnév | Szabványos időbélyeg (0.1.8) | Igen |
| K-13 (új) | Igen – régi, 7 jegyű indexszámú tesztrekord | Szigorított ellenőrzés (0.1.5); tisztítás pilot előtt | Részben |
