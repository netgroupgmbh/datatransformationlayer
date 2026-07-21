# Data Transformation Layer

[![Lizenz](https://img.shields.io/badge/Lizenz-Apache--2.0-blue.svg)](LICENSE)
![PHP](https://img.shields.io/badge/PHP-%5E8.3-777bb4.svg)
![Contao](https://img.shields.io/badge/Contao-%5E5.3-ea5d0b.svg)

Eine schlanke **Data Transformation Layer** für Contao-Bundles: Du definierst **Projections** (ausgabespezifische Feld-Transformationen) und wendest sie effizient auf große Datenmengen an – inklusive **Prefetching**, um N+1-Queries zu vermeiden. Neben der Transformation bestehender Felder können auch **neue berechnete Felder hinzugefügt** und **bestehende Felder entfernt** werden.

---


## Autor

**NetGroup GmbH** - Patrick Froch <info@netgroup.de>

---


## Support

NetGroup Gesellschaft für Informationstechnologien in Deutschland mbH<br>
Kaiserstraße 67<br>
44135 Dortmund

Kontakt:<br>
Telefon: +49 231 557509-0<br>
Telefax: +49 231 557509-99<br>
E-Mail: info@netgroup.de

Internet: https://www.netgroup.de/userguide.html

---


## Inhalt

- [Motivation](#motivation)
- [Features](#features)
- [Voraussetzungen](#voraussetzungen)
- [Installation](#installation)
- [Konfiguration (Symfony Services)](#konfiguration-symfony-services)
- [Quickstart](#quickstart)
- [Benutzung](#benutzung)
  - [1) Projection erstellen](#1-projection-erstellen)
  - [2) Converter erstellen](#2-converter-erstellen)
  - [3) Transformer anwenden](#3-transformer-anwenden)
  - [4) Prefetching (N+1 vermeiden)](#4-prefetching-n1-vermeiden)
  - [5) Felder hinzufügen (Additions)](#5-felder-hinzufügen-additions)
  - [6) Felder entfernen (Removals)](#6-felder-entfernen-removals)
  - [7) Kombiniertes Beispiel](#7-kombiniertes-beispiel)
- [Fehler- & Null-Policy](#fehler--null-policy)
- [Architektur (kurz)](#architektur-kurz)
- [Lexikon (Begriffe)](#lexikon-begriffe)
- [Testing & Quality](#testing--quality)
- [Contributing](#contributing)
- [Lizenz](#lizenz)

---

## Motivation

In realen Contao-Projekten werden Daten häufig über **komplexe DBAL-Abfragen** geladen. Bevor die Daten ausgegeben
werden können (Twig-Templates, Exporte, APIs), müssen Werte oft transformiert werden:

- Einfaches Formatieren (z. B. `tstamp -> "12.03.2026"`)
- Ersetzen technischer Werte durch Anzeige-Werte (z. B. `member_id -> "Jane Doe"`)
- Normalisieren/Ableiten von Feldern (z. B. Status-Labels, berechnete Summen)
- Anreichern mit Lookup-Daten aus anderen Tabellen
- Hinzufügen berechneter Felder (z. B. `total_price` aus `quantity * unit_price`)
- Entfernen technischer Hilfsspalten aus dem Output

Ohne klare Trennung der Verantwortlichkeiten wird diese Logik häufig über Controller, Templates und Repositories verteilt. Das führt schnell zu:

- **starker Kopplung** zwischen Query-Logik und Darstellung
- **Duplikation**, sobald mehrere Ausgaben unterschiedliche Formate benötigen (Liste vs. Export vs. API)
- **Performance-Problemen**, insbesondere N+1-Queries, wenn Fremdschlüssel pro Zeile einzeln aufgelöst werden
- fragile Konfiguration durch „stringly typed“ Arrays

**Lösung:**
Dieses Paket liefert eine **Projection-basierte Transformation Layer**:

- Eine *Projection* ist eine PHP-Klasse, die definiert, *welche Felder* für eine Ausgabe transformiert, hinzugefügt oder entfernt werden.
- Ein *Converter* ist ein wiederverwendbarer Feld-Transformer (Formatting, Lookup, Mapping, Berechnung).
- Converter mit Prefetching können benötigte Lookup-Daten **einmal pro Dataset** laden und so N+1 verhindern.

Dadurch bleiben DBAL-Queries sauber, Ausgabelogik ist explizit und wiederverwendbar, und große Datenmengen können effizient verarbeitet werden.

---

## Features

- Projection-Definitionen als **PHP-Klassen** (keine „Array of strings"-Konfiguration)
- Converter-Referenzen via `::class` (refactor-freundlich, IDE-sicher)
- Optionales **Prefetching** gegen N+1 Lookups
- **Felder hinzufügen** (berechnete Werte, abgeleitete Felder)
- **Felder entfernen** (Hilfsspalten nach Berechnung entfernen)
- Direkte Verwendung von **DBAL-Result-Arrays** möglich (`fetchAllAssociative()`)
- Symfony-Integration über **Tagged Services** + `ServiceLocator`
- Geeignet für tausende Datensätze (Request-lokales Caching möglich)

---

## Voraussetzungen

- PHP: `^8.3`
- Contao: `contao/core-bundle ^5.3`

---

## Installation

Installation per Composer in deinem Contao-Projekt:

```bash
composer require netgroup/datatransformationlayer
```

Alternativ kannst du das Paket (`netgroup/datatransformationlayer`) über den **Contao Manager** installieren.

---

## Konfiguration (Symfony Services)

### `services.yaml` (Beispiel)

Lege die Datei `config/services.yaml` in deinem Bundle an (oder ergänze sie):

```yaml
services:
  _defaults:
    autowire: true
    autoconfigure: false

  # --- Converter ---
  App\Conversion\Converter\:
    resource: '../../Classes/Conversion/Converter/*'
    tags: ['netgroup.datatransformation.converter']

  # --- Projections ---
  App\Conversion\Projection\:
    resource: '../../Classes/Conversion/Projection/*'
    tags: ['netgroup.datatransformation.projection']
```

_(Pfade müssen ggf. angepasst werden.)_

---

## Quickstart

1. Projection-Klasse erstellen (z. B. `member_list`)
2. Converter-Klassen erstellen (z. B. Datum formatieren, Lookups)
3. Optional: Berechnete Felder hinzufügen (`addField`) oder Felder entfernen (`removeField`)
4. DBAL-Resultset mit `DatasetTransformer` transformieren

---

## Benutzung

### 1) Projection erstellen

Eine Projection definiert **welche Felder** für eine bestimmte Ausgabe transformiert werden sollen.

Beispiel: `MemberListProjection` transformiert:

- `tstamp` (timestamp/string) → formatiertes Datum
- `id` → lesbarer Fullname (aus `firstname` + `lastname`)

```php
<?php

namespace App\Conversion\Projection;

use App\Conversion\Definition\ProjectionPlanBuilder;
use App\Conversion\Converter\DateTimeFormatConverter;
use App\Conversion\Converter\MemberFullnameConverter;

final class MemberListProjection implements ProjectionInterface
{
    public function name(): string
    {
        return 'member_list';
    }

    public function build(ProjectionPlanBuilder $builder): void
    {
        $builder->field(OrderListField::CreatedAt)
                //->convert(TrimConverter::class) // mehrere Konvertierungsschritte möglich!
                ->convert(DateTimeFormatConverter::class, ['format' => 'd.m.Y H:i']);
    }
}
```

---

### 2) Converter erstellen

Converter implementieren `FieldConverterInterface`. Sie erhalten:

- den aktuellen Feldwert,
- die komplette Zeile (`$row`),
- einen `ConversionContext` (z. B. Zeitzone),
- optionale Parameter (`$params`).

#### Beispiel A: Datum formatieren (`DateTimeFormatConverter`)

```php
<?php

namespace App\Conversion\Converter;

use App\Conversion\Definition\ConversionContext;
use DateTimeImmutable;
use DateTimeZone;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class DateTimeFormatConverter implements FieldConverterInterface
{
    public function convert(mixed $value, array $row, ConversionContext $context, array $params = []): mixed
    {
        $params = $this->resolve($params);

        if ($value === null || $value === '') {
            return null;
        }

        $timezone = new DateTimeZone((string) $context->option('timezone', 'UTC'));

        if (is_int($value) || (is_string($value) && ctype_digit($value))) {
            $dt = (new DateTimeImmutable('@' . (string) $value))->setTimezone($timezone);
            return $dt->format($params['format']);
        }

        $dt = new DateTimeImmutable((string) $value, $timezone);
        return $dt->format($params['format']);
    }

    private function resolve(array $params): array
    {
        $r = new OptionsResolver();
        $r->setDefaults(['format' => 'c']);
        $r->setAllowedTypes('format', 'string');

        return $r->resolve($params);
    }
}
```

#### Beispiel B: Fullname aus `tl_member` bauen (`MemberFullnameConverter`)

Dieser Converter zeigt bewusst ein Pattern, das in DBAL-Readmodels häufig ist:
Der Converter kann auch dann funktionieren, wenn er nur die Row bekommt (kein Lookup nötig).

```php
<?php

namespace App\Conversion\Converter;

use App\Conversion\Definition\ConversionContext;

final class MemberFullnameConverter implements FieldConverterInterface
{
    public function convert(mixed $value, array $row, ConversionContext $context, array $params = []): mixed
    {
        $firstname = trim((string) ($row['firstname'] ?? ''));
        $lastname  = trim((string) ($row['lastname'] ?? ''));

        $full = trim($firstname . ' ' . $lastname);

        // Policy: wenn kein Name vorhanden, dann null (konfigurierbar über euer Projekt)
        return $full !== '' ? $full : null;
    }
}
```

---

### 3) Transformer anwenden

In deinem Controller/Service lädst du Daten per DBAL und transformierst sie mit dem Transformer.

```php
<?php

namespace App\Controller;

use NetGroup\DataTransformationLayer\Classes\Services\Helper\TransforamtionHelper;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class MemberController extends AbstractController
{

    public function __construct(private readonly Connection $connection, private readonly TransforamtionHelper $helper)
    {
    }

    public function list(): Response
    {
        $rows = $this->connection->createQueryBuilder()
            ->select('m.id', 'm.tstamp', 'm.firstname', 'm.lastname', 'm.email')
            ->from('tl_member', 'm')
            ->where('m.disable != 1')
            ->setMaxResults(5000)
            ->executeQuery()
            ->fetchAllAssociative()
        ;

        $rows = $this->helper->transform('member_list', $rows, ['timezone' => 'Europe/Berlin']);

        return $this->render('member/list.html.twig', [
            'rows' => $rows,
        ]);
    }
}
```

---

### 4) Prefetching (N+1 vermeiden)

Wenn ein Converter Lookup-Daten benötigt (z. B. `pid -> Titel aus tl_page` oder IDs zu Labels), implementiere `PrefetchingConverterInterface`.

**Prinzip:**
- Beim Transformieren ruft der Transformer *einmalig* `prefetch($rows, $context, $params)` auf.
- Der Converter baut eine Map (z. B. `id => label`).
- Pro Row wird dann nur noch über die Map aufgelöst (keine DB pro Zeile).

Beispiel: `PageTitleLookupConverter` (Lookup `tl_page.id -> tl_page.title`)

```php
<?php

namespace App\Conversion\Converter;

use App\Conversion\Definition\ConversionContext;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

final class PageTitleLookupConverter implements FieldConverterInterface, PrefetchingConverterInterface
{
    /** @var array<int, string> */
    private array $titleById = [];

    public function __construct(private readonly Connection $connection) {}

    public function prefetch(array $rows, ConversionContext $context, array $params = []): void
    {
        $ids = [];

        foreach ($rows as $row) {
            $id = $row['pid'] ?? null;
            if (is_numeric($id)) {
                $ids[(int) $id] = true;
            }
        }

        $ids = array_keys($ids);

        if ($ids === []) {
            $this->titleById = [];
            return;
        }

        $result = $this->connection->createQueryBuilder()
            ->select('p.id', 'p.title')
            ->from('tl_page', 'p')
            ->where('p.id IN (:ids)')
            ->setParameter('ids', $ids, ArrayParameterType::INTEGER)
            ->executeQuery()
            ->fetchAllAssociative()
        ;

        $map = [];

        foreach ($result as $row) {
            $map[(int) $row['id']] = (string) $row['title'];
        }

        $this->titleById = $map;
    }

    public function convert(mixed $value, array $row, ConversionContext $context, array $params = []): mixed
    {
        if (!is_numeric($value)) {
            return null;
        }

        $id = (int) $value;

        // Policy: unbekannte IDs -> null (siehe Abschnitt "Fehler- & Null-Policy")
        return $this->titleById[$id] ?? null;
    }
}
```

---

### 5) Felder hinzufügen (Additions)

Mit `addField()` können neue, berechnete Felder zum Output hinzugefügt werden, ohne bestehende Felder zu verändern. Das ist nützlich, wenn:

- ein berechneter Wert zusätzlich zu den Originalwerten ausgegeben werden soll
- abgeleitete Felder aus bestehenden Daten erzeugt werden sollen
- Lookup-Werte als separates Feld neben der technischen ID erscheinen sollen

#### API

```php
$builder->addField('zielfeld_name')
        ->compute(ConverterClass::class, $params, 'optionales_quellfeld');
```

- **`addField($targetField)`** - gibt einen `FieldAdditionBuilder` zurück
- **`compute($converterClass, $params, $sourceField)`** – definiert den Converter, der den Wert für das neue Feld berechnet
  - `$converterClass` – FQCN eines `FieldConverterInterface`
  - `$params` – Converter-Parameter (optional, Standard: `[]`)
  - `$sourceField` – Name eines bestehenden Feldes, dessen Wert als Eingangswert an den Converter übergeben wird (optional, Standard: `''` → Converter erhält `null` als Eingangswert)

#### Beispiel: Berechnetes Feld mit Quellfeld

```php
public function build(ProjectionPlanBuilder $builder): void
{
    // Neues Feld 'formatted_price' aus dem bestehenden Feld 'price'
    $builder->addField('formatted_price')
            ->compute(CurrencyFormatConverter::class, ['currency' => 'EUR'], 'price');
}
```

**Ergebnis:** Jede Row enthält nun zusätzlich `formatted_price`. Das Originalfeld `price` bleibt unverändert erhalten.

#### Beispiel: Berechnetes Feld ohne Quellfeld

Wenn der Converter seinen Wert vollständig aus der gesamten Row berechnet (via `$row`-Parameter), kann `sourceField` weggelassen werden. Der Converter erhält dann `null` als `$value`.

```php
public function build(ProjectionPlanBuilder $builder): void
{
    // Neues Feld 'full_name' aus firstname + lastname (kein einzelnes Quellfeld)
    $builder->addField('full_name')
            ->compute(FullNameConverter::class);
}
```

#### Beispiel: Mehrere compute()-Schritte verketten

`compute()` unterstützt Fluent Chaining – der Rückgabewert des ersten Converters wird als Eingangswert des zweiten verwendet:

```php
$builder->addField('display_label')
        ->compute(LookupConverter::class, ['table' => 'tl_page'], 'pid')
        ->compute(TruncateConverter::class, ['maxLength' => 50]);
```

> **Hinweis:** Additions werden *nach* den regulären Feld-Konvertierungen ausgeführt. Dadurch haben Addition-Converter Zugriff auf bereits konvertierte Feldwerte in `$row`.

---

### 6) Felder entfernen (Removals)

Mit `removeField()` können Felder aus dem Output entfernt werden. Das ist nützlich, wenn:

- Hilfsspalten nach einer Berechnung nicht mehr benötigt werden
- technische Felder (z. B. IDs) nicht in der Ausgabe erscheinen sollen
- sensible Daten vor der Ausgabe gefiltert werden müssen

#### API

```php
$builder->removeField('feldname');
```

`removeField()` gibt `$this` zurück und unterstützt Fluent Chaining:

```php
$builder->removeField('quantity')
        ->removeField('unit_price');
```

#### Beispiel

```php
public function build(ProjectionPlanBuilder $builder): void
{
    // Technische IDs entfernen
    $builder->removeField('pid');
    $builder->removeField('sorting');
}
```

> **Hinweis:** Removals werden *nach* den Additions ausgeführt. Dadurch kann ein Feld zunächst als Quellwert für eine Addition dienen und anschließend entfernt werden.

---

### 7) Kombiniertes Beispiel

Ein typischer Use Case: Aus `quantity` und `unit_price` wird ein berechnetes Feld `total_price` erzeugt, und die technischen Felder werden anschließend entfernt.

```php
<?php

namespace App\Conversion\Projection;

use NetGroup\DataTransformationLayer\Classes\Definition\ProjectionPlanBuilder;
use NetGroup\DataTransformationLayer\Classes\Projection\ProjectionInterface;
use App\Conversion\Converter\DateTimeFormatConverter;
use App\Conversion\Converter\MultiplyFieldsConverter;

final class OrderExportProjection implements ProjectionInterface
{
    public function name(): string
    {
        return 'order_export';
    }

    public function build(ProjectionPlanBuilder $builder): void
    {
        // 1) Bestehendes Feld transformieren
        $builder->field('created_at')
                ->convert(DateTimeFormatConverter::class, ['format' => 'd.m.Y']);

        // 2) Neues berechnetes Feld hinzufügen
        $builder->addField('total_price')
                ->compute(MultiplyFieldsConverter::class, [
                    'fields' => ['quantity', 'unit_price'],
                ]);

        // 3) Originalfelder nach Berechnung entfernen
        $builder->removeField('quantity');
        $builder->removeField('unit_price');
    }
}
```

**Input:**
```
['created_at' => 1710700800, 'quantity' => 3, 'unit_price' => 2500, 'name' => 'Widget']
```

**Output:**
```
['created_at' => '17.03.2026', 'total_price' => 7500, 'name' => 'Widget']
```

#### Ausführungsreihenfolge

Der `DatasetTransformer` führt die drei Operationstypen in einer festen Reihenfolge aus:

1. **Convert** – Bestehende Felder transformieren (Wert-Pipeline)
2. **Add** – Neue berechnete Felder hinzufügen (Additions)
3. **Remove** – Felder aus dem Output entfernen (Removals)

Diese Reihenfolge stellt sicher, dass:
- Additions auf bereits konvertierte Werte zugreifen können
- Removals erst am Ende greifen, sodass Felder sowohl als Quellwert für Additions als auch für Convert-Pipelines dienen können

---

## Fehler- & Null-Policy

Damit Ausgaben stabil und vorhersehbar sind, sollte im Projekt eine klare Policy gelten. Dieses Paket ist bewusst flexibel; du solltest dich aber **explizit** für ein Verhalten entscheiden.

### Empfohlene Default-Policy

- **Ungültige Eingabewerte** (z. B. leere Strings, falscher Typ)
  → `null` zurückgeben (statt Exception), sofern es ein „Display Field“ ist.

- **Lookup nicht gefunden** (z. B. ID existiert nicht in `tl_page`)
  → `null` zurückgeben *oder* einen Fallback liefern (z. B. `"(unbekannt)"`).

- **Hard-Fail nur bei echten Programmfehlern**
  → z. B. wenn Pflicht-Parameter fehlen (OptionsResolver), oder die Projection-Konfiguration inkonsistent ist.

### Empfehlung für Exporte/APIs

Für Exporte oder APIs ist `null` manchmal unpraktisch. Typische Alternativen:

- String-Fallback: `"(unbekannt)"`
- Technischer Fallback: `"#{$id}"` (z. B. `#123`)
- Separates Feld: `pageTitle` + `pageTitleResolved=false`

> Tipp: Wenn ihr mehrere Ausgaben habt (Liste vs. Export), löst ihr das sauber über **verschiedene Projections**.

---

## Architektur (kurz)

Das Paket trennt bewusst drei Ebenen:

1. **Query Layer (DBAL / Read Model)**
   - Liefert rohe Arrays (z. B. `fetchAllAssociative()`).
   - Keine Formatierung/Lookup-Logik.

2. **Transformation Layer (Projections + Converter)**
   - Eine Projection definiert Transformationsregeln pro Ausgabe (Liste/Export/API).
   - Converter sind kleine, wiederverwendbare Bausteine.
   - Prefetching verhindert N+1.
   - Additions erzeugen neue berechnete Felder.
   - Removals entfernen nicht benötigte Felder aus dem Output.

3. **Presentation Layer (Twig/JSON/Export)**
   - Bekommt „ready-to-display“ Daten.
   - Keine DB-Lookups mehr nötig.

---

## Lexikon (Begriffe)

- **Dataset**: Eine Liste von Datensätzen/Zeilen, typischerweise das Ergebnis von `fetchAllAssociative()`.
- **Row**: Ein einzelner Datensatz (assoziatives Array).
- **Projection**: Ausgabespezifische Definition *welche Felder* wie transformiert werden.
- **ProjectionPlan**: Internes, „kompiliertes“ Regelwerk einer Projection.
- **Converter**: Baustein, der einen Feldwert transformiert (Formatierung, Mapping, Lookup, …).
- **Pipeline**: Mehrere Converter-Schritte hintereinander für ein Feld (z. B. `trim` → `map` → `format`).
- **Prefetching**: Einmaliges Vorladen von Lookup-Daten pro Dataset, um N+1 zu verhindern.
- **Addition (FieldAddition)**: Definition eines neuen, berechneten Feldes im Output. Wird über `addField()` im Builder definiert.
- **Removal**: Markierung eines Feldes zur Entfernung aus dem Output. Wird über `removeField()` im Builder definiert.
- **ConversionContext**: Kontextinformationen (z. B. `timezone`, „caller"-Optionen), die Converter nutzen können.

---

## Testing & Quality

Dieses Projekt ist für saubere Softwarequalität ausgelegt und kann u. a. mit folgenden Tools abgesichert werden:

- **PHPUnit** für Unit- & Integration-Tests
- **PHPStan** für statische Analyse
- **Easy Coding Standard** für Coding-Standards

> CI läuft typischerweise in GitLab CI (nicht öffentlich einsehbar).

---

## Contributing

Beiträge sind willkommen:

1. Fork erstellen
2. Feature-Branch anlegen (`feature/...`)
3. Änderungen mit Tests/Analyse absichern
4. Merge Request eröffnen

Bitte achte auf:
- klare, kleine Commits
- konsistente Namensgebung
- verständliche Tests (Arrange/Act/Assert)

---

## Lizenz

Dieses Projekt ist unter der **Apache License 2.0** lizenziert. Siehe `LICENSE`.
