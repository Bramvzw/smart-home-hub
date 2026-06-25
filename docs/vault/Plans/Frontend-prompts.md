# Front-end prompts voor Claude Design

Paste-klare prompts om per module de front-end te laten ontwerpen in
**claude.ai/design**, daarna importeren we het ontwerp en bouwen het in Blade
binnen de hub-layout (sidebar + `--hub-*` thema).

**Werkwijze per module:**
1. Start een ontwerp in claude.ai/design.
2. Plak eerst het **House style**-blok hieronder, dan de **module-prompt**.
3. Itereer op het ontwerp; exporteer de HTML/JSX.
4. Laat het importeren + in Blade bouwen (de data-contracten staan in de plannen).

De data-velden in elke prompt komen uit het JSON-contract van het bijbehorende
plan, zodat de mock-data realistisch is en 1-op-1 met de backend matcht.

---

## House style (plak dit boven ELKE module-prompt)

```
Je ontwerpt een scherm voor "Smart Home Hub" — een lokaal dashboard (alleen ik
gebruik het, op een NAS, bekeken op desktop en een wandtablet). Donker, rustig,
modern, functioneel. Geen marketing-fluff, geen glas/gradient-slop.

Thema-tokens (gebruik exact deze):
- Achtergrond: #0d0e12 ; surface #0f1015 ; kaart #1a1c23 ; kaart-hover #20222b
- Lijnen: rgba(255,255,255,0.07), sterker rgba(255,255,255,0.15)
- Tekst: #e9eaef ; gedempt #a4a7b4 ; dim #6b6e7c
- Accent: amber oklch(0.82 0.13 62) (ongeveer #e0a44e) ; accent-soft 14% ; accent-ink donker
- Status: done/ok #54b896 ; danger #e0575c ; info-blauw #4eb0e6
- Radii: kaart 16px, klein 8-11px, pill 999px
- Typografie: display "Space Grotesk", body "Hanken Grotesk"
  (via https://fonts.bunny.net) ; cijfers tabular-nums
- Spacing royaal, duidelijke hiërarchie, subtiele hover-states

Context/layout:
- Het scherm staat in een content-gebied RECHTS van een vaste sidebar
  (de sidebar hoef je NIET te ontwerpen). Ontwerp alleen de module-pagina.
- Responsive: vult de breedte, max ~1200-1440px, leesbaar op tablet.
- UI-microcopy in het NEDERLANDS.

Toon altijd deze states waar relevant: normaal (met data), leeg, laden, fout.
Gebruik realistische mock-data (zie de prompt). Lever een self-contained
artifact (HTML + inline CSS + evt. React/JSX zoals in mijn bestaande
design-project), geen externe dependencies behalve het font-CDN.
```

---

## 1. Nieuws (News)

```
Ontwerp de "Nieuws"-pagina. Een RSS-aggregator gegroepeerd per onderwerp, met
ongelezen-tracking.

Onderwerpen (kolommen of secties): 3D-printen & making · Dev & werk ·
Fitness & gezondheid · Tuinieren/moestuin · Nintendo Switch 2.

Per nieuwsitem toon: titel, korte samenvatting (1-2 regels), bron-label,
relatieve tijd ("2u geleden"), en een subtiel "keyword match"-badge als het item
een alert-trefwoord raakte. Ongelezen items visueel onderscheiden van gelezen.
Per onderwerp een ongelezen-teller. Klik op een item = opent de bron (nieuw tab)
en markeert als gelezen. Acties: "markeer alles gelezen" (globaal en per
onderwerp) en een "nu verversen"-knop. Toon ergens "laatst ververst om HH:MM".

Mock-data voorbeelden: "Bambu firmware 1.08 released" (3D-printen, keyword-match),
"Laravel 12.3 released" (dev), "5 moestuinklussen voor juni" (tuinieren),
"Nieuwe Mario Kart-update" (switch2).

States: normaal (meerdere onderwerpen met items), leeg (nog niets opgehaald, met
"nu verversen"), laden, fout.
```

---

## 2. Dagelijkse briefing (Briefing)

```
Ontwerp de "Dagelijkse briefing" — een AI-gegenereerde ochtend-samenvatting in
natuurlijk Nederlands.

Bovenaan: de datum + een grote, vriendelijke samenvattingstekst (één alinea,
informeel). Daaronder een opdeling per onderdeel met korte regels: Weer, Agenda,
Taken & leerdoel, Nieuws. Toon "gegenereerd om HH:MM" en een subtiele indicator
of het door AI is gemaakt of een fallback-versie (zonder AI). Een "opnieuw
genereren"-knop.

Ontwerp OOK een compacte variant: een dashboard-tegel die de eerste 1-2 zinnen
van de briefing toont met een "lees meer".

Mock: "Goedemorgen! Het wordt vandaag 24°C en droog, je hebt 2 afspraken, en er
is een nieuwe Bambu-firmware." + secties Weer/Agenda/Taken/Nieuws.

States: vandaag aanwezig, nog niet gegenereerd (met "genereer nu"), genereren
(laden), fallback-modus.
```

---

## 3. Tasks — Gewoontes & onderhoud

```
Ontwerp een "Gewoontes"-sectie/tab die NAAST het bestaande kanban-bord van de
Taken-module komt (kanban zelf hoef je niet te herontwerpen).

Gewoontes: lijst van gewoontekaarten. Per gewoonte: titel, een cadans-label
(bijv. "3× per week", "ma/wo/vr", "wekelijks"), de voortgang van de huidige
periode (bijv. "2/3 deze week" met een voortgangsindicator), de huidige streak
(bijv. een vlam-icoon met "4") en de beste streak. Een grote check-knop om
vandaag/deze periode af te vinken (en undo). Toon duidelijk "vandaag al gedaan".

Onderhoud: een aparte segment/lijst met terugkerende onderhoudstaken — titel,
volgende vervaldatum ("over 12 dagen"), cadans (elke 3 maanden / elk voorjaar).
Wanneer due verschijnen ze ook als kaart op het kanban-bord (toon hoe zo'n
"onderhoud"-kaart eruitziet met een terugkerend-markering).

Mock: gewoonte "Sporten 3× per week (2/3, streak 4)", "Lezen ma/wo/vr (streak 9)";
onderhoud "Printer-rails smeren — over 12 dagen", "Moestuin: zaaien voorjaar".

States: normaal, leeg (nog geen gewoontes), laden.
```

---

## 4. 3D-printer voorraad (Printer)

```
Ontwerp de "3D-printer voorraad"-pagina. Twee inventarissen, handmatig beheerd.

Filament: kaarten per spoel. Per spoel: kleur-swatch (hex) + materiaal + kleurnaam
(bijv. "PLA · Galaxy Black"), merk, en restvoorraad als balk: "320 / 1000 g" met
percentage (32%) en een subtiele "laag"-indicator onder een drempel. Acties:
spoel toevoegen/bewerken/verwijderen en "verbruik/aanvullen" (grammen aftrekken
of bijvullen).

Onderdelen: lijst gegroepeerd in "reserveonderdelen" en "verbruiksartikelen".
Per item: naam, aantal + eenheid (3 stuks / 500 ml). Toevoegen/bewerken/aantal
aanpassen.

Mock: spoelen "PLA Galaxy Black 320/1000g (32%)", "PETG Wit 850/1000g"; onderdelen
"0.4mm nozzle ×3 (reserve)", "Isopropanol 500 ml (verbruik)".

Ontwerp ook de tovoeg-/bewerk-formulieren (filament: materiaal, kleurnaam, hex,
merk, totaalgewicht, restgewicht, aankoopprijs/winkel/datum optioneel).

States: normaal, leeg (per inventaris), laden.
```

---

## 5. Recepten (Recipes)

```
Ontwerp de "Recepten"-pagina: wekelijkse recepten op basis van supermarkt-
aanbiedingen (Albert Heijn & Lidl), gegenereerd op vrijdagavond.

Overzicht: 4-5 receptkaarten voor deze week. Per kaart: titel, korte
omschrijving, bereidingstijd (25 min), geschatte kosten (€6,40), en gemarkeerde
"in de aanbieding"-ingrediënten met winkel-tag (bijv. "kipfilet (AH)",
"paprika (Lidl)"). Toon welke winkels zijn opgehaald en evt. welke faalde.

Receptdetail: ingrediëntenlijst (aanbieding-items gemarkeerd), stappen, en een
aparte boodschappenlijst (afvinkbaar). Een aparte "Aanbiedingen"-weergave met de
ruwe deals. Een "opnieuw genereren"-knop. Indicator als het een fallback is
(alleen aanbiedingen, geen AI-recepten).

Mock: "Snelle kip-teriyaki met paprika — 25 min — €6,40", "Pasta pesto met
kerstomaat — 20 min". Winkels: AH + Lidl opgehaald.

States: deze week aanwezig, nog niets (voor vrijdag), genereren, één winkel
gefaald (partieel), AI niet beschikbaar.
```

---

## 6. Entertainment & muziek (Entertainment)

```
Ontwerp de "Entertainment & muziek"-pagina met drie secties (tabs of kolommen):

Films: aanbevolen films, AI-gecureerd op smaak. Per film: poster, titel, korte
"waarom jij dit leuk vindt"-pitch, beschikbaarheidsbadges (Bios / Netflix / Prime),
en duim-omhoog/omlaag knoppen. Ergens een klein "smaakprofiel"-paneel
(favoriete genres/films instellen).

Concerten: brede lijst (Hedon Zwolle + heel NL). Per concert: artiest, venue +
stad, datum, en een relevantie-badge (Gevolgd / Hedon / Misschien leuk).

Nieuwe muziek: releases van gevolgde Spotify-artiesten. Per item: cover, artiest,
titel, type-badge (album/single/EP), releasedatum.

Mock films: "Dune: Part Three — omdat je sci-fi met sterke visuals waardeert
(Bios)". Concert: "Sigrid — Hedon, Zwolle — 14 sep (Gevolgd)". Muziek: "Nieuwe
single van Fred again.. — vandaag".

States: per sectie normaal/leeg/laden; Spotify nog niet gekoppeld (concerten/
muziek) met een "koppel Spotify"-prompt.
```

---

## 7. Dealtracker (Deals)

```
Ontwerp de "Dealtracker"-pagina: een prijs-watchlist die specifieke producten
volgt bij meerdere winkels en waarschuwt bij prijsdalingen.

Watchlist: kaart per product. Per product: naam en per winkel (bol.com / Amazon /
Tweakers) de huidige prijs, de laagste-ooit, en een daling-indicator (pijl omlaag
+ verschil) als de prijs recent zakte. Een kleine prijsgeschiedenis-sparkline per
listing. Toon "laatst gecheckt".

Toevoegen-flow: je typt een productnaam → de module zoekt en toont
KANDIDAAT-matches per winkel die je moet BEVESTIGEN of VERWIJDEREN (om verkeerde
matches te voorkomen) voordat tracking start. Ontwerp dit review-scherm.

Mock: product "Bambu Lab AMS" — bol.com €319 (laagste €299, ↓ vandaag), Tweakers
€312. Review-scherm met 2 kandidaten per winkel.

States: watchlist met producten, leeg, product toevoegen → matches reviewen,
laden, een winkel onbereikbaar.
```

---

## 8. AI agenda-planner (Planner)

```
Ontwerp de "Agenda-planner"-pagina: plant flexibele wekelijkse voornemens in rond
vaste afspraken (uit Google Calendar) en biedt 1-klik toevoegen.

Weekplan: een voorstel voor de komende week. Toon een korte AI-samenvatting
bovenaan ("Deze week: 3× sporten, zondag je moeder, zaterdagavond date"). Daaronder
de voorgestelde blokken — als weekrooster of lijst — per blok: titel, categorie
(sport/familie/date), dag + tijd, en knoppen "toevoegen aan agenda" (per blok) +
"alles toevoegen" + afwijzen. Een aparte "niet ingepland"-sectie voor voornemens
die niet pasten, met reden ("geen vrij avondblok in het weekend").

Beheer voornemens: een paneel om voornemens te beheren — titel, frequentie
(3-4× per week / wekelijks), voorkeurstijden (doordeweeks na 17:00 / weekend),
duur. Plus een "koppel Google Calendar"-state als nog niet verbonden.

Mock: blokken "Sporten — ma 18:00-19:30", "Sporten — wo 18:00", "Moeder bezoeken
— zo 14:00-16:30", "Date night — za 19:00-22:00". Niet ingepland: "1× extra
sporten — te weinig vrije avonden".

States: voorgesteld weekplan, Google nog niet gekoppeld, genereren, niets
ingepland (lege week).
```

---

## Na het ontwerpen

Per module: importeer het ontwerp (zoals we met de Weather-pagina deden) en laat
het in Blade bouwen binnen `<x-dashboard.layout>`. De backend-endpoints en
JSON-contracten staan in de bijbehorende plannen in deze map — de front-end
consumeert die. Houd de microcopy in het Nederlands en de tokens consistent met
het House style-blok.
```
