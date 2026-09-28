# Sidrene cijene i dnevni cjenici — Liber Media

Ovaj modul je port Bebes modula na OpenCart 3.0.3.8 i aktivnu Basel/Twig temu. Referentni datum je 10. 9. 2026. Modul čuva nepromjenjivu sidrenu cijenu po proizvodu, revizijski trag ručnih izmjena te dnevne PJ1/PJ3 CSV publikacije.

## Važno prije produkcije

Produkciju ne mijenjati dok lokalna provjera nije prihvaćena. Prije migracije napraviti i provjeriti sigurnosnu kopiju baze i datoteka, posebno `upload/`, `storageunbtbl/modification/` i `storageunbtbl/download/`.

SFTP alat prenosi samo aplikacijske datoteke. SQL migracije se namjerno izvršavaju odvojeno i kontrolirano.

## Redoslijed puštanja

1. Prenijeti potvrđeni Git raspon pomoću `tools/deploy-sftp.php`.
2. Izvršiti SQL datoteke točno ovim redoslijedom:
   1. `sql/2026_09_28_anchor_price_01_schema.sql`
   2. `sql/2026_09_28_anchor_price_02_activation.sql`
   3. `sql/2026_09_28_anchor_price_03_backfill.sql`
   4. `sql/2026_09_28_anchor_price_04_verify_read_only.sql`
3. U administraciji otvoriti **Extensions > Modifications**, pritisnuti **Refresh** i očistiti theme/cache predmemoriju.
4. Provjeriti administraciju **Catalog > Sidrene cijene**, javne prikaze i stranicu `index.php?route=information/price_list`.
5. Tek nakon čiste read-only provjere ručno napraviti prvu PJ1/PJ3 publikaciju.
6. Nakon uspješne ručne publikacije postaviti radni-dan cron prije 08:00.

Migracije su idempotentne i ne brišu postojeće sidrene cijene, reviziju ni publikacije.

## Pravilo početnog punjenja

Za proizvode koji su postojali do 10. 9. 2026. sprema se tadašnja uvezena cijena s referentnim datumom 10. 9. 2026. Aktivni proizvodi dobivaju status `confirmed`, a neaktivni `pending`.

Proizvod prvi put objavljen poslije referentnog datuma dobiva datum prvog opažanja i status `pending` dok administrator ne potvrdi stvarni datum. Standardno spremanje proizvoda, Product Quick Edit i postojeći import API imaju zasebne evente; dnevna sinkronizacija dodatno hvata propuštene aktivne proizvode.

## Blokade publikacije

Publikacija se zaustavlja ako aktivni proizvod nema:

- potvrđenu sidrenu cijenu;
- naziv, model ili proizvođača;
- valjan upisani GTIN u EAN/JAN/ISBN polju.

Prazan EAN/JAN/ISBN je dopušten. Ako je vrijednost upisana, mora biti valjani GTIN-8, GTIN-12, GTIN-13 ili GTIN-14. UPC se ovdje ne koristi kao barkod jer ga trgovina koristi za GLS podatke.

Sve nepravilnosti iz četvrte SQL provjere moraju biti riješene prije prvog crona.

### Trenutačna lokalna blokada (28. 9. 2026.)

Kod lokalne provjere objavu namjerno zaustavljaju tri aktivna proizvoda:

- proizvod 2174 (`Mini Waffle, 300 kom`) ima nevaljan JAN `1`;
- proizvod 3750 (`Set alata za finu motoriku 1/24`) nema proizvođača;
- proizvod 3759 (`Komplet za malog doktora`) nema proizvođača.

Te podatke treba ispraviti i ponovno pokrenuti read-only provjeru prije prve PJ1/PJ3 objave. Modul i prikaz sidrenih cijena rade neovisno o tome; nijedan nepotpun CSV nije objavljen.

## PJ1 i PJ3

- PJ1 predstavlja fizičku trgovinu. U nazivu datoteke koristi se prva nesenzitivna linija koja nalikuje poštanskoj adresi; OIB, MBS, IBAN i bankovni podaci nikad ne ulaze u javni naziv.
- PJ3 predstavlja webshop i koristi domenu trgovine.

Obje publikacije nastaju iz istog atomskog snimka podataka. Trenutna OpenCart baza ima jednu globalnu količinu i jedan skup proizvoda, pa PJ1 i PJ3 zasad imaju isti asortiman i dostupnost. Ako se stvarno stanje po lokaciji razlikuje, prije produkcije treba dodati vjerodostojno mapiranje zalihe/asortimana po lokaciji.

## Cron

Poziv koristi isključivo HTTP zaglavlje `X-Anchor-Price-Key`; ključ se ne stavlja u URL ni logove.

```sh
curl --fail --silent --show-error \
  --header 'X-Anchor-Price-Key: <KLJUČ_IZ_ADMINISTRACIJE>' \
  'https://<PRODUKCIJSKA_DOMENA>/index.php?route=extension/module/anchor_price/cron'
```

Točnu produkcijsku domenu potvrditi prije uključivanja crona. Objavljene datoteke čuvaju se 30 dana.

## Završna provjera

Provjeriti najmanje:

- proizvod s akcijom i proizvod bez akcije;
- kategoriju, pretragu, proizvođača, povezane proizvode i Basel module;
- quickview, MSmart live-search, usporedbu i listu želja;
- mini-košaricu, košaricu, standardni checkout i QuickCheckout bez promjene iznosa narudžbe;
- administracijski popis, uređivanje uz obvezan razlog i revizijski trag;
- točno jedan dnevni PJ1/PJ3 par, jednak broj proizvoda i ispravne SHA-256 kontrolne zbrojeve;
- javno preuzimanje CSV-a i skrivanje publikacija starijih od 30 dana.

Ako health check ili bilo koja kontrola ne uspije, ne uključivati cron. Vratiti aplikacijske datoteke iz lokalne `.deploy-backups/` kopije i bazu iz provjerene produkcijske sigurnosne kopije.
