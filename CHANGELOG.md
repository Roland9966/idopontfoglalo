# Változásnapló

## 0.1.8 – mentési eljárás igazolása és hordozható fájlnév

- a teljes adatbázis mentése és külön tesztadatbázisba történő visszaállítása sikeresen igazolt;
- a mentésből mind a 10 szükséges tábla helyreállt, a phpMyAdmin 119 lekérdezést hibamentesen végrehajtott;
- a mentési fájl időbélyege már nem függ a Windows területi dátum- és időformátumától;
- sikertelen mentés esetén az esetlegesen létrejött hiányos SQL-fájl automatikusan törlődik;
- a tesztjegyzőkönyv a belépési zárolás, a mobilnézet, a billentyűzetes kezelés, az auditnapló és a mentés igazolt eredményeivel bővült.

## 0.1.7 – biztonságos jelszó-visszaállítás

- a bejelentkezési oldalról e-mailben kérhető új jelszó beállítására szolgáló hivatkozás;
- a hivatkozás alapértelmezetten 60 percig érvényes, egyszer használható, az új kérés pedig érvényteleníti a korábbit;
- a rendszer azonos választ ad létező és nem létező e-mail-címnél, így nem árulja el a regisztrált felhasználókat;
- a tokenek csak SHA-256 kivonatként kerülnek az adatbázisba;
- sikeres jelszócsere után törlődik a sikertelen belépések száma és az ideiglenes zárolás;
- az új jelszó mindkét mezője akadálymentes megjelenítő ikont kapott;
- külön, adatvesztés nélkül és ismételten futtatható 0.1.7-es adatbázis-frissítő készült;
- a tesztjegyzőkönyv jelszó-visszaállítási esetekkel és a 2026. szeptember 14-én igazolt eredményekkel bővült.

## 0.1.6 – kötelező e-mailes fiókaktiválás és jelszómegjelenítés

- az új hallgatói fiók csak a 24 óráig érvényes, egyszer használható e-mailes hivatkozás megnyitása után használható;
- lejárt vagy elveszett aktiváló levél helyett a bejelentkezési oldalról új kérhető;
- a Gmail SMTP-küldés alkalmazásjelszóval, STARTTLS kapcsolaton keresztül beállítható;
- külön parancssori SMTP-teszt készült;
- a regisztrációs és bejelentkezési jelszómezők akadálymentes szem ikont kaptak;
- az adminisztrátor felhasználólistáján látható az e-mail-aktiválás állapota;
- adatvesztés nélküli, ismételten futtatható 0.1.6-os adatbázis-frissítő készült;
- a foglalási értesítések ugyanazt a napló-, PHP `mail()`- vagy SMTP-adaptert használják.

## 0.1.5 – az intézményi indexszám formátumának pontosítása

- a regisztráció kizárólag pontosan 8 számjegyből álló indexszámot fogad el;
- a regisztrációs mező számbillentyűzetet kérő beviteli módot és böngészőoldali formátum-ellenőrzést kapott;
- az adatbázisséma 8 karakteres indexszámmezőre módosult;
- a mintadiák indexszáma `26000000` értékre változott;
- a frissítőparancs a 0.1.4-es változatból történő biztonságos továbblépést is kezeli;
- az automatikus tesztek a 7 és 9 számjegyű, valamint a betűt tartalmazó hibás értékeket is ellenőrzik.

## 0.1.4 – hallgatói indexszám kezelése

- a hallgatói regisztrációnál kötelezővé vált az indexszám megadása;
- az indexszám formátumát a rendszer ellenőrzi, egységesíti és egyedileg tárolja;
- a foglalás megerősítésénél, az adminisztrátori felhasználólistában és a foglaláskezelő felületen is megjelenik az indexszám;
- a CSV-export új indexszám oszloppal bővült;
- meglévő telepítéshez adatvesztés nélküli parancssori és SQL-adatbázisfrissítés készült;
- az automatikus tesztek indexszám-ellenőrzési esetekkel bővültek.

## 0.1.3 – foglaláskezelő felület rendezése

- a dátum és az időtartam külön, egységesen formázott sorba került;
- a foglaló neve, e-mail-címe és a kapcsolódó adatok áttekinthetőbb elrendezést kaptak;
- az állapotváltó mező és a mentés gomb minden foglalási sorban azonos felépítésben jelenik meg;
- a lezárt foglalásoknál a művelet hiányát egyértelmű szöveg jelzi;
- a mobilnézet elrendezése az új foglalási sorokhoz is igazodik.

## 0.1.2 – felhasználókezelő felület rendezése

- a szerepkör- és állapotmezők egységes űrlapblokkba kerültek;
- a szolgáltatás-hozzárendelés külön, jól tagolt mezőcsoportot kapott;
- a műveleti gombok minden sorban a blokkok alján, azonos elrendezéssel jelennek meg;
- a gombfeliratok pontosabbá váltak, és a mobilnézethez külön elrendezés készült.

## 0.1.1 – kezdőoldali megjelenés javítása

- a főoldali címsor fehér, nagy kontrasztú megjelenést kapott;
- a bevezető mondat rövidebb lett;
- a feltöltött óra–naptár logó bekerült a felső fejlécbe;
- eltávolításra kerültek a felesleges globális `use` utasítások miatti PHP-figyelmeztetések.

## 0.1.0 – első tesztelhető MVP

- elkészült az adatbázisséma és a mintaadatok betöltése;
- elkészült a regisztráció, a bejelentkezés és a három szerepkör;
- elkészült a szolgáltatás-, időablak-, foglalás- és lemondáskezelés;
- elkészült a tranzakciós és adatbázis-szintű kettősfoglalás-védelem;
- elkészült az adminisztrációs felület, az auditnapló és a CSV-export;
- elkészült az értesítési sor és a helyi naplóadapter;
- elkészült a reszponzív felület és az alapvető biztonsági védelem;
- elkészült a telepítési útmutató, a tesztjegyzőkönyv és a munkanapló;
- elkészült a konkurens foglalás parancssori integrációs tesztje.

A 0.1.0 változat forráskód-szinten elkészült, de a felhasználó saját XAMPP-környezetében végrehajtandó rendszer- és elfogadási tesztek eredménye még nincs rögzítve.
