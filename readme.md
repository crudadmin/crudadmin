# Čo je to CrudAdmin

CrudAdmin je systém postavený na frameworku **Laravel**, ktorý ponúka jednoduchú a ucelenú štruktúru pri programovaní.

Bol navrhnutý pre programátorov a firmy zamerané na vývoj webových a mobilných databázových aplikácii. V jednoduchosti **niekoľko násobne zvyšuje produktivitu** programátora tým, že **odbremeňuje** všetky zložité kroky ako **návrh databázy, použivateľského rozhrania, validácie formulárov** a prácu s dátami v databáze.

- [O projekte](https://docs.crudadmin.com/#/)
- [Ako to funguje](https://docs.crudadmin.com/#/how-it-works.md)
- [Inštalácia](https://docs.crudadmin.com/#/install.md)
- [Konfigurácia](https://docs.crudadmin.com/#/config.md)
- [Licencia](https://docs.crudadmin.com/#/license.md)
- [Kontakt](https://docs.crudadmin.com/#/contact.md)

---

## Čo všetko podporuje?

Laravel model dostal nadstavbu a nazývame ho **Admin model**. Po jednoduchom vytvorení admin modelu systém automatický navrhne všetky tieto podporované funkcionality vypísané nižšie.

#### NÁVRH UI / UX
Automatický vytvorí prehľadné používateľské rozhranie pre jednoduchú správu systému koncovým používateľom.

#### Autentifikácia
Automatický navrhne rozhranie pre prihlásovanie, správu používateľov, používateľských rol

#### Právomoci
Podporuje možnosť vytvorenia používateľských skupín pre správu právomoci administrátorov, každá rola môže mať presne zadefinované práva pre dane moduly.

#### Návrh databázy
Automatický navrhne relačnú databázu bez akejkoľvek znalosti databázového jazyka. Taktiež sa postará o jej modifikáciu pri vývoji projektu alebo migrovaní na novú verziu.

#### Formuláre
Automaticky navrhne všetky formuláre v administrácii, sa postará sa o kompletnú validáciu položiek vo formulári a ich následne uloženie do databázy.

#### Validácia
Postará sa o validáciu všetkých položiek vo formulári na strane backendu bez potrebnej konfigurácie zo strany programátora. Taktiež sa postará o validáciu na strane frontendu.

#### Nahrávanie súborov
Automaticky uloží všetky súbory, obrázky, ktoré sa nachádzajú vo formulári. Taktiež sa postará o následne zmenšenie rozmerov obrázkov na frontende.

#### Jazykové mutácie
Automatický vytvorí jazykovú tabuľku, ktorú prepojí s modelmi a vstupnými poliami. Postará sa o automaticku správu jazykov v administrácii, no taktiež aj na strane frontendu. V spolupráci s Gettext rozšírením, je možné spravovať preklady statických textov v zdrojových šablonách aplikácie **blade**, no taktiež aj v **javascriptoch**, či **VueJs** šablónach.

#### Výpis dát
Automatický navrhne všetky tabuľky pre inteligentný a prehliadny výpis dát v administrácii. Postará sa o možnosť pridania záznamov, úpravy, mazania, skývania záznamov a zmeny ich poradia.

#### Vstupné polia
Podporuje veľké množstvo vstupných polí od obyčajného textu, textarei, čísla, des. čísla, textového editoru, dátumu, času, selectboxu, checkboxu až po automatické uploadovanie súborov. Systém taktiež podporuje možnosť vytvorenia vlastných kustomizovateľných vstupných komponentov.

#### Relácie
Automatický sa postará o vygenerovanie všetkých typov relácii v databáze a ich prepojenie do používateľského rozhrania. Systém CrudAdmin odstránil nutnosť písania vzťahov medzi Eloquentmi, ktoré je vo frameworku Laravel potrebné definovať. Podporuje belongsTo, belongsToMany, hasMany, hasOne, manyToMany...

#### Jednoduchá inštalácia
CrudAdmin sa inštaluje do frameworku Laravel pomocou **composer**. Systém ponúka jednoduchú inštaláciu a vytváranie admin modelov pomocou príkazu `php artisan`.

## História CrudAdminu

Rozšírenie uzrelo svetlo sveta začiatkom roku **2016**. Vzniklo pri tvorbe komplexného a zložitého projektu, za účelom urýchlenia developmentu tohto projektu.

Už v zárodku tvorby projektu bol potenciál systému natoľko vysoký, že sa jadro systému začalo vyvíjať ako oddelený projekt, ktorý  sa postupne začal používať vo viacerých projektoch.

## Kto používa CrudAdmin

Po niekoľkých rokoch vývoja systému, ho používa cez **stovky webových aplikácii, eshopov, informačných systémov či mobilných aplikácii** pripajajúcich sa na API systému, ktorý obsluhuje dáta tychto aplikácii.

Nachádza sa taktiež v niekoľkých **Slovenských** a **Českých firmách**, jeho rast je do budúcnosti plánovaný pre globálny trh.

## CrudAdmin a testovanie

CrudAdmin plné obsahuje automatizované **unit** a **integračné testy**, ktoré sa starajú a kontrolujú väčšinu funkcionalit v systéme.

Pred každým zverejnením novej verzie systému podliehaju jeho súčasti kontrole funkcionality na viacerých testovacích rozhraniach. Kontrola taktiež prebieha pomocou integračných testov, ktoré automatický spustia aplikáciu a otestujú jej správne chovanie v prehliadači.