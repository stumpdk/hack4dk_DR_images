# Crowdsourcing historic images of DR

Check it out: http://www.bbhenriksen.dk/drsbilleder


## Read more about the API:

http://www.bbhenriksen.dk/drsbilleder/about

# Documentation of the code (in danish)
## Struktur
Sitet består af fire dele: frontend (JavaScript og HTML), backend (baseret på PHP-frameworket Phalcon), en MySQL-database, og henvisninger til konkrete billeder (som ligger eksternt).
De enkelte dele er beskrevet nedenfor. Derudover findes et særskilt afsnit om Facebook-integrationen.

## Frontend
Frontenden består af seks statiske html-sider (forside, om projektet, seneste tags, søgeside og statistik).

De enkelte sider indeholder den JavaScript-kode, som er relevante for funktionerne på de enkelte sider. Derudover er der en fil med fælles JavaScript-funktioner (js/tagging.js). Det omhandler blandt andet funktioner til at hente og gemme tags, hente tilfældige billeder og søgefunktionaliteten. Alle disse funktioner laver kald til API'et, som beskrives nedenfor.

Derudover indeholder tagging.js også Facebook-funktionalitetsom håndterer når brugere logger ind og ud.

I frontenden benyttes tre eksterne libraries:
* FacebookSDK til at håndtere brugere og delinger
* Taggd til at opmærke billeder med tags. Bemærk at der er foretaget mindre ændringer i dette library for at tilpasse det siden, blandt andet er der indført en typeahead
* D3 bruges til at vise grafer på statistik-siden
* Twitter Typeahead bruges til typeahead-funktionalitet ved tagging og søgning
* jQuery bruges til DOM-modifikationer

## Backend
Backenden er bygget med frameworket Phalcon (https://phalconphp.com/en/). Det er et MVC-baseret framework som adskiller sig fra andre frameworks ved, at det er compileret til C, og er tilføjet som en extension til PHP på den server, som afvikler koden.
Koden er placeret i mappen application.

Her er en gennemgang af de vigtigste filer og mapper:

* index.php: Hovedindgangen til applikationen. Indeholder alle routes, som angiver de konkrete handlinger ved konkrete API-requests. Indeholder også den bootstrapping, som er nødvendig for at tilgå databasen og lignende.
* models: Indeholder de enkelte models, som udgør PHP's mapping til databasen. Hver model er således repræsenteret i databasen som en tabel.

API'et har følgende indgange:
* /images/random (GET). Load a random image
* /image/{id} (GET). Load an image by its id
* /image/metadata/{id} (POST). Save metadata for a specific image
* /tags/latest (GET). The latest tags
* /stats (GET). Stats for the project
* /images/{offset}/{limit} (GET). Get a list of images from and to certain points
* /images/search?term={query} (GET). Search images by their tags
* /images/latest (GET). The latest images to get checked
* /tags?term={query} (GET). Get tags based on the query


## Database
Databasen består af fem tabeller:
* images: Består af et id (som er skabt specielt til denne database) og en URL til det oprindelige billede i dets originale størrelse (JPEG). Disse billeder ligger på URL'en hack4dk.dk.dk.
    * Filename: Billedets filnavn, som er unikt, og den eneste identifikator af billeder på tværs af DRs billedeliste og den der findes i dette projekt.
    * batch: Angiver fra hvilket batch billederne stammer. Bruges ikke længere.
    * type: Angiver om billedet er behandet eller ej. Bruges ikke længere.
* Tags: Indeholder informationer om enkelte tags. Tags i denne tabel er unikke.
    * id: Primærnøgle
    * name: Taggets navn (det som brugeren angiver ved oprettelse af tagget)
    * category_id: Angivelse af, hvilken kategori tagget tilhører. Bruges ikke.
    * time_added: Angiver hvornår første tag af denne type er oprettet.
    * is_used: Angiver om tagget er bruger-oprettet eller ej. Der findes få tags, som er maskinelt oprettet under forsøg med automatisk opmærkning af ansigt og alder. Disse vises ikke i frontenden.
* images_tags: Tabellen udgør koblingen mellem et tag og et billede.
    * tag_id og image_id: Fremmednøgler til tags og billeder.
    * user_id: Angiver hvilken bruger der har oprettet tagget, hvis den information findes.
    * x og y: Koordinatangivelse af taggets position på billedet. Angives fra 0 til 1.
    * confidence og value: Bruges til at angive detaljer om maskinelt oprettede tags. Benyttes ikke.
    * created: Angiver hvornår tagget er blevet knyttet til billedet.
* users: Indeholder informationer om de brugere som har valgt at logge ind med deres Facebook-konto.
    * id: Facebook-brugerid.
    * name: Navn hentet fra brugerens Facebook-konto.
    * created: Angiver hvornår brugeren har logget ind med sin konto første gang.
    * last_seen: Angiver hvornår brugeren sidst loggede ind. Hvis denne er NULL har brugeren ikke været logget ind siden oprettelsen.
* additional_image_info: Indeholder informationer importeret fra DRs regneark med oplysninger om de enkelte billeder. De enkelte felter i databasen stemmer overens med felterne i regnearkene.
    * filename: fremmednøgle til images.filename. Billedets id.

## Billeder
De historiske billeder findes i offentligt tilgængeligt i tre forskellige udgaver. Den første ligger i den oprindelige samling på hack4dk.dr.dk. Derudover findes der yderligere to udgaver af hvert billede. Den ene bruges til visning på siden hvor brugerne tagger, og er 1024 pixels på den længste led. Det andet bruges til thumbnail-visning, og er 250 pixel på den længste led.

Billederne af de to sidstnævnte typer ligger placeret i et Amazon S3 bucket storage. Disse billeder blev tidligere konverteret løbende på serveren, men blev efter performanceudfordringer konverteret på én gang, og derefter placeret hos Amazon. Ved tilføjelse af nye billeder skal dette altså ske igen.

## Facebook-integration
Der sker integration til Facebook på to måder: For det første når brugere vælger at logge ind med deres konto. For det andet hvis brugere vælger at dele billeder på Facebook.

I begge tilfælde håndteres kommunikationen med Facebook gennem PHP. Filerne login.php, logout.php, static.php, fb-callback.php håndterer brugerinformationer, og static.php static_template.php benyttes til deling af billeder.
   * fb-callback.php: Henter informationer om en bruger, hvis brugeren er logget ind, og applikationen har adgang til brugerens informationer.
   * login.php: Når en Facebook-bruger logger ind, gemmes informationer i projektets database, såfremt der er tale om en ny bruger. Derudover sættes sessions-informationer så brugeren kan identificeres når vedkommende tagger billeder (brugernavn og Facebook-id)
   * logout.php: Angiver tidspunktet for logout i databasen ("last_seen"), og nulstiller sessionen.
   * static.php: Bruges til at præsentere siden for Facebooks bot. Informationer om billedet og dets tags hentes fra databasen, og de vises på en simpel HTML-side (angivet i static_template.php)
